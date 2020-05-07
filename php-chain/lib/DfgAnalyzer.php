<?php


namespace PhpChain;

use PHPCfg;
use PHPCfg\{Block, Op};
use PhpChain\DfgAnalyzer\{Rule, PenaltyMatrix, RuleManager};

/**
 * Class DfgAnalyzer
 * @package PhpChain
 */
class DfgAnalyzer extends \PHPCfg\AbstractVisitor
{
    public const BEST_PATH = 1;
    public const WORST_PATH = 2;

    private $dfg;
    private $allowRules;
    private $penaltyRules;

    private $dfgScore = PenaltyMatrix::UNSET_PENALTY;

    public function __construct(Dfg $dfg, array $rules)
    {
        $this->dfg = $dfg;
        foreach ($rules as $parameter_number => $rule_set_for_parameter) {
            $this->allowRules[$parameter_number] = [];
            $this->penaltyRules[$parameter_number] = [];
            foreach ($rule_set_for_parameter as $rule) {
                if ($rule instanceof Rule\AllowRule) {
                    $this->allowRules[$parameter_number] [] = $rule;
                } elseif ($rule instanceof Rule\PenaltyRule) {
                    $this->penaltyRules[$parameter_number] [] = $rule;
                } else {
                    throw new \Exception('Rule should be Allow or Penalty');
                }
            }
        }
    }

    /**
     * @param array $blocks
     * @param Op $cond_op
     * @return Block
     */
    public static function chooseParentBlock(array $blocks, Op $cond_op): Block {
        $parents = [];
        foreach ($blocks as $block) {
            if (in_array($cond_op, $block->conditions, true) and isset($block->score)) {
                $parents []= $block;
            }
        }
        $scores = array_map(function ($item){
            if (isset($item->score)) {
                return $item->score;
            } else {
                return PenaltyMatrix::UNSET_PENALTY;
            }
        }, $parents);
        if ($cond_op->strategy == DfgAnalyzer::BEST_PATH) {
            $score = max($scores);
        }
        else {
            $score = min($scores);
        }
        return array_filter($parents, function ($parent) use ($score){
            if (isset( $parent->score )) {
                return $parent->score === $score;
            }
            return false;
        })[0];
    }

    private function selectParent(Block $block)
    {
        $selected_parent = null;
        // Remove all blocks that are iterator results
        $seen_parents = array_filter($block->parents, function ($parent){
            return isset($parent->conditions);
        });
        if (!count($seen_parents)) {
            throw new \Exception("Impossible to have parents but to have no seen parents");
        }
        // Nothing to choose of
        if (count($seen_parents) == 1) {
            $selected_parent = $seen_parents[array_key_first($seen_parents)];
        } else {
            $conditions = [];
            foreach ($seen_parents as $parent) {
                foreach ($parent->conditions as $cond_op) {
                    if (! in_array($cond_op, $conditions, true)) {
                        $conditions []= $cond_op;
                    } else {
                        $selected_parent = $this->chooseParentBlock($seen_parents, $cond_op);
                        // TODO: manage Switches
                    }
                }
            }
        }
        return $selected_parent;
    }

    private function setPhiPenalties(Op\Phi $phi_op, Block $selected_parent)
    {
        $target_phi = $this->dfg->getTargetOp($phi_op, true);
        $target_var = $this->dfg->getTargetVar($phi_op->result, true);
        // If phi not in scope
        if (!$target_phi or !$target_var) {
            return;
        }
        foreach ($phi_op->vars as $var) {
            foreach ($var->ops as $var_op) {
                foreach ($selected_parent->children as $child) {
                    if ($child === $var_op) {
                        // Get penalties from phi
                        $target_op = $this->dfg->getTargetOp($child, true);
                        foreach ($target_op->penalties as $parameter_number => $penalty) {
                            $target_phi->penalties[$parameter_number] = $penalty;
                            $target_var->result->penalties[$parameter_number] = $penalty;
                        }
                    }
                }
            }
        }
        // Todo: test and remove this block. It should not be reached normally
        foreach ($target_phi->penalties as $parameter_number => $penalty) {
            if ($penalty == PenaltyMatrix::UNSET_PENALTY) {
                $target_phi->penalties[$parameter_number] = PenaltyMatrix::UNDEFINED_PENALTY;
            }
        }
    }

    public function enterBlock(Block $block, Block $prior = null)
    {
        // Init block score
        $block->{'score'} = PenaltyMatrix::UNSET_PENALTY;
        if (!isset($block->{'conditions'})) {
            $block->{'conditions'} = [];
        }
        if (!count($block->parents)){
            return;
        }
        if (count($block->parents) > 1) {
            $block->{'score'} *= PenaltyMatrix::UNDER_CONDITION_BLOCK;
        }
        // Resolve conditions if block has parents

        $selected_parent = $this->selectParent($block);
        if (!$selected_parent)  {
            throw new \Exception('No selected parent');
        }
        if (! count($block->{'conditions'})) {
            $block->{'conditions'} = $selected_parent->conditions;
        } else {
            $block->{'conditions'} = array_merge($block->{'conditions'},
                $selected_parent->conditions);
        }
        // Update Phi using penalties from selected parent
        foreach ($block->phi as $phi_op) {
            $this->setPhiPenalties($phi_op, $selected_parent);
        }
        $block->{'score'} *= $selected_parent->score;
    }

    private function resolveLastOp(Op $op)
    {
        // No need to do anything with other op types
        if ($op instanceof Op\Expr\FuncCall or $op instanceof Op\Expr\MethodCall) {
            $penalties = [];
            foreach ($this->dfg->getOutputParams() as $outputParam) {
                if ($outputParam->penalties) {
                    $penalties []= min($outputParam->penalties);
                }
            }
            if (!$penalties) {
                return PenaltyMatrix::UNSET_PENALTY;
            } else {
                return min($penalties);
            }
        }
    }

    public function applyRules(TargetOp $target_op, $parameter_number, array $rules)
    {
        $op = $target_op->op;
        // Count penalty foreach target argument + condition
        if (isset($rules[$parameter_number])){
            foreach ($rules[$parameter_number]  as $rule) {
                if ($rule->check($op, $parameter_number)) {
                    $penalty = $rule->countPenalty($op, $parameter_number);
                    return [$penalty, $rule];
                }
            }
        }
    }

    public function enterOp(Op $op, Block $block)
    {
        $target_op = $this->dfg->getTargetOp($op, true);
        // Update constant penalties
        if (!$target_op) {
            return;
        }
        RuleManager::updateOperandPenalties($target_op, $this->dfg);

        // If op is target op
        if (!$target_op->penalties) {
            $this->dfgScore *= $this->resolveLastOp($op);
            return;
        }
        foreach ($target_op->penalties as $parameter_number => $penalty) {
            // Check allow rules first
            [$penalty, $rule_applied] = $this->applyRules($target_op, $parameter_number, $this->allowRules);
            if (!$rule_applied) {
                [$penalty, $rule_applied] = $this->applyRules($target_op, $parameter_number, $this->penaltyRules);
            }
            if (!$penalty) {
                throw new \Exception('Undefined penalty');
            }
            $target_op->penalties[$parameter_number] = $penalty;
            if (isset($op->result)) {
                $target_result = $this->dfg->getTargetVar($op->result, true);
                $target_result->penalties[$parameter_number] = $penalty;
            }
        }

        $block->{'score'} *= min($target_op->penalties);
        echo "Rule applied: ".get_class($rule_applied).'(score: '.min($target_op->penalties).')'.PHP_EOL;

        RuleManager::resolveCondition($op, $block, $this->dfg);
    }

    public function getDfgScore()
    {
        return $this->dfgScore;
    }
}
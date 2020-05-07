<?php


namespace PhpChain\DfgAnalyzer;


use PHPCfg\Block;
use PHPCfg\Op;
use PHPCfg\Operand;
use PhpChain\DfgAnalyzer;
use PhpChain\Dfg;
use PhpChain\TargetOp;


/**
 * Class RuleManager
 * @package PhpChain\DfgAnalyzer
 */
class RuleManager
{
    /**
     * @param Op $op
     * @param Dfg $dfg
     * @param int $parameter_number
     * @param array $operands
     * @return array
     */
    private static function getAllOperandsScores(Op $op, Dfg $dfg, int $parameter_number, array $operands=[]) : array
    {
        if ($operands) {
            $args = $operands;
        } else {
            $target_op = $dfg->getTargetOp($op, true);
            $args = $target_op->getArguments();
        }
        return array_map(function($arg) use($dfg, $parameter_number){
            return self::getOperandScore($arg, $dfg, $parameter_number);
        }, $args);
    }

    /**
     * @param Op $op
     * @param Dfg $dfg
     * @param int $parameter_number
     * @param array $operands
     * @return float
     */
    public static function bestOperandScore(Op $op, Dfg $dfg, int $parameter_number, array $operands=[]): float
    {
        $scores = self::getAllOperandsScores($op, $dfg, $parameter_number, $operands);
        if (!$scores) {
            return PenaltyMatrix::UNSET_PENALTY;
        }
        return max($scores);
    }

    /**
     * @param Op $op
     * @param Dfg $dfg
     * @param int $parameter_number
     * @param array $operands
     * @return float
     */
    public static function worstOperandScore(Op $op, Dfg $dfg, int $parameter_number, array $operands=[]): float {
        $scores = self::getAllOperandsScores($op, $dfg, $parameter_number, $operands);
        if (!$scores) {
            return PenaltyMatrix::UNSET_PENALTY;
        }
        return min($scores);
    }

    /**
     * @param TargetOp $target_op
     * @param Dfg $dfg
     */
    public static function updateOperandPenalties(TargetOp $target_op, Dfg $dfg)
    {
        foreach ($target_op->getArguments() as $var) {
            $target_arg = $dfg->getTargetVar($var, true);
            if ($target_arg) {
                if (!isset($var)) {
                    /// Todo: Не забыть протетсить это и понять, где кто на кого матичтся, если здесь 0 пришел!
                    return;
                }
                $target_var = $dfg->getTargetVar($var, true);
                if (!$target_var) {
                    return;
                }
                if (!$target_var->penalties) {
                    if (isset($var->ops[0])) {
                        $target_op = $dfg->getTargetOp($var->ops[0], true);
                        if ($target_op) {
                            $target_var->penalties = $target_op->penalties;
                        }
                    }
                }
                $type = $var->getType();
                if (property_exists($var, 'value')) {
                    $general_penalty = PenaltyMatrix::CONSTANT;
                }
                switch ($type) {
                    case "BoundVariable":
                        $general_penalty = PenaltyMatrix::PROPERTY;
                        break;
                    case "Variable":
                        $general_penalty = PenaltyMatrix::CONSTANT;
                }
                if (isset($general_penalty)) {
                    foreach ($target_var->penalties as $parameter_number => $penalty) {
                        if ($parameter_number == DFG::CONDITION and $general_penalty == PenaltyMatrix::CONSTANT) {
                            $target_var->penalties[$parameter_number] = PenaltyMatrix::CONSTANT_FOR_CONDITION;
                        } else {
                            $target_var->penalties[$parameter_number] = $general_penalty;
                        }
                    }
                } else {
                    if (min($target_var->penalties) == PenaltyMatrix::UNSET_PENALTY) {
                        if (count($var->ops) == 1) {
                            $target_op = $dfg->getTargetOp($var->ops[0], true);
                            foreach ($target_var->penalties as $parameter_number => $penalty) {
                                $target_var->penalties[$parameter_number] = $target_op->penalties[$parameter_number];
                            }
                        }
                    }
                }
            }
        }

    }

    /**
     * @param Operand $var
     * @param Dfg $dfg
     * @param int $parameter_number
     * @return float
     */
    public static function getOperandScore(Operand $var, Dfg $dfg, int $parameter_number) {
        $target_var = $dfg->getTargetVar($var, true);
        if (!$target_var) {
            return PenaltyMatrix::UNSET_PENALTY;
        }
        if (!$target_var->penalties) {
            return PenaltyMatrix::UNSET_PENALTY;
        }
        if (!isset($target_var->penalties[$parameter_number])) {
            return PenaltyMatrix::UNSET_PENALTY;
        }
        return $target_var->penalties[$parameter_number];
    }

    /**
     * @param array $blocks
     * @return mixed
     */
    public static function worstParentBlock(array $blocks)
    {
        $score = min(array_map(function ($item){
            return $item->score;
        }, $blocks));
        return  array_filter($blocks, function ($parent) use ($score){
            return $parent->score === $score;
        })[0];

    }

    /**
     * @param Op $op
     * @param Block $block
     * @param Dfg $dfg
     * @return int
     */
    public static function resolveCondition(Op $op, Block $block, Dfg $dfg) {
        if ($op instanceof Op\Stmt\JumpIf or $op instanceof Op\Stmt\Switch_) {
            $target_cond = $dfg->getTargetVar($op->cond, true);
            $penalty = $target_cond->penalties[Dfg::CONDITION];
            if ($penalty < PenaltyMatrix::CONDITION_LEVEL_THRESHOLD) {
                $strategy = DfgAnalyzer::WORST_PATH;
            } else {
                $strategy = DfgAnalyzer::BEST_PATH;
            }
            $op->{'strategy'} = $strategy;
            if ($op instanceof Op\Stmt\JumpIf) {
                $op->if->{'conditions'} []= $op;
                $op->else->{'conditions'} []= $op;
            } else {
                foreach ($op->cases as $case) {
                    $case->{'conditions'} []= $op;
                }
            }
            return $strategy;
        }
        if ($op instanceof Op\Stmt\Jump) {
            $op->target->{'conditions'} = $block->conditions;
        }
    }
}
<?php


namespace PhpChain\ChainAnalyzer;


use PHPCfg\Block;
use PHPCfg\Op;
use PHPCfg\Operand;
use PhpChain\ChainAnalyzer;
use PhpChain\Dfg;
use PhpChain\TargetOp;


/**
 * Class RuleManager
 * @package PhpChain\ChainAnalyzer
 */
class RuleManager
{
    /**
     * @param Op $op
     * @param Dfg $dfg
     * @param array $operands
     * @param array $exclude_args
     * @return array
     */
    private static function excludeOperands(Op $op, Dfg $dfg, array $operands, array $exclude_args=[]): array {
        // Unused now
        if ($operands) {
            return $operands;
        }
        $target_op = $dfg->getTargetOp($op, true);
        $args = $target_op->getArguments();
        if ($exclude_args) {
            foreach ($exclude_args as $exclude_arg) {
                if ($arg = array_search($op->$exclude_arg, $args, true)) {
                    unset($arg);
                }
            }
        }
        return $args;
    }

    /**
     * @param Op $op
     * @param Dfg $dfg
     * @param int $parameter_number
     * @param array $operands
     * @return float
     */
    public static function bestOperandScore(Op $op, Dfg $dfg, int $parameter_number, array $operands=[]): float {
        if ($operands) {
            $args = $operands;
        } else {
            $target_op = $dfg->getTargetOp($op, true);
            $args = $target_op->getArguments();
        }
        $scores = array_map(function($arg) use($dfg, $parameter_number){
            return self::getOperandScore($arg, $dfg, $parameter_number);
        }, $args);
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
        if ($operands) {
            $args = $operands;
        } else {
            $target_op = $dfg->getTargetOp($op, true);
            $args = $target_op->getArguments();
        }
        $scores = array_map(function($arg) use($dfg, $parameter_number){
            return self::getOperandScore($arg, $dfg, $parameter_number);
        }, $args);
        if (!$scores) {
            return PenaltyMatrix::UNSET_PENALTY;
        }
        return min($scores);
    }

    /**
     * @param Op $op
     * @param Dfg $dfg
     * @param int $parameter_number
     * @param array $operands
     * @return float
     */
    public static function sumOperandScore(Op $op, Dfg $dfg, int $parameter_number, array $operands=[]): float {
        if ($operands) {
            $args = $operands;
        } else {
            $target_op = $dfg->getTargetOp($op, true);
            $args = $target_op->getArguments();
        }
        $scores = array_map(function($arg) use($dfg, $parameter_number){
            return self::getOperandScore($arg, $dfg, $parameter_number);
        }, $args);
        if (!$scores) {
            return PenaltyMatrix::UNSET_PENALTY;
        }
        return sum($scores);
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
        if ($cond_op->strategy == ChainAnalyzer::BEST_PATH) {
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
                $strategy = ChainAnalyzer::WORST_PATH;
            } else {
                $strategy = ChainAnalyzer::BEST_PATH;
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
<?php


namespace PhpChain\DfgAnalyzer\Rule\AllowRule;


use PHPCfg\Op;
use PhpChain\DfgAnalyzer\Rule;
use PhpChain\DfgAnalyzer\RuleManager;

/**
 * Class RightConcat
 * @package PhpChain\DfgAnalyzer\Rule\AllowRule
 */
class RightConcat implements Rule\AllowRule
{
    /**
     * @var
     */
    private $dfg;

    /**
     * RightConcat constructor.
     * @param $dfg
     */
    public function __construct($dfg)
    {
        $this->dfg = $dfg;
    }

    /**
     * @param Op $op
     * @param int $parameter_number
     * @return bool
     */
    public function check(Op $op, int $parameter_number): bool
    {
        if ($op instanceof Op\Expr\BinaryOp\Concat) {
            if (RuleManager::getOperandScore($op->left, $this->dfg, $parameter_number) >=
                RuleManager::getOperandScore($op->right, $this->dfg, $parameter_number)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param Op $op
     * @param int $parameter_number
     * @return float
     */
    public function countPenalty(Op $op, int $parameter_number): float
    {
        return RuleManager::getOperandScore($op->left, $this->dfg, $parameter_number);
    }
}
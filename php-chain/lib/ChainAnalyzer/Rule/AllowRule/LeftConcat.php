<?php


namespace PhpChain\ChainAnalyzer\Rule\AllowRule;


use PHPCfg\Op;
use PhpChain\ChainAnalyzer\Rule;
use PhpChain\ChainAnalyzer\RuleManager;

/**
 * Class LeftConcat
 * @package PhpChain\ChainAnalyzer\Rule\AllowRule
 */
class LeftConcat implements Rule\AllowRule
{
    /**
     * @var
     */
    private $dfg;

    /**
     * LeftConcat constructor.
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
            if (RuleManager::getOperandScore($op->right, $this->dfg, $parameter_number) >=
                RuleManager::getOperandScore($op->left, $this->dfg, $parameter_number)) {
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
        return RuleManager::getOperandScore($op->right, $this->dfg, $parameter_number);
    }
}
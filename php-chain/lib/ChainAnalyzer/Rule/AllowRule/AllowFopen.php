<?php


namespace PhpChain\ChainAnalyzer\Rule\AllowRule;


use PHPCfg\Op;
use PhpChain\ChainAnalyzer\PenaltyMatrix;
use PhpChain\ChainAnalyzer\Rule\AllowRule;
use PhpChain\ChainAnalyzer\RuleManager;

/**
 * Class AllowFopen
 * @package PhpChain\ChainAnalyzer\Rule\AllowRule
 */
class AllowFopen implements AllowRule
{
    /**
     * @var
     */
    private $dfg;
    /**
     * @var array
     */
    private $file_func_names = ['fopen'];

    /**
     * AllowFopen constructor.
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
        // Example for file processing
        //Todo: implement more complicated rules
        if ($op instanceof Op\Expr\FuncCall) {
            if (isset($op->name->value)) {
                if (in_array($op->name->value, $this->file_func_names)) {
                    return true;
                }
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
        if ($op->args[1]->value == 'r') {
            return PenaltyMatrix::CONSTANT;
        } else {
            return RuleManager::getOperandScore($op->args[0], $this->dfg, $parameter_number);
        }
    }
}
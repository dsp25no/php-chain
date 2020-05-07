<?php


namespace PhpChain\DfgAnalyzer\Rule\AllowRule;


use PHPCfg\Op;
use PhpChain\DfgAnalyzer\PenaltyMatrix;
use PhpChain\DfgAnalyzer\Rule\AllowRule;
use PhpChain\DfgAnalyzer\RuleManager;

/**
 * Class AllowFopen
 * @package PhpChain\DfgAnalyzer\Rule\AllowRule
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
            return PenaltyMatrix::SPECIFIC_SANITIZER_PENALTY;
        } else {
            return RuleManager::getOperandScore($op->args[0], $this->dfg, $parameter_number);
        }
    }
}
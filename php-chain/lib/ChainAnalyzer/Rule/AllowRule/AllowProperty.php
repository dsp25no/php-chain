<?php


namespace PhpChain\ChainAnalyzer\Rule\AllowRule;


use PHPCfg\Op;
use PhpChain\ChainAnalyzer\PenaltyMatrix;
use PhpChain\ChainAnalyzer\Rule\AllowRule;
use PhpChain\ChainAnalyzer\RuleManager;
use PhpChain\Dfg;

/**
 * Class AllowProperty
 * @package PhpChain\ChainAnalyzer\Rule\AllowRule
 */
class AllowProperty implements AllowRule
{
    /**
     * @var Dfg
     */
    private $dfg;

    /**
     * AllowProperty constructor.
     * @param Dfg $dfg
     */
    public function __construct(Dfg $dfg)
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
        return $op instanceof Op\Expr\PropertyFetch;
    }

    /**
     * @param Op $op
     * @param int $parameter_number
     * @return float
     */
    public function countPenalty(Op $op, int $parameter_number): float
    {
        return PenaltyMatrix::PROPERTY;
    }
}
<?php


namespace PhpChain\ChainAnalyzer\Rule\PenaltyRule;


use PHPCfg\Op;
use PhpChain\ChainAnalyzer\Rule\PenaltyRule;

/**
 * Class Sanitizer
 * @package PhpChain\ChainAnalyzer\Rule\PenaltyRule
 */
class Sanitizer implements PenaltyRule
{
    /**
     * @var array
     */
    private $sanitizers;
    /**
     * @var float
     */
    private $penalty;

    /**
     * Sanitizer constructor.
     * @param array $sanitizers
     * @param float $penalty
     */
    public function __construct(array $sanitizers, float $penalty)
    {
        $this->sanitizers = $sanitizers;
        $this->penalty = $penalty;
    }

    /**
     * @param Op $op
     * @param int $parameter_number
     * @return bool
     */
    public function check(Op $op, int $parameter_number): bool
    {
        if ($op instanceof Op\Expr\FuncCall) {
            if (in_array($op->name, $this->sanitizers)) {
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
    public function countPenalty(Op $op, int $parameter_number): float {
        return $this->penalty;
    }
}
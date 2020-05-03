<?php


namespace PhpChain\ChainAnalyzer;

use PHPCfg\Op;

/**
 * Interface Rule
 * @package PhpChain\ChainAnalyzer
 */
interface Rule
{
    /**
     * @param Op $op
     * @param int $parameter_number
     * @return bool
     */
    public function check(Op $op, int $parameter_number): bool;

    /**
     * @param Op $op
     * @param int $parameter_number
     * @return float
     */
    public function countPenalty(Op $op, int $parameter_number): float;
}
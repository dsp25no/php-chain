<?php


namespace PhpChain\DfgAnalyzer\Rule\PenaltyRule;


use PHPCfg\Op;
use PhpChain\DfgAnalyzer\PenaltyMatrix;
use PhpChain\DfgAnalyzer\Rule\PenaltyRule;

/**
 * Class NonStringPenalty
 * @package PhpChain\DfgAnalyzer\Rule\PenaltyRule
 */
class NonStringPenalty implements PenaltyRule
{
    /**
     * @var
     */
    private $dfg;

    /**
     * NonStringPenalty constructor.
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
        switch (true) {
            case $op instanceof Op\Expr\Cast\Bool_:
            case $op instanceof Op\Expr\Cast\Int_:
            case $op instanceof Op\Expr\Cast\Double:
            case $op instanceof Op\Expr\Cast\Unset_:
            case $op instanceof Op\Expr\Cast\Array_:
            case $op instanceof Op\Expr\Cast\Object_:
            case $op instanceof Op\Expr\Empty_:
            case $op instanceof Op\Expr\InstanceOf_ :
            case $op instanceof Op\Expr\Print_:
            case $op instanceof Op\Expr\Isset_:
            case $op instanceof Op\Expr\Array_:
            case $op instanceof Op\Expr\New_:
            case $op instanceof Op\Expr\BitwiseNot:
            case $op instanceof Op\Expr\BooleanNot:
            case $op instanceof Op\Expr\BinaryOp\BitwiseAnd:
            case $op instanceof Op\Expr\BinaryOp\BitwiseOr:
            case $op instanceof Op\Expr\BinaryOp\BitwiseXor:
            case $op instanceof Op\Expr\BinaryOp\Coalesce:
            case $op instanceof Op\Expr\BinaryOp\Div:
            case $op instanceof Op\Expr\BinaryOp\Equal:
            case $op instanceof Op\Expr\BinaryOp\Greater:
            case $op instanceof Op\Expr\BinaryOp\GreaterOrEqual:
            case $op instanceof Op\Expr\BinaryOp\Identical:
            case $op instanceof Op\Expr\BinaryOp\LogicalXor:
            case $op instanceof Op\Expr\BinaryOp\Minus:
            case $op instanceof Op\Expr\BinaryOp\Mod:
            case $op instanceof Op\Expr\BinaryOp\Mul:
            case $op instanceof Op\Expr\BinaryOp\NotEqual:
            case $op instanceof Op\Expr\BinaryOp\NotIdentical:
            case $op instanceof Op\Expr\BinaryOp\Plus:
            case $op instanceof Op\Expr\BinaryOp\Pow:
            case $op instanceof Op\Expr\BinaryOp\ShiftLeft:
            case $op instanceof Op\Expr\BinaryOp\ShiftRight:
            case $op instanceof Op\Expr\BinaryOp\Smaller:
            case $op instanceof Op\Expr\BinaryOp\SmallerOrEqual:
            case $op instanceof Op\Expr\BinaryOp\Spaceship:
            case $op instanceof Op\Iterator\Valid:
            case $op instanceof Op\Iterator\Key:
            case $op instanceof Op\Iterator\Next:
            case $op instanceof Op\Iterator\Reset:
                return true;
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
        return PenaltyMatrix::TYPE_CHANGED;
    }
}
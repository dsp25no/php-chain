<?php


namespace PhpChain\DfgAnalyzer\Rule\PenaltyRule;


use PHPCfg\Op;
use PhpChain\Dfg;
use PhpChain\DfgAnalyzer\PenaltyMatrix;
use PhpChain\DfgAnalyzer\Rule;
use PhpChain\DfgAnalyzer\RuleManager;

/**
 * Class BasePenalty
 * @package PhpChain\DfgAnalyzer\Rule\PenaltyRule
 */
class BasePenalty implements Rule\PenaltyRule
{
    /**
     * @var
     */
    private $dfg;

    /**
     * BasePenalty constructor.
     * @param $dfg
     */
    public function __construct($dfg)
    {
        $this->dfg = $dfg;
    }

    public function check(Op $op, int $parameter_number): bool
    {
        return true;
    }

    public function countPenalty(Op $op, int $parameter_number): float {
        $dfg = $this->dfg;
        switch (true) {
            /// Simple one-argument
            case $op instanceof Op\Expr\Assign:
            case $op instanceof Op\Expr\AssignRef:
            case $op instanceof Op\Expr\Cast:
            case $op instanceof Op\Expr\BitwiseNot:
            case $op instanceof Op\Expr\BooleanNot:
            case $op instanceof Op\Expr\Clone_:
            case $op instanceof Op\Expr\Empty_:
            case $op instanceof Op\Expr\Eval_:
            case $op instanceof Op\Expr\Include_:
            case $op instanceof Op\Expr\InstanceOf_ :
            case $op instanceof Op\Expr\Print_:
            case $op instanceof Op\Expr\UnaryMinus:
            case $op instanceof Op\Expr\UnaryPlus:
                return RuleManager::getOperandScore($op->expr, $dfg, $parameter_number) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Expr\Yield_:
                return RuleManager::getOperandScore($op->value, $dfg, $parameter_number) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Stmt\JumpIf:
            case $op instanceof Op\Stmt\Switch_:
                return RuleManager::getOperandScore($op->cond, $dfg, $parameter_number) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Iterator\Value:
            case $op instanceof Op\Iterator\Key:
                return RuleManager::getOperandScore($op->var, $dfg, $parameter_number) * PenaltyMatrix::PENALTY_FOR_OP;
            /// List
            case $op instanceof Op\Expr\ConcatList:
                return RuleManager::worstOperandScore($op, $dfg, $parameter_number, $op->list) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Expr\Isset_:
            case $op instanceof Op\Phi:
                return RuleManager::worstOperandScore($op, $dfg, $parameter_number, $op->vars) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Expr\BinaryOp:
                return min(RuleManager::getOperandScore($op->left, $dfg, $parameter_number),
                    RuleManager::getOperandScore($op->right, $dfg, $parameter_number)) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Expr\ArrayDimFetch:
                $array_penalty = RuleManager::getOperandScore($op->var, $dfg, $parameter_number);
                if ($array_penalty > PenaltyMatrix::PROPERTY_LEVEL_THRESHOLD) {
                    return $array_penalty * PenaltyMatrix::PENALTY_FOR_OP;
                } else {
                    return (PenaltyMatrix::ARRAY_NAME_PENALTY * $array_penalty +
                        PenaltyMatrix::ARRAY_DIM_PENALTY  *
                        RuleManager::getOperandScore($op->dim, $dfg, $parameter_number)) * PenaltyMatrix::PENALTY_FOR_OP ;
                }
            case $op instanceof Op\Expr\Array_:
                return RuleManager::bestOperandScore($op, $dfg, $parameter_number, $op->values) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Expr\FuncCall:
            case $op instanceof Op\Expr\NsFuncCall:
            case $op instanceof Op\Expr\MethodCall:
            case $op instanceof Op\Expr\StaticCall:
            case $op instanceof Op\Expr\New_:
                return PenaltyMatrix::UNDEFINED_PENALTY * RuleManager::worstOperandScore($op, $dfg,
                        $parameter_number, $op->args) * PenaltyMatrix::PENALTY_FOR_OP;
            case $op instanceof Op\Expr\Closure:
                return PenaltyMatrix::UNDEFINED_PENALTY * RuleManager::worstOperandScore($op, $dfg,
                        $parameter_number, $op->useVars) * PenaltyMatrix::PENALTY_FOR_OP;

                ///Constants
            case $op instanceof Op\Expr\ConstFetch:
            case $op instanceof Op\Expr\ClassConstFetch:
            case $op instanceof Op\Expr\StaticPropertyFetch:
            case $op instanceof Op\Expr\Cast\Unset_:
                if ($parameter_number == Dfg::CONDITION) {
                    return PenaltyMatrix::CONSTANT_FOR_CONDITION * PenaltyMatrix::PENALTY_FOR_OP;
                }
                return PenaltyMatrix::CONSTANT * PenaltyMatrix::PENALTY_FOR_OP;

            case $op instanceof Op\Expr\Assertion:
                return throwException(new \Exception("Unexpected OP in CFG: " . $op->getType()));
            case $op instanceof Op\Expr\Param:
                return PenaltyMatrix::FUNC_PARAM * PenaltyMatrix::PENALTY_FOR_OP;
            ///No need to count
            case $op instanceof Op\Expr:
            case $op instanceof Op\Terminal:
            case $op instanceof Op\Stmt:
            case $op instanceof Op\Type:
            default:
                return PenaltyMatrix::UNSET_PENALTY;
        }
    }
}
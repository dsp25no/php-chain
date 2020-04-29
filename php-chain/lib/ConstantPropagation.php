<?php


namespace PhpChain;

use PHPCfg\{Op, Block, Operand, Operand\NullOperand, Visitor};

class ConstantPropagation  extends \PHPCfg\AbstractVisitor
{
    private $dfg;

    public function __construct($dfg)
    {
        $this->dfg = $dfg;
    }

    private function aliveParents(Block $block)
    {
        $alive_parents = [];
        foreach ($block->parents as $parent) {
            if (!$parent->dead) {
                $alive_parents []= $parent;
            }
        }
        return $alive_parents;
    }

    public function enterBlock(Block $block, Block $prior = null)
    {
        // Do not count dead block
        if ($block->dead) {
            return;
        }
        // If block has parents and all of them dead - kill block
        if ($block->parents and !$this->aliveParents($block)) {
            $block->dead = true;
            return;
        }
        // Remove phi variants from dead blocks
        foreach ($block->phi as $phi_op) {
            for ($i = count($phi_op->vars)-1; $i >= 0; $i--) {
                $phi_parent = $this->findParent($block, $phi_op->vars[$i]);
                if (!$phi_parent or $phi_parent->dead) {
                    array_splice($phi_op->vars, $i, 1);
                }
            }
        }
        // Update phi->result if only one variant left
        foreach ($block->phi as $phi_op) {
            if (count($phi_op->vars) == 1 and property_exists($phi_op->vars[0], 'value')) {
                $phi_op->result->{'value'} = $phi_op->vars[0]->value;
            }
        }
    }

    public function enterOp(Op $op, Block $block)
    {
        $target_op = $this->dfg->getTargetOp($op);
        // Check that all vars are constant or already counted
        foreach ($target_op->getArguments() as $argument) {
            // Null is also a constant
            if ($argument instanceof NullOperand) {
                $argument->{'value'} = null;
            }
            // Operand is either Literal(Null) or a Temporary with counted result
            if (!property_exists($argument, 'value')) {
                return;
            }
        }
        switch (true){
            case $op instanceof Op\Expr\Assign:
            case $op instanceof Op\Expr\AssignRef:
                $op->result->{'value'} = $op->expr->value;
                $op->var->{'value'} = $op->expr->value;
                break;
            case $op instanceof Op\Expr\BinaryOp:
                $op->result->{'value'} = $this->binaryOpResolver($op);
                break;
            case $op instanceof Op\Expr\ArrayDimFetch:
                if (is_string($op->var->value)) {
                    $var = str_split($op->var->value);
                } else {
                    $var = $op->var->value;
                }
                foreach ($var as $key => $value) {
                    if ($key == $op->dim->value) {
                        $op->result->{'value'} = $value;
                        break;
                    }
                }
                break;
            case $op instanceof Op\Expr\Array_:
                $op->result->{'value'} = Array();
                foreach ($op->values as $i => $value) {
                    $key = $op->keys[$i]->value;
                    $op->result->{'value'}[$key] = $value->value;
                }
                break;
            case $op instanceof Op\Expr\Assertion:
                $op->result->{'value'} = assert($op->expr->value);
                break;
            case $op instanceof Op\Expr\BitwiseNot:
                $op->result->{'value'} = ~$op->expr->value;
                break;
            case $op instanceof Op\Expr\BooleanNot:
                $op->result->{'value'} = ! $op->expr->value;
                break;
            case $op instanceof Op\Expr\Cast:
                $op->result->{'value'} = $this->castResolver($op);
                break;
            case $op instanceof Op\Expr\ClassConstFetch:
                $const = $op->name->value;
                $op->result->{'value'} = $op->class->$const;
                break;
            case $op instanceof Op\Expr\Clone_:
                $op->result->{'value'} = clone $op->expr->value;
                break;
            case $op instanceof Op\Expr\ConcatList:
                foreach ($op->list as $item) {
                    $op->result->{'value'} .= $item->value;
                }
                break;
            case $op instanceof Op\Expr\ConstFetch:
                // NS ignored!!
                $op->result->{'value'} = $op->name->value;
                break;
            case $op instanceof Op\Expr\Empty_:
                $op->result->{'value'} = empty($op->expr->value);
                break;
            case $op instanceof Op\Expr\Eval_:
                // Forbid any strange evals
                break;
            case $op instanceof Op\Expr\InstanceOf_:
                $op->result->{'value'} = $op->expr->value instanceof $op->class->value;
                break;
            case $op instanceof Op\Expr\Isset_:
                foreach ($op->vars as $var) {
                    if (!isset($var->value)) {
                        $op->result->{'value'} = false;
                        break;
                    }
                }
                if (! isset($op->result->{'value'})) {
                    $op->result->{'value'} = true;
                }
                break;
            case $op instanceof Op\Expr\Print_:
                $op->result->{'value'} = 1;
                break;
            case $op instanceof Op\Expr\UnaryMinus:
                $op->result->{'value'} = -$op->expr->value;
                break;
            case $op instanceof Op\Expr\UnaryPlus:
                $op->result->{'value'} = +$op->expr->value;
                break;
            case $op instanceof Op\Phi:
                // Remove here PHI if no condition detected
                if (count($op->vars) == 1) {
                    $op->result->{'value'} = $op->vars[0]->value;
                }
                break;
            case $op instanceof Op\Stmt\JumpIf:
                if ($op->cond->value) {
                    $op->else->dead = true;
                } else {
                    $op->if->dead = true;
                }
                break;
            case $op instanceof Op\Stmt\Switch_:
                $matched = false;
                foreach ($op->cases as $i => $case) {
                    if ($op->cond->value != $case->value) {
                        $op->targets[$i]->dead = true;
                    } else {
                        $matched = true;
                    }
                }
                if ($matched) {
                    $op->default->dead = true;
                }
                break;
            case $op instanceof Op\Terminal\Unset_:
                foreach ($op->exprs as $expr) {
                    unset($expr->value);
                }
                break;
            // Not processed
            case $op instanceof Op\Iterator\Key:
            case $op instanceof Op\Iterator\Valid:
            case $op instanceof Op\Iterator\Value:
            case $op instanceof Op\Expr\FuncCall:
            case $op instanceof Op\Expr\MethodCall:
            case $op instanceof Op\Expr\New_:
            case $op instanceof Op\Expr\NsFuncCall:
            case $op instanceof Op\Expr\PropertyFetch:
            case $op instanceof Op\Expr\StaticCall:
            case $op instanceof Op\Expr\StaticPropertyFetch:
            case $op instanceof Op\Expr\Param:
            case $op instanceof Op\Expr\Closure:
            case $op instanceof Op\Expr\Include_:
            case $op instanceof Op\Expr\Exit_:
            case $op instanceof Op\Expr\Yield_:
            case $op instanceof Op\Terminal\Const_:
            case $op instanceof Op\Terminal\Return_:
            case $op instanceof Op\Terminal\Throw_:
        }
    }

    private function binaryOpResolver(Op\Expr\BinaryOp $op)
    {
        $left = $op->left->value;
        $right = $op->right->value;
        switch ($op->getType()) {
            case 'Expr_BinaryOp_BitwiseAnd':
                return $left & $right;
            case 'Expr_BinaryOp_BitwiseOr':
                return $left | $right;
            case 'Expr_BinaryOp_BitwiseXor':
                return $left ^ $right;
            case 'Expr_BinaryOp_Coalesce':
                return $left ?? $right;
            case 'Expr_BinaryOp_Concat':
                return $left . $right;
            case 'Expr_BinaryOp_Div':
                return $left / $right;
            case 'Expr_BinaryOp_Equal':
                return $left == $right;
            case 'Expr_BinaryOp_Greater':
                return $left > $right;
            case 'Expr_BinaryOp_GreaterOrEqual':
                return $left >= $right;
            case 'Expr_BinaryOp_Identical':
                return $left === $right;
            case 'Expr_BinaryOp_LogicalXor':
                return $left xor $right;
            case 'Expr_BinaryOp_Minus':
                return $left - $right;
            case 'Expr_BinaryOp_Mod':
                return $left % $right;
            case 'Expr_BinaryOp_Mul':
                return $left * $right;
            case 'Expr_BinaryOp_NotEqual':
                return $left != $right;
            case 'Expr_BinaryOp_NotIdentical':
                return $left !== $right;
            case 'Expr_BinaryOp_Plus':
                return $left + $right;
            case 'Expr_BinaryOp_Pow':
                return $left ** $right;
            case 'Expr_BinaryOp_ShiftLeft':
                return $left << $right;
            case 'Expr_BinaryOp_ShiftRight':
                return $left >> $right;
            case 'Expr_BinaryOp_Smaller':
                return $left < $right;
            case 'Expr_BinaryOp_SmallerOrEqual':
                return $left <= $right;
            case 'Expr_BinaryOp_Spaceship':
                return $left <=> $right;
            default:
                throw new \RuntimeException('Unknown constant op found: '.$op->getType());
        }
    }

    private function castResolver(Op\Expr\Cast $op)
    {
        $expr = $op->expr->value;
        switch ($op->getType()) {
            case 'Expr_Cast_Array':
                return (array)$expr;
            case 'Expr_Cast_Bool':
                return (bool)$expr;
            case 'Expr_Cast_Double':
                return (double)$expr;
            case 'Expr_Cast_Int':
                return (int)$expr;
            case 'Expr_Cast_Object':
                return (object)$expr;
            case 'Expr_Cast_String':
                return (string)$expr;
            case 'Expr_Cast_Unset':
                return (unset)$expr;
        }
    }

    public function leaveOp(Op $op, Block $block)
    {
        if ($op instanceof  Op\Phi) {
            // additional checks and removes
        }
    }

    private function findParent(Block $block, Operand $var) {
        foreach ($var->ops as $op) {
            foreach ($block->parents as $parent) {
                foreach ($parent->children as $parent_op) {
                    if ($op === $parent_op) {
                        return $parent;
                    }
                }
            }
        }
    }

    public function leaveBlock(Block $block, Block $prior = null)
    {
        if ($block->dead) {
            return Visitor::REMOVE_BLOCK;
        }
    }

    public function skipBlock(Block $block, Block $prior = null)
    {
    }
}
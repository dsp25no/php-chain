<?php


namespace PhpChain;


use PHPCfg;
use PHPCfg\{Block, Op};

class BackwardSlice extends \PHPCfg\AbstractVisitor
{
    private $dfg;
    public $block;
    public $op;

    private $op_counter = 0;

    public function __construct($dfg)
    {
        $this->dfg = $dfg;
    }

    public function enterBlock(Block $block, Block $prior = null)
    {
    }

    public function enterOp(Op $op, Block $block)
    {
        $op->{'id'} = $this->op_counter++;
        $call = $this->dfg->getCall();
        if($op->getLine() === $call->node->getLine() and
            ($op->getType() === "Expr_FuncCall" or $op->getType() === "Expr_MethodCall") and
            $op->name->value == $call->name) {

            $this->op = $op;
            $this->block = $block;

            $important_params = $this->dfg->getCall()->getTargetArgs();
            if($op->args) {
                // skip function that don't have arguments
                for ($i = 0; $i < sizeof($op->args); $i++) {
                    if (in_array($i, $important_params)) {
                        // add vars and ops to scope
                        $target_var = $this->dfg->getTargetVar($op->args[$i]);
                        foreach ($target_var->var->ops as $var_op) {
                            $this->dfg->getTargetOp($var_op);
                        }

                        $this->dfg->setOutputParam($target_var, $i);
                        $target_var->setOut();
                    }
                }
            }
        }
    }

    private function checkInScope(Op $op)
    {
        // All ops are added to scope with Vars. But return can be missed
        if ($this->dfg->getTargetOp($op, true)) {
            return true;
        }
        //check weather result goes to return statement
        if ($op instanceof Op\Expr\ArrayDimFetch or $op instanceof Op\Expr\PropertyFetch) {
            // don't look through var variable
            foreach ($op->result->usages as $usage) {
                if ($usage instanceof Op\Terminal\Return_ and $this->dfg->getTargetOp($usage, true)) {
                    return true;
                }
            }
        } else {
                if ($op->var) {
                    foreach ($op->var->usages as $usage) {
                        if ($usage instanceof Op\Terminal\Return_ and $this->dfg->getTargetOp($usage, true)) {
                            return true;
                        }
                    }
                }
                if ($op->result) {
                    foreach ($op->result->usages as $usage) {
                        if ($usage instanceof Op\Terminal\Return_ and $this->dfg->getTargetOp($usage, true)) {
                            return true;
                        }
                    }
                }
        }
    }

    public function leaveOp(Op $op, Block $block)
    {
        //this->op is needed to check weather "system" is reached
        if ($this->op) {
            if ($op instanceof Op\Expr\MethodCall or $op instanceof Op\Expr\FuncCall) {
                //Todo: how to add another functions to slice???
            }
            if (! $this->checkInScope($op) and $op->id != $this->op->id) {
                return \PHPCfg\Visitor::REMOVE_OP;
            }
            if ($op->id != $this->op->id) {
                // add ops and vars to scope
                $target_op = $this->dfg->getTargetOp($op);
                foreach ($target_op->getArguments() as $argument) {
                    $target_var = $this->dfg->getTargetVar($argument);
                    foreach ($target_var->var->ops as $var_op) {
                        $this->dfg->getTargetOp($var_op);
                    }
                }
                // for anonymous functions
                if ($op instanceof Op\Expr\Closure ) {
                    if ($func = $op->getFunc()) {
                        $return  = $func->cfg->children[count($func->cfg->children)-1];
                        $this->dfg->getTargetOp($return);
                    }
                }
            }
            // add if-else condition or other parent last stmt
            foreach ($block->parents as $parent) {
                $cond = $parent->children[count($parent->children)-1];
                $this->dfg->getTargetOp($cond);
            }

        }
    }

    public function leaveBlock(Block $block, Block $prior = null)
    {
    }

    public function skipBlock(Block $block, Block $prior = null)
    {
    }
}
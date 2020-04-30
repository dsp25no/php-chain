<?php


namespace PhpChain;

use PHPCfg;
use PHPCfg\{Block, Op};

/**
 * Class BackwardSlice
 * @package PhpChain
 */
class BackwardSlice extends \PHPCfg\AbstractVisitor
{
    /**
     * @var
     */
    private $dfg;
    /**
     * @var
     */
    public $block;
    /**
     * @var
     */
    public $op;

    /**
     * BackwardSlice constructor.
     * @param $dfg
     */
    public function __construct($dfg)
    {
        $this->dfg = $dfg;
    }

    /**
     * @param Block $block
     * @param Block|null $prior
     */
    public function enterBlock(Block $block, Block $prior = null)
    {
    }

    private function addToScope($variables, $usage)
    {
        if (! is_array($variables)) {
            $variables = [$variables];
        }
        foreach ($variables as $variable) {
            $target_var = $this->dfg->getTargetVar($variable);
            $this->dfg->addUsedIn($target_var, $usage);
            foreach ($target_var->var->ops as $var_op) {
                $target_op = $this->dfg->getTargetOp($var_op);
                $this->dfg->addUsedIn($target_op, $usage);
            }
        }
    }

    /**
     * @param Op $op
     * @param Block $block
     */
    public function enterOp(Op $op, Block $block)
    {
        $call = $this->dfg->getCall();
        if($op->getLine() === $call->node->getLine() and
            ($op->getType() === "Expr_FuncCall" or $op->getType() === "Expr_MethodCall") and
            isset($op->name->value) and $op->name->value == $call->name) {

            $this->op = $op;
            $this->block = $block;

            $important_params = $this->dfg->getCall()->getTargetArgs();
            if($op->args) {
                // skip function that don't have arguments
                for ($i = 0; $i < sizeof($op->args); $i++) {
                    if (in_array($i, $important_params)) {
                        // add vars and ops to scope
                        $this->addToScope($op->args[$i], $i);
                        $target_var = $this->dfg->getTargetVar($op->args[$i], true);
                        $this->dfg->setOutputParam($target_var, $i);
                        $target_var->setOut();
                        $this->dfg->addUsedIn($target_var, $i);
                    }
                }
            }
        }
    }

    /**
     * @param Op $op
     * @return bool
     */
    private function checkInScope(Op $op)
    {
        // All ops are added to scope with Vars. But return can be missed
        if ($this->dfg->getTargetOp($op, true)) {
            return true;
        }
        $usages = [];
        if ($op instanceof Op\Expr\ArrayDimFetch or $op instanceof Op\Expr\PropertyFetch) {
            // don't look through var variable
            $usages = array_merge($usages, $op->result->usages);
        } else {
            if (isset($op->var)) {
                $usages = array_merge($usages, $op->var->usages);
            }
            if (isset($op->result)) {
                $usages = array_merge($usages, $op->result->usages);
            }
        }
        //check whether result goes to return statement
        foreach ($usages as $usage) {
            if ($usage instanceof Op\Terminal\Return_ and $target_usage = $this->dfg->getTargetOp($usage, true)) {
                $target_op = $this->dfg->getTargetOp($op);
                $this->dfg->addUsedIn($target_op, $target_usage->penalties);
                return true;
            }
        }
    }

    /**
     * @param Op $op
     * @param Block $block
     * @return int|void
     */
    public function leaveOp(Op $op, Block $block)
    {
        if ($op instanceof Op\Expr\MethodCall or $op instanceof Op\Expr\FuncCall) {
            //Todo: how to add another functions to slice???
        }
        if (! $this->checkInScope($op) and $op !== $this->op) {
                return \PHPCfg\Visitor::REMOVE_OP;
        }
        if ($op !== $this->op) {
            // add ops and vars to scope
            $target_op = $this->dfg->getTargetOp($op);
            $this->addToScope($target_op->getArguments(), $target_op->penalties);
            // for anonymous functions
            if ($op instanceof Op\Expr\Closure ) {
                if ($func = $op->getFunc()) {
                    $return  = $func->cfg->children[count($func->cfg->children)-1];
                    $target_return = $this->dfg->getTargetOp($return);
                    $this->dfg->addUsedIn($target_return, $target_op->penalties);
                }
            }
        }
    }


    /**
     * @param Block $block
     * @param Block|null $prior
     */
    public function leaveBlock(Block $block, Block $prior = null)
    {
        // add if-else condition or other parent last stmt
        foreach ($block->parents as $parent) {
            if ($parent->children) {
                $cond = $parent->children[count($parent->children) - 1];
                $target_op = $this->dfg->getTargetOp($cond);
                $this->dfg->addUsedIn($target_op, Dfg::CONDITION);
            }
        }

        // add phi to scope
        foreach ($block->phi as $phi) {
            foreach ($phi->result->usages as $phi_usage) {
                if ($target_op = $this->dfg->getTargetOp($phi_usage, true)) {
                    $target_phi = $this->dfg->getTargetOp($phi);
                    $this->addToScope($target_phi->getArguments(), $target_op->penalties);
                }
            }
        }
    }

    /**
     * @param Block $block
     * @param Block|null $prior
     */
    public function skipBlock(Block $block, Block $prior = null)
    {
    }
}
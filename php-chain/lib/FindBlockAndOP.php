<?php

/**
 */

namespace PhpChain;

use PHPCfg;
use PHPCfg\{Block, Op};

class FindBlockAndOP extends \PHPCfg\AbstractVisitor
{
    private $call;
    public $block;
    public $op;

    public function __construct($call)
    {
        $this->call = $call;
    }

    public function enterBlock(Block $block, Block $prior = null)
    {
    }

    public function enterOp(Op $op, Block $block)
    {
        if (
            $op->getLine() === $this->call->node->getLine() and
            ($op->getType() === "Expr_FuncCall" or $op->getType() === "Expr_MethodCall") and
            $op->name->value == $this->call->name
        ) {
            $this->op = $op;
            $this->block = $block;
        }
    }
    public function leaveOp(Op $op, Block $block)
    {
    }
    public function leaveBlock(Block $block, Block $prior = null)
    {
        if (sizeof($block->children) === 0) {
            return \PHPCfg\Visitor::REMOVE_BLOCK;
        }
    }
    public function skipBlock(Block $block, Block $prior = null)
    {
    }
}

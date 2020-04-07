<?php


namespace PhpChain;

use PHPCfg\Block;
use PHPCfg\Func;
use PHPCfg\Script;
use PHPCfg\Traverser;
use PHPCfg\Visitor;

class BackwardSliceWalker
{
    /** @var \SplObjectStorage */
    private $seen;
    private $visitors = [];

    public function addVisitor(Visitor $visitor)
    {
        $this->visitors[] = $visitor;
    }

    public function traverse(Script $script)
    {
        foreach ($script->functions as $func) {
            $this->traverseFunc($func, 'forward');
        }
        foreach ($script->functions as $func) {
            $this->traverseFunc($func, 'backward');
        }
    }

    private function traverseFunc(Func $func, $direction)
    {
        $this->seen = new \SplObjectStorage();
        if ($direction == 'forward') {
            $this->event('enterFunc', [$func]);
        }
        $block = $func->cfg;
        if (null !== $block) {
            $result = $this->traverseBlock($block, $direction,null);
                if ($result === Visitor::REMOVE_BLOCK) {
                    throw new \RuntimeException('Cannot remove function start block');
                }
                if (null !== $result) {
                    $block = $result;
                }
                $func->cfg = $block;
        }
        if ($direction == 'backward') {
            $this->event('leaveFunc', [$func]);
        }
        $this->seen = null;
    }

    private function traverseBlock(Block $block, $direction, Block $prior = null)
    {
        if ($this->seen->contains($block)) {
            $this->event('skipBlock', [$block, $prior]);
            // Always return null on a skip event
            return;
        }
        $this->seen->attach($block);
        if ($direction == 'forward') {
            $this->event('enterBlock', [$block, $prior]);
            $children = $block->children;
        } elseif ($direction == 'backward') {
            $children = array_reverse($block->children);
        }
        for ($i = 0; $i < count($children); ++$i) {
            $op = $children[$i];
            if ($direction == 'forward') {
                $this->event('enterOp', [$op, $block]);
            }
            foreach ($op->getSubBlocks() as $subblock) {
                $sub = $op->{$subblock};
                if (!$sub) {
                    continue;
                }
                if (!is_array($sub)) {
                    $sub = [$sub];
                }
                if ($direction == 'forward') {
                    for ($j = 0; $j < count($sub); ++$j) {
                        $this->traverseBlock($sub[$j], 'forward', $block);
                    }
                } elseif ($direction == 'backward') {
                    for ($j = count($sub) - 1; $j >= 0; --$j) {
                        $result = $this->traverseBlock($sub[$j], 'backward', $block);
                        if ($result === Visitor::REMOVE_BLOCK) {
                            array_splice($sub, $j, 1, []);
                            // Revisit the ith block
                            if ($j < count($sub)) {
                                ++$j;
                            }
                        } elseif ($result instanceof Block) {
                            $sub[$j] = $result;
                            // Revisit the ith block again
                            if ($j < count($sub)) {
                                ++$j;
                            }
                        } elseif (null !== $result) {
                            throw new \RuntimeException('Unknown return from visitor: '.gettype($result));
                        }
                    }
                }
                if (is_array($op->{$subblock})) {
                    $op->{$subblock} = $sub;
                } else {
                    $op->{$subblock} = array_shift($sub);
                }
            }
            if ($direction == 'backward') {
                $result = $this->event('leaveOp', [$op, $block]);
                if ($result === Visitor::REMOVE_OP) {
                    array_splice($children, $i, 1, []);
                    // Revisit the ith node
                    if ($i < count($children)) {
                        --$i;
                    }
                } elseif ($result instanceof Op) {
                    $children[$i] = $result;
                    // Revisit the ith node again
                    if ($i < count($children)) {
                        --$i;
                    }
                } elseif (null !== $result && $result !== $op) {
                    throw new \RuntimeException('Unknown return from visitor: ' . gettype($result));
                }
            }
        }
        if ($direction == 'forward') {
            $block->children = $children;
        }
        if ($direction == 'backward') {
            $block->children = array_reverse($children);
            return $this->event('leaveBlock', [$block, $prior]);
        }
    }

    private function event($name, array $args)
    {
        foreach ($this->visitors as $visitor) {
            $return = call_user_func_array([$visitor, $name], $args);
            if (null !== $return) {
                return $return;
            }
        }
    }
}
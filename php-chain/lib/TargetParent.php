<?php
/**
 */

namespace PhpChain;


class TargetParent
{
    public $block;
    public $reachable = 0;
    public $conditions;

    private $dfg;

    public function __construct($block, $dfg, $conditions=[])
    {
        $this->block = $block;
        $this->dfg = $dfg;
        if (!$conditions) {
            $this->conditions = [];
        }
    }

    public function functionStartBlock() {
        return sizeof($this->block->parents) == 0;
    }

    public function getCondition() {
        return $this->block->children[sizeof($this->block->children) - 1];
    }

    public function checkCondition($cond) {
        if ($cond->getType() == "Stmt_Jump") {
            $reachable = 1;
        } elseif ($cond->getType() == "Stmt_JumpIf") {
            //ToDo: check if it has already been checked for another branch (if/else)
//            if ($this->ifConditionVisited($cond)) {
//                $reachable = 1;
//                return $reachable;
//            }
            $var_cond = $this->dfg->getTargetVar($cond->cond);
            // ToDo: check value and condition op->getType() + check if or else block == this->block
//                foreach ($cond->cond->ops as $op) {
//                    var_dump($op->getType());
//                }
            $cond_category = $var_cond->getCategory();
            if ($cond_category == "PROPERTY") {
                $reachable = 1 ;
            } elseif ($cond_category ==  "CONST") {
                $reachable = 0.8;
            } else {
                $reachable = 0.1;
            }
        } else {
            // ToDO: implement other cases
            $reachable = 0.1;
        }
        return $reachable;
    }

    public function isReachable() {
        if ($this->functionStartBlock()) {
            $this->reachable = 1;
            return $this->reachable;
        }
        $max = 0;
        foreach ($this->block->parents as $block_parent) {
            $parent = new TargetParent($block_parent, $this->dfg,$this->conditions);
            $cond = $parent->getCondition();
            $reachable = $this->checkCondition($cond);
            $this->conditions = clone $cond;
            $reachable *= $parent->isReachable();
            if ($reachable == 1) {
                $this->reachable = 1;
                return $reachable;
            } elseif ($reachable > $max) {
                $max = $reachable;
            }
        }
        return $max;
    }

    public function ifConditionVisited($cond) {
        foreach ($this->conditions as $condition) {
//            if ($condition == $cond) {
//                var_dump("[");
                if ($cond->if == $condition->else or $cond->else == $condition->if) {
//                    return true;
                }
        }
        return false;
    }
}

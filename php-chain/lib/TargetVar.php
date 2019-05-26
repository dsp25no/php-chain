<?php
/**
 */

namespace PhpChain;

class TargetVar {
    public $var;
    public $value;
    private $category;
    private $metric;
    private $dfg;
    private $processing;

    public function __construct($operand, $dfg)
    {
        $this->var = $operand;
        $this->dfg = $dfg;
        $this->metric = 1.0;
        $this->processing = False;
    }

    private function classify()
    {
        $type = $this->var->getType();
        if ($type === "Literal" or $type === "NullOperand") {
            $this->category = "CONST";
            if ($type == "Literal") {
                $this->value = $this->var->value;
            }
        } elseif ($type === "Variable") {
            $this->category = "VAR";
        } elseif ($type === "Temporary") {
            $op = $this->var->ops[0];
            $target_op = $this->dfg->getTargetOp($op);
            $this->category = $target_op->getCategory();
        } elseif ($type === "BoundVariable") {
            $this->category = "PROPERTY";
        }
    }

    public function getMetric()
    {
        $category = $this->getCategory();
        $this->metric = TargetVar::getNumber($category);
        return $this->metric;
    }

    public function getCategory()
    {
        if (!$this->category && !$this->processing) {
            $this->processing = True; //TODO: move to classify
            $this->classify();
            $this->processing = False;
        } elseif ($this->processing) {
            return "TMP";
        }
        return $this->category;
    }

    public function updateCategory($category)
    {
        if($this->processing)
            return;
        $this->processing = true;
        $this->category = $category;
        $this->metric = TargetVar::getNumber($this->category);
        foreach ($this->var->usages as $op) {
            $target_op = $this->dfg->getTargetOp($op, True);
            if($target_op) {
                $target_op->updateCategory();
            }
        }
        $this->processing = false;
    }

    public static function getNumber($category)
    {
        if ($category == "CONST") {
            return 0.5;
        } elseif ($category == "PROPERTY") {
            return 0.9;
        } elseif ($category == "VAR") {
            return 0.2;
        } elseif ($category == "FUNC_PARAM") {
            return 0.1;
        } elseif ($category == "TMP") {
            return 1;
        }
    }
}

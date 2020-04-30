<?php
/**
 */

namespace PhpChain;


class TargetOp
{
    public $op;
    public $category;
    public $result;
    public $metric;
    public $penalties = [];

    private $dfg;

    public function __construct($op, $dfg)
    {
        $this->op = $op;
        $this->dfg = $dfg;
        $this->metric = 0.0;
    }

    public function composeBinaryOp()
    {
        $right = $this->dfg->getTargetVar($this->op->right);
        $left = $this->dfg->getTargetVar($this->op->left);
        $right_category = $right->getCategory();
        $left_category = $left->getCategory();
        $this->category = null;
        if (!$right_category) {
            $this->category = $left_category;
        } elseif ($right_category == $left_category) {
            $this->category = $right_category;
        } elseif ($right_category == "VAR" or $left_category == "VAR") {
            $this->category = "VAR";
        } elseif ($right_category == "CONST") {
            $this->category = $left_category;
        } elseif ($left_category == "CONST") {
            $this->category = $right_category;
        } elseif ($right_category == "FUNC_PARAM" or $left_category == "FUNC_PARAM") {
            $this->category = "FUNC_PARAM";
        } else {
            $this->category = "TMP";
        }
        $this->metric = TargetVar::getNumber($this->category);
    }

    public function composePhiOp()
    {
        $this->category = "TMP";
        foreach ($this->op->vars as $var) {
            $target_var = $this->dfg->getTargetVar($var);
            $category = $target_var->getCategory();
            if($category == "TMP") {
                continue;
            }
            $metric = TargetVar::getNumber($category);
            if($metric > $this->metric) {
                $this->metric = $metric;
                $this->category = $category;
            }
        }
        if($this->category === "TMP") {
            $this->metric = TargetVar::getNumber($this->category);
        }
    }

    public function classify() {
        $type = $this->op->getType();
        if ($type == "Expr_PropertyFetch") {
            $in = $this->dfg->getTargetVar($this->op->var);
            $this->category = $in->getCategory();
            $this->result = $in->value;
        }
        elseif ($type == "Expr_Param") {
            $this->category = "FUNC_PARAM";
        }
        elseif ($type == "Expr_Assign") {
            $assign_val = $this->dfg->getTargetVar($this->op->expr);
            $this->category = $assign_val->getCategory();
            $this->result = $assign_val->value;
        }
        elseif ($type == "Phi") {
            $this->composePhiOp();
        }
        elseif (substr($type, 0, 13) == "Expr_BinaryOp") {
            $this->composeBinaryOp();
            if ($this->category == "CONST") {
                $this->result = TargetOp::countExpr($this->dfg->getTargetVar($this->op->result), $this->dfg);
            }
        }
        else {
            $this->category = "VAR";
        }
    }

    public static function countExpr(TargetVar $expr, $dfg) {
        $var_type = $expr->var->getType();
        if ($var_type == "Literal") {
            return $expr->var->value;
        }
        foreach ($expr->var->ops as $op) {
            $type = $op->getType();
            if(substr($type, 0, 13) == "Expr_BinaryOp") {
                if ($type == "Expr_BinaryOp_Plus") {
                    return TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg) +
                        TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg);
                } elseif ($type == "Expr_BinaryOp_Minus") {
                    return TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg) -
                        TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg);
                } elseif ($type == "Expr_BinaryOp_Greater") {
                    return TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg) >
                        TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg);
                } elseif ($type == "Expr_BinaryOp_Smaller") {
                    return TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg) <
                        TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg);
                } elseif ($type == "Expr_BinaryOp_Concat") {
                    return TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg) .
                        TargetOp::countExpr($dfg->getTargetVar($op->left, $dfg), $dfg);
                }
            }
        }
        return $expr->value;
    }

    public function updateCategory($category=null)
    {
        $type = $this->op->getType();
        if ($type == "Expr_Param") {
            if ($category === null) {
                throw new \Exception("Bad update category");
            }
            $this->category = $category;
        } elseif ($type == "Phi") {
            $this->composePhiOp();
        } elseif (substr($type, 0, 13) == "Expr_BinaryOp") {
            $this->composeBinaryOp();
            if ($this->category == "CONST") {
                $this->result = TargetOp::countExpr($this->dfg->getTargetVar($this->op->result), $this->dfg);
            }
        }
        $var = $this->op->result;
        $var = $this->dfg->getTargetVar($var);
        $var->updateCategory($this->category);
    }

    public function getCategory()
    {
        if (!$this->category) {
            $this->classify();
        }
        return $this->category;
    }

    public function getMetric()
    {
        if ($this->category == null) {
            $this->classify();
        }
        return $this->metric;
    }

    public function getArguments()
    {
        $arguments = [];
        // get all argument names
        $type = $this->op->getType();
        foreach ($this->op->getVariableNames() as $arg_name) {
            $arg = $this->op->$arg_name;
            if (is_null($arg)) {
                continue;
            }
            switch ($arg_name) {
                case 'result':
                    break;
                case 'name':
                    if ( $type == 'Expr_FuncCall' or $type == 'Expr_MethodCall') {
                        $arguments [] = $arg;
                    }
                    break;
                case 'var':
                    if ($type == 'Iterator_Value' or $type == 'Expr_ArrayDimFetch'
                        or $type == 'Expr_PropertyFetch' or $type == 'Expr_ConstFetch'
                        or $type == 'Expr_MethodCall' ) {
                        $arguments [] = $arg;
                    }
                    break;
                case 'list':
                case 'args':
                case 'vars':
                case 'keys':
                case 'values':
                case 'cases':
                case 'exprs':
                case 'useVars':
                    $arguments = array_merge($arguments, $arg);
                    break;
                case 'left':
                case 'cond':
                case 'right':
                case 'expr':
                case 'dim':
                case 'key':
                case 'class':
                case 'nsName':
                case 'defaultVar':
                    $arguments [] = $arg;
            }
        }
        return $arguments;
    }

}

<?php
/**
 */

namespace PhpChain;


class TargetOp
{
    public $op;
    public $result;
    public $penalties = [];

    private $dfg;

    public function __construct($op, $dfg)
    {
        $this->op = $op;
        $this->dfg = $dfg;
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

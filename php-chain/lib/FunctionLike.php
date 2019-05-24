<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:10
 */

namespace PhpChain;

use PhpParser\{Node, NodeFinder};
use PhpChain\ExprCall\{FuncCall, MethodCall};

abstract class FunctionLike
{
    public $name;
    public $params;
    public $attributes = [];
    public $node;
    protected $_string;
    protected $_calls;

    public function __construct($name, $node, $params, array $attributes = [])
    {
        $this->name = $name;
        $this->node = $node;
        $this->params = $params;
        $this->_calls = null;
    }

    public function extractCalls()
    {
        if ($this->_calls) {
            return $this->_calls;
        }
        $nodeFinder = new NodeFinder;
        $calls = $nodeFinder->find($this->node->stmts, function(Node $node) {
            return $node instanceof Node\Expr\FuncCall or
                $node instanceof Node\Expr\MethodCall or
                $node instanceof Node\Expr\New_;
        });
        $this->_calls = [];
        foreach ($calls as $call) {
            if ($call instanceof Node\Expr\New_) {
                $this->_calls[] = new MethodCall($call);
                continue;
            }
            if (!($call->name instanceof Node\Identifier or
                $call->name instanceof Node\Name or
                $call->name instanceof Node\Expr\Variable)) {
                continue;
            }
            if ($call instanceof Node\Expr\FuncCall){
                $this->_calls[] = new FuncCall($call);
            } elseif ($call instanceof Node\Expr\MethodCall) {
                $this->_calls[] = new MethodCall($call, $this);
            }
        }
        return $this->_calls;
    }

    public function getNumberOfRequiredParameters()
    {
        for($i = sizeof($this->params) - 1; $i >= 0; $i--) {
            $param = $this->params[$i];
            if($param->default === null and !$param->variadic) {
                return $i + 1;
            }
        }
        return 0;
    }

    public function getFullName()
    {
        return strval($this->name);
    }

    public function __toString()
    {
        if($this->_string) {
            return $this->_string;
        }
        if($this->params) {
            $required = $this->getNumberOfRequiredParameters();
            $optional = sizeof($this->params) - $required;
            $this->_string = $this->name . "(" . str_repeat("r", $required) . \
                    str_repeat("o", $optional) . \
                    str_repeat("v", end($this->params)->variadic) . ")";
        } else {
            $this->_string = $this->name . "()";
        }
        return $this->_string;
    }
}

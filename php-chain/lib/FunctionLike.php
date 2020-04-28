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

/**
 * Class FunctionLike
 * @package PhpChain
 */
abstract class FunctionLike
{
    /**
     * @var \PhpParser\Node\Identifier|string
     */
    public $name;
    /**
     * @var
     */
    public $params;
    /**
     * @var array
     */
    public $attributes = [];
    /**
     * @var Node\FunctionLike|null
     */
    public ?Node\FunctionLike $node;
    /**
     * @var string
     */
    protected string $_string;
    /**
     * @var ExprCall[]
     */
    protected array $_calls;

    /**
     * FunctionLike constructor.
     * @param string $name
     * @param Node\FunctionLike|null $node
     * @param $params
     * @param array $attributes
     */
    public function __construct(string $name, ?Node\FunctionLike $node, $params, array $attributes = [])
    {
        $this->name = $name;
        $this->node = $node;
        $this->params = $params;
    }

    /**
     * @return ExprCall[]
     * @throws \Exception
     */
    public function extractCalls()
    {
        if (isset($this->_calls)) {
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

    /**
     * @return int
     */
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

    /**
     * @return string
     */
    public function getFullName()
    {
        return strval($this->name);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if(isset($this->_string)) {
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

<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-04-03
 * Time: 19:28
 */

namespace PhpChain\ExprCall;

use PhpChain\ClassMethod;
use PhpParser\Node\Expr\MethodCall as ParserMethodCall;
use PhpParser\Node\Expr\New_ as ParserNew;
use PhpParser\Node;
use PhpChain\ExprCall;

/**
 * Class MethodCall
 * @package PhpChain\ExprCall
 */
class MethodCall extends ExprCall
{
    /**
     * @var string
     */
    public string $owner;

    /**
     * MethodCall constructor.
     * @param ParserMethodCall|ParserNew $node
     * @param ClassMethod|null $func
     * @throws \Exception
     */
    public function __construct($node, ClassMethod $func = null)
    {
        if ($node instanceof ParserMethodCall) {
            $this->name = $node->name;
            if ($node->var->name == "this") {
                if (!$func) {
                    throw new \Exception("Method without owner");
                }
                $this->owner = $func->class->name;
            } else {
                $this->owner = "*";
            }
        } elseif ($node instanceof ParserNew) {
            $this->name = "__construct";
            if (
                $node->class instanceof Node\Identifier or
                $node->class instanceof Node\Name
            ) {
                $this->owner = $node->class;
            } else {
                $this->owner = "*";
            }
        }
        $this->argsCount = sizeof($node->args);
        $this->node = $node;
        $this->countUse = 0;
    }

    /**
     * @return bool
     */
    public function isClassFixed()
    {
        return $this->owner instanceof Node\Identifier or
            $this->owner instanceof Node\Name;
    }

    /**
     * @return bool
     */
    public function isStrict()
    {
        return $this->name instanceof Node\Identifier or
            $this->name instanceof Node\Name;
    }

    /**
     * @return string
     */
    public function getMethodName()
    {
        return $this->owner . "->" . $this->name;
    }

    /**
     * @return string
     */
    public function getRegex()
    {
        $regex = "/^";
        $regex .= $this->owner === "*" ?
                   "[\w\\\\]+" :
                   preg_quote($this->owner);
        $regex .= "->";
        if ($this->name instanceof Node\Expr\Variable) {
            $regex .= "[\w]+";
        } else {
            $regex .= $this->name;
        }
        $regex .= "\(r{0," . $this->argsCount . "}[^r]*\)";
        $regex .= "$/";
        return $regex;
    }
}

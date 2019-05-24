<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:11
 */

namespace PhpChain;

use PhpParser\Node\Stmt\ClassMethod as ParserClassMethod;

class ClassMethod extends FunctionLike
{
    public $class;
    public $flags;

    public function __construct($name, $node, $params, $flags, $class, array $attributes = [])
    {
        parent::__construct($name, $node, $params, $attributes);
        $this->class = $class;
        $this->flags = $flags;
    }

    public static function create(ParserClassMethod $node, $class)
    {
        return new self($node->name, $node, $node->params, $node->flags, $class);
    }

    public function isPrivate()
    {
        return $this->node->isPrivate();
    }

    public function getFullName()
    {
        return strval($this->class->name) . "->" . strval($this->name);
    }

    public function __toString()
    {
        if($this->_string) {
            return $this->_string;
        }
        $function = parent::__toString();
        $this->_string = $this->class->name->toCodeString() . "->" . $function;

        return $this->_string;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:11
 */

namespace PhpChain;

use PhpParser\Node\Stmt\ClassMethod as ParserClassMethod;

/**
 * Class ClassMethod
 * @package PhpChain
 */
class ClassMethod extends FunctionLike
{
    /**
     * @var ClassLike
     */
    public ClassLike $class;
    /**
     * @var
     */
    public $flags;

    /**
     * ClassMethod constructor.
     * @param \PhpParser\Node\Identifier|string $name
     * @param ParserClassMethod $node
     * @param $params
     * @param $flags
     * @param ClassLike $class
     * @param array $attributes
     */
    public function __construct($name, ParserClassMethod $node, $params, $flags, ClassLike $class, array $attributes = [])
    {
        parent::__construct($name, $node, $params, $attributes);
        $this->class = $class;
        $this->flags = $flags;
    }

    /**
     * @param ParserClassMethod $node
     * @param ClassLike $class
     * @return ClassMethod
     */
    public static function create(ParserClassMethod $node, ClassLike $class)
    {
        return new self($node->name, $node, $node->params, $node->flags, $class);
    }

    /**
     * @return bool
     */
    public function isPrivate()
    {
        return $this->node->isPrivate();
    }

    /**
     * @return string
     */
    public function getFullName()
    {
        return strval($this->class->name) . "->" . strval($this->name);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        if(isset($this->_string)) {
            return $this->_string;
        }
        $function = parent::__toString();
        $this->_string = $this->class->name->toCodeString() . "->" . $function;

        return $this->_string;
    }
}

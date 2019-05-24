<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:05
 */

namespace PhpChain;

use PhpParser\Node\Stmt\ClassLike as ParserClassLike;

abstract class ClassLike
{
    public $knowledge;
    public $name;
    public $namespacedName;
    public $attributes;
    protected $methods;
    protected $node;

    protected function __construct($name, $node, $knowledge, array $attributes = [])
    {
        $this->name = $name;
        $this->node = $node;
        $this->knowledge = $knowledge;
        $this->methods = null;
    }

    public static function create(ParserClassLike $node, $knowledge)
    {
        $type = __NAMESPACE__.'\\'.explode('_', $node->getType())[1].'_';
        $class = $type::create($node, $knowledge);
        return $class;
    }

    public function addMethod($method)
    {
        $this->methods[strval($method->name)] = $method;
    }
}

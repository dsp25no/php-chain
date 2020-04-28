<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:05
 */

namespace PhpChain;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\ClassLike as ParserClassLike;

/**
 * Class ClassLike
 * @package PhpChain
 */
abstract class ClassLike
{
    /**
     * @var ProjectKnowledge
     */
    public ProjectKnowledge $knowledge;
    /**
     * @var Name
     */
    public Name $name;
    /**
     * @var string
     */
    public string $namespacedName;
    /**
     * @var
     */
    public $attributes;
    /**
     * @var ClassMethod[]
     */
    protected array $methods;
    /**
     * @var ParserClassLike
     */
    protected ParserClassLike $node;

    /**
     * ClassLike constructor.
     * @param Name $name
     * @param ParserClassLike $node
     * @param ProjectKnowledge $knowledge
     * @param array $attributes
     */
    protected function __construct(Name $name, ParserClassLike $node, ProjectKnowledge $knowledge, array $attributes = [])
    {
        $this->name = $name;
        $this->node = $node;
        $this->knowledge = $knowledge;
    }

    /**
     * @param ParserClassLike $node
     * @param ProjectKnowledge $knowledge
     * @return mixed
     */
    public static function create(ParserClassLike $node, ProjectKnowledge $knowledge)
    {
        $type = __NAMESPACE__.'\\'.explode('_', $node->getType())[1].'_';
        $class = $type::create($node, $knowledge);
        return $class;
    }

    /**
     * @param ClassMethod $method
     */
    public function addMethod(ClassMethod $method)
    {
        $this->methods[strval($method->name)] = $method;
    }
}

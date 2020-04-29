<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-04
 * Time: 22:10
 */

namespace PhpChain;

use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_ as ParserClass;
use PhpParser\Node\Stmt\ClassLike as ParserClassLike;

/**
 * Class Class_
 * @package PhpChain
 */
// phpcs:ignore
class Class_ extends ClassLike
{
    /**
     * @var FullyQualified|null
     */
    private ?FullyQualified $extends;
    /**
     * @var Interface_[]
     */
    private array $implements;
    /**
     * @var ClassMethod[]
     */
    private array $inherited_methods;

    /**
     * Class_ constructor.
     * @param Name $name
     * @param ParserClass $node
     * @param ProjectKnowledge $knowledge
     * @param FullyQualified|null $extends
     * @param Interface_[] $implements
     * @param array $attributes
     */
    public function __construct(
        Name $name,
        ParserClass $node,
        ProjectKnowledge $knowledge,
        ?FullyQualified $extends,
        array $implements,
        array $attributes = []
    ) {
        parent::__construct($name, $node, $knowledge, $attributes);
        $this->extends = $extends;
        $this->implements = $implements;
    }

    /**
     * @param ParserClassLike $node
     * @param ProjectKnowledge $knowledge
     * @return mixed|Class_
     */
    public static function create(ParserClassLike $node, ProjectKnowledge $knowledge)
    {
        if (! $node instanceof ParserClass) {
            return parent::create($node, $knowledge);
        }
        return new self($node->namespacedName, $node, $knowledge, $node->extends, $node->implements);
    }

    /**
     * @return mixed|null
     */
    public function getExtends()
    {
        if ($this->extends) {
            return $this->knowledge->getClass(strval($this->extends));
        }
        return null;
    }

    /**
     * @return Interface_[]|null
     */
    public function getImplements()
    {
        if ($this->implements) {
            $res = [];
            foreach ($this->implements as $implement) {
                if ($implement) {
                    $res[] = $this->knowledge->getClass(strval($implement));
                }
            }
            return $res;
        }
        return null;
    }

    /**
     * @return ClassMethod[]
     */
    private function getInheritedMethods()
    {
        if (isset($this->inherited_methods)) {
            return $this->inherited_methods;
        }
        $this->inherited_methods = [];
        if ($this->extends) {
            $extends = $this->knowledge->getClass(strval($this->extends));
            if (!$extends) {
                echo "Warning! No info about parent of {$this->name} ({$this->extends})" . PHP_EOL;
                return $this->inherited_methods;
            }
            $methods = $extends->getMethods();
            if (!$methods) {
                return $this->inherited_methods;
            }
            $abstract_parent = $extends->node->isAbstract();
            foreach ($methods as $name => $method) {
                if ($abstract_parent or !$method->isPrivate()) {
                    $this->inherited_methods[$name] = clone $method;
                    $this->inherited_methods[$name]->class = $this;
                }
            }
        }
        return $this->inherited_methods;
    }

    /**
     * @return ClassMethod[]
     */
    public function getMethods()
    {
        if ($this->methods) {
            return array_merge($this->getInheritedMethods(), $this->methods);
        }
        return $this->getInheritedMethods();
    }

    /**
     * @return bool
     */
    public function isAbstract()
    {
        return $this->node->isAbstract();
    }
}

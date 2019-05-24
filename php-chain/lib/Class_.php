<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-04
 * Time: 22:10
 */

namespace PhpChain;

use PhpParser\Node\Stmt\Class_ as ParserClass;

class Class_ extends ClassLike
{
    private $extends;
    private $implements;
    private $_inherited_methods;

    public function __construct($name, $node, $knowledge, $extends, array $implements, array $attributes = [])
    {
        parent::__construct($name, $node, $knowledge, $attributes);
        $this->extends = $extends;
        $this->implements = $implements;
        $this->_inherited_methods = null;
    }

    public static function create(ParserClass $node, $knowledge)
    {
        return new self($node->namespacedName, $node, $knowledge, $node->extends, $node->implements);
    }

    public function getExtends()
    {
        if ($this->extends) {
            return $this->knowledge->getClass(strval($this->extends));
        }
        return null;
    }

    public function getImplements()
    {
        if ($this->implements) {
            $res = [];
            foreach ($this->implements as $implement) {
                if($implement) {
                    $res[] = $this->knowledge->getClass(strval($implement));
                }
            }
            return $res;
        }
        return null;
    }

    private function getInheritedMethods()
    {
        if ($this->_inherited_methods !== null) {
            return $this->_inherited_methods;
        }
        $this->_inherited_methods = [];
        if($this->extends) {
            $extends = $this->knowledge->getClass(strval($this->extends));
            if (!$extends) {
                echo "Warning! No info about parent of {$this->name} ({$this->extends})".PHP_EOL;
                return $this->_inherited_methods;
            }
            $methods = $extends->getMethods();
            $abstract_parent = $extends->node->isAbstract();
            foreach ($methods as $name => $method) {
                if ($abstract_parent or !$method->isPrivate()) {
                    $this->_inherited_methods[$name] = clone $method;
                    $this->_inherited_methods[$name]->class = $this;
                }
            }
        }
        return $this->_inherited_methods;
    }

    public function getMethods()
    {
        if($this->methods) {
            return array_merge($this->getInheritedMethods(), $this->methods);
        }
        return $this->getInheritedMethods();
    }

    public function isAbstract()
    {
        return $this->node->isAbstract();
    }
}

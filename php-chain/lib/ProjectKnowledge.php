<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 19:57
 */

namespace PhpChain;

use PhpChain\ExprCall\FuncCall;

class ProjectKnowledge {
    private $classes;
    private $functions;
    private $methods;
    private $__call_methods;

    public function __construct()
    {
        $this->classes = [];
        $this->functions = [];
        $this->methods = [];
    }

    public function &getClasses()
    {
        return $this->classes;
    }

    public function &getFunctions()
    {
        return $this->functions;
    }

    public function &getMethods()
    {
        return $this->methods;
    }

    public function getClass(string $name) {
        return $this->classes[$name];
    }

    public function getFunction(string $name) {
        return $this->functions[$name];
    }

    public function getMethod(string $name) {
        return $this->methods[$name];
    }

    public function addClass($class) {
        $this->classes[strval($class->name)] = $class;
    }

    public function addFunction($function) {
        $this->functions[$function->getFullName()] = $function;
    }

    public function addMethod($method) {
        $fullName = $method->getFullName();
        $this->methods[$fullName] = $method;
        if($method->name == "__call") {
            $this->__call_methods[] = $method;
        }
    }

    public function getFunctionLikeByCall($call, $strict=true)
    {
        if ($call instanceof FuncCall) {
            $regex = $call->getRegex();
            $searchSet = $this->functions;
            return new \RegexIterator(new \ArrayIterator($searchSet), $regex);
        }
        $classFixed = $call->isClassFixed();
        if ($classFixed) {
            if ($call->isStrict()) {
                $methodName = $call->getMethodName();
                $method = $this->getMethod($methodName);
                if ($method) {
                    return [$method];
                } else {
                    return [];
                }
            } else {
                $class = $this->getClass($call->owner);
                if (!$class) {
                    return [];
                }
                $searchSet = $class->getMethods();
            }
        } else {
            $searchSet = $this->methods;
        }
        $regex = $call->getRegex();
        $iterator = new \AppendIterator();
        $iterator->append(new \RegexIterator(new \ArrayIterator($searchSet), $regex));
        if(!$classFixed && !$strict) {
            $iterator->append(new \ArrayIterator($this->__call_methods));
        }
        return $iterator;
    }
}

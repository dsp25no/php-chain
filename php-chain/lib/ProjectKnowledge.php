<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 19:57
 */

namespace PhpChain;

use PhpChain\ExprCall\FuncCall;

/**
 * Class ProjectKnowledge
 * @package PhpChain
 */
class ProjectKnowledge {
    /**
     * @var ClassLike[]
     */
    private array $classes;
    /**
     * @var Function_[]
     */
    private array $functions;
    /**
     * @var ClassMethod[]
     */
    private array $methods;
    /**
     * @var ClassMethod[]
     */
    private array $__call_methods;

    /**
     * ProjectKnowledge constructor.
     */
    public function __construct()
    {
        $this->classes = [];
        $this->functions = [];
        $this->methods = [];
    }


    /**
     * @return ClassLike[]
     */
    public function &getClasses()
    {
        return $this->classes;
    }

    /**
     * @return Function_[]
     */
    public function &getFunctions()
    {
        return $this->functions;
    }

    /**
     * @return ClassMethod[]
     */
    public function &getMethods()
    {
        return $this->methods;
    }

    /**
     * @param string $name
     * @return ClassLike
     */
    public function getClass(string $name) {
        return $this->classes[$name];
    }

    /**
     * @param string $name
     * @return Function_
     */
    public function getFunction(string $name) {
        return $this->functions[$name];
    }

    /**
     * @param string $name
     * @return ClassMethod
     */
    public function getMethod(string $name) {
        return $this->methods[$name];
    }

    /**
     * @param ClassLike $class
     */
    public function addClass(ClassLike $class) {
        $this->classes[strval($class->name)] = $class;
    }

    /**
     * @param Function_ $function
     */
    public function addFunction(Function_ $function) {
        $this->functions[$function->getFullName()] = $function;
    }

    /**
     * @param ClassMethod $method
     */
    public function addMethod(ClassMethod $method) {
        $fullName = $method->getFullName();
        $this->methods[$fullName] = $method;
        if($method->name == "__call") {
            $this->__call_methods[] = $method;
        }
    }

    /**
     * @param ExprCall $call
     * @param bool $strict
     * @return \Iterator|array
     */
    public function getFunctionLikeByCall(ExprCall $call, $strict=true)
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
        } elseif(!$strict) {
            $iterator->append(new \ArrayIterator(["__call" => $searchSet["__call"]]));
        }
        return $iterator;
    }
}

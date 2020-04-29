<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:00
 */

namespace PhpChain\AstVisitor;

use PhpParser\Node\Stmt\ClassLike as ParserClassLike;
use PhpParser\Node\Stmt\ClassMethod as ParserClassMethod;
use PhpParser\Node\Stmt\Function_ as ParserFunction;
use PhpParser\NodeVisitorAbstract;
use PhpChain\{ProjectKnowledge, ClassLike, Function_, ClassMethod};

class Collector extends NodeVisitorAbstract
{
    private $knowledge;
    private $currentClass;

    public function __construct(ProjectKnowledge $knowledge)
    {
        $this->knowledge = $knowledge;
    }

    public function enterNode(\PhpParser\Node $node)
    {
        if ($node instanceof ParserClassLike) {
            $class = ClassLike::create($node, $this->knowledge);
            $this->knowledge->addClass($class);
            $this->currentClass = $class;
        } elseif ($node instanceof ParserClassMethod) {
            $method = ClassMethod::create($node, $this->currentClass);
            $this->currentClass->addMethod($method);
        } elseif ($node instanceof ParserFunction) {
            $function = Function_::create($node);
            $this->knowledge->addFunction($function);
        }
    }
}

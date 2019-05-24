<?php

require_once '../vendor/autoload.php';

use Roave\BetterReflection\Reflection\{ReflectionMethod, ReflectionFunction};
use PhpParser\{Node, NodeFinder};

function extract_calls($func_reflection) {
    if (!($func_reflection instanceof ReflectionMethod) and
        !($func_reflection instanceof ReflectionFunction)) {
        throw new Exception("Recieved not reflection");
    }
    $ast = $func_reflection->getBodyAst();

    $nodeFinder = new NodeFinder;
    $calls = $nodeFinder->find($ast, function(Node $node) {
        return $node instanceof Node\Expr\FuncCall or
               $node instanceof Node\Expr\MethodCall;
    });
    $res = [];
    foreach ($calls as $call) {
        if ($call instanceof Node\Expr\FuncCall){
            $owner = null;
        } else if ($func_reflection instanceof ReflectionMethod and
                   $call->var->name == "this") {
            $owner = $func_reflection->getImplementingClass()->getName();
        } else {
            $owner = "*";
        }
        $res[] = [
                  "owner" => $owner,
                  "name" => $call->name->toString(),
                  "params" => sizeof($call->args)
        ];
    }
    return $res;
}


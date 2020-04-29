<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-05-09
 * Time: 17:40
 */

namespace PhpChain\AstVisitor;

use PhpParser\{Node, NodeVisitorAbstract};
use PhpParser\Node\Expr\MethodCall as ParserMethodCall;
use PhpParser\Node\Expr\ArrayDimFetch as ParserArrayDimFetch;
use PhpParser\Node\Arg as ParserArg;

class ArrayAccessResolver extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof ParserArrayDimFetch) {
            $attributes = $node->getAttributes();
            if ($node->dim) {
                $param = new ParserArg($node->dim);
                $param->setAttributes($attributes);
                $params = [$param];
            } else {
                $params = [];
            }
            return new ParserMethodCall(
                $node->var,
                "offsetGet",
                $params,
                $attributes
            );
        }
    }
}

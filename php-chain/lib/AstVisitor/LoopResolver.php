<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-05-06
 * Time: 10:12
 */

namespace PhpChain\AstVisitor;

use PhpParser\Node\Stmt\Foreach_ as ParserForeach;
use PhpParser\Node\Stmt\If_ as ParserIf;
use PhpParser\Node\Expr\Instanceof_ as ParserInstanceof;
use PhpParser\Node\Expr\Assign as ParserAssign;
use PhpParser\Node\Expr\MethodCall as ParserMethodCall;
use PhpParser\{Node, NodeVisitorAbstract};

class LoopResolver extends NodeVisitorAbstract
{
    public function leaveNode(Node $node)
    {
        if ($node instanceof ParserForeach) {
            /*
             if ($it instanceof IteratorAggregate) {
                $it = $it->getIterator();
             }
             for ($it->rewind(); $it->valid(); $it->next()) {
                $v = $it->current();
                $k = $it->key();
                ...
             }
             */
            $it = $node->expr;
            $v = $node->valueVar;
            $k = $node->keyVar;
            $attributes = $node->getAttributes();
            $result = [];
            $result[] = new ParserIf(
                new ParserInstanceof(
                    $it,
                    new Node\Name("\IteratorAgreggate", $attributes),
                    $attributes
                ),
                ["stmts" => [
                    new ParserAssign(
                        $it,
                        new ParserMethodCall($it, "getIterator", [], $attributes),
                        $attributes
                    )
                ]],
                $attributes
            );
            $result[] = new ParserMethodCall($it, "rewind", [], $attributes);
            $if = new ParserIf(
                new ParserMethodCall($it, "valid", [], $attributes),
                ["stmts" => [
                        new ParserAssign(
                            $v,
                            new ParserMethodCall(
                                $it,
                                "current",
                                [],
                                $attributes),
                            $attributes
                        ),
                    ]
                ]
            );
            if($k) {
                $if->stmts[] = new ParserAssign(
                    $k,
                    new ParserMethodCall($it, "key", [], $attributes),
                    $attributes
                );
            }
            $if->stmts[] = new ParserMethodCall($it, "next", [], $attributes);
            $result[] = $if;
            return $result;
        }
    }
}

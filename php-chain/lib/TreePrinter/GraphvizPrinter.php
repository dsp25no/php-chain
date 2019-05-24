<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-28
 * Time: 13:08
 */

namespace PhpChain\TreePrinter;

use PhpChain\TreePrinter;

class GraphvizPrinter implements TreePrinter
{
    static public function print($tree)
    {
        $graph = "digraph G {".PHP_EOL;
        foreach ($tree->walk() as list($call, $chain_node)) {
            if($chain_node->getParent()->getParent()) {
                $graph .= '"'.$chain_node->getParent()->value().'" -> "' . $chain_node->value() . '";' . PHP_EOL;
            }
        }
        $graph .= "}".PHP_EOL;
        return $graph;
    }
}

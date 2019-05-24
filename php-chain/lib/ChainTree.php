<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-27
 * Time: 19:33
 */

namespace PhpChain;


class ChainTree implements \JsonSerializable
{
    private $children;
    private $reverseMapping;
    private $parent;
    private $function;
    private $metric;
    private $dfg;
    private $depth;

    public function __construct($function)
    {
        $this->function = $function;
        $this->children = new \SplObjectStorage();
        $this->reverseMapping = new \SplObjectStorage();
        $this->depth = 0;
    }

    public function setMetric($value) {
        $this->metric = $value;
    }

    public function hasMetric() {
        return $this->metric != 0;
    }

    public function  getMetric() {
        return $this->metric;
    }


    public function getChildren()
    {
        return $this->children;
    }

    public function getChildByCall($call)
    {
        return $this->children[$call];
    }

    public function getChildByFuncName($func_name)
    {
        foreach ($this->children as $call) {
            $node = $this->children[$call];
            if($node->function->getFullName() == $func_name) {
                return $node;
            }
        }
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function value()
    {
        return $this->function;
    }

    public function getRoot()
    {
        $item = $this;
        while($item->parent) {
            $item = $item->parent;
        }
        return $item;
    }

    public function walk()
    {
        foreach ($this->children as $call) {
            yield array($call, $this->children[$call]);
            yield from $this->children[$call]->walk();
        }
    }

    public function reverseWalk()
    {
        foreach ($this->children as $call) {
            yield from $this->children[$call]->reverseWalk();
            yield array($call, $this->children[$call]);
        }
    }

    public function addChildren($function, $call)
    {
        $node = new ChainTree($function);
        $this->children[$call] = $node;
        $this->reverseMapping[$node] = $call;
        $node->parent = $this;
        $node->depth = $this->depth + 1;
        return $node;
    }

    public function addChain($chain)
    {
        $root = $this->getRoot();
        $reverse_chain = $chain->last();
        $system_function = $reverse_chain->value();
        $root = $root->getChildByFuncName($system_function->getFullName());
        while ($reverse_chain = $reverse_chain->prev()) {
            $call = $reverse_chain->getCall();
            if(isset($root->children[$call])) {
                $root = $root->children[$call];
            } else {
                $function = $reverse_chain->value();
                $root = $root->addChildren($function, $call);
            }
        }
    }

    private function findNode($function, $maxDepth)
    {
        if($maxDepth > $this->depth) {
            foreach ($this->children as $call) {
                $node = $this->children[$call];
                if ($node->function === $function) {
                    yield $node;
                } else {
                    yield from $node->findNode($function, $maxDepth);
                }
            }
        }
    }

    public function getChainsStartFrom($function, $maxLen)
    {
        foreach ($this->findNode($function, $maxLen) as $node) {
            $chain = new Chain($node->function);
            $call = $node->parent->reverseMapping[$node];
            $node = $node->parent;
            while ($node->parent) {
                $chain->append(new Chain($node->function), $call);
                $call = $node->parent->reverseMapping[$node];
                $node = $node->parent;
            }
            yield $chain;
        }
    }

    public function setDfg($dfg)
    {
        if($this->dfg) {
            throw new \Exception("DFG exists!");
        }
        $this->dfg = $dfg;
    }

    public function getDfg()
    {
        return $this->dfg;
    }

    public function jsonSerialize()
    {
        $json = [
            "function" => $this->function->getFullName()
        ];
        if($this->metric) {
            $json["metric"] = $this->metric;
        }
        $json["children"] = [];

        foreach ($this->children as $call) {
            $node = $this->children[$call];
            $json["children"][] = $node->jsonSerialize();
        }
        if($this->metric) {
            usort($json["children"],
                function ($a, $b) {
                    return strval($a["metric"]) < strval($b["metric"]);
                }
            );
        }
        return $json;
    }
}

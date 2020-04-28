<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-27
 * Time: 19:33
 */

namespace PhpChain;


/**
 * Class ChainTree
 * @package PhpChain
 */
class ChainTree implements \JsonSerializable
{
    /**
     * @var \SplObjectStorage<ExprCall, ChainTree>
     */
    private \SplObjectStorage $children;
    /**
     * @var \SplObjectStorage<ChainTree, ExprCall>
     */
    private $reverseMapping;
    /**
     * @var ChainTree
     */
    private ChainTree $parent;
    /**
     * @var FunctionLike
     */
    private FunctionLike $function;
    // TODO add type
    private $metric;
    /**
     * @var Dfg
     */
    private Dfg $dfg;
    /**
     * @var int
     */
    private int $depth;

    /**
     * ChainTree constructor.
     * @param FunctionLike $function
     */
    public function __construct(FunctionLike $function)
    {
        $this->function = $function;
        $this->children = new \SplObjectStorage();
        $this->reverseMapping = new \SplObjectStorage();
        $this->depth = 0;
    }

    /**
     * @param $value
     */
    public function setMetric($value) {
        $this->metric = $value;
    }

    /**
     * @return bool
     */
    public function hasMetric() {
        return $this->metric != 0;
    }

    /**
     * @return float
     */
    public function  getMetric() {
        return $this->metric;
    }


    /**
     * @return \SplObjectStorage
     */
    public function getChildren()
    {
        return $this->children;
    }

    /**
     * @param ExprCall $call
     * @return object
     */
    public function getChildByCall(ExprCall $call)
    {
        return $this->children[$call];
    }

    /**
     * @param string $func_name
     * @return ChainTree
     */
    public function getChildByFuncName(string $func_name)
    {
        foreach ($this->children as $call) {
            $node = $this->children[$call];
            if($node->function->getFullName() == $func_name) {
                return $node;
            }
        }
    }

    /**
     * @return ChainTree
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @return FunctionLike
     */
    public function value()
    {
        return $this->function;
    }

    /**
     * @return ChainTree
     */
    public function getRoot()
    {
        $item = $this;
        while(isset($item->parent)) {
            $item = $item->parent;
        }
        return $item;
    }

    /**
     * @return \Generator
     */
    public function walk()
    {
        foreach ($this->children as $call) {
            yield array($call, $this->children[$call]);
            yield from $this->children[$call]->walk();
        }
    }

    /**
     * @return \Generator
     */
    public function reverseWalk()
    {
        foreach ($this->children as $call) {
            yield from $this->children[$call]->reverseWalk();
            yield array($call, $this->children[$call]);
        }
    }

    /**
     * @param FunctionLike $function
     * @param ExprCall|\stdClass $call
     * @return ChainTree
     */
    public function addChildren(FunctionLike $function, $call)
    {
        $node = new ChainTree($function);
        $this->children[$call] = $node;
        $this->reverseMapping[$node] = $call;
        $node->parent = $this;
        $node->depth = $this->depth + 1;
        return $node;
    }

    /**
     * @param Chain $chain
     */
    public function addChain(Chain $chain)
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

    /**
     * @param FunctionLike $function
     * @param int $maxDepth
     * @return \Generator
     */
    private function findNode(FunctionLike $function, int $maxDepth)
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

    /**
     * @param FunctionLike $function
     * @param int $maxLen
     * @return \Generator
     */
    public function getChainsStartFrom(FunctionLike $function, int $maxLen)
    {
        foreach ($this->findNode($function, $maxLen) as $node) {
            $chain = new Chain($node->function);
            $call = $node->parent->reverseMapping[$node];
            $node = $node->parent;
            while (isset($node->parent)) {
                $chain->append(new Chain($node->function), $call);
                $call = $node->parent->reverseMapping[$node];
                $node = $node->parent;
            }
            yield $chain;
        }
    }

    /**
     * @param Dfg $dfg
     * @throws \Exception
     */
    public function setDfg(Dfg $dfg)
    {
        if(isset($this->dfg)) {
            throw new \Exception("DFG exists!");
        }
        $this->dfg = $dfg;
    }

    /**
     * @return Dfg
     */
    public function getDfg()
    {
        return $this->dfg;
    }

    /**
     * @return array|mixed
     */
    public function jsonSerialize()
    {
        $json = [
            "function" => $this->function->getFullName()
        ];
        if(isset($this->metric)) {
            $json["metric"] = $this->metric;
        }
        $json["children"] = [];

        foreach ($this->children as $call) {
            $node = $this->children[$call];
            $json["children"][] = $node->jsonSerialize();
        }
        if(isset($this->metric)) {
            usort($json["children"],
                function ($a, $b) {
                    return strval($a["metric"]) < strval($b["metric"]);
                }
            );
        }
        return $json;
    }
}

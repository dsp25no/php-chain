<?php

namespace PhpChain;

use PhpParser\BuilderFactory as AstBuilder;
use PhpChain\ExprCall\FuncCall;

/**
 * Class ChainBuilder
 * @api FindAllChains
 */
class ChainBuilder{
    private $system = [];
    private $knowledge;
    private $chainTree;
    private $checked;
    private $depth;
    private $magic;

    private $not_call;

    //@todo make it optimal returning chain tails
    private $system_inside;

    /**
     * BuildChain constructor.
     * @param $system : list of system calls we're interested in
     */
    public function __construct($knowledge, $config)
    {
        $this->system = $config["system"];
        $this->magic = $config["magic"];
        if(!$config["features"]["__call"]) {
            $this->not_call = true;
        }
        $this->knowledge = $knowledge;
        $this->system_inside = new \SplObjectStorage();
        $this->checked = new \SplObjectStorage();
        $this->chainTree = new ChainTree(new Function_("", null, null));

        $factory = new AstBuilder();
        foreach($this->system as $function_name) {
            $function = $factory->function($function_name);
            $params = [];
            for ($i = 0; $i < $config["functions"][$function_name]["params"]; $i++) {
                $params[] = $factory->param("p$i");
            }
            $function->addParams($params);
            $this->chainTree->addChildren(Function_::create($function->getNode()), new \StdClass);
        }
    }

    private function getSuffixChain($function, $maxLen)
    {
        foreach ($this->chainTree->getChainsStartFrom($function, $maxLen) as $chain) {
            yield $chain;
        }
    }

    /**
     * @param $function_like
     * @param $chain
     * @return \Generator : new chain
     * @throws \Exception
     */
    private function findCritical($chain, $depth)
    {
        if ($depth <= $this->depth) {
            $calls = $chain->value()->extractCalls();
            foreach ($calls as $call) {
                if ($call->countUse >= 1) {
                    continue;
                }
                $call->countUse++;
                if ($this->isCallCritical($call)) {
                    //TODO(dsp25no): not optimal,
                    // add to tree any critical function node
                    if($call->name instanceof \PhpParser\Node\Expr\Variable) {
                        $critical_names = $this->system;
                    } else {
                        $critical_names = [$call->name];
                    }
                    foreach ($critical_names as $name) {
                        $this->system_inside->attach($chain->value());
                        $system = $this->chainTree->getChildByFuncName($name);
                        $chain->append(new Chain($system->value()), $call);
                        $this->chainTree->addChain($chain);
                        $result_chain = $chain->copyChain();
                        $chain->delLastNode();
                        yield $result_chain;
                    }
                } else {
                    $matched_functions =  $this->knowledge->getFunctionLikeByCall($call, $this->not_call);
                    foreach ($matched_functions as $matched) {
//                    if($chain->value() === $matched) {
//                        // Don't support recursive chains now
//                        continue;
//                    }
                        if ($this->system_inside->contains($matched)) {
                            $this->system_inside->attach($chain->value());
                            foreach ($this->getSuffixChain(
                                        $matched,
                                $this->depth - $depth) as $suffix
                            ) {
                                $chain->append($suffix, $call);
                                $this->chainTree->addChain($chain);
                                $result_chain = $chain->copyChain();
                                $chain->delTail();
                                yield $result_chain;
                            }
                        } elseif (!isset($this->checked[$matched])
                                    or $this->checked[$matched] > $depth
                        ) {
                            if (yield from $this->findCritical($chain->append(
                                                                    new Chain($matched),
                                                                    $call
                                                                ),
                                                        $depth + 1)
                            ) {
                                $this->system_inside->attach($chain->value());
                            } else {
                                $this->checked[$matched] = $depth;
                            }
                            $chain->delLastNode();
                        }
                    }
                }
                $call->countUse--;
            }
        }
    }

    public function findAllChains($depth=10)
    {
        $this->depth = $depth;
        $methods = $this->knowledge->getMethods();
        $count = sizeof($methods);
        $i = 0;
        foreach ($methods as $method) {
            echo $i++."/".$count."\r";
            if(in_array(strval($method->name), $this->magic)) {
                foreach ($this->findCritical(new Chain($method), 1) as $new_chain) {
                }
            }
        }
        return $this->chainTree;
    }

    /**
     * Checks if call in system calls list
     * @param $call
     * @return bool
     */
    private function isCallCritical($call)
    {
        return $call instanceof FuncCall and (
                in_array($call->name, $this->system) or
            $call->name instanceof \PhpParser\Node\Expr\Variable);
    }
}

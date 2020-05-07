<?php
/**
 */

namespace PhpChain;

use PhpParser\ParserFactory;
use PhpParser\BuilderFactory as AstBuilder;
use PHPCfg;

class TreeAnalyzer
{
    private $chainTree;

    public function __construct($chainTree)
    {
        $this->chainTree = $chainTree;
        $criticalNodes = $this->chainTree->getChildren();
        foreach ($criticalNodes as $key) {
            foreach ($criticalNodes[$key]->getChildren() as $call) {
                $call->setTargetArgs(
                    range(0, sizeof($criticalNodes[$key]->value()->params) - 1)
                );
            }
        }
    }

    private function intraProceduralAnalyze()
    {
        $parser = new PHPCfg\Parser(
            (new ParserFactory)->create(ParserFactory::PREFER_PHP7)
        );
        $factory = new AstBuilder();
        $target_function = null;

        foreach ($this->chainTree->walk() as list($call, $chain_node)) {
            if ($call instanceof \StdClass) {
                continue;
            }
            $parent = $chain_node->getParent()->value();
            if (!$parent->extractCalls()) {
                $target_function = $parent;
            }

            $function = $chain_node->value();
            $ast = $function->node;
            if ($function instanceof ClassMethod) {
                $new_ast = $factory->class($function->class->name);
                $new_ast->addStmt($ast);
                $ast = $new_ast->getNode();
            }

            $script = $parser->parseAst(array($ast), "stub_name");
            $dfg = new Dfg($script, $call);
            $dfg->setLastCall($target_function);
            $func_params = [];
            if ($chain_node->getParent()->getDfg()) {
                $func_params = $chain_node->getParent()->getDfg()->getFuncParams();
            }
            $dfg->buildSlice($func_params);

            $chain_node->setDfg($dfg);
            $important_params = $dfg->getImportantParamNums();
            foreach ($chain_node->getChildren() as $call) {
                $call->setTargetArgs($important_params);
            }
        }
    }

    public function interProceduralAnalyze()
    {
        foreach ($this->chainTree->reverseWalk() as list($chain_call, $chain_node)) {
            $children = $chain_node->getChildren();
            if ($children->count() === 0) {
                continue;
            }

            $best_child = null;
            $best_metric = -1;
            foreach ($children as $call) {
                $child = $children[$call];
                $metric = $child->getDfg()->analyze();
                if ($child->getMetric()) {
                    $metric *= $child->getMetric();
                }
                $child->setMetric($metric);
                if ($metric > $best_metric) {
                    $best_metric = $metric;
                    $best_child = $child;
                }
            }
            $chain_node->setMetric($best_metric);
            $params = $best_child->getDfg()->getOutputParams();
            if(!$chain_call instanceof \StdClass) {
                $dfg = $chain_node->getDfg();
                $dfg->matchOutputInput($params);
            }
        }
    }

    public function analyze()
    {
        echo "IntraProcedural Analyze".PHP_EOL;
        $this->intraProceduralAnalyze();
        echo "InterProcedural Analyze".PHP_EOL;
        $this->interProceduralAnalyze();
    }
}

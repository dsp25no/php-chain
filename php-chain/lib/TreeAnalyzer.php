<?php
/**
 */

namespace PhpChain;

use PhpParser\ParserFactory;
use PhpParser\BuilderFactory as AstBuilder;
use PHPCfg;
use function PHPSTORM_META\type;

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

        foreach ($this->chainTree->walk() as list($call, $chain_node)) {
            if ($call instanceof \StdClass) {
                continue;
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

            $metric = $dfg->analyze();

            $chain_node->setMetric($metric);
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
            $best_metric = [-1, -1];
            foreach ($children as $call) {
                $child = $children[$call];
                $metric = $child->getMetric();
                if ($metric[0] > $best_metric[0] or
                    $metric[0] === $best_metric[0] and
                    $metric[1] > $best_metric[1]) {
                    $best_metric = $metric;
                    $best_child = $child;
                }
            }

            $params = $best_child->getDfg()->getOutputParams();
            if(!$chain_call instanceof \StdClass) {
                $dfg = $chain_node->getDfg();
                $dfg->matchOutputInput($params);
                $metric = $dfg->updateMetric();
                $chain_node->setMetric($metric);
            } else {
                $chain_node->setMetric($best_child->getMetric());
            }
        }
    }

    public function analyze()
    {
        $this->intraProceduralAnalyze();
        $this->interProceduralAnalyze();
    }
}

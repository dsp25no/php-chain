<?php
/**
 */

namespace PhpChain;

use PHPCfg;

class Dfg {
    private $script;
    private $call;
    private $output_params = [];
    private $input_params = [];

    private $targetVars;
    private $targetOps;

    public function __construct($script, $call)
    {
        $this->script = $script;
        $this->call = $call;
        $this->targetVars = new \SplObjectStorage();
        $this->targetOps = new \SplObjectStorage();
    }

    public function getTargetVar($var, $strict=False)
    {
        if(!isset($this->targetVars[$var]) and !$strict) {
            $this->targetVars[$var] = new TargetVar($var, $this);
        }
        if(isset($this->targetVars[$var])) {
            return $this->targetVars[$var];
        }
    }

    /**
     * @return \SplObjectStorage
     */
    public function getTargetVars()
    {
        return $this->targetVars;
    }

    public function getTargetOp($op, $strict=False)
    {
        if(!isset($this->targetOps[$op]) and !$strict) {
            $this->targetOps[$op] = new TargetOp($op, $this);
        }
        if(isset($this->targetOps[$op])) {
            return $this->targetOps[$op];
        }
    }

    public function getScript()
    {
        return $this->script;
    }

    public function getCall()
    {
        return $this->call;
    }

    private function buildSlice()
    {
        $traverser = new BackwardSliceWalker();
        $slicer = new BackwardSlice($this);
        $traverser->addVisitor($slicer);
        $traverser->traverse($this->script);
    }

    private function resolveConstants()
    {
        $traverser = new PHPCfg\Traverser();
        $resolver = new ConstantPropagation($this);
        $traverser->addVisitor($resolver);
        $traverser->traverse($this->script);
    }

    public function countMetric()
    {
        $metric = 1.0;
        foreach ($this->output_params as $var) {
            $number = $var->getMetric();
            $metric *= $number;
        }
        return $metric;
    }

    public function analyze()
    {
        $this->buildSlice();
        $this->resolveConstants();
        $metric = $this->countMetric();
        return $metric;
    }

    public function updateMetric()
    {
        $metric = $this->countMetric();
        return $metric;
    }

    public function getImportantParamNums()
    {
        $params = $this->script->functions[0]->params;
        $nums = [];
        for($i = 0; $i < sizeof($params); $i++) {
            if (isset($this->targetOps[$params[$i]])) {
                $nums[] = $i;
                $this->input_params[$i] = $this->targetOps[$params[$i]];
            }
        }
        return $nums;
    }

    public function getOutputParams()
    {
        return $this->output_params;
    }

    public function setOutputParam(TargetVar $param, $num)
    {
        $this->output_params[$num] = $param;
    }

    public function matchOutputInput($prev_output)
    {
        foreach ($prev_output as $index=>$var) {
            $this->input_params[$index]->updateCategory($var->getCategory());
        }
    }
}

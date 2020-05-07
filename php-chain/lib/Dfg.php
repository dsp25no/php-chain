<?php
/**
 */

namespace PhpChain;
use PHPCfg;
use PhpChain\DfgAnalyzer\{RulesMatrix, PenaltyMatrix};


class Dfg {
    public const CONDITION = -1;

    private $script;
    private $call;
    private $output_params = [];
    private $input_params = [];
    private $func_params = [];

    private $targetVars;
    private $targetOps;

    private $metric;
    private $last_call;

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

    public function addUsedIn($target, $usage)
    {
        if (! $target instanceof TargetVar and ! $target instanceof TargetOp) {
            throw new \Exception("Usage can be added only to Target Var or Op");
        }
        if ( is_array($usage) ) {
            if (empty($usage)) {
                throw new \Exception("Usage should not be empty");
            }
            foreach ($usage as $key => $item) {
                $this->addUsedIn($target, $key);
            }
            return;
        }
        if ( !$usage == Dfg::CONDITION and ($usage < 0 or $usage >= count($this->getCall()->getTargetArgs()))) {
            throw new \Exception("Incorrect usage of TargetVar");
        }
        if (isset($target->penalties[$usage])) {
            return;
        }
        $target->penalties[$usage] = PenaltyMatrix::UNSET_PENALTY;
    }

    public function buildSlice(array $func_params)
    {
        $traverser = new BackwardSliceWalker();
        $slicer = new BackwardSlice($this, $func_params);
        $traverser->addVisitor($slicer);
        $traverser->traverse($this->script);
    }

    public function analyze(array $func_params=[])
    {
        try {
            $traverser = new PHPCfg\Traverser();
            $resolver = new ConstantPropagation($this);
            $rules_matrix = new RulesMatrix();
            $rules = $rules_matrix->setRules($this->last_call, $this);
            $chain_analyzer = new DfgAnalyzer($this, $rules);
            $traverser->addVisitor($resolver);
            $traverser->addVisitor($chain_analyzer);
            $traverser->traverse($this->script);
            $this->metric = $chain_analyzer->getDfgScore();
        } catch (\Exception $exception) {
            echo "Error in Dfg metric count".PHP_EOL;
            $this->metric = PenaltyMatrix::UNDEFINED_PENALTY;
        }
        return $this->metric;
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
            $this->input_params[$index]->penalites = $var->penalties;
        }
    }

    /**
     * @return mixed
     */
    public function getLastCall()
    {
        return $this->last_call;
    }

    /**
     * @param mixed $last_call
     */
    public function setLastCall(Function_ $last_call): void
    {
        $this->last_call = $last_call;
    }

    public function setFuncParam(TargetOp $target_param, int $parameter_number): void
    {
        $this->func_params [$parameter_number]= $target_param;
    }

    /**
     * @return array
     */
    public function getFuncParams(): array
    {
        return $this->func_params;
    }
}

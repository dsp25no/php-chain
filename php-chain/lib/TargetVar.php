<?php
/**
 */

namespace PhpChain;


class TargetVar
{
    public $var;
    public $value;
    public $penalties = [];
    private $dfg;
    private $processing;
    private $out = false;

    public function __construct($operand, $dfg)
    {
        $this->var = $operand;
        $this->dfg = $dfg;
        $this->processing = False;
    }

    public function setOut()
    {
        $this->out = true;
    }

    public function getOut()
    {
        return $this->out;
    }
}

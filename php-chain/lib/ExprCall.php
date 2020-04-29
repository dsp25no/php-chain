<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-05-02
 * Time: 20:45
 */

namespace PhpChain;

abstract class ExprCall
{
    public string $name;
    public $argsCount;
    public $node;
    public $countUse;
    protected array $targetArgs;

    public function setTargetArgs($targetArgs)
    {
        $this->targetArgs = $targetArgs;
    }

    public function getTargetArgs()
    {
        return $this->targetArgs;
    }
}

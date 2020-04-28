<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-04-03
 * Time: 19:28
 */

namespace PhpChain\ExprCall;

use PhpParser\Node\Expr\FuncCall as ParserFuncCall;
use PhpChain\ExprCall;

/**
 * Class FuncCall
 * @package PhpChain\ExprCall
 */
class FuncCall extends ExprCall
{
    /**
     * FuncCall constructor.
     * @param ParserFuncCall $node
     */
    public function __construct(ParserFuncCall $node)
    {
        $this->name = $node->name;
        $this->argsCount = sizeof($node->args);
        $this->node = $node;
        $this->countUse = 0;
    }

    /**
     * @return string
     */
    public function getRegex() {
        $regex = "/^";
        $regex .= $this->name . "\(";
        $regex .= "r{0," . $this->argsCount . "}[^r]*\)";
        $regex .= "$/";
        return $regex;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-04
 * Time: 00:59
 */

namespace PhpChain;

use PhpParser\Node\Stmt\Function_ as ParserFunction;

class Function_ extends FunctionLike
{
    public static function create(ParserFunction $node)
    {
        return new self($node->name, $node, $node->params);
    }
}

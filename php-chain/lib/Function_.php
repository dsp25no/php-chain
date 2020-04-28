<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-04
 * Time: 00:59
 */

namespace PhpChain;

use PhpParser\Node\Stmt\Function_ as ParserFunction;

/**
 * Class Function_
 * @package PhpChain
 */
class Function_ extends FunctionLike
{
    /**
     * @param ParserFunction $node
     * @return Function_
     */
    public static function create(ParserFunction $node)
    {
        return new self($node->name, $node, $node->params);
    }
}

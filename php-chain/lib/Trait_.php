<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:07
 */

namespace PhpChain;

use PhpParser\Node\Stmt\Trait_ as ParserTrait;

class Trait_ extends ClassLike
{
    public static function create(ParserTrait $node, $knowledge) {
        return new self($node->namespacedName, $node, $knowledge);
    }
}

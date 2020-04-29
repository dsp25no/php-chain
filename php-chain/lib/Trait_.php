<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:07
 */

namespace PhpChain;

use PhpParser\Node\Stmt\ClassLike as ParserClassLike;
use PhpParser\Node\Stmt\Trait_ as ParserTrait;

/**
 * Class Trait_
 * @package PhpChain
 */
// phpcs:ignore
class Trait_ extends ClassLike
{
    /**
     * @param ParserClassLike $node
     * @param ProjectKnowledge $knowledge
     * @return Trait_
     */
    public static function create(ParserClassLike $node, ProjectKnowledge $knowledge)
    {
        if (! $node instanceof ParserTrait) {
            return parent::create($node, $knowledge);
        }
        return new self($node->namespacedName, $node, $knowledge);
    }
}

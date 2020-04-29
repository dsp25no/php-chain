<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:06
 */

namespace PhpChain;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Interface_ as ParserInterface;

/**
 * Class Interface_
 * @package PhpChain
 */
// phpcs:ignore
class Interface_ extends ClassLike
{
    /**
     * @var array
     */
    private array $extends;

    /**
     * Interface_ constructor.
     * @param Name $name
     * @param ParserInterface $node
     * @param ProjectKnowledge $knowledge
     * @param Interface_[] $extends
     * @param array $attributes
     */
    public function __construct(
        Name $name,
        ParserInterface $node,
        ProjectKnowledge $knowledge,
        array $extends,
        array $attributes = []
    ) {
        parent::__construct($name, $node, $knowledge, $attributes);
        $this->extends = $extends;
    }

    /**
     * @param ParserInterface $node
     * @param ProjectKnowledge $knowledge
     * @return Interface_
     */
    public static function create(ParserInterface $node, $knowledge)
    {
        return new self($node->namespacedName, $node, $knowledge, $node->extends);
    }

    /**
     * @return Interface_[]
     */
    public function getExtends()
    {
        if ($this->extends) {
            $res = [];
            foreach ($this->extends as $extend) {
                if ($extend) {
                    $res[] = $this->knowledge->getClass([strval($extend)]);
                }
            }
            return $res;
        }
        return null;
    }

    /**
     * @return array
     */
    public function getMethods()
    {
        // This case was noticed only in code for compatibility with old requirements
        echo "Get methods from interface ({$this->name})";
        return [];
    }
}

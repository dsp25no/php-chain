<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:06
 */

namespace PhpChain;

use PhpParser\Node\Stmt\Interface_ as ParserInterface;

class Interface_ extends ClassLike
{
    private $extends;

    public function __construct($name, $node, $knowledge, $extends, array $attributes = [])
    {
        parent::__construct($name, $node, $knowledge, $attributes);
        $this->extends = $extends;
    }

    public static function create(ParserInterface $node, $knowledge)
    {
        return new self($node->namespacedName, $node, $knowledge, $node->extends);
    }

    public function getExtends()
    {
        if ($this->extends) {
            $res = [];
            foreach ($this->extends as $extend) {
                if($extend) {
                    $res[] = $this->knowledge->getClass([strval($extend)]);
                }
            }
            return $res;
        }
        return null;
    }

    public function getMethods()
    {
        // This case was noticed only in code for compatibility with old requirements
        echo "Get methods from interface ({$this->name})";
        return [];
    }
}

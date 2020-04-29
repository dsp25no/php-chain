<?php

/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:12
 */

namespace PhpChain;

/**
 * Class ChainIterator
 * @package PhpChain
 */
class ChainIterator implements \Iterator
{
    /**
     * @var int
     */
    private int $index;
    /**
     * @var Chain
     */
    private Chain $node;

    /**
     * ChainIterator constructor.
     * @param Chain $root
     */
    public function __construct(Chain $root)
    {
        $this->index = 0;
        $this->node = $root;
    }

    /**
     *
     */
    public function next()
    {
        $this->node = $this->node->next();
        $this->index++;
    }

    /**
     *
     */
    public function prev()
    {
        $this->node = $this->node->prev();
        $this->index--;
    }

    /**
     *
     */
    public function rewind()
    {
        while ($this->node->prev()) {
            $this->prev();
        }
        $this->index = 0;
    }

    /**
     * @return mixed|Chain|null
     */
    public function current()
    {
        if ($this->valid()) {
            return $this->node;
        }
        return null;
    }

    /**
     * @return int
     */
    public function key()
    {
        return $this->index;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->node instanceof Chain;
    }
}

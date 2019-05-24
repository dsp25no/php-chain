<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:12
 */

namespace PhpChain;


class ChainIterator implements \Iterator
{
    private $index;
    private $node;

    public function __construct($root)
    {
        $this->index = 0;
        $this->node = $root;
    }

    public function next()
    {
        $this->node = $this->node->next();
        $this->index++;
    }

    public function prev()
    {
        $this->node = $this->node->prev();
        $this->index--;
    }

    public function rewind()
    {
        while ($this->node->prev()){
            $this->prev();
        }
        $this->index = 0;
    }

    public function current()
    {
        if($this->valid()){
            return $this->node;
        }
        return null;
    }

    public function key()
    {
        return $this->index;
    }

    public function valid()
    {
        return $this->node instanceof Chain;
    }
}

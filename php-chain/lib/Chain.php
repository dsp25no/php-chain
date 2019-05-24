<?php

namespace PhpChain;

class Chain
{
    private $prev;
    private $function;
    private $next;
    private $call;

    function __construct($function, $call=null)
    {
        $this->function = $function;
        $this->call = $call;
        $this->next = NULL;
        $this->prev = NULL;
    }

    public function value()
    {
        return $this->function;
    }

    public function getCall()
    {
        return $this->call;
    }

    public function next()
    {
        return $this->next;
    }

    public function prev()
    {
        return $this->prev;
    }

    public function append($new_node, $call)
    {
        if (!$new_node instanceof Chain) {
            throw new \Exception('invalid new node'.PHP_EOL);
        }
        $item = $this->last();
        $item->call = $call;
        $item->next = & $new_node;
        $new_node->prev = & $item;
        return $item->next;
    }

    public function rewind()
    {
        $item = $this;
        while($item->prev){
            $item = $item->prev;
        }
        return $item;
    }

    public function last()
    {
        $item = $this;
        while($item->next){
            $item = $item->next;
        }
        return $item;
    }

    public function length() {
        $length = 1;
        $item = $this->rewind();
        while($item->next){
            $item = $item->next;
            $length++;
        }
        return $length;
    }

    public function copyTail()
    {
        $item = $this;
        $root = new Chain($item->function, $item->call);
        $result = $root;
        while ($item = $item->next){
            $new_node = new Chain($item->function, $item->call);
            $result->next = $new_node;
            $new_node->prev = $result;
            $result = $new_node;
        }
        return $root;
    }

    public function copyChain()
    {
        $root = $this->rewind();
        return $root->copyTail();
    }

    public function delLastNode()
    {
        $last = $this->last();
        if ($prev = $last->prev) {
            $prev->call = null;
            $prev->next = null;
            $last->prev = null;
        }
        return $prev;
    }

    public function delTail()
    {
        if($this->next) {
            $this->next->prev = null;
            $this->next = null;
            $this->call = null;
        }
    }

    public function isEqualTail($node)
    {
        return $this->function === $node->function and
               $this->call === $node->call and
               $this->next->isEqualTail($node->next);
    }

    public function __toString()
    {
        return strval($this->function) . ($this->next ? $this->call->node->getStartLine() . " => ".strval($this->next) : "");
    }

    public function __destruct()
    {
        unset($this->function);
        unset($this->next);
    }
}

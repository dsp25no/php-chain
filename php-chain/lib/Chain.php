<?php

namespace PhpChain;

/**
 * Class Chain
 * @package PhpChain
 */
class Chain
{

    /**
     * @var Chain
     */
    private Chain $prev;
    /**
     * @var FunctionLike
     */
    private FunctionLike $function;
    /**
     * @var Chain
     */
    private Chain $next;
    /**
     * @var ExprCall|null
     */
    private ?ExprCall $call;

    /**
     * Chain constructor.
     * @param FunctionLike $function
     * @param ExprCall|null $call
     */
    function __construct(FunctionLike $function, $call=null)
    {
        $this->function = $function;
        $this->call = $call;
    }

    /**
     * @return FunctionLike
     */
    public function value()
    {
        return $this->function;
    }

    /**
     * @return ExprCall|null
     */
    public function getCall()
    {
        return $this->call;
    }

    /**
     * @return Chain
     */
    public function next()
    {
        return $this->next;
    }

    /**
     * @return Chain|null
     */
    public function prev()
    {
        if(isset($this->prev)) {
            return $this->prev;
        }
        return null;
    }

    /**
     * @param Chain $new_node
     * @param ExprCall $call
     * @return Chain
     */
    public function append(Chain $new_node, ExprCall $call)
    {
        $item = $this->last();
        $item->call = $call;
        $item->next = & $new_node;
        $new_node->prev = & $item;
        return $item->next;
    }

    /**
     * @return Chain
     */
    public function rewind()
    {
        $item = $this;
        while(isset($item->prev)){
            $item = $item->prev;
        }
        return $item;
    }

    /**
     * @return Chain
     */
    public function last()
    {
        $item = $this;
        while(isset($item->next)){
            $item = $item->next;
        }
        return $item;
    }

    /**
     * @return int
     */
    public function length() {
        $length = 1;
        $item = $this->rewind();
        while($item->next){
            $item = $item->next;
            $length++;
        }
        return $length;
    }

    /**
     * @return Chain
     */
    public function copyTail()
    {
        $item = $this;
        $root = new Chain($item->function, $item->call);
        $result = $root;
        while (isset($item->next)){
            $item = $item->next;
            $new_node = new Chain($item->function, $item->call);
            $result->next = $new_node;
            $new_node->prev = $result;
            $result = $new_node;
        }
        return $root;
    }

    /**
     * @return Chain
     */
    public function copyChain()
    {
        $root = $this->rewind();
        return $root->copyTail();
    }

    /**
     * @return Chain
     */
    public function delLastNode()
    {
        $last = $this->last();
        if ($prev = $last->prev) {
            $prev->call = null;
            unset($prev->next);
            unset($last->prev);
        }
        return $prev;
    }

    /**
     *
     */
    public function delTail()
    {
        if($this->next) {
            unset($this->next->prev);
            unset($this->next);
            $this->call = null;
        }
    }

    /**
     * @param $node
     * @return bool
     */
    public function isEqualTail($node)
    {
        return $this->function === $node->function and
               $this->call === $node->call and
               $this->next->isEqualTail($node->next);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return strval($this->function) . ($this->next ? $this->call->node->getStartLine() . " => ".strval($this->next) : "");
    }

    /**
     *
     */
    public function __destruct()
    {
        unset($this->function);
        unset($this->next);
    }
}

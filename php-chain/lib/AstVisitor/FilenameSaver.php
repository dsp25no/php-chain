<?php


namespace PhpChain\AstVisitor;


use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

class FilenameSaver extends NodeVisitorAbstract
{
    private $filename;
    public function __construct(string $filename)
    {
        $this->filename = $filename;
    }

    public function leaveNode(Node $node)
    {
        $node->setAttribute('filename', $this->filename);
    }
}
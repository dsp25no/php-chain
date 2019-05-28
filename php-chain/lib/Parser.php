<?php

namespace PhpChain;

use PhpParser\{ParserFactory,NodeTraverser};
use PhpParser\NodeVisitor\NameResolver;
use PhpChain\AstVisitor\{Collector, LoopResolver, ArrayAccessResolver};


class Parser
{
    private $target;
    private $parser;
    private $traverser;
    private $visitors = [];
    private $knowledge;

    public function __construct($target, $knowledge, $config)
    {
        $this->target = $target;
        $this->knowledge = $knowledge;
        $this->parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $this->traverser = new NodeTraverser;
        $this->visitors[] = new NameResolver;
        $this->visitors[] = new Collector($knowledge);
        if($config["features"]["foreach"]) {
            $this->visitors[] = new LoopResolver();
        }
        if($config["features"]["offsetGet"]) {
            $this->visitors[] = new ArrayAccessResolver();
        }
        foreach ($this->visitors as $visitor) {
            $this->traverser->addVisitor($visitor);
        }
    }

    public function collectMethods() {
        foreach ($this->knowledge->getClasses() as $class) {
            if(!$class instanceof Class_ or $class->isAbstract()) {
                continue; // Don't load interfaces' methods, because they don't have body
                // and abstract classes' methods^ because we can't create object of abstract class
            }
            $methods = $class->getMethods();
            foreach ($methods as $method) {
                $this->knowledge->addMethod($method);
            }
        }
    }

    public function parse()
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->target));
        $files = new \RegexIterator(
            $files,
            // ignore directories with tests
            '/^(?!.*(?=[\/\\\\][Tt]ests?[\/\\\\])).*\.(php|inc)$/'
        );
        foreach ($files as $file) {
            $filename = $file->getPathName();

            $code = file_get_contents($filename);
            try {
                // parse
                $stmts = $this->parser->parse($code);

                $this->traverser->traverse($stmts);

            } catch (\PhpParser\Error $e) {
                echo "Fail to parse $filename: ", $e->getMessage().PHP_EOL;
            }
        }
        $this->collectMethods();
    }
}

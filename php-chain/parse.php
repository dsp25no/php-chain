<?php
require_once "vendor/autoload.php";

use PhpChain\Parser;

$config = require_once "config.php";
$parser = new Parser("/target", $config);
$knowledge = $parser->parse();

echo "Parsed".PHP_EOL;

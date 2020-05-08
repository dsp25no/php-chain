<?php
require_once "parse.php";

use PhpChain\ChainBuilder;

$builder = new ChainBuilder($knowledge, $config);
$chainTree = $builder->findAllChains($config["depth"]);
echo "Search finished".PHP_EOL;
$file = fopen("/res/chains", "w");
fwrite($file, json_encode($chainTree, JSON_PRETTY_PRINT));
fclose($file);
$file = fopen("/res/chains_serialized", "w");
fwrite($file, serialize($chainTree));
fclose($file);
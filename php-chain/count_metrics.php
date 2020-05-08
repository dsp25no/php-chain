<?php
require_once "vendor/autoload.php";

use PhpChain\TreeAnalyzer;

if (!isset($chainTree)) {
    require_once "load_chains.php";
}

echo "Count metrics".PHP_EOL;
$analyzer = new TreeAnalyzer($chainTree);
$analyzer->analyze();
$file = fopen("/res/metric_chains", "w");
fwrite($file, json_encode($chainTree, JSON_PRETTY_PRINT));
fclose($file);

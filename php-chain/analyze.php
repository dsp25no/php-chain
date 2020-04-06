<?php
/**
 * Created by PhpStorm.
 * User: dsp25no
 * Date: 2019-03-22
 * Time: 20:17
 */

require_once "vendor/autoload.php";

use PhpChain\{ProjectKnowledge, Parser, ChainBuilder, TreeAnalyzer};

$config = require "config.php";
$parser = new Parser("/target", $config);
$knowledge = $parser->parse();

echo "Parsed".PHP_EOL;

$builder = new ChainBuilder($knowledge, $config);
$chainTree = $builder->findAllChains($config["depth"]);
echo "Search finished".PHP_EOL;
$file = fopen("/res/chains", "w");
fwrite($file, json_encode($chainTree, JSON_PRETTY_PRINT));
fclose($file);
if ($config["metrics"]) {
    echo "Count metrics".PHP_EOL;
    $analyzer = new TreeAnalyzer($chainTree);
    $analyzer->analyze();
    $file = fopen("/res/metric_chains", "w");
    fwrite($file, json_encode($chainTree, JSON_PRETTY_PRINT));
    fclose($file);
}

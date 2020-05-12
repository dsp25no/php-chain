<?php

$filename = $_GET['filename'];
$startLine = intval($_GET['startLine']) - 1 or 0;
$endLine = intval($_GET['endLine']);

$content = file($filename);

if ($endLine) {
    $length = $endLine - $startLine;
    $content = array_slice($content, $startLine, $length);
} else {
    $content = array_slice($content, $startLine);
}

echo implode("\n", $content);

#!/usr/bin/env php
<?php
date_default_timezone_set('America/Los_Angeles');
/**
 * Flow-based example of counting lines of a file, roughly equivalent
 * to "wc -l <filename>"
 */

//if (!isset($_SERVER['argv'][1])) {
//    die("You must provide a filename\n");
//}
//$fileName = $_SERVER['argv'][1];
$fileName = __DIR__ . DIRECTORY_SEPARATOR . 'count.json';

// Include standard autoloader
require __DIR__ . '/../../vendor/autoload.php';

// Add nodes to the graph
$graph = PhpFlo\Graph::createGraph("linecount");
$graph->addNode("Read File", "ReadFile");
$graph->addNode("Split by Lines", "SplitStr");
$graph->addNode("Count Lines", "Counter");
$graph->addNode("Display", "Output");

// Add connections between nodes
$graph->addEdge("Read File", "out", "Split by Lines", "in");
$graph->addEdge("Read File", "error", "Display", "in");
$graph->addEdge("Split by Lines", "out", "Count Lines", "in");
$graph->addEdge("Count Lines", "count", "Display", "in");

// Kick-start the process by sending filename to Read File
$graph->addInitial($fileName, "Read File", "source");

//echo $graph->toJSON();

// Make the graph "live"
$network = PhpFlo\PhpFlo::createNetwork($graph);
//$network->start();
?>

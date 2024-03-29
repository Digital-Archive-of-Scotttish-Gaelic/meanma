<?php

require_once 'includes.php';

error_reporting(E_ERROR && ~E_NOTICE);  //suppress notice warnings

//start the clock running to track time
$startTime = new DateTime();

$lemmatiser = new Lemmatiser();
$lemmatiser->createLexicon();
$lemmatiser->tagFiles();

$elapsedTime = $startTime->diff(new DateTime());
echo "\n -- " . $elapsedTime->format('%H:%I:%S') . "\n\n";




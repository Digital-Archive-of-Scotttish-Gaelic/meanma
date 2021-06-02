<?php

require_once 'includes.php';

foreach (new DirectoryIterator(INPUT_FILEPATH) as $fileinfo) {
  if ($fileinfo->isDot()) continue;
  $filename = $fileinfo->getFilename();
  $handler = new FileHandler($filename);
  $handler->parseFile();;
}

echo "\n\nProcess complete\n\n";




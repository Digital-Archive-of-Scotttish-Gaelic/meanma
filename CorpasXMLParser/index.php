<?php

if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}

require_once 'includes.php';

foreach (new DirectoryIterator(INPUT_FILEPATH) as $fileinfo) {
  if ($fileinfo->isDot()) continue;
  $filename = $fileinfo->getFilename();
  $handler = new FileHandler($filename);
  $handler->parseFile();;
}

echo "\n\nProcess complete\n\n";




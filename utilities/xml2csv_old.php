<?php
/* converts the corpus into a csv file for import to lemma database */

error_reporting(E_ERROR);

//create ass array from filenames to years
$query = <<<SPQR
PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
PREFIX : <http://faclair.ac.uk/meta/>
PREFIX dc: <http://purl.org/dc/terms/>
SELECT DISTINCT ?xml ?date ?title ?supertitle ?supersupertitle ?supersupersupertitle ?medium
WHERE
{
  ?id :xml ?xml .
  OPTIONAL { ?id dc:title ?title . }
  OPTIONAL { ?id dc:isPartOf ?id2 . ?id2 dc:title ?supertitle . }
  OPTIONAL { ?id dc:isPartOf ?id2 . ?id2 dc:isPartOf ?id3 . ?id3 dc:title ?supersupertitle . }
  OPTIONAL { ?id dc:isPartOf ?id2 . ?id2 dc:isPartOf ?id3 . ?id3 dc:isPartOf ?id4 . ?id4 dc:title ?supersupersupertitle . }
  {
    { ?id :internalDate ?date . }
    UNION
    { ?id dc:isPartOf ?id2 . ?id2 :internalDate ?date . }
    UNION
    { ?id dc:isPartOf ?id2 . ?id2 dc:isPartOf ?id3 . ?id3 :internalDate ?date . }
    UNION
    { ?id dc:isPartOf ?id2 . ?id2 dc:isPartOf ?id3 . ?id3 dc:isPartOf ?id4 . ?id4 :internalDate ?date . }
  }
  {
    { ?id :medium ?medium . }
    UNION
    { ?id dc:isPartOf ?id2 . ?id2 :medium ?medium . }
    UNION
    { ?id dc:isPartOf ?id2 . ?id2 dc:isPartOf ?id3 . ?id3 :medium ?medium . }
    UNION
    { ?id dc:isPartOf ?id2 . ?id2 dc:isPartOf ?id3 . ?id3 dc:isPartOf ?id4 . ?id4 :medium ?medium . }
  }
}
SPQR;
$url = 'https://daerg.arts.gla.ac.uk/fuseki/Corpus?output=json&query=' . urlencode($query);
if (getcwd()=='/Users/mark/Sites/gadelica/corpas/code/mm_utilities') {
  $url = 'http://localhost:3030/Corpus?output=json&query=' . urlencode($query);
}
$json = file_get_contents($url);
//echo $json;
$results = json_decode($json,false)->results->bindings;
$dates = [];
foreach ($results as $nextResult) {
  $nextFile = $nextResult->xml->value;
  $nextDate = $nextResult->date->value;
  $dates[$nextFile] = $nextDate;
}
$titles = [];
foreach ($results as $nextResult) {
  $nextFile = $nextResult->xml->value;
  $nextTitle = '';
  if ($nextResult->supersupersupertitle->value!='') $nextTitle .= $nextResult->supersupersupertitle->value . ' – ';
  if ($nextResult->supersupertitle->value!='') $nextTitle .= $nextResult->supersupertitle->value . ' – ';
  if ($nextResult->supertitle->value!='') $nextTitle .= $nextResult->supertitle->value . ' – ';
  $nextTitle .= $nextResult->title->value;
  $titles[$nextFile] = $nextTitle;
}
$media = [];
foreach ($results as $nextResult) {
  $nextFile = $nextResult->xml->value;
  $nextMedium = $nextResult->medium->value;
  $media[$nextFile] = $nextMedium;
}


/*
foreach ($dates as $key => $value) {
  echo $key . ' ' . $value . PHP_EOL;
}
*/


$path = '/var/www/html/dasg.arts.gla.ac.uk/www/gadelica/corpas/xml';
if (getcwd()=='/Users/mark/Sites/gadelica/corpas/code/mm_utilities') {
  $path = '../../xml';
}
$it = new RecursiveDirectoryIterator($path);
foreach (new RecursiveIteratorIterator($it) as $nextFile) {
  if ($nextFile->getExtension()=='xml') {
    $xml = simplexml_load_file($nextFile);
    $xml->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
    foreach ($xml->xpath("//dasg:w") as $nextWord) {
      $lemma = (string)$nextWord['lemma'];
      if ($lemma /*&& !strpos($lemma,' ')*/) { echo $lemma . ','; }
      else { echo $nextWord . ','; }
      if (getcwd()=='/Users/mark/Sites/gadelica/corpas/code/mm_utilities') {
        $filename = substr($nextFile,10);
      }
      else {
        $filename = substr($nextFile,58);
      }
      echo $filename . ',';
      echo $nextWord['id'] . ',';
      echo $nextWord . ',';
      echo $nextWord . ',';
      echo $nextWord['pos'] . ',';
      if ($dates[$filename]) { echo $dates[$filename] . ','; }
      else { echo '9999,'; }
      if ($titles[$filename]) { echo '"' . $titles[$filename] . '",'; }
      else { echo '"6666,"'; }
      $nextWord->registerXPathNamespace('dasg','https://dasg.ac.uk/corpus/');
      $xxxs = $nextWord->xpath("preceding::dasg:pb[1]/@n");
      echo $xxxs[0] . ",";
      if ($media[$filename]) { echo $media[$filename]; }
      else { echo '7777'; }
      echo PHP_EOL;
    }
  }
}




?>

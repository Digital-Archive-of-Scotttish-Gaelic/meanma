<?php

namespace models;

class sparqlquery
{
  private $_url;

  private function _setUrl($query) {
    $this->_url = "https://daerg.arts.gla.ac.uk/fuseki/Corpus?output=json&query={$query}";
    if (getcwd()=="/Users/mark/Sites/gadelica/corpas/code" || getcwd()=="/Users/stephenbarrett/Sites/gadelica/corpas/code") {
      $this->_url = "http://localhost:3030/Corpus?output=json&query={$query}";
    }
    return $this->_url;
  }

  private function _getUrl() {
    return $this->_url;
  }

  private function _getQueryHeader() {
    $queryHeader = <<<SPQR
      PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#>
      PREFIX : <http://faclair.ac.uk/meta/>
      PREFIX dc: <http://purl.org/dc/terms/>
SPQR;
    return $queryHeader;
  }

  public function getQueryResults($query) {
    $query = $this->_getQueryHeader() . $query;
    $query = urlencode($query);
    $url = $this->_setUrl($query);
    $json = file_get_contents($url);
    $results = json_decode($json,false)->results->bindings;
    if (count($results)==0) {
      return [];
    }
    return $results;
  }
}
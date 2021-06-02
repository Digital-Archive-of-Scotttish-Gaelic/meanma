<?php


namespace models;


class slow_search
{
	private $_filepathOffset = 58;  //used to find the filename in a path
	private $_path = '/var/www/html/dasg.arts.gla.ac.uk/www/gadelica/corpas/xml'; //server path to XML files
	private $_id;   //the text ID
	private $_db;   //database

	public function __construct($id) {
		if (substr(getcwd(), 0, 6) == "/Users") {   //for local testing
			$this->_filepathOffset = 7;
			$this->_path = "../xml";
		}
		$this->_id = $id ? $id : 0;
		$this->_db = isset($this->_db) ? $this->_db : new database();
	}

	public function search($xpath, $chunkSize=null, $offsetFilename=null, $offsetId=null, $index=-1) {
		$files = array();
		//populate the files array
		if ($this->_id) {   //search in single text
			$files = $this->getFilenamesFromId();
		} else {          //search whole corpus
			$it = new \RecursiveDirectoryIterator($this->_path);
			foreach (new \RecursiveIteratorIterator($it) as $nextFile) {
				if ($nextFile->getExtension() == 'xml') {
					$filename = substr($nextFile, $this->_filepathOffset);
					$files[] = $filename;
				}
			}
		}
		$xpath = "//dasg:w[" . $xpath . "]/@id";   //format the xpath
		$chunkSize = $chunkSize ? intval($chunkSize) : null;
		$results = array();
		$i = 0; //increment counter for results array
		if ($files) {
			foreach ($files as $filename) {
				//check for an offset filename and skip until we reach it if there is one
				if ($offsetFilename && ($offsetFilename != $filename)) {
					continue;
				} else if ($offsetFilename == $filename) {
					$offsetFilename = "";
				}
				$handler = new xmlfilehandler($filename);
				$xml = simplexml_load_file($this->_path . '/' . $filename);
				$xml->registerXPathNamespace('dasg', 'https://dasg.ac.uk/corpus/');
				$result = $xml->xpath($xpath);
				foreach ($result as $id) {
					//check for an offset ID and skip until we reach it if there is one
					if ($offsetId && $offsetId != $id) {
						continue;
					} else if ($offsetId == $id) {
						$offsetId = "";
						continue;
					}
					$index++;
					$results[$i]["data"] = corpus_search::getDataById($filename, $id);
					$results[$i]["data"]["context"] = $handler->getContext($id);
					$results[$i]["data"]["slipLinkHtml"] = collection::getSlipLinkHtml($results[$i]["data"], $index);
					$pos = new partofspeech($results[$i]["data"]["pos"]);
					$results[$i]["data"]["posLabel"] = $pos->getLabel();
					$results[$i]["index"] = $index;

					//limit results to chunk size
					if ($i === $chunkSize) {
						return $results;
					}
					$i++;
				}
			}
			return $results;
		}
		return array("error" => "Text ID not recognised");
	}

	private function getFilenamesFromId() {
		$sql = <<<SQL
			SELECT filepath FROM text t WHERE 
				t.id = :id OR t.id LIKE CONCAT(:id, '-%')
SQL;
		$results = $this->_db->fetch($sql, array(":id" => $this->_id));
		foreach ($results as $result) {
			if (!empty($result["filepath"])) {
				$filepaths[] = $result["filepath"];
			}
		}
		return $filepaths;
	}
}
<?php


namespace models;


class manuscript
{
	private $_id, $_title, $_filename, $_xml;

	public function __construct($id) {
		$this->_id = $id;
	}

	// SETTERS
	public function setTitle($title) {
		$this->_title = $title;
	}

	public function setFilename($filename) {
		$this->_filename = $filename;
	}

	public function loadXml() {
		$this->_xml = simplexml_load_file(TRANSCRIPTION_PATH . $this->getFilename());
		$this->_xml->registerXPathNamespace('tei', 'http://www.tei-c.org/ns/1.0');
	}

	// GETTERS

	public function getId() {
		return $this->_id;
	}

	public function getTranscriptionId() {
		return substr($this->getId(), 4);
	}

	public function getTitle() {
		return $this->_title;
	}

	public function getFilename() {
		return $this->_filename;
	}

	public function getXml() {
		return $this->_xml;
	}

	// METHODS
	public function getModalData($chunkId) {
		$modalData = array_merge($this->_getLocalModalData($chunkId), $this->_getExternalModalData($chunkId));
		return $modalData;
	}

	private function _getLocalModalData($chunkId) {
		$modalData = array();
		// run XPath
		$xmlResults = $this->getXml()->xpath("//tei:w[@id='{$chunkId}'] | //tei:name[@id='{$chunkId}']");
		$teiXml = $xmlResults[0];
		//create a copy of the XML
		$xmlString = $teiXml->asXml();
		$xml = new \SimpleXMLElement($xmlString);

		$modalData["headword"] = $this->_getHeadword($xml);
		if ($xml->getName()=='name') {
			$modalData["onomastics"] = $this->_getOnomastics($xml);
			$wordCount = count($xml->w);
			$nameCount = count($xml->name);
			if ($wordCount==1 && $nameCount==0) {
				$modalData["complexFlag"] =  0;
				$xml2 = $xml->w[0];
				$modalData = array_merge($modalData, $this->_populateData($xml2));
			}
			else {
				$modalData["abbrevs"] = [];
				$modalData["complexFlag"] = 1;
				foreach ($xml->children() as $c) {
					$n = $c->getName();
					if ($n=='w' || $n=='name') {
						$modalData["child"][] = $this->_getLocalModalData($c->attributes()->id);
					}
				}
			}
		} else if ($xml->getName()=='w') {
			$modalData = array_merge($modalData, $this->_populateData($xml));
			$wordCount = count($xml->w);
			if ($wordCount > 1) {
				$modalData["complexFlag"] = 1;
				foreach ($xml->w as $w) {
					$modalData["child"][] = $this->_getLocalModalData($w->attributes()->id);
				}
			} else {
				$modalData["complexFlag"] = 0;
			}
		}
		return $modalData;
	}

	private function _getExternalModalData($chunkId) {
		$modalData = array();
		// run XPath
		$xmlResults = $this->getXml()->xpath("//tei:w[@id='{$chunkId}'] | //tei:name[@id='{$chunkId}']");
		$teiXml = $xmlResults[0];
		//create a copy of the XML
		$xmlString = $teiXml->asXml();
		$xml = new \SimpleXMLElement($xmlString);
		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$modalData["xml"] = htmlentities($dom->saveXML());
		$modalData["partOfInsertion"] = $this->_getPartOfInsertion($xml);
		$modalData["emendation"] = $this->_getEmendation($xml);
		$modalData["interpObscureSection"] = $this->_isPartOfInterpObscureSection($xml);
		$modalData["obscureSection"] = $this->_isPartOfObscureSection($xml);
		$modalData["hand"] = $this->_getStartingHandInfo();

		return $modalData;
	}

	//Just to cut down on repetition for <w> and <name> elements
	private function _populateData($xml) {
		$modalData["pos"] = $this->_getPOS($xml);
		$modalData["edil"] = $this->_getEdilUrl($xml);
		$modalData["lemma"] = $this->_getLemma($xml);
		$modalData["abbrevs"] = $this->_getAbbrevs($xml);
		$modalData["insertions"] = $this->_getInsertions($xml);
		$modalData["deletions"] = $this->_getDeletions($xml);
		$modalData["damaged"] = $this->_getDamaged($xml);
		$modalData["obscure"] = $this->_getObscured($xml);
		$modalData["supplied"] = $this->_getSupplied($xml);
		$modalData["handShift"] = $this->_getHandShiftInfo($xml);
		$modalData["language"] = $this->_getLanguage($xml);
		return $modalData;
	}

	private function _getStartingHandInfo() {
		$handInfo = null;
		$result = $this->getXml()->xpath('//tei:div[@hand]');
		if ($result) {
			$handId = $result[0]->attributes()->hand;
			$hand = new hand($handId);
			$handInfo = array("id" => $handId, "forename" => $hand->getForename(), "surname" => $hand->getSurname(),
				"writerId" => $hand->getWriterId());
		}
		return $handInfo;
	}

	private function _getHandShiftInfo($element) {
		$handInfo = null;
		$id = $element->attributes()->id;
		$result = $this->getXml()->xpath('//tei:w[@id="' . $id . '"]/preceding::tei:handShift');
		if ($result) {
			$r = end($result);    //closest preceding element
			$handId = $r->attributes()->new;
			$hand = new hand($handId);
			$handInfo = array("id" => $handId, "forename" => $hand->getForename(), "surname" => $hand->getSurname(),
				"writerId" => $hand->getWriterId());
		}
		return $handInfo;
	}

	private function _getLanguage($element) {
		$language = null;
	//	$id = $element->attributes()->id;
		$result = $element->xpath('.//@xml:lang');
		if ($result) {
			$language = $result[0];
		}
		return $language;
	}

	private function _getEmendation($element) {
		$emendation = null;
		//test if part of an emendation
		$id = $element->attributes()->id;
		$result = $this->getXml()->xpath('//tei:choice[child::tei:sic[child::tei:w[@id="' . $id . '"]]]');
		if ($result) {
			$choiceXml = $result[0];
			$sic = $choiceXml->sic;
			$corr = $choiceXml->corr;
			$resp = $corr->attributes()->resp;
			//create a copy of the XML for <sic>
			$sicString = $sic->asXml();
			$sicXml = new \SimpleXMLElement($sicString);
			$xsl = simplexml_load_file('xsl/assembleForm.xsl');
			$xslt = new \XSLTProcessor;
			$xslt->importStyleSheet($xsl);
			$sicWord = trim($xslt->transformToXML($sicXml));
			//create a copy of the XML for <corr>
			$corrString = $corr->asXml();
			$corrXml = new \SimpleXMLElement($corrString);
			$xsl = simplexml_load_file('xsl/assembleForm.xsl');
			$xslt = new \XSLTProcessor;
			$xslt->importStyleSheet($xsl);
			$corrWord = trim($xslt->transformToXML($corrXml));
			$emendation = array('sic' => $sicWord, "corr" => $corrWord, "resp" => $resp);
		}
		return $emendation;
	}

	private function _getPartOfInsertion($element) {
		$partOfInsertion = null;
		//test if part of an insertion
		$id = $element->attributes()->id;
		$result = $this->getXml()->xpath('//tei:add[@type="insertion" and child::tei:w[@id="' . $id . '"]]'); 
		if ($result) {
			$partOfXml = $result[0];
			$place = $partOfXml->attributes()->place;
			//create a copy of the XML
			$xmlString = $partOfXml->asXml();
			$addXml = new \SimpleXMLElement($xmlString);
			$xsl = simplexml_load_file('xsl/assembleForm.xsl');
			$xslt = new \XSLTProcessor;
			$xslt->importStyleSheet($xsl);
			$fullWord = $xslt->transformToXML($addXml);
			$partOfInsertion = array('fullWord' => $fullWord, "place" => $place);
		}
		return $partOfInsertion;
	}

	private function _isPartOfInterpObscureSection($element) {
		$id = $element->attributes()->id;
		$result = $this->getXml()->xpath('//tei:unclear[@reason="interp_obscure" and child::tei:w[@id="' . $id . '"]]');
		if ($result) {
			return 1; //is part of an interp obscured section
		}
	}

	private function _isPartOfObscureSection($element) {
		$partOfObscure = null;
		$id = $element->attributes()->id;
		$xpath = <<<XPATH
			//tei:unclear[@reason="text_obscure" and descendant::tei:w[@id="{$id}"] or descendant::tei:name[@id="{$id}"]]
XPATH;
		$result = $this->getXml()->xpath($xpath);
		if ($result) {
			$partOfObscure["cert"] = $result[0]->attributes()->cert;
			$partOfObscure["resp"] = $result[0]->attributes()->resp;
		}
		return $partOfObscure;
	}

	private function _getAbbrevs($element) {
		$results = array();
		$abbrevs = $element->xpath("abbr");
		foreach ($abbrevs as $abbr) {
			if ($abbr->g) {
				$glyg = new glygature($abbr->g->attributes()->ref);
				$results[] = array("g" => $abbr->g, "cert" => $abbr["cert"] ? $abbr["cert"] : ['undefined'],
					"name" => $glyg->getName(), "note" => $glyg->getNote(), "corresp" => $glyg->getCorresp(), "id" => $abbr->g["id"]);
			}
		}
		return $results;
	}

	private function _getLemma($element) {
		return $element->attributes()->lemma;
	}

	private function _getEdilUrl($element) {
		return $element->attributes()->lemmaRef;
	}

	private function _getOnomastics($element) {
		$name = array();
		if ($element->getName() == "name") {
			switch ($element->attributes()->type) {
				case "personal":
					$name["type"] = "anthroponym";
					break;
				case "place":
					$name["type"] = "toponym";
					break;
				case "population":
					$name["type"] = "demonym";
					break;
				default:
					$name["type"] = "name";
			}
			$name["url"] = $element->attributes()->corresp ? $element->attributes()->corresp : "";
		}
		return $name;
	}

	private function _getInsertions($element) {
		$results = array();
		$insertions = $element->xpath("add");
		foreach ($insertions as $insertion) {
			$results[] = $insertion;
		}
		return $results;
	}

	private function _getSupplied($element) {
		$results = array();
		$supplied = $element->xpath("supplied");
		foreach ($supplied as $s) {
			$results[] = $s;
		}
		return $results;
	}

	private function _getDeletions($element) {
		$results = array();
		$deletions = $element->xpath("del");
		foreach ($deletions as $deletion) {
			$results[] = $deletion;
		}
		return $results;
	}

	private function _getDamaged($element) {
		$results = array();
		$damaged = $element->xpath("unclear[@reason='damage']");
		foreach ($damaged as $damage) {
			$results[] = $damage;
		}
		return $results;
	}

	private function _getObscured($element) {
		$results = array();
		$obscured = $element->xpath("unclear[@reason='text_obscure']");
		foreach ($obscured as $obscure) {
			$results[] = $obscure;
		}
		return $results;
	}

	private function _getPOS($element) {
		return $element->attributes()->pos;
	}


	private function _getHeadword($element) {
		//apply the XSLT
		$xsl = new \DOMDocument;
		$xsl->load('xsl/assembleForm.xsl');
		$proc = new \XSLTProcessor;
		$proc->importStyleSheet($xsl);
		//return the result
		return $proc->transformToXML($element);
	}
}

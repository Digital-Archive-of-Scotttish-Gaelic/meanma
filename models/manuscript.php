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
		$this->_xml = simplexml_load_file(INPUT_FILEPATH . $this->getFilename());
		$this->_xml->registerXPathNamespace('tei','https://dasg.ac.uk/corpus/');
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
		$xpath = <<<XPATH
			//tei:*[@id='{$chunkId}']
XPATH;
		$xmlResults = $this->getXml()->xpath($xpath);
		$dasgXml = $xmlResults[0];
		//create a copy of the XML
		$xmlString = $dasgXml->asXml();
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

			$wordCheck = $xml->xpath("//w[ancestor::*[@id='{$chunkId}']]");
			$wordCount = count($wordCheck);
//			$wordCount = count($xml->w);
			if ($wordCount > 1) {
				$modalData["complexFlag"] = 1;
				foreach ($wordCheck as $w) {
					$modalData["child"][] = $this->_getLocalModalData($w->attributes()->id);
				}
			} else {
				$modalData["complexFlag"] = 0;
			}
		} else {   //punctuation, characters, gaps, etc.
			$modalData = array_merge($modalData, $this->_populateData($xml));
			$modalData["headword"] = (string)$xml;
			$modalData["complexFlag"] = -1;
			if ($xml->getName() == "pc" || $xml->getName() == "c") {
				$modalData["punctuation"] = 1;
			} else if ($xml->getName() == "gap") {
				$modalData["handShift"] = null;   //don't want scribe info for gaps
			}
		}
		return $modalData;
	}

	private function _getExternalModalData($chunkId) {
		$modalData = array();
		// run XPath
		$xpath = <<<XPATH
			//tei:*[@id='{$chunkId}']
XPATH;
		$xmlResults = $this->getXml()->xpath($xpath);
		$dasgXml = $xmlResults[0];
		//create a copy of the XML
		$xmlString = $dasgXml->asXml();
		$xml = new \SimpleXMLElement($xmlString);
		$dom = new \DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($xml->asXML());
		$modalData["xml"] = htmlentities($dom->saveXML());
		$modalData["partOfInsertion"] = $this->_getPartOfInsertion($xml);
		$modalData["externalEmendation"] = $this->_getEmendation($xml, "external");
		$modalData["interpObscureSection"] = $this->_isPartOfInterpObscureSection($xml);
		$modalData["obscureSection"] = $this->_isPartOfObscureSection($xml);
		$modalData["externalSupplied"] = $this->_getExternalSupplied($xml);
		$modalData["externalDeletions"] = $this->_getExternalDeletions($xml);
		return $modalData;
	}

	//Just to cut down on repetition for <w> and <name> elements
	private function _populateData($xml) {
		$modalData["pos"] = $this->_getPOS($xml);
		// deal with all the possible permutations of links to edil, dwelly, and place data
		$modalData["edil"] = $this->_getEdilUrl($xml);
		$modalData["dwelly"] = $modalData["edil"] ? $this->_getDwelly($modalData["edil"]) : null;
		//if both lemmaDW and lemmaRefDW are provided then don't use hwData to get info
		if ($xml->attributes()->lemmaDW && $xml->attributes()->lemmaRefDW) {
			$modalData["dwelly"]["hw"] = (string)$xml->attributes()->lemmaDW;
			$modalData["dwelly"]["url"] = (string)$xml->attributes()->lemmaRefDW;
		} else if (!stristr($modalData["edil"], "dil.ie") && !stristr($modalData["dwelly"]["url"], "faclair.com")) {
			$modalData["placeLemma"] = $modalData["edil"];    //this is just a placename link
			unset($modalData["edil"]);    //this is just a Dwelly link so remove
			unset ($modalData["dwelly"]); //not a Dwelly link so remove
		} else {
			if (!stristr($modalData["edil"], "dil.ie")) {
				unset($modalData["edil"]);    //this is just a Dwelly link so remove
			}
			if (!stristr($modalData["dwelly"]["url"], "faclair.com")) {
				unset ($modalData["dwelly"]); //not a Dwelly link so remove
			}
		}
		$modalData["hdsg"] = $this->_getSlipRef($xml);
		$modalData["lemma"] = $this->_getLemma($xml);
		$modalData["abbrevs"] = $this->_getAbbrevs($xml);
		$modalData["insertions"] = $this->_getInsertions($xml);
		$modalData["emendation"] = $this->_getEmendation($xml, "local");
		$modalData["deletions"] = $this->_getDeletions($xml);
		$modalData["damaged"] = $this->_getDamaged($xml);
		$modalData["gapDamaged"] = $this->_getGapDamaged($xml);
		$modalData["gapObscured"] = $this->_getGapObscured($xml);
		$modalData["gapSurfaceLost"] = $this->_getGapSurfaceLost($xml);
		$modalData["obscure"] = $this->_getObscured($xml);
		$modalData["supplied"] = $this->_getLocalSupplied($xml);
		$modalData["handShift"] = $this->_getHandShiftInfo($xml);
		$modalData["language"] = $this->_getLanguage($xml);
		return $modalData;
	}

	private function _getHandShiftInfo($element) {
		$handInfo = null;
		$id = $element->attributes()->id;
		$result = $this->getXml()->xpath('//tei:*[@id="' . $id . '"]/preceding::tei:handShift');
		if ($result) {
			$r = end($result);    //closest preceding element
			$handId = $r->attributes()->new;
			$handInfo = $this->_getHandInfo($handId);
		}
		//check for handShifts contained within a chunk
		$contains = $this->getXml()->xpath('//tei:handShift[ancestor::tei:*[@id="' . $id . '"]]');
		if ($contains) {
			foreach ($contains as $subhand) {
				$handId = $subhand->attributes()->new;
				$handInfo = $this->_getHandInfo($handId);
			}
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

	/**
	 * @param $element
	 * @param string $scope : local or external - alters the XPath depending on the requirement
	 * @return array|null
	 */
	private function _getEmendation($element, $scope) {
		$emendation = null;
		//test if part of an emendation
		$id = $element->attributes()->id;
		$xpath = $scope == "external"
			? '//tei:choice[child::tei:corr[child::*[@id="' . $id . '"]]]'
			: '//tei:choice[ancestor::*[@id="' . $id . '"]]';
		$result = $this->getXml()->xpath($xpath);
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
			$handId = $partOfXml->attributes()->hand;
			$handInfo = $this->_getHandInfo($handId);
			//create a copy of the XML
			$xmlString = $partOfXml->asXml();
			$addXml = new \SimpleXMLElement($xmlString);
			$xsl = simplexml_load_file('xsl/assembleForm.xsl');
			$xslt = new \XSLTProcessor;
			$xslt->importStyleSheet($xsl);
			$fullWord = $xslt->transformToXML($addXml);
			$partOfInsertion = array('fullWord' => $fullWord, "place" => $place, "hand" => $handInfo);
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

	private function _getExternalSupplied($element) {
		$externalSupplied = array();
		$id = $element->attributes()->id;
		//		$results = $this->getXml()->xpath("//tei:supplied[descendant::tei:*[@id='{$id}']  or ancestor::tei:*[@id='{$id}']]");
		$xpath = <<<XPATH
			//tei:supplied[descendant::tei:*[@id='{$id}']]
XPATH;

		$results = $this->getXml()->xpath($xpath);
		if ($results)	{
			$i = 0;
			foreach ($results as $result) {
				//if there is a child then return its contents as the text supplied, otherwise just return the current element's contents
				$externalSupplied[$i]["text"] = $result->children()[0] ? $result->children()[0] : $result;
				$externalSupplied[$i]["resp"] = $result->attributes()->resp;
				$i++;
			}
		}
		return $externalSupplied;
	}

	private function _getExternalDeletions($element) {
		$results = array();
		$id = $element->attributes()->id;
		$deletions = $this->getXml()->xpath("//tei:del[descendant::tei:*[@id='{$id}']]");
		foreach ($deletions as $deletion) {
			$handId = $deletion->attributes()->hand;
			$handInfo = $this->_getHandInfo($handId);
			$results[] = array("hand" => $handInfo, "data" => functions::cleanForm($deletion));
		}
		return $results;
	}

	private function _getAbbrevs($element) {
		$results = array();
		$abbrevs = $element->xpath("abbr");
		foreach ($abbrevs as $abbr) {
			foreach ($abbr->g as $g) {
				if ($g->attributes()->ref) {
					$glyg = new glygature($g->attributes()->ref);
					$results[] = array("g" => $g, "cert" => $abbr["cert"] ? $abbr["cert"] : ['undefined'],
						"name" => $glyg->getName(), "note" => $glyg->getNote(), "corresp" => $glyg->getCorresp(), "id" => $g["id"]);
				}
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

	private function _getSlipRef($element) {
		if ($slipRef = $element->attributes()->slipRef) {
			return array("url" => $slipRef, "lemma" => $element->attributes()->lemmaSL);
		}
		return null;
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
			$handId = $insertion->attributes()->hand;
			$handInfo = $this->_getHandInfo($handId);
			$results[] = array("hand" => $handInfo, "data" => $insertion);
		}
		return $results;
	}

	private function _getLocalSupplied($element) {
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
			$handId = $deletion->attributes()->hand;
			$handInfo = $this->_getHandInfo($handId);
			$results[] = array("hand" => $handInfo, "data" => $deletion);
		}
		return $results;
	}

	private function _getDamaged($element) {
		$results = array();
		$damaged = $element->xpath("./descendant-or-self::unclear[@reason='damage']");
		foreach ($damaged as $damage) {
			$results[] = $damage;
		}
		return $results;
	}

	private function _getGapDamaged($element) {
		$gapDamaged = null;
		$id = $element->attributes()->id;
		$damaged = $element->xpath("//gap[@id='{$id}' and @reason='damage']");
		if ($damaged[0]) {
			$gapDamaged = $damaged[0];
		}
		return $gapDamaged;
	}

	private function _getGapObscured($element) {
		$gapObscured = null;
		$id = $element->attributes()->id;
		$obscured = $element->xpath("//gap[@id='{$id}' and @reason='text_obscure']");
		if ($obscured[0]) {
			$gapObscured = $obscured[0];
		}
		return $gapObscured;
	}

	private function _getGapSurfaceLost($element) {
		$gapSurfaceLost = null;
		$id = $element->attributes()->id;
		$surfaceLost = $element->xpath("//gap[@id='{$id}' and @reason='writing_surface_lost']");
		if ($surfaceLost[0]) {
			$gapSurfaceLost = $surfaceLost[0];
		}
		return $gapSurfaceLost;
	}

	private function _getObscured($element) {
		$results = array();
		$obscured = $element->xpath("unclear[@reason='text_obscure']");
		foreach ($obscured as $obscure) {
			$obscure->word = functions::cleanForm($obscure);
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

	private function _getDwelly($edil) {
		$filepath = "../mss/Transcribing/hwData.xml";
		$xml = simplexml_load_file($filepath);
		$xml->registerXPathNamespace('tei', 'http://www.tei-c.org/ns/1.0');
		$nodes = $xml->xpath("/tei:TEI/tei:text/tei:body/tei:entryFree[@corresp='{$edil}']/tei:w");
		$node = $nodes[0];
		$lemmaDW = (string)$node["lemmaDW"];
		$lemmaRefDW = (string)$node["lemmaRefDW"];

		if (!$lemmaDW) {        //why do I have to put in this "hack"?? SB
			$lemmaDW = (string)$node["lemma"];
			$lemmaRefDW = (string)$node["lemmaRef"];
		}

		$dwelly = array("hw" => $lemmaDW, "url" => $lemmaRefDW);
		return $dwelly;
	}

	private function _getHandInfo($handId) {
		$hand = new hand($handId);
		return array("id" => $handId, "forename" => $hand->getForename(), "surname" => $hand->getSurname(),
			"writerId" => $hand->getWriterId());
	}
}

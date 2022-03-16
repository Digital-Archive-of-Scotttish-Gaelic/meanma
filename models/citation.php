<?php

namespace models;

class citation
{
	private $_db; //an instance of models\database
	private $_id, $_type, $_preContextScope, $_postContextScope, $_preContextString, $_postContextString;
	private $_lastUpdated;
	private $_translations = array(); //an array of translation objects
	private $_emendations = array(); //an array of emendation objects
	private $_slip; //an instance of \models\slip - the slip this citation is attached to

	const SCOPE_DEFAULT = 80;

	public static $types = array("draft", "sense", "form");  //the possible values for citation type

	public function __construct($db, $id = null) {
		$this->_db = $db;
		if ($id) {
			$this->_id = $id;
			$this->_load();
		} else {
			$this->_init();
		}
	}

	private function _init() {
		$this->_preContextScope = self::SCOPE_DEFAULT;
		$this->_postContextScope = self::SCOPE_DEFAULT;
		$this->_type = "draft";   //default for new citation
		$sql = <<<SQL
			INSERT INTO citation (`preContextScope`, `postContextScope`, `type`) VALUES(:pre, :post, :type);
SQL;
		$this->_db->exec($sql, array(":pre" => $this->_postContextScope, ":post" => $this->_postContextScope,
			":type" => $this->_type));
		$id = $this->_db->getLastInsertId();
		$this->_id = $id;
	}

	public function save() {
		$sql = <<<SQL
			UPDATE citation SET `type` = :type, `preContextScope` = :pre, `postContextScope` = :post,
			                    `preContextString` = :preString, `postContextString` = :postString
				WHERE id = :id
SQL;
		$this->_db->exec($sql, array(":type" => $this->getType(), ":pre" => $this->getPreContextScope(),
			":post" => $this->getPostContextScope(), ":preString" => $this->getPreContextString(),
			":postString" => $this->getPostContextString(), ":id" => $this->getId()));
	}

	private function _load() {
		$sql = <<<SQL
			SELECT * FROM citation c WHERE id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		$row = $result[0];
		$this->_type = $row["type"];
		$this->_preContextScope = $row["preContextScope"];
		$this->_postContextScope = $row["postContextScope"];
		$this->_preContextString = $row["preContextString"];
		$this->_postContextString = $row["postContextString"];
		$this->_lastUpdated = $row["lastUpdated"];
		$this->getSlip();
		$this->_loadTranslations();
	}

	private function _loadTranslations() {
		$sql = <<<SQL
			SELECT translation_id FROM citation_translation WHERE citation_id = :id
SQL;
		$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
		foreach ($result as $row) {
			$this->_translations[] = new translation($this->_db, $row["translation_id"]);
		}
	}

	public function addTranslation($translation) {
		$this->_translations[] = $translation;
	}

	public function attachToSlip($slipId) {
		$this->_slip = collection::getSlipBySlipId($slipId, $this->_db);
		if ($this->_slip->getSlipIsAttachedTiCitation($this->getId())) {
			return;   //slip is already attached
		}
		$sql = <<<SQL
			REPLACE INTO slip_citation (`slip_id`, `citation_id`) VALUES (:slipId, :citationId)
SQL;
		$this->_db->exec($sql, array(":slipId" => $slipId, ":citationId" => $this->getId()));
		$this->_slip->addCitation($this);
	}

	/**
	 * Gets the data required to correctly format the context as a citation.
	 * @return string array : an associative array of strngs comprising context output and flags for processing:
	 *    : string html : the generated html based on the pre and post contexts, the word, and any required joins
	 *    : string preIncrementDisable : empty or 'disabled' if the start of the document has been reached
	 *    : string postIncrementDisable : empty or 'disabled' if the end of the document has been reached
	 */
	public function getContext($tagContext = false, $emendations = false) {
		$context["html"] = $context["preDisable"] = $context["postDisable"] = $context["prelimit"] = $context["postlimit"] = "";
		if ($this->_slip->getType() == "paper") {       //paper slip
			$context["html"] = $this->getPreContextString() . ' <mark class="hi">' . $this->_slip->getWordform() . '</mark> '
				. $this->getPostContextString();
		} else {                                        //corpus_slip
			$handler = new xmlfilehandler($this->_slip->getFilename());
			$preScope = $this->getPreContextScope();
			$postScope = $this->getPostContextScope();
			$context = $handler->getContext($this->_slip->getWid(), $preScope, $postScope, $emendations, $tagContext);

			//check for start/end of document
			if (isset($context["prelimit"])) {  // the start of the citation is shorter than the preContextScope default
				$this->setPreContextScope($context["prelimit"]);
				$preIncrementDisable = "disabled";
			}
			if (isset($context["postlimit"])) { // the end of the citation is shorter than the postContextScope default
				$this->setPostContextScope($context["postlimit"]);
				$postIncrementDisable = "disabled";
			}
			$contextHtml = $context["pre"]["output"];
			if ($context["pre"]["endJoin"] != "right" && $context["pre"]["endJoin"] != "both") {
				$contextHtml .= ' ';
			}
			$wordformHtml = ($emendations) ? $context["word"]["output"] : $context["word"];
			$contextHtml .= <<<HTML
        <mark class="hi slipWordInContext" data-headwordid="{$context["headwordId"]}">{$wordformHtml}</mark>
HTML;
			if ($context["post"]["startJoin"] != "left" && $context["post"]["startJoin"] != "both") {
				$contextHtml .= ' ';
			}
			$contextHtml .= $context["post"]["output"];
			$context["html"] = $contextHtml;
			$context["preDisable"] = $preIncrementDisable;
			$context["postDisable"] = $postIncrementDisable;
		}
		return array("html" => $context["html"], "preIncrementDisable" => $context["preDisable"], "postIncrementDisable" =>
			$context["postDisable"], "prelimit" => $context["prelimit"], "postlimit" => $context["postlimit"]);
	}

	/**
	 * Deletes citation from DB
	 * Deletes all attached translations for the citation
	 */
	public static function delete($id, $db) {
		//translations
		$sql = <<<SQL
			DELETE t, ct FROM translation t
				JOIN citation_translation ct ON t.id = ct.translation_id
				WHERE ct.citation_id = :id
SQL;
		$db->exec($sql, array(":id" => $id));
		//citation
		$sql = <<<SQL
			DELETE c, sc FROM citation c 
				JOIN slip_citation sc ON c.id = sc.citation_id
				WHERE c.id = :id
SQL;
		$db->exec($sql, array(":id" => $id));
	}

	//GETTERS
	public function getId() {
		return $this->_id;
	}

	public function getType() {
		return $this->_type;
	}

	public function getPreContextScope() {
		return $this->_preContextScope;
	}

	public function getPostContextScope() {
		return $this->_postContextScope;
	}

	public function getPreContextString() {
		return $this->_preContextString;
	}

	public function getPostContextString() {
		return $this->_postContextString;
	}

	public function getTranslations() {
		return $this->_translations;
	}

	public function getTranslationIdsString() {
		$tids = [];
		foreach ($this->getTranslations() as $translation) {
			$tids[] = $translation->getId();
		}
		return implode('|', $tids);
	}

	public function getLastUpdated() {
		return $this->_lastUpdated;
	}

	public function getScopeDefault() {
		return self::SCOPE_DEFAULT;
	}

	public function getSlip() {
		if (empty($this->_slip)) {
			$sql = <<<SQL
				SELECT slip_id FROM slip_citation WHERE citation_id = :id
SQL;
			$result = $this->_db->fetch($sql, array(":id" => $this->getId()));
			$this->_slip = collection::getSlipBySlipId($result[0]["slip_id"], $this->_db);
		}
		return $this->_slip;
	}

	//SETTERS
	public function setType($type) {
		$this->_type = $type;
	}

	public function setPreContextScope($pre) {
		$this->_preContextScope = $pre;
	}

	public function setPostContextScope($post) {
		$this->_postContextScope = $post;
	}

	public function setPreContextString($string) {
		$this->_preContextString = $string;
	}

	public function setPostContextString($string) {
		$this->_postContextString = $string;
	}



}
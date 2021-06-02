<?php

namespace models;

class corpus_search
{
	private $_id; // the id number for the text in the corpus being searched
	private $_term; // the word being searched for
	private $_db; // an instance of models\database
	private $_params; // an array of query string parameters
	private $_dbResults;  // an array of search results from the database
	private $_perpage, $_page, $_mode, $_case, $_accent, $_lenition, $_view, $_order;
	private $_hits;

	/**
	 * corpus_search constructor.
	 * @param $params
	 * @param bool $fullSearch : a flag for switching to result set required for auto slip creation
	 */
	public function __construct($params, $fullSearch=true) {
		$this->_db = $this->_db ? $this->_db : new database();
		$this->_params = $params;
		$this->_init();
		if (!empty($this->getTerm())) {  //only run the search if there is a search term
			$this->_dbResults = $this->_getDBSearchResults($fullSearch);
		}
	}

	/**
	 * Sets the class properties
	 */
	private function _init() {
		$params = $this->_params;
		$this->_id          = isset($params["id"]) ? $params["id"] : null;
		$this->_term        = isset($params["term"]) ? $params["term"] : null;
		$this->_perpage     = isset($params["pp"]) ? $params["pp"] : 10;
		$this->_page        = isset($params["page"]) ? $params["page"] : 1;
		$this->_mode        = $params["mode"] == "wordform" ? "wordform" : "headword";
		$this->_case        = $params["case"];
		$this->_accent      = $params["accent"];
		$this->_lenition    = $params["lenition"];
		$this->_view        = (isset($params["view"])) ? $params["view"] : "corpus";
		$this->_order       = (isset($params["order"])) ? $params["order"] : "random";
	}

	// GETTERS

	public function getDBResults() {
		return $this->_dbResults;
	}

	public function getId() {
		return $this->_id;
	}

	public function getTerm() {
		return $this->_term;
	}

	public function getPerPage() {
		return $this->_perpage;
	}

	public function getPage() {
		return $this->_page;
	}

	public function getMode() {
		return $this->_mode;
	}

	public function getCase() {
		return $this->_case;
	}

	public function getAccent() {
		return $this->_accent;
	}

	public function getLenition() {
		return $this->_lenition;
	}

	public function getView() {
		return $this->_view;
	}

	public function getOrder() {
		return $this->_order;
	}

	/**
	 * Returns an array of search results
	 * -- If the view is "corpus" it returns the file results, otherwise it returns the
	 *  database results --
	 * @return array of results
	 */
	public function getResults() {
		if ($this->getView() == "corpus") {
			return $this->_getFileSearchResults();
		}
		return $this->_dbResults;
	}

	public function getHits() {
		return $this->_hits;
	}

	/**
	 * Processes the array of database results and searches through the XML corpus to get the context
	 * @return array of results
	 */
	private function _getFileSearchResults() {
		$fileResults = array();
		$filename = "";
		$fh = null; //an instance of xmlfilehandler
		$i = 0;
		foreach ($this->_dbResults as $result) {
			$id = $result["id"];
			if ($filename != $result["filename"]) { //check for the next file in the results list
				$filename = $result["filename"];
				$fh = new xmlfilehandler($filename);
			}
			$fileResults[$i]["context"] = $fh->getContext($id, 12, 12);
			$fileResults[$i]["id"] = $id;
			$fileResults[$i]["tid"] = $result["tid"];
			$fileResults[$i]["lemma"] = $result["lemma"];
			$fileResults[$i]["pos"] = $result["pos"];
			$fileResults[$i]["date_of_lang"] = $result["date_of_lang"];
			$fileResults[$i]["filename"] = $result["filename"];
			$fileResults[$i]["auto_id"] = $result["auto_id"];
			$fileResults[$i]["title"] = $result["title"];
			$fileResults[$i]["page"] = $result["page"];
			$fileResults[$i]["level"] = $result["level"];
			$fileResults[$i]["group_id"] = $result["group_id"];
			$i++;
		}
		return $fileResults;
	}

	/**
	 * Runs the query to get the corpus database result set
	 * Sets the number of hits in the results set
	 * @param $params: the array of parameters for the query, e.g. pp, page, order, mode, term
	 * @param bool $fullSearch : a flag for switching to result set required for auto slip creation
	 * @return array of database results
	 */
	public function _getDBSearchResults($fullSearch) {
		$params = $this->_params;
		$perpage = "";
		if ($fullSearch) {
			$perpage = $params["pp"];
			$pagenum = $params["page"];
			$offset = $pagenum == 1 ? 0 : ($perpage * $pagenum) - $perpage;
		} else {
			$params["term"] = urldecode($params["term"]); //need to decode if passed via JS encodeURI (auto create slips)
		}
		$searchPrefix = "[[:<:]]";  //default to word boundary at start for REGEXP
		$whereClause = "";
		switch ($params["order"]) {
			case "random":
				$orderBy = "RAND()";
				break;
			case "dateAsc":
				$orderBy = "date_of_lang ASC";
				break;
			case "dateDesc":
				$orderBy = "date_of_lang DESC";
				break;
			case "precedingWord":
				$orderBy = "preceding_word ASC";
				break;
			case "precedingWordReverse":
				$orderBy = "REVERSE(preceding_word) ASC";
				break;
			case "followingWord":
				$orderBy = "following_word ASC";
				break;
			default:
				$orderBy = "filename, id";
		}
		if ($params["mode"] != "wordform") {    //lemma query
			$query["search"] = $params["term"];
			$textJoinSql = "";
			if ($params["id"]) {    //restrict to this text
				$textJoinSql = <<<SQL
				 AND (t.id = '{$params["id"]}' OR t.id LIKE '{$params["id"]}-%')
SQL;
			}
			$writerJoinSql = "";
			if ($params["district"]) {
				if (count($params["district"]) < 15) {   //restrict by district (location)
					$writerJoinSql = <<<SQL
						JOIN text_writer tw ON t.id = tw.text_id
						JOIN writer w ON tw.writer_id = w.id
SQL;
				}
			}
			$whereClause = <<<SQL
				lemma REGEXP :term
SQL;
							//end lemma query build
		} else {                               //wordform query
			$search = $params["term"];
			if ($params["accent"] != "sensitive") {
				$search = functions::getAccentInsensitive($search, $params["case"] == "sensitive");
			}
			if ($params["lenition"] != "sensitive") {
				$search = functions::getLenited($search);
				$search = functions::addMutations($search);
			} else {
				//deal with h-, n-, t-
				$searchPrefix = "^";  //don't use word boundary at start of search, but start of string instead
			}
			if ($params["case"] == "sensitive") {   //case sensitive
				$whereClause = "wordform_bin REGEXP :term";
			} else {                              //case insensitive
				$whereClause = "wordform REGEXP :term";
			}
			$query["search"] = $search;
		}         //end wordform query build

		$query["sql"] = <<<SQL
			SELECT SQL_CALC_FOUND_ROWS l.filename AS filename, l.id AS id, wordform, pos, lemma, date_of_lang, l.title,
                page, medium, s.auto_id as auto_id, t.id AS tid, t.level as level, district_id,
               	preceding_word, following_word, e.group_id AS group_id
SQL;
		if (!$fullSearch) {
			//we need only the following fields for auto slip creation
			$query["sql"] = "SELECT s.auto_id AS auto_id, l.filename AS filename, l.id AS id, pos";
		}
		$query["sql"] .= <<<SQL
            FROM lemmas AS l
            LEFT JOIN slips s ON l.filename = s.filename AND l.id = s.id
						LEFT JOIN entry e ON e.id = s.entry_id 
            JOIN text t ON t.filepath = l.filename {$textJoinSql}
        		{$writerJoinSql}
            WHERE {$whereClause}

SQL;
		$query["search"] = $searchPrefix . $query["search"] . "[[:>:]]";  //word boundary
		$pdoParams = array(":term" => $query["search"]);    //params required to pass for the PDO DB query
		if ($params["selectedDates"]) {       //restrict by date
			$query["sql"] .= $this->_getDateWhereClause();
		}
		if ($params["level"]) {   //restrict by level ("importance")
			$query["sql"] .= $this->_getLevelWhereClause();
		}
		$query["sql"] .= $this->_getMediumWhereClause(); //restrict by medium
		if ($params["district"] && count($params["district"]) != 15) {
			$query["sql"] .= $this->_getDistrictWhereClause();  //restrict by district
		}
		if ($params["pos"][0] != "") {
			$query["sql"] .= $this->_getPOSWhereClause();  //restrict by POS
		}
		if ($params["district"]) {
			if (count($params["district"]) != 15) {   //restrict by district (location)
				$query["sql"] .= $this->_getDistrictWhereClause();
			}
		}
		if ($params["pw"] != "") {         //multi word search for preceding word
			$query["sql"] .= $params["preMode"] == "wordform"
				? " AND preceding_word REGEXP :pw"
				: " AND preceding_lemma REGEXP :pw";
			$pdoParams[":pw"] = "[[:<:]]" . $params["pw"] . "[[:>:]]";
		}
		if ($params["fw"] != "") {         //multi word search for following word
			$query["sql"] .= $params["postMode"] == "wordform"
				? " AND following_word REGEXP :fw"
				: " AND following_lemma REGEXP :fw";
			$pdoParams[":fw"] = "[[:<:]]" . $params["fw"] . "[[:>:]]";
		}

		$this->_queryAll = $query["sql"];
		$query["sql"] .= <<<SQL
        ORDER BY {$orderBy}
SQL;
		if ($perpage) {
			$query["sql"] .= <<<SQL
				LIMIT {$perpage} OFFSET {$offset}
SQL;
		}
		$results = $this->_db->fetch($query["sql"], $pdoParams);
		$hits = $this->_db->fetch("SELECT FOUND_ROWS() as hits;");
		$this->_hits = $hits[0]["hits"];
		return $results;
	}

	private function _getDateWhereClause() {
		$dates = explode('-', $this->_params["selectedDates"]);
		$whereClause = " AND date_of_lang >= {$dates[0]} AND date_of_lang <= {$dates[1]} ";
		return $whereClause;
	}

	private function _getLevelWhereClause() {
		$whereClause = "";
		if (!$this->_params["level"] || count($this->_params["level"]) == 3) {
			return $whereClause;    //don't bother with restrictions if all selected
		}
		$whereClause = " AND (";
		foreach ($this->_params["level"] as $level) {
			$levelString[] = " level = '{$level}' ";
		}
		$whereClause .= implode(" OR ", $levelString);
		$whereClause .= ") ";
		return $whereClause;
	}

	private function _getMediumWhereClause() {
		$whereClause = "";
		if (!$this->_params["medium"] || count($this->_params["medium"]) == 3) {
			return $whereClause;    //don't bother with restrictions if all selected
		}
		$whereClause = " AND (";
		foreach ($this->_params["medium"] as $medium) {
			$mediumString[] = " medium = '{$medium}' ";
		}
		$whereClause .= implode(" OR ", $mediumString);
		$whereClause .= ") ";
		return $whereClause;
	}

	private function _getDistrictWhereClause() {
		$whereClause = "";
		$whereClause = " AND (";
		foreach ($this->_params["district"] as $district) {
			$districtString[] = " district_id = '{$district}' ";
		}
		$whereClause .= implode(" OR ", $districtString);
		$whereClause .= ") ";
		return $whereClause;
	}

	private function _getPOSWhereClause() {
		$whereClause = " AND (";
		foreach ($this->_params["pos"] as $pos) {
			$posString[] = " BINARY pos REGEXP '{$pos}\$|{$pos}[[:space:]]' ";
		}
		$whereClause .= implode(" OR ", $posString);
		$whereClause .= ") ";
		return $whereClause;
	}

	//Retrieves the minimum and maximum dates of language in the database
	public static function getMinMaxDates() {
		$db = new database();
		$sql = <<<SQL
        SELECT MIN(date_of_lang) AS min, MAX(date_of_lang) AS max FROM lemmas
            WHERE date_of_lang != ''
SQL;
		$result = $db->fetch($sql, array());
		return $result[0];
	}

	/**
	 * Retrieves a list of distinct parts of speech
	 * @return array of distinct POS strings
	 */
	public static function getDistinctPOS() {
		$db = new database();
		$sql = <<<SQL
        SELECT DISTINCT BINARY pos FROM lemmas
            ORDER BY pos
SQL;
		$results = $db->fetch($sql);
		//parse out the extra POS info
		$distinctPOS = array();
		foreach ($results as $result) {
			if (in_array($result[0], $distinctPOS) || stristr($result[0], " ") || $result[0] == "") {
				continue;
			}
			$distinctPOS[] = $result[0];
		}
		return $distinctPOS;
	}

	/**
	 * Queries the database based on filename and ID to get data pertaining to a particular lemma
	 * @param $filename
	 * @param $id
	 * @return array of fields in the database
	 */
	public static function getDataById($filename, $id) {
		$db = new database();
		$sql = <<<SQL
			SELECT l.id as id, l.filename as filename, wordform, pos, lemma, date_of_lang, l.title, page, medium, s.auto_id as auto_id, 
			       e.wordclass as wordClass, t.id AS tid, t.level as level, district_id
            FROM lemmas AS l
            LEFT JOIN slips s ON l.filename = s.filename AND l.id = s.id
            LEFT JOIN entry e ON e.id = s.entry_id AND group_id = {$_SESSION["groupId"]}
            JOIN text t ON t.filepath = l.filename
            WHERE l.filename = :filename AND l.id = :id
SQL;
		$result = $db->fetch($sql, array(":filename" => $filename, ":id" => $id));
		return $result[0];
	}
}

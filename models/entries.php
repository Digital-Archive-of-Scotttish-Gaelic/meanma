<?php

namespace models;

class entries
{

	public static function getEntryByHeadwordAndWordclass($headword, $wordclass) {
		$db = new database();
		$sql = <<<SQL
        SELECT * FROM entry WHERE headword = :headword AND wordclass = :wordclass 
					AND group_id = :groupId
SQL;
		$result = $db->fetch($sql, array(":headword" => $headword, ":wordclass" => $wordclass, ":groupId" => $_SESSION["groupId"]));
		$entry = null;
		if ($result) {
			$result = $result[0];
			$entry = new entry($result["id"]);
			$entry->setGroupId($result["group_id"]);
			$entry->setHeadword($result["headword"]);
			$entry->setWordclass($result["wordclass"]);
			$entry->setNotes($result["notes"]);
			$entry->setUpdated($result["updated"]);
		} else {
			$entry = self::createEntry(array("groupId" => $_SESSION["groupId"], "headword" => $headword,
				"wordclass" => $wordclass, "notes" => ""));
		}
		return $entry;
	}

	public static function getEntryById($id, $db) {
//		$db = new database();
		$sql = <<<SQL
        SELECT * FROM entry WHERE id = :id 
SQL;
		$result = $db->fetch($sql, array(":id" => $id));
		if ($result) {
			$result = $result[0];
			$entry = new entry($id);
			$entry->setGroupId($result["group_id"]);
			$entry->setHeadword($result["headword"]);
			$entry->setWordclass($result["wordclass"]);
			$entry->setNotes($result["notes"]);
			$entry->setUpdated($result["updated"]);
			return $entry;
		} else {
			return false; //there is no entry with this ID
		}
	}

	public static function createEntry($params) {
		$db = new database();
		$sql = <<<SQL
        INSERT INTO entry (group_id, headword, wordclass, notes) 
        	VALUES (:groupId, :headword, :wordclass, :notes) 
SQL;
		$db->exec($sql, array(":groupId" => $params["groupId"], ":headword" => $params["headword"],
			":wordclass" => $params["wordclass"], ":notes" => $params["notes"]));
		$entryId = $db->getLastInsertId();
		$entry = new entry($entryId);
		return $entry;
	}

	public static function updateEntry($params) {
		$db = new database();
		$sql = <<<SQL
      UPDATE entry	 
        SET group_id = :groupId, headword = :headword, wordlcass = :wordclass, notes = :notes
				WHERE id = :id
SQL;
		$db->execute($sql, array(":group_id" => $params["groupId"], ":headword" => $params["headword"],
			":wordclass" => $params["wordclass"], ":notes" => $params["notes"], ":id" => $params["id"]));
		$entry = new entry($params["id"]);
		return $entry;
	}

  public static function getActiveEntryIds($db) {
    $entryIds = array();
//    $db = new database();
    //only get IDs for this group
    $sql = <<<SQL
        SELECT DISTINCT e.id as id FROM entry e    
        	JOIN slips s ON e.id = s.entry_id 
        	WHERE group_id = {$_SESSION["groupId"]}
            ORDER BY headword ASC
SQL;
    $results = $db->fetch($sql);
    foreach ($results as $row) {
      $entryIds[] = $row["id"];
    }
    return $entryIds;
  }

	/**
	 * Deletes an entry from the DB - !! should only be used on empty entries (with no slips) – see ::getIsEntryEmpty()
	 * @param $id : the entry ID
	 */
  public static function deleteEntry($id) {
		$db = new database();
		// delete senses for this entry
	  $sql = <<<SQL
			DELETE FROM sense WHERE entry_id = :id
SQL;
	  $db->exec($sql, array(":id" => $id));
	  // and delete the entry itself
		$sql = <<<SQL
			DELETE FROM entry WHERE id = :id
SQL;
		$db->exec($sql, array(":id" => $id));
  }

	/**
	 * Runs a DB check to see if an entry has no slips
	 * @param $id : the entry ID
	 * @return bool : true if the entry is empty
	 */
  public static function isEntryEmpty($id) {
  	$db = new database();
		$sql = <<<SQL
			SELECT count(*) as c FROM slips s WHERE entry_id = :id
SQL;
		$result = $db->fetch($sql, array(":id" => $id));
		$isEmpty = $result[0]["c"] == "0";
		return $isEmpty;
  }

  public static function addSenseIdsForEntry($entry, $db) {
//		$db = new database();
		$sql = <<<SQL
			SELECT se.id as id, auto_id AS slipId FROM sense se
					JOIN slip_sense ss ON ss.sense_id = se.id
					JOIN slips s ON s.auto_id = ss.slip_id
			    JOIN lemmas l ON l.id = s.id AND l.filename = s.filename
					JOIN entry e ON e.id = s.entry_id
        	WHERE group_id = {$_SESSION["groupId"]} AND e.id = :entryId
					ORDER BY date_of_lang
SQL;
		$results = $db->fetch($sql, array("entryId"=>$entry->getId()));
	  foreach ($results as $row) {
		  $sense = new sense($row["id"], $db);
		  $slipId = $row["slipId"];
		  $entry->addSense($sense, $slipId);
	  }
		return $entry;
  }

  public static function getWordformsForEntry($entryId, $db) {
  	$wordforms = array();
 // 	$db = new database();
  	$sql = <<<SQL
			SELECT l.wordform AS wordform, auto_id AS slipId
				FROM lemmas l 
				JOIN slips s ON s.id = l.id AND s.filename = l.filename
				JOIN entry e ON e.id = s.entry_id
				WHERE e.id = :entryId
				ORDER BY date_of_lang
SQL;
  	$results = $db->fetch($sql, array(":entryId"=>$entryId));
  	foreach ($results as $row) {
  		$wordform = mb_strtolower($row["wordform"], "UTF-8");
  		$slipId = $row["slipId"];

		  $slipMorphResults = collection::getSlipMorphBySlipId($slipId, $db);

		  $morphString = implode('|', $slipMorphResults);

  		$wordforms[$wordform][$morphString][] = $slipId;
	  }
  	return $wordforms;
  }
}
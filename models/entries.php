<?php

namespace models;

class entries
{

	public static function getEntryByHeadwordAndWordclass($headword, $wordclass, $db) {
		$sql = <<<SQL
        SELECT * FROM entry WHERE headword = :headword AND wordclass = :wordclass 
					AND group_id = :groupId
SQL;
		$result = $db->fetch($sql, array(":headword" => $headword, ":wordclass" => $wordclass, ":groupId" => $_SESSION["groupId"]));
		$entry = null;
		if (!empty($result)) {
			$result = $result[0];
			$entry = new entry($result["id"]);
			$entry->setGroupId($result["group_id"]);
			$entry->setHeadword($result["headword"]);
			$entry->setWordclass($result["wordclass"]);
			$entry->setNotes($result["notes"]);
			$entry->setUpdated($result["updated"]);
		} else {
			$entry = self::createEntry(array("groupId" => $_SESSION["groupId"], "headword" => $headword,
				"wordclass" => $wordclass), $db);
		}
		return $entry;
	}

	public static function getEntryById($id, $db) {
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
			$entry->setSubclass($result["subclass"]);
			$entry->setEtymology($result["etymology"]);
			$entry->setUpdated($result["updated"]);
			return $entry;
		} else {
			return false; //there is no entry with this ID
		}
	}

	public static function createEntry($params, $db) {
		$sql = <<<SQL
        INSERT INTO entry (group_id, headword, wordclass) 
        	VALUES (:groupId, :headword, :wordclass) 
SQL;
		$db->exec($sql, array(":groupId" => $params["groupId"], ":headword" => $params["headword"],
			":wordclass" => $params["wordclass"]));
		$entryId = $db->getLastInsertId();
		$entry = new entry($entryId);
		return $entry;
	}

  public static function getActiveEntryIds($db) {
    $entryIds = array();
    //only get IDs for this group
    $sql = <<<SQL
        SELECT DISTINCT e.id as id, headword FROM entry e    
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
  public static function deleteEntry($id, $db) {
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
   * Deletes an entry and all associated slips from the DB
   * @param array $ids : the entry IDs to be deleted
   * @param database $db : database object
   */
  public static function deleteEntries($ids, $db) {
		foreach ($ids as $id) {
			$slipIds = self::getSlipIdsForEntry($id, $db);
			collection::deleteSlips($slipIds, $db);
			self::deleteEntry($id);
		}
		return $slipIds;
  }

	/**
	 * @param $id : entry ID
	 * @param database $db : database object
	 * @return array : slip IDs
	 */
  public static function getSlipIdsForEntry($id, $db) {
  	$slipIds = array();
		$sql = <<<SQL
			SELECT auto_id FROM slips WHERE entry_id = :eid
SQL;
		$result = $db->fetch($sql, array(":eid" => $id));
		foreach ($result as $row) {
			$slipIds[] = $row["auto_id"];
		}
  	return $slipIds;
  }

  /**
	 * Runs a DB check to see if an entry has no slips
	 * @param $id : the entry ID
	 * @return bool : true if the entry is empty
	 */
  public static function isEntryEmpty($id, $db) {
		$sql = <<<SQL
			SELECT count(*) as c FROM slips s WHERE entry_id = :id
SQL;
		$result = $db->fetch($sql, array(":id" => $id));
		$isEmpty = $result[0]["c"] == 0;
		return $isEmpty;
  }

  public static function addPileIdsForEntry($entry, $db) {
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
		  $pile = new pile($row["id"], $db);
		  $slipId = $row["slipId"];
		  $entry->addPile($pile, $slipId);
	  }
	  //query for paper slips -
	  $sql2 = <<<SQL
			SELECT se.id as id, auto_id AS slipId FROM sense se
					JOIN slip_sense ss ON ss.sense_id = se.id
					JOIN slips s ON s.auto_id = ss.slip_id
					JOIN entry e ON e.id = s.entry_id
        	WHERE filename = '' AND group_id = {$_SESSION["groupId"]} AND e.id = :entryId
SQL;
	  $results2 = $db->fetch($sql2, array("entryId"=>$entry->getId()));
	  foreach ($results2 as $row) {
		  $pile = new pile($row["id"], $db);
		  $slipId = $row["slipId"];
		  $entry->addPile($pile, $slipId);
	  }
		return $entry;
  }

}
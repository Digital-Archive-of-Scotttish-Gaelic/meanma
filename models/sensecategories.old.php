<?php

namespace models;

class sensecategories
{
  public static function saveCategory($slipId, $catName)
  {
    $db = new database();
    $dbh = $db->getDatabaseHandle();
    try {
      $sth = $dbh->prepare("INSERT INTO senseCategory VALUES(:slip_id, :cat_name)");
      $sth->execute(array(":slip_id" => $slipId, ":cat_name" => $catName));
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

  public static function deleteCategory($slipId, $catName)
  {
    $db = new database();
    $dbh = $db->getDatabaseHandle();
    try {
      $sth = $dbh->prepare("DELETE FROM senseCategory WHERE slip_id = :slip_id AND category = :cat_name");
      $sth->execute(array(":slip_id" => $slipId, ":cat_name" => $catName));
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

  /**
   * Fetches all the categories not used by the given slip
   * @param $slipId
   * @return array
   */
  public static function getAllUnusedCategories($slipId, $lemma, $wordclass) {
    $categories = array();
    $db = new database();
    $dbh = $db->getDatabaseHandle();
    try {
      $sql = <<<SQL
        SELECT DISTINCT category FROM senseCategory sc
        JOIN slips s ON s.auto_id = sc.slip_id
        JOIN lemmas l ON s.filename = l.filename AND s.id = l.id
        WHERE s.group_id = {$_SESSION["groupId"]} AND lemma = :lemma AND wordclass = :wordclass
            AND slip_id != :slip_id
            ORDER BY category ASC
SQL;
      $sth = $dbh->prepare($sql);
      $sth->execute(array(":slip_id" => $slipId, ":lemma"=>$lemma, ":wordclass"=>$wordclass));
      while ($row = $sth->fetch()) {
        $categories[] = $row["category"];
      }
      return $categories;
    } catch (PDOException $e) {
      echo $e->getMessage();
    }
  }

	/**
	 * Fetches all the categories used for a given lemma/wordclass combination
	 * @param $slipId
	 * @return array
	 */
	public static function getAllUsedCategories($lemma, $wordclass) {
		$categories = array();
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        SELECT DISTINCT category FROM senseCategory sc
        	JOIN slips s ON s.auto_id = sc.slip_id 
        	JOIN lemmas l ON s.filename = l.filename AND s.id = l.id
        	WHERE s.group_id = {$_SESSION["groupId"]} AND lemma = :lemma AND wordclass = :wordclass
            ORDER BY category ASC
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":lemma"=>$lemma, ":wordclass"=>$wordclass));
			while ($row = $sth->fetch()) {
				$categories[] = $row["category"];
			}
			return $categories;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Fetches all the slipIds without a sense for a given lemma/wordclass combination
	 * @param $lemma
	 * @param $wordclass
	 * @return array of slipIds
	 */
	public static function getNonCategorisedSlipIds($lemma, $wordclass) {
		$slipIds = array();
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        SELECT auto_id FROM slips s 
        	JOIN lemmas l ON s.filename = l.filename AND s.id = l.id 
        	WHERE auto_id NOT IN (SELECT slip_id FROM senseCategory) AND lemma = :lemma AND wordclass= :wordclass 
        	AND group_id = {$_SESSION["groupId"]}
        	ORDER by auto_id ASC
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":lemma"=>$lemma, ":wordclass"=>$wordclass));
			while ($row = $sth->fetch()) {
				$slipIds[] = $row["auto_id"];
			}
			return $slipIds;
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Renames a sense category
	 * @param $lemma
	 * @param $wordclass
	 * @param $oldName
	 * @param $newName
	 */
	public static function renameSense($lemma, $wordclass, $oldName, $newName) {
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        UPDATE senseCategory sc
        JOIN slips s ON s.auto_id = sc.slip_id AND group_id = {$_SESSION["groupId"]}
        JOIN lemmas l ON s.filename = l.filename AND s.id = l.id
        SET category = :newName WHERE category = :oldName
        AND lemma = :lemma AND wordclass = :wordclass
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":lemma"=>$lemma, ":wordclass"=>$wordclass,
				":newName" => $newName, ":oldName" => $oldName));
		} catch (PDOException $e) {
			echo $e->getMessage();
		}
	}
}
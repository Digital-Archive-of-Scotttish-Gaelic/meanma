<?php

namespace models;

class collection
{
  /**
   * Get the slip info required for a browse table from the DB
   *
   * @return array of DB results
   */
  public static function getAllSlipInfo($offset = 0, $limit = 10, $search = "", $sort = "date_of_lang", $order = "ASC") {
  	$sort = empty($sort) ? "date_of_lang" : $sort;
  	$order = empty($order) ? "ASC" : $order;
  	if (stristr("'", $sort) || stristr('"', $sort)) {
  		echo json_encode(array("error" => "invalid sort param"));
  		return false;   //possible attack
	  }
  	if ($order != "ASC" AND $order != "DESC") {
		  echo json_encode(array("error" => "invalid order param"));
  		return false;   //possible attack
	  }
  	$params = array(":limit" => (int)$limit, ":offset" => (int)$offset);
    $db = new database();
    $dbh = $db->getDatabaseHandle();
    try {
			$whereClause = "WHERE (group_id = {$_SESSION["groupId"]}) ";
			if (mb_strlen($search) > 1) {     //there is a search to run
				$sth = $dbh->prepare("SET @search = :search");  //set a MySQL variable for the searchterm
				$sth->execute(array(":search" => "%{$search}%"));
				$whereClause .= <<<SQL
					AND (auto_id LIKE @search	
            	OR lemma LIKE @search
            	OR wordform LIKE @search
            	OR lemma LIKE @search
            	OR firstname LIKE @search
            	OR lastname LIKE @search)
SQL;
			}
	    $dbh->setAttribute( \PDO::ATTR_EMULATE_PREPARES, false );
	    $sql = <<<SQL
        SELECT SQL_CALC_FOUND_ROWS s.filename as filename, s.id as id, auto_id, pos, lemma, wordform, firstname, lastname,
                date_of_lang, title, page, CONCAT(firstname, ' ', lastname) as fullname, locked,
             		l.pos as pos, s.lastUpdated as lastUpdated, updatedBy, wordclass, e.headword as headword
            FROM slips s
            JOIN lemmas l ON s.filename = l.filename AND s.id = l.id
            JOIN entry e ON e.id = s.entry_id
            LEFT JOIN user u ON u.email = s.ownedBy
            {$whereClause}
            ORDER BY {$sort} {$order}
            LIMIT :limit OFFSET :offset;
SQL;
			$sth = $dbh->prepare($sql);
      $sth->execute($params);
      $rows = $sth->fetchAll(\PDO::FETCH_ASSOC);
      $hits = $db->fetch("SELECT FOUND_ROWS() as hits;");
      foreach ($rows as $index => $slip) {
      	$slipId = $slip["auto_id"];
      	//get the categories
	      $sql = <<<SQL
					SELECT name, description, se.id as senseId
						FROM sense se
						LEFT JOIN slip_sense ss ON ss.sense_id = se.id
						WHERE slip_id = :slipId
SQL;
	      $senseRows = $db->fetch($sql, array(":slipId" => $slipId));
	      foreach ($senseRows as $sense) {
	      	$rows[$index]["senses"] .= <<<HTML
						<span class="badge badge-success senseBadge" data-slip-id="{$slipId}" data-sense="  {$sense["senseId"]}"
							data-toggle="modal" data-target="#senseModal" data-sense-description="{$sense["description"]}"
							data-title="{$sense["description"]}" data-sense-name="{$sense["name"]}">
							{$sense["name"]}</span>
HTML;
	      }

	      //get the morph data
	      $sql = <<<SQL
					SELECT value
						FROM slipMorph sm
						LEFT JOIN slips s ON sm.slip_id = auto_id
						WHERE slip_id = :slipId
SQL;
	      $morphRows = $db->fetch($sql, array(":slipId" => $slipId));
	      foreach ($morphRows as $morph) {
		      $rows[$index]["morph"] .= '<span class="badge badge-secondary">' . $morph["value"] . '</span> ';
	      }
	      $checked = in_array($slipId, $_SESSION["printSlips"]) ? "checked" : "";
				$rows[$index]["printSlip"] = <<<HTML
					<input type="checkbox" class="chooseSlip" {$checked} id="printSlip_{$slipId}"> 
HTML;
				$rows[$index]["deleteSlip"] = <<<HTML
					<input type="checkbox" class="markToDelete" id="deleteSlip_{$slipId}"> 
HTML;
				$headword = $slip["headword"] ? $slip["headword"] : $slip["lemma"]; //if there is an entry then use its hw
	                                                                    //otherwise use the default DB lemma
      	//create the slip link code
	      $slipUrl = <<<HTML
                <a href="#" class="slipLink2"
                    data-toggle="modal" data-target="#slipModal"
                    data-auto_id="{$slip["auto_id"]}"
                    data-headword="{$headword}"
                    data-pos="{$slip["pos"]}"
                    data-id="{$slip["id"]}"
                    data-xml="{$slip["filename"]}"
                    data-uri="{$slip["uri"]}"
                    data-date="{$slip["date_of_lang"]}"
                    data-title="{$slip["title"]}"
                    data-page="{$slip["page"]}"
                    data-resultindex="-1"
                    title="view slip {$slip["auto_id"]}">
                    {$slip["auto_id"]}
                </a>
HTML;
	      $rows[$index]["auto_id"] = $slipUrl;
      }
      return array("total"=>(int)$hits[0]["hits"], "totalNotFiltered"=>count($rows), "rows"=>$rows);
    } catch (\PDOException $e) {
      echo $e->getMessage();
    }
  }

  public static function slipExists($groupId, $filename, $id) {
	  $db = new database();
	  $dbh = $db->getDatabaseHandle();
	  try {
		  $sql = <<<SQL
        SELECT auto_id FROM slips s
        	JOIN entry e ON s.entry_id = e.id
        	WHERE e.group_id = :groupId AND s.filename = :filename AND s.id = :id
SQL;
		  $sth = $dbh->prepare($sql);
		  $sth->execute(array(":groupId"=>$groupId, ":filename"=>$filename, ":id"=>$id));
		  $row = $sth->fetch();
		  if ($row["auto_id"]) {
			  return $row["auto_id"];
		  } else {
		  	return false;
		  }
	  } catch (\PDOException $e) {
		  echo $e->getMessage();
	  }
  }

	/**
	 * Gets slip info from the DB
	 * @param $slipId
	 * @param $db the current models\database object
	 * @param $groupId a workspace/group ID passed through an API call
	 * @return array of DB results
	 */
	public static function getSlipInfoBySlipId($slipId, $db, $groupId = null) {
		if ($groupId) {
			$_SESSION["groupId"] = $groupId; //used in API calls to Meanma for other apps (such as briathradan)
		}
		$slipInfo = array();
		$sql = <<<SQL
      SELECT s.filename as filename, s.id as id, auto_id, pos, lemma,
              date_of_lang, l.title AS title, page, starred, t.id AS tid, entry_id, 
              e.headword AS headword
          FROM slips s
          JOIN entry e ON e.id = s.entry_id
          JOIN lemmas l ON s.filename = l.filename AND s.id = l.id
          JOIN text t ON s.filename = t.filepath
          WHERE group_id = {$_SESSION["groupId"]} AND s.auto_id = :slipId
          ORDER BY auto_id ASC
SQL;

		$slipInfo = $db->fetch($sql, array(":slipId" => $slipId));
		return $slipInfo;
	}

	/**
	 * Gets slip info from the DB
	 * @param $slipId
	 * @param $db the current models\database object
	 * @return slip object
	 */
	public static function getSlipBySlipId($slipId, $db) {
		$sql = <<<SQL
      SELECT s.filename AS filename, s.id AS wid, pos
        FROM slips s
      	JOIN lemmas l ON s.filename = l.filename AND s.id = l.id 
        WHERE auto_id = :slipId
SQL;
		$result = $db->fetch($sql, array(":slipId" => $slipId));
		$row = $result[0];
		return new slip($row["filename"], $row["wid"], $slipId, $row["pos"]);
	}

	public static function getWordformBySlipId($slipId) {
		$db = new database();
		$sql = <<<SQL
			SELECT wordform FROM lemmas l
				JOIN slips s ON s.filename = l.filename AND s.id = l.id
				WHERE s.auto_id = :slipId
SQL;
		$results = $db->fetch($sql, array(":slipId"=>$slipId));
		return $results[0]["wordform"];
	}


	public static function deleteSlips($slipIds) {
		$db = new database();
		foreach ($slipIds as $slipId) {
			$slipInfo = self::getSlipInfoBySlipId($slipId, $db);
			$entryId = $slipInfo[0]["entry_id"];
			// delete morpho info for this slip
			$sql = <<<SQL
    		DELETE FROM slipMorph WHERE slip_id = :slipId
SQL;
			$db->exec($sql, array(":slipId" => $slipId));
			// delete sense categories for this slip
			sensecategories::deleteSensesForSlip($slipId);
			// delete the slip itself
			$sql = <<<SQL
    		DELETE FROM slips WHERE auto_id = :slipId
SQL;
			$db->exec($sql, array(":slipId" => $slipId));
			// check the entry for this slip and delete if now empty
			if (entries::isEntryEmpty($entryId)) {
				entries::deleteEntry($entryId);
			}
		}
	}

	/**
	 * Gets morph info from the DB to populate an Entry with data required for citations
	 * @param $slipId
	 * @return array of DB results
	 */
	public static function getSlipMorphBySlipId($slipId, $db) {
		$morphInfo = array();
//		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        SELECT relation, value
        	FROM slipMorph
        	WHERE slip_id = :slipId
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":slipId"=>$slipId));
			while ($row = $sth->fetch()) {
				$morphInfo[$row["relation"]] = $row["value"];
			}
			return $morphInfo;
		} catch (\PDOException $e) {
			echo $e->getMessage();
		}
	}

	/**
	 * Gets slip info from the DB to populate an Entry with data required for citations
	 * @param $lemma
	 * @param $wordclass
	 * @param $category : the sense category
	 * @return array of DB results
	 */
	/*public static function getSlipsBySenseCategory($lemma, $wordclass, $category) {
		$slipInfo = array();
		$db = new database();
		$dbh = $db->getDatabaseHandle();
		try {
			$sql = <<<SQL
        SELECT s.filename as filename, s.id as id, auto_id, pos, lemma, preContextScope, postContextScope,
                translation, wordform, date_of_lang, title, page
            FROM slips s
            JOIN lemmas l ON s.filename = l.filename AND s.id = l.id
            JOIN senseCategory sc on sc.slip_id = auto_id
            WHERE group_id = {$_SESSION["groupId"]} AND lemma = :lemma AND wordclass = :wordclass AND sc.category = :category 
            ORDER BY auto_id ASC
SQL;
			$sth = $dbh->prepare($sql);
			$sth->execute(array(":lemma"=>$lemma, ":wordclass"=>$wordclass, ":category"=>$category));
			while ($row = $sth->fetch()) {
				$slipInfo[] = $row;
			}
			return $slipInfo;
		} catch (\PDOException $e) {
			echo $e->getMessage();
		}
	}*/

	/**
	 * Sends an email to slip owner to request a slip unlock
	 * @param $slipId
	 */
	public static function requestUnlock($slipId) {
		$superusers = users::getAllSuperusers();
		$user = users::getUser($_SESSION["user"]);
		$slip = self::getSlipInfoBySlipId($slipId)[0];
		$editUrl = "https://dasg.ac.uk/gadelica/corpas/code/index.php?m=collection&a=edit";
		$editUrl .= <<<HTML
			&filename={$slip["filename"]}&wid={$slip["id"]}&headword={$slip["lemma"]}&pos={$slip["pos"]}&id={$slipId}
HTML;
		foreach ($superusers as $superuser) {
			$emailText = <<<HTML
				<p>Dear {$superuser->getFirstName()},</p>
				<p>{$user->getFirstName()} {$user->getLastName()} has requested that slip #{$slipId} be unlocked.</p>
				<p>You can view and update the slip <a href="{$editUrl}">here</a></p>
				<p>If you have received this email in error or have any other queries please contact <a title="Email DASG" href="mailto:mail@dasg.ac.uk">mail@dasg.ac.uk</a>.</p>	
				<p>Kind regards</p>
				<p>The DASG team</p>
HTML;
			$email = new email($superuser->getEmail(), "Slip Unlock Request", $emailText, "mail@dasg.ac.uk");
			$email->send();
		}
	}

  /**
   * Updates user and date columns
   */
  public static function touchSlip($slipId) {
    $db = new database();
    $sql = <<<SQL
    	UPDATE slips SET updatedBy = :user WHERE auto_id = :slipId
SQL;
    $db->exec($sql, array(":user"=>$_SESSION["user"], ":slipId"=>$slipId));
  }

  public static function writeSlipDiv() {
    echo <<<HTML
        <div class="modal fade" id="slipModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <span class="text-muted" style="float:right;" id="slipNo">§</span>
              </div>
              <div class="modal-body">
              </div>
              <div class="modal-footer">
                <a id="lockedBtn" data-toggle="tooltip" data-owner="" data-slipid="" title="Slip is locked - click to request unlock" class="d-none lockBtn locked btn btn-large btn-danger" href="#">
                  <i class="fa fa-lock" aria-hidden="true"></i></a>
                <a data-toggle="tooltip" title="Slip is unlocked" class="d-none lockBtn unlocked btn btn-large btn-success" href="#">
                  <i class="fa fa-unlock" aria-hidden="true"></i></a>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                <button type="button" id="editSlip" class="btn btn-primary">edit</button>
                <input type="hidden" id="slipFilename">
                <input type="hidden" id="slipId">
                <input type="hidden" id="auto_id">
                <input type="hidden" id="slipPOS">
              </div>
            </div>
          </div>
        </div>

HTML;
  }

  public static function getSlipLinkHtml($data, $index = null) {
	  $slipUrl = "#";
	  $slipClass = "slipLink2";
	  $modalCode = "";
	  $slipId = self::slipExists($_SESSION["groupId"], $data["filename"], $data["id"]);  //check if there is a slip for this group
	  if ($slipId) {
		  $slipLinkText = "view";
		  $createSlipStyle = "";
		  $modalCode = 'data-toggle="modal" data-target="#slipModal"';
		  $dataUrl = "";
	  } else {    //there is no slip so show link for adding one
		  $dataUrl = "index.php?m=collection&a=add&filename=" . $data["filename"] . "&wid=".$data["id"];
		  $dataUrl .= "&headword=" . $data["lemma"] . "&pos=" . $data["pos"];
		  $slipLinkText = "add";
		  $createSlipStyle = "createSlipLink";
		  $slipClass = "editSlipLink";
	  }
	  $html = <<<HTML
        <a href="{$slipUrl}" data-url="{$dataUrl}" class="{$slipClass} {$createSlipStyle}"
            {$modalCode}
            data-auto_id="{$slipId}"
            data-headword="{$data["headword"]}"
            data-pos="{$data["pos"]}"
            data-id="{$data["id"]}"
            data-xml="{$data["filename"]}"
            data-uri="{$data["context"]["uri"]}"
            data-date="{$data["date_of_lang"]}"
            data-page="{$data["page"]}"
            data-resultindex="{$index}">
            {$slipLinkText}
        </a>
HTML;
  	return $html;
  }
}
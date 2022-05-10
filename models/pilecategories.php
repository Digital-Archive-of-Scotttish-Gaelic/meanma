<?php

namespace models;

class pilecategories
{
	/**
	 * Adds a new pile entry to the database and returns its ID
	 * @param $name
	 * @param $description
	 * @param $entryId
	 * @return string : the ID of the newly created pile
	 */
  public static function addPile($name, $description, $entryId, $db) {
    $sql = <<<SQL
			INSERT INTO sense(name, description, entry_id)
				VALUES (:name, :description, :entryId)
SQL;
    $db->exec($sql, array(":name"=>$name, ":description"=>$description, ":entryId" => $entryId));
    return $db->getLastInsertId();
  }

	/**
	 * Deletes a pile from the database and removes all its associated slip references also
	 * @param $id : the pile ID
	 */
  public static function deletePile($id, $db) {
    $db->exec("DELETE FROM sense WHERE id = :id", array(":id" => $id));
    $db->exec("DELETE FROM slip_sense WHERE sense_id = :id", array(":id" => $id));
  }

  public static function deletePilesForSlip($slipId) {
  	$db = new database();
  	$db->exec("DELETE FROM slip_sense WHERE slip_id = :slipId", array("slipId" => $slipId));
  }

	/**
	 * Adds a record to the slip_sense table matching a slip to a pile
	 * @param $slipId
	 * @param $pileId
	 */
  public static function saveSlipPile($slipId, $pileId, $db) {
	  $sql = "INSERT INTO slip_sense VALUES(:slipId, :pileId)";
	  $db->exec($sql, array(":slipId" => $slipId, ":pileId" => $pileId));
  }

	/**
	 * Removes a record in the slip_sense table
	 * @param $slipId
	 * @param $pileId
	 */
	public static function deleteSlipPile($slipId, $pileId, $db) {
		$sql = "DELETE FROM slip_sense WHERE slip_id = :slipId AND sense_id = :pileId";
		$db->exec($sql, array(":slipId" => $slipId, ":pileId" => $pileId));
		if (self::getPileIsOrphaned($pileId, $db)) {
			self::deletePile($pileId, $db);       //delete the pile if it's an orphan
		}
	}

	public static function getPileIsOrphaned($id, $db) {
		$sql = "SELECT * FROM slip_sense WHERE sense_id = :pileId";
		$result = $db->fetch($sql, array(":pileId" => $id));
		return empty($result);
	}

	/**
	 * Updates a pile record
	 * @param $id
	 * @param $name
	 * @param $description
	 * @param $db
	 */
	public static function updatePile($id, $name, $description, $db) {
		$sql = <<<SQL
				UPDATE sense SET name = :name, description = :description WHERE id = :id
SQL;
		$db->exec($sql, array(":id" => $id, ":name" => $name, ":description" => $description));
	}

	/**
	 * Fetches all the slipIds without a pile for a given entry
	 * @param $entryId
	 * @return array of slipIds
	 */
	public static function getNonCategorisedSlipIds($entryId, $db) {
		$slipIds = array();
		$sql2 = <<<SQL
			SELECT auto_id FROM slips s 
				JOIN entry e ON e.id = s.entry_id
				JOIN text t ON t.id = s.text_id 
				WHERE auto_id NOT IN (SELECT slip_id FROM slip_sense) AND s.entry_id = :entryId 		  
        	AND group_id = {$_SESSION["groupId"]}
        	ORDER BY date ASC
SQL;
		$results2 = $db->fetch($sql2, array(":entryId"=>$entryId));
		foreach ($results2 as $row) {
			$slipIds[] = $row["auto_id"];
		}
		return $slipIds;
	}

	public static function writePileModal() {
		echo <<<HTML
			<div class="modal fade" id="pileModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Pile</h5>
              </div>
              <div class="modal-body">
								<div class="form-group row">							
	                <label class="col-sm-3" for="newPileName">Name:</label>
	                <input class="col=sm-7" type="text" size="40" id="modalPileName" name="modalPileName">
                </div>
                <div class="form-group row">
                  <label class="col-sm-3" for="modalPileDescription">Description:</label>
                  <textarea class="col-sm-8" id="modalPileDescription" name="modalPileDescription" cols="100" rows="6">                  
                  </textarea>
                </div>
                <div id="modalSlipRemoveSection" class="form-group row">
                  <label class="col-sm-3" for="modalPileSlipRemove">Remove from §<span id="modalSlipIdDisplay"></span></label>
                  <input type="checkbox" id="modalPileSlipRemove" name="modalPileSlipRemove">
                  <input type="hidden" id="modalSlipId" name="modalSlipId">
								</div>
                <input type="hidden" name="pileId" id="pileId">
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                <button type="button" id="editPile" class="btn btn-primary">save</button>
              </div>
            </div>
          </div>
        </div>
HTML;
	}
}
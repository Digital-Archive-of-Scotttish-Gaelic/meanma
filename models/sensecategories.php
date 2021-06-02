<?php

namespace models;

class sensecategories
{
	/**
	 * Adds a new sense entry to the database and returns its ID
	 * @param $name
	 * @param $description
	 * @param $entryId
	 * @return string : the ID of the newly created sense
	 */
  public static function addSense($name, $description, $entryId) {
    $db = new database();
    $sql = <<<SQL
			INSERT INTO sense(name, description, entry_id)
				VALUES (:name, :description, :entryId)
SQL;
    $db->exec($sql, array(":name"=>$name, ":description"=>$description, ":entryId" => $entryId));
    return $db->getLastInsertId();
  }

	/**
	 * Deletes a sense from the database and removes all its associated slip references also
	 * @param $id : the sense ID
	 */
  public static function deleteSense($id) {
    $db = new database();
    $db->exec("DELETE FROM sense WHERE id = :id", array(":id" => $id));
    $db->exec("DELETE FROM slip_sense WHERE sense_id = :id", array(":id" => $id));
  }

  public static function deleteSensesForSlip($slipId) {
  	$db = new database();
  	$db->exec("DELETE FROM slip_sense WHERE slip_id = :slipId", array("slipId" => $slipId));
  }

	/**
	 * Adds a record to the slip_sense table matching a slip to a sense
	 * @param $slipId
	 * @param $senseId
	 */
  public static function saveSlipSense($slipId, $senseId) {
	  $db = new database();
	  $sql = "INSERT INTO slip_sense VALUES(:slipId, :senseId)";
	  $db->exec($sql, array(":slipId" => $slipId, ":senseId" => $senseId));
  }

	/**
	 * Removes a record in the slip_sense table
	 * @param $slipId
	 * @param $senseId
	 */
	public static function deleteSlipSense($slipId, $senseId) {
		$db = new database();
		$sql = "DELETE FROM slip_sense WHERE slip_id = :slipId AND sense_id = :senseId";
		$db->exec($sql, array(":slipId" => $slipId, ":senseId" => $senseId));
	}

	/**
	 * Updates a sense record
	 * @param $id
	 * @param $name
	 * @param $description
	 */
	public static function updateSense($id, $name, $description) {
		$db = new database();
		$sql = <<<SQL
				UPDATE sense SET name = :name, description = :description WHERE id = :id
SQL;
		$db->exec($sql, array(":id" => $id, ":name" => $name, ":description" => $description));
	}

	/**
	 * Fetches all the slipIds without a sense for a given entry
	 * @param $entryId
	 * @return array of slipIds
	 */
	public static function getNonCategorisedSlipIds($entryId) {
		$slipIds = array();
		$db = new database();
		$sql = <<<SQL
        SELECT auto_id FROM slips s 
        	JOIN entry e ON e.id = s.entry_id
        	WHERE auto_id NOT IN (SELECT slip_id FROM slip_sense) AND s.entry_id = :entryId 
        	AND group_id = {$_SESSION["groupId"]}
        	ORDER by auto_id ASC
SQL;
		$results = $db->fetch($sql, array(":entryId"=>$entryId));
		foreach ($results as $row) {
			$slipIds[] = $row["auto_id"];
		}
		return $slipIds;
	}

	public static function writeSenseModal() {
		echo <<<HTML
			<div class="modal fade" id="senseModal" tabindex="-1" role="dialog">
          <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Edit Sense</h5>
              </div>
              <div class="modal-body">
								<div class="form-group row">							
	                <label class="col-sm-3" for="newSenseName">Name:</label>
	                <input class="col=sm-7" type="text" size="40" id="modalSenseName" name="modalSenseName">
                </div>
                <div class="form-group row">
                  <label class="col-sm-3" for="modalSenseDescription">Description:</label>
                  <textarea class="col-sm-8" id="modalSenseDescription" name="modalSenseDescription" cols="100" rows="6">                  
                  </textarea>
                </div>
                <div id="modalSlipRemoveSection" class="form-group row">
                  <label class="col-sm-3" for="modalSenseSlipRemove">Remove from §<span id="modalSlipIdDisplay"></span></label>
                  <input type="checkbox" id="modalSenseSlipRemove" name="modalSenseSlipRemove">
                  <input type="hidden" id="modalSlipId" name="modalSlipId">
								</div>
                <input type="hidden" name="senseId" id="senseId">
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">close</button>
                <button type="button" id="editSense" class="btn btn-primary">save</button>
              </div>
            </div>
          </div>
        </div>
HTML;
	}
}
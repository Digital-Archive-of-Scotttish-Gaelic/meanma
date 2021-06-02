<?php


namespace models;


class districts
{
	/**
	 * Queries the database for district info
	 * @return array of results
	 */
	public static function getAllDistrictsInfo() {
		$db = new database();
		$sql = <<<SQL
			SELECT * FROM districts ORDER BY id ASC
SQL;
		$results = $db->fetch($sql);
		return $results;
	}

	/**
	 * Gets the writer info associated with a district
	 * @param int $districtId
	 * @return array of results
	 */
	public static function getWritersInfoForDistrict($districtId) {
		$db = new database();
		$sql = <<<SQL
			SELECT * FROM writer  
				WHERE district_1_id = :id OR district_2_id = :id
SQL;
		$results = $db->fetch($sql, array(":id"=>$districtId));
		return $results;
	}
}
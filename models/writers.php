<?php

namespace models;

class writers
{

	/**
	 * Queries the database for writer info
	 * @return array of results
	 */
	public static function getAllWritersInfo() {
		$db = new database();
		$sql = <<<SQL
			SELECT w.id as id, surname_gd, forenames_gd, surname_en, forenames_en, preferred_name, title,
					nickname, yob, yod, w.notes, district_1_id, district_2_id, d.name as district1 FROM writer w
					JOIN districts d ON district_1_id = d.id
					ORDER BY surname_en ASC
SQL;
		$results = $db->fetch($sql);
		return $results;
	}

	/**
	 * Updates an existing writer record in the database or adds a new one if required
	 * @param $data the form data for the writer record
	 */
	public static function save($data) {
		$db = new database();
		//set null values if empty
		$data["district_1_id"] = $data["district_1_id"] == "" ? null : $data["district_1_id"];
		$data["district_2_id"] = $data["district_2_id"] == "" ? null : $data["district_2_id"];
		$sql = <<<SQL
			REPLACE INTO writer (id, surname_gd, forenames_gd, surname_en, forenames_en, preferred_name, title,
					nickname, yob, yod, district_1_id, district_2_id, notes)
				VALUES(:id, :surname_gd, :forenames_gd, :surname_en, :forenames_en, :preferred_name, :title,
					:nickname, :yob, :yod, :district_1_id, :district_2_id, :notes)
SQL;
		$db->exec($sql, array(":id"=>$data["id"], ":surname_gd"=>$data["surname_gd"], ":forenames_gd"=>$data["forenames_gd"],
			":surname_en"=>$data["surname_en"], ":forenames_en"=>$data["forenames_en"], ":preferred_name"=>$data["preferred_name"],
			":title"=>$data["title"], ":nickname"=>$data["nickname"], ":yob"=>$data["yob"], ":yod"=>$data["yod"],
			":district_1_id"=>$data["district_1_id"], ":district_2_id"=>$data["district_2_id"], ":notes"=>$data["notes"]));
	}

}

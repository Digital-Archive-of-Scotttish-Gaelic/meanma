<?php

require_once "includes/include.php";

$db = new models\database();
$db2 = new models\database(DB2);


$directory = '../gadelica/meanma-2'; // <-- Change this

$filenames = [];

// Scan the directory
foreach (scandir($directory) as $file) {
    // Only files that end in .xml
    if (is_file("$directory/$file") && str_ends_with($file, '.xml')) {
        $filepath = "$directory/$file";
        $filename = pathinfo($file, PATHINFO_FILENAME);
        $beforeDash = explode('-', $filename)[0];

        $id = $beforeDash ? $beforeDash : $filename;

        //check if there is an entry for this text in the 'Faclair' DB
        $sql = <<<SQL
            SELECT reference_number, short_title, date_of_edition, date_of_language_ed
            FROM corpus_text
            WHERE reference_number = :id
SQL;
        $result = $db2->fetch($sql, [':id' => $id]);

        echo($result[0]['reference_number']) . "  ";
    }
}

//print_r($filenames);
//print_r($results);
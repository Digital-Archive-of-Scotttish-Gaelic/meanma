<?php

namespace models;

class text
{
    private $_db;

    public $textModel;

    public function __construct() {
        
        $this->_db = isset($this->_db) ? $this->_db : new database();
        $pdo = $this->_db->getDatabaseHandle();

        $this->textModel = new DynamicModel($pdo, 'text', 'id');
    }

    public function getModel() {
        return $this->textModel;
    }
}
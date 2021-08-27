<?php

namespace models;

require_once 'includes/htmlHeader.php';

$db = new models\database();

$slips = collection::getAllSlipInfo()

require_once 'includes/htmlFooter.php';
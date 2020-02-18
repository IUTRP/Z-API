<?php
require'class.zoneannuaire.php';

$obj = new ZoneAnnuaire();

echo json_encode($obj->getInformation($obj->getList('Reine des Neiges')[0]['id']));
// Echo all informations of the first item found ( JSON )

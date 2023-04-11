<?php
ensure_parameters(array("number"));
$sql = "SELECT v_destinations.* FROM v_destinations WHERE v_destinations.destination_number = :number";
$parameters['number'] = $_POST['number'];
$database = new database;
$extension = $database->select($sql, $parameters, 'row');
echo json_encode($extension);

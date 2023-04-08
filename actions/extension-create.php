<?php
$sql = "SELECT *";
$parameters['domain'] = $_POST['domain'];
$parameters['extension'] = $_POST['extension'];
$database = new database;
$extension = $database->select($sql, $parameters, 'all');
echo json_encode($extension);

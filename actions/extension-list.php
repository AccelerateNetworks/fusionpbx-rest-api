<?php
ensure_parameters(array("domain_uuid"));

$sql = "SELECT extension_uuid, extension, emergency_caller_id_number FROM v_extensions WHERE v_extensions.domain_uuid = :domain_uuid";
$parameters['domain_uuid'] = $_POST['domain_uuid'];
$database = new database;
$extensions = $database->select($sql, $parameters, 'all');
echo json_encode($extensions);

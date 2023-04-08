<?php
ensure_parameters(array("domain"));

$sql = "SELECT v_extensions.extension_uuid, v_extensions.extension FROM v_extensions, v_domains WHERE v_extensions.domain_uuid = v_domains.domain_uuid AND v_domains.domain_name = :domain";
$parameters['domain'] = $_POST['domain'];
$database = new database;
$extensions = $database->select($sql, $parameters, 'all');
echo json_encode($extensions);

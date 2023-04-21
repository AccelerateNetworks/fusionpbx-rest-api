<?php
$required_params = array("domain_uuid");

function do_action($body) {
    $sql = "SELECT extension_uuid, extension, emergency_caller_id_number FROM v_extensions WHERE v_extensions.domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    return $database->select($sql, $parameters, 'all');
}

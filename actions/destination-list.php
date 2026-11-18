<?php
require_once __DIR__."/../lib/destinations.php";
$required_params = array("domain_uuid");

function do_action($body) {
    $sql = "SELECT destination_uuid, dialplan_uuid, destination_type, destination_prefix, destination_number,";
    $sql .= " destination_caller_id_name, destination_caller_id_number, destination_actions,";
    $sql .= " destination_order, destination_enabled, destination_description";
    $sql .= " FROM v_destinations WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    if(isset($body->destination_type) && $body->destination_type !== "") {
        $sql .= " AND destination_type = :destination_type";
        $parameters['destination_type'] = (string)$body->destination_type;
    }
    $sql .= " ORDER BY destination_number ASC";
    $database = new database;
    $destinations = $database->select($sql, $parameters, 'all');
    if(!$destinations) {
        return array();
    }
    return array_map("destination_format", $destinations);
}

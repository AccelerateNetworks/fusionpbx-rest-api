<?php
require_once __DIR__."/../lib/destinations.php";
$required_params = array();

function do_action($body) {
    if(isset($body->destination_uuid) && $body->destination_uuid !== "") {
        // look up by UUID, which requires the domain
        if(!isset($body->domain_uuid) || $body->domain_uuid === "") {
            return array("error" => "missing required parameter(s)", "missing_parameters" => array("domain_uuid"), "code" => 400);
        }
        $destination = destination_find($body->domain_uuid, $body->destination_uuid, null);
    } elseif(isset($body->number) && $body->number !== "") {
        // numbers are unique across domains, so the domain is optional here
        $sql = "SELECT * FROM v_destinations WHERE destination_number = :number";
        $parameters['number'] = (string)$body->number;
        if(isset($body->domain_uuid) && $body->domain_uuid !== "") {
            $sql .= " AND domain_uuid = :domain_uuid";
            $parameters['domain_uuid'] = $body->domain_uuid;
        }
        $database = new database;
        $destination = $database->select($sql, $parameters, 'row');
    } else {
        return array("error" => "missing required parameter(s)", "missing_parameters" => array("number or destination_uuid"), "code" => 400);
    }

    if(!$destination) {
        return array("error" => "no such destination", "code" => 404);
    }

    // destination_actions is JSON-encoded in the DB. parse it here (#5)
    return destination_format($destination);
}

<?php
require_once __DIR__."/../lib/destinations.php";
$required_params = array("domain_uuid");

function do_action($body) {
    $domain_name = destination_domain_name($body->domain_uuid);
    if(!$domain_name) {
        return array("error" => "domain not found", "code" => 400);
    }

    // "number" is accepted as an alias for destination_number
    if(!property_exists($body, "destination_number") && isset($body->number)) {
        $body->destination_number = $body->number;
    }
    $missing = array();
    if(!isset($body->destination_number) || $body->destination_number === "") {
        $missing[] = "number";
    }
    if(!property_exists($body, "extension") && !property_exists($body, "destination_actions")) {
        $missing[] = "extension or destination_actions";
    }
    if($missing) {
        return array("error" => "missing required parameter(s)", "missing_parameters" => $missing, "code" => 400);
    }

    $row = array(
        "domain_uuid" => $body->domain_uuid,
        "destination_uuid" => uuid(),
        "dialplan_uuid" => uuid(),
        // a country code of 1 matches the number with or without a leading +1
        "destination_prefix" => "1",
        "destination_number" => "",
        "destination_caller_id_name" => "",
        "destination_caller_id_number" => null,
        "destination_cid_name_prefix" => "",
        "destination_hold_music" => "",
        "destination_distinctive_ring" => "",
        "destination_accountcode" => "",
        "destination_order" => "100",
        "destination_description" => "",
        "destination_enabled" => "true",
        "destination_record" => "false",
        "destination_type_voice" => null,
        "destination_type_fax" => null,
        "destination_type_text" => null,
        "destination_actions" => array()
    );

    $error = destination_apply_body($row, $body, $domain_name);
    if($error) {
        return array("error" => $error, "code" => 400);
    }
    if(empty($row['destination_actions'])) {
        return array("error" => "destination_actions must not be empty", "code" => 400);
    }
    if($row['destination_caller_id_number'] === null) {
        $row['destination_caller_id_number'] = $row['destination_number'];
    }

    if(destination_number_in_use($row['destination_number'])) {
        return array("error" => "number already routed", "code" => 400);
    }

    if(!destination_save($row, $domain_name, $row['destination_number'])) {
        return array("error" => "error adding destination");
    }
    destination_clear_cache($row);

    return destination_format(destination_find($body->domain_uuid, $row['destination_uuid'], null));
}

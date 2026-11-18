<?php
require_once __DIR__."/../lib/destinations.php";
$required_params = array("domain_uuid");

function do_action($body) {
    list($existing, $error) = destination_find_from_body($body);
    if($error) {
        return $error;
    }
    if($existing['destination_type'] != "inbound") {
        return array("error" => "only inbound destinations can be updated", "code" => 400);
    }
    // the dialplan is rebuilt from the destination's fields, and these can't be rebuilt here
    if(is_uuid($existing['fax_uuid'] ?? "")) {
        return array("error" => "destinations with fax detection can only be edited in the FusionPBX web interface", "code" => 400);
    }
    $conditions = json_decode($existing['destination_conditions'] ?? "", true);
    if(!empty($conditions)) {
        return array("error" => "destinations with conditions can only be edited in the FusionPBX web interface", "code" => 400);
    }

    $updatable = array_merge(destination_text_fields(), destination_boolean_fields(), destination_type_fields(), array("destination_actions", "extension"));
    $has_update = false;
    foreach($updatable as $field) {
        if(property_exists($body, $field)) {
            $has_update = true;
        }
    }
    if(!$has_update) {
        return array("error" => "no fields to update", "code" => 400);
    }

    $domain_name = destination_domain_name($existing['domain_uuid']);
    $row = destination_normalize_row($existing);
    $error = destination_apply_body($row, $body, $domain_name);
    if($error) {
        return array("error" => $error, "code" => 400);
    }
    if(empty($row['destination_actions'])) {
        return array("error" => "destination_actions must not be empty", "code" => 400);
    }

    if($row['destination_number'] !== $existing['destination_number'] && destination_number_in_use($row['destination_number'], $existing['destination_uuid'])) {
        return array("error" => "number already routed", "code" => 400);
    }

    // keep the existing inbound route and its name, unless the number changed
    $dialplan_name = $row['destination_number'];
    $existing_dialplan = null;
    if(is_uuid($existing['dialplan_uuid'] ?? "")) {
        $sql = "SELECT dialplan_uuid, dialplan_name FROM v_dialplans WHERE dialplan_uuid = :dialplan_uuid";
        $parameters['dialplan_uuid'] = $existing['dialplan_uuid'];
        $database = new database;
        $existing_dialplan = $database->select($sql, $parameters, 'row');
        unset($parameters);
    }
    if($existing_dialplan) {
        if($row['destination_number'] === $existing['destination_number'] && $existing_dialplan['dialplan_name'] !== "") {
            $dialplan_name = $existing_dialplan['dialplan_name'];
        }
    } else {
        $row['dialplan_uuid'] = uuid();
    }

    if(!destination_save($row, $domain_name, $dialplan_name)) {
        return array("error" => "error updating destination");
    }
    destination_clear_cache($existing);
    destination_clear_cache($row);

    return destination_format(destination_find($existing['domain_uuid'], $existing['destination_uuid'], null));
}

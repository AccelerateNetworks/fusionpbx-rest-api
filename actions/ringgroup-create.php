<?php
$required_params = array("domain_uuid", "name", "extension", "destinations", "strategy");

function do_action($body) {
    $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    $domain_name = $database->select($sql, $parameters, 'column');
    unset($parameters);
    if(!$domain_name) {
        return array("error" => "domain not found");
    }

    $sql = "SELECT ring_group_uuid FROM v_ring_groups WHERE ring_group_extension = :extension AND domain_uuid = :domain_uuid";
    $parameters['extension'] = $body->extension;
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    if($database->select($sql, $parameters, 'column')) {
        return array("error" => "ring group already exists");
    }
    unset($parameters);

    $ring_group_uuid = uuid();
    $dialplan_uuid = uuid();

    $ring_group_destinations = array();
    $requested_destinations = json_decode($body->destinations);
    if(sizeof($requested_destinations) == 0) {
        return_error("no destinations specified. Value must be a JSON array");
    }

    foreach($requested_destinations as $destination) {
        $ring_group_destinations[] = array(
            "ring_group_uuid" => $ring_group_uuid,
            "ring_group_destination_uuid" => uuid(),
            "destination_number" => $destination->{'number'},
            "destination_delay" => "0",
            "destination_timeout" => "30",
            "destination_prompt" => "",
            "destination_enabled" => "true",
            "domain_uuid" => $body->domain_uuid
        );
    }

    $array["ring_groups"][] = array(
        "ring_group_uuid" => $ring_group_uuid,
        "domain_uuid" => $body->domain_uuid,
        "ring_group_name" => $body->name,
        "ring_group_extension" => $body->extension,
        "ring_group_greeting" => "",
        "ring_group_strategy" => $body->strategy,
        "ring_group_call_timeout" => "30",
        "ring_group_caller_id_name" => "",
        "ring_group_caller_id_number" => "",
        "ring_group_distinctive_ring" => "",
        "ring_group_ringback" => "\${us-ring}",
        "ring_group_call_forward_enabled" => "",
        "ring_group_follow_me_enabled" => "",
        "ring_group_missed_call_app" => null,
        "ring_group_missed_call_data" => null,
        "ring_group_forward_enabled" => "false",
        "ring_group_forward_destination" => "",
        "ring_group_forward_toll_allow" => "",
        "ring_group_context" => $domain_name,
        "ring_group_enabled" => "true",
        "ring_group_description" => "",
        "dialplan_uuid" => $dialplan_uuid,
        "ring_group_timeout_app" => "",
        "ring_group_timeout_data" => "",
        "ring_group_destinations" => $ring_group_destinations
    );

    $dialplan_xml = "<extension name=\"".$body->name."\" continue=\"\" uuid=\"".$dialplan_uuid."\">\n";
    $dialplan_xml .= "\t<condition field=\"destination_number\" expression=\"^".$body->extension."$\">\n";
    $dialplan_xml .= "\t\t<action application=\"ring_ready\" data=\"\" />\n";
    $dialplan_xml .= "\t\t<action application=\"set\" data=\"ring_group_uuid=".$ring_group_uuid."\" />\n";
    $dialplan_xml .= "\t\t<action application=\"lua\" data=\"app.lua ring_groups\" />\n";
    $dialplan_xml .= "\t</condition>\n";
    $dialplan_xml .= "</extension>";

    $array["dialplans"][] = array(
        "domain_uuid" => $body->domain_uuid,
        "dialplan_uuid" => $dialplan_uuid,
        "dialplan_name" => $body->name,
        "dialplan_number" => $body->extension,
        "dialplan_context" => $domain_name,
        "dialplan_continue" => "false",
        "dialplan_xml" => $dialplan_xml,
        "dialplan_order" => "101",
        "dialplan_enabled" => "true",
        "dialplan_description" => "",
        "app_uuid" => "1d61fb65-1eec-bc73-a6ee-a6203b4fe6f2" // ring group app
    );

    $_SESSION["permissions"]["ring_group_add"] = true;
    $_SESSION["permissions"]["ring_group_destination_add"] = true;
    $_SESSION["permissions"]["dialplan_add"] = true;

    $database = new database;
    $database->app_name = 'rest_api';
    $database->app_uuid = '2bfe71d9-e112-4b8b-bcff-75aeb0e06302';
    if(!$database->save($array)) {
        return array("error" => "error adding ring group");
    }

    $sql = "SELECT * FROM v_ring_groups WHERE ring_group_uuid = :ring_group_uuid";
    $parameters['ring_group_uuid'] = $ring_group_uuid;
    $database = new database;
    $ring_group = $database->select($sql, $parameters, 'row');
    return $ring_group;
}

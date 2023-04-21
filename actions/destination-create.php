<?php
$required_params = array("domain_uuid", "number", "extension");

function do_action($body) {
    $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    $domain_name = $database->select($sql, $parameters, 'column');
    unset($parameters);
    if(!$domain_name) {
        return array("error" => "domain not found");
    }

    $sql = "SELECT destination_uuid FROM v_destinations WHERE destination_number = :number";
    $parameters['number'] = $body->number;
    $database = new database;
    if($database->select($sql, $parameters, 'column')) {
        return array("error" => "number already routed");
    }
    unset($parameters);

    $dialplan_uuid = uuid();
    $destination_uuid = uuid();
    $destination_data = $body->extension." XML ".$domain_name;

    $array["destinations"][] = array(
        "dialplan_uuid" => $dialplan_uuid,
        "domain_uuid" => $body->domain_uuid,
        "destination_uuid" => $destination_uuid,
        "fax_uuid" => "",
        "user_uuid" => "",
        "group_uuid" => "",
        "destination_type" => "inbound",
        "destination_number" => $body->number,
        "destination_number_regex" => "^(".$body->number.")$",
        "destination_prefix" => "",
        "destination_caller_id_name" => "",
        "destination_caller_id_number" => $body->number,
        "destination_cid_name_prefix" => "",
        "destination_context" => "public",
        "destination_hold_music" => "",
        "destination_distinctive_ring" => "",
        "destination_record" => "",
        "destination_accountcode" => "",
        "destination_type_voice" => null,
        "destination_type_fax" => null,
        "destination_type_text" => null,
        "destination_type_emergency" => null,
        "destination_conditions" => "",
        "destination_actions" => json_encode(array(array("destination_app" => "transfer", "destination_data" => $destination_data))),
        "destination_order" => "100",
        "destination_enabled" => "true",
        "destination_description" => ""
    );

    $dialplan_xml = "<extension name=\"".$body->number."\" continue=\"false\" uuid=\"".$dialplan_uuid."\">\n";
    $dialplan_xml .= "\t<condition field=\"destination_number\" expression=\"^\+?1?(".$body->number.")$\">\n";
    $dialplan_xml .= "\t\t<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\" />\n";
    $dialplan_xml .= "\t\t<action application=\"set\" data=\"domain_uuid=".$body->domain_uuid."\" inline=\"true\" />\n";
    $dialplan_xml .= "\t\t<action application=\"set\" data=\"domain_name=".$domain_name."\" inline=\"true\" />\n";
    $dialplan_xml .= "\t\t<action application=\"set\" data=\"hangup_after_bridge=true\" inline=\"true\"/>\n";
    $dialplan_xml .= "\t\t<action application=\"set\" data=\"continue_on_fail=true\" inline=\"true\"/>\n";
    $dialplan_xml .= "\t\t<action application=\"transfer\" data=\"".$destination_data."\"/>\n";
    $dialplan_xml .= "\t</condition>\n";
    $dialplan_xml .= "</extension>";

    $array["dialplans"][] = array(
        "app_uuid" => "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4", // inbound routes must have the this app_uuid
        "dialplan_uuid" => $dialplan_uuid,
        "domain_uuid" => $body->domain_uuid,
        "dialplan_name" => $body->number,
        "dialplan_number" => $body->number,
        "dialplan_context" => "public",
        "dialplan_continue" => "false",
        "dialplan_order" => "100",
        "dialplan_enabled" => "true",
        "dialplan_description" => "",
        "dialplan_xml" => $dialplan_xml,
        "dialplan_details" => array(
            array(
                "domain_uuid" => $body->domain_uuid,
                "dialplan_uuid" => $dialplan_uuid,
                "dialplan_detail_tag" => "condition",
                "dialplan_detail_type" => "destination_number",
                "dialplan_detail_data" => "^(".$body->number.")$",
                "dialplan_detail_order" => 20
            ),
            array(
                "domain_uuid" => $body->domain_uuid,
                "dialplan_uuid" => $dialplan_uuid,
                "dialplan_detail_tag" => "action",
                "dialplan_detail_type" => "transfer",
                "dialplan_detail_data" => $destination_data,
                "dialplan_detail_order" => 40
            )
        )
    );

    $_SESSION["permissions"]["dialplan_detail_add"] = true;
    $_SESSION["permissions"]["dialplan_add"] = true;
    $_SESSION["permissions"]["destination_add"] = true;

    $database = new database;
    $database->app_name = 'rest_api';
    $database->app_uuid = '2bfe71d9-e112-4b8b-bcff-75aeb0e06302';
    if(!$database->save($array)) {
        return array("error" => "error adding destination");
    }

    $sql = "SELECT * FROM v_destinations WHERE destination_uuid = :destination_uuid";
    $parameters['destination_uuid'] = $destination_uuid;
    $database = new database;
    $extension = $database->select($sql, $parameters, 'row');
    return $extension;
}

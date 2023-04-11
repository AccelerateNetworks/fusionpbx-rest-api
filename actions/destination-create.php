<?php
ensure_parameters(array("domain_uuid", "number", "extension"));

$sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
$parameters['domain_uuid'] = $_POST['domain_uuid'];
$database = new database;
$domain_name = $database->select($sql, $parameters, 'column');
unset($parameters);
if(!$domain_name) {
    echo json_encode(array("error" => "domain not found"));
    die();
}

$sql = "SELECT destination_uuid FROM v_destinations WHERE destination_number = :number";
$parameters['number'] = $_POST['number'];
$database = new database;
if($database->select($sql, $parameters, 'column')) {
    echo json_encode(array("error" => "number already routed"));
    die();
}
unset($parameters);

$dialplan_uuid = uuid();
$destination_uuid = uuid();
$destination_data = $_POST['extension']." XML ".$domain_name;

$array["destinations"][] = array(
    "dialplan_uuid" => $dialplan_uuid,
    "domain_uuid" => $_POST['domain_uuid'],
    "destination_uuid" => $destination_uuid,
    "fax_uuid" => "",
    "user_uuid" => "",
    "group_uuid" => "",
    "destination_type" => "inbound",
    "destination_number" => $_POST['number'],
    "destination_number_regex" => "^(".$_POST['number'].")$",
    "destination_prefix" => "",
    "destination_caller_id_name" => "",
    "destination_caller_id_number" => $_POST['number'],
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
$array["dialplans"][] = array(
    "app_uuid" => "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4", // inbound routes must have the this app_uuid
    "dialplan_uuid" => $dialplan_uuid,
    "domain_uuid" => $_POST['domain_uuid'],
    "dialplan_name" => $_POST['number'],
    "dialplan_number" => $_POST['number'],
    "dialplan_context" => "public",
    "dialplan_continue" => "false",
    "dialplan_order" => "100",
    "dialplan_enabled" => "true",
    "dialplan_description" => "",
    "dialplan_xml" => "\n\t\n\t\t\n\t\t\n\t\t\n\t\t\n\t<\/condition>\n<\/extension>\n",
    "dialplan_details" => array(
        array(
            "domain_uuid" => $_POST['domain_uuid'],
            "dialplan_uuid" => $dialplan_uuid,
            "dialplan_detail_tag" => "condition",
            "dialplan_detail_type" => "destination_number",
            "dialplan_detail_data" => "^(".$_POST['number'].")$",
            "dialplan_detail_order" => 20
        ),
        array(
            "domain_uuid" => $_POST['domain_uuid'],
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
    echo json_encode(array("error" => "error adding destination"));
    die();
}

$sql = "SELECT * FROM v_destinations WHERE destination_uuid = :destination_uuid";
$parameters['destination_uuid'] = $destination_uuid;
$database = new database;
$extension = $database->select($sql, $parameters, 'row');
echo json_encode($extension);


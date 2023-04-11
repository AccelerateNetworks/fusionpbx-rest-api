<?php
ensure_parameters(array("domain_uuid", "extension"));

$sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
$parameters['domain_uuid'] = $_POST['domain_uuid'];
$database = new database;
$domain_name = $database->select($sql, $parameters, 'column');
unset($parameters);
if(!$domain_name) {
    echo json_encode(array("error" => "domain not found"));
    die();
}

$sql = "SELECT extension_uuid FROM v_extensions WHERE extension = :extension AND domain_uuid = :domain_uuid";
$parameters['extension'] = $_POST['extension'];
$parameters['domain_uuid'] = $_POST['domain_uuid'];
$database = new database;
if($database->select($sql, $parameters, 'column')) {
    echo json_encode(array("error" => "extension already exists"));
    die();
}
unset($parameters);
$extension_uuid = uuid();

$array["extensions"][] = array(
    "domain_uuid" => $_POST['domain_uuid'],
    "extension_uuid" => $extension_uuid,
    "extension" => $_POST['extension'],
    "password" => generate_password(10, 4),
    "accountcode" => $domain_name,
    "effective_caller_id_name" => "",
    "effective_caller_id_number" => "",
    "outbound_caller_id_name" => "",
    "outbound_caller_id_number" => "",
    "emergency_caller_id_name" => "",
    "emergency_caller_id_number" => "",
    "directory_first_name" => "",
    "directory_last_name" => "",
    "directory_visible" => "true",
    "directory_exten_visible" => "true",
    "max_registrations" => "",
    "limit_max" => "5",
    "limit_destination" => "!USER_BUSY",
    "user_context" => $domain_name,
    "missed_call_app" => "",
    "missed_call_data" => "",
    "toll_allow" => "",
    "call_timeout" => "30",
    "call_group" => "",
    "call_screen_enabled" => "false",
    "user_record" => "",
    "hold_music" => "",
    "auth_acl" => "",
    "cidr" => "",
    "sip_force_contact" => "",
    "sip_force_expires" => "",
    "mwi_account" => "",
    "sip_bypass_media" => "",
    "absolute_codec_string" => "",
    "force_ping" => "",
    "dial_string" => "",
    "enabled" => "true",
    "description" => ""
);
$array["voicemails"][] = array(
    "domain_uuid" => $_POST['domain_uuid'],
    "voicemail_uuid" => uuid(),
    "voicemail_id" => $_POST['extension'],
    "voicemail_password" => generate_password(10, 1),
    "voicemail_mail_to" => "",
    "voicemail_file" => "attach",
    "voicemail_local_after_email" => "true",
    "voicemail_transcription_enabled" => "false",
    "voicemail_tutorial" => null,
    "voicemail_enabled" => "true",
    "voicemail_description" => ""
);

$_SESSION["permissions"]["extension_add"] = true;
$_SESSION["permissions"]["voicemail_add"] = true;

$database = new database;
$database->app_name = 'rest_api';
$database->app_uuid = '2bfe71d9-e112-4b8b-bcff-75aeb0e06302';
if(!$database->save($array)) {
    echo json_encode(array("error" => "error adding extension"));
    die();
}

$sql = "SELECT * FROM v_extensions WHERE extension_uuid = :extension_uuid";
$parameters['extension_uuid'] = $extension_uuid;
$database = new database;
$extension = $database->select($sql, $parameters, 'row');
echo json_encode($extension);

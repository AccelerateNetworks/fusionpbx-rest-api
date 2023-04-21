<?php
$required_params = array("domain_uuid", "extension");
function do_action($body) {    
    $caller_id_name = "";
    if($body->caller_id_name) {
        $caller_id_name = $body->caller_id_name;
    }

    $caller_id_number = "";
    if($body->caller_id_number) {
        $caller_id_number = $body->caller_id_number;
    }

    $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    $domain_name = $database->select($sql, $parameters, 'column');
    unset($parameters);
    if(!$domain_name) {
        return array("error" => "domain not found");
    }

    $sql = "SELECT extension_uuid FROM v_extensions WHERE extension = :extension AND domain_uuid = :domain_uuid";
    $parameters['extension'] = $body->extension;
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    if($database->select($sql, $parameters, 'column')) {
        return array("error" => "extension already exists");
    }
    unset($parameters);
    $extension_uuid = uuid();

    $array["extensions"][] = array(
        "domain_uuid" => $body->domain_uuid,
        "extension_uuid" => $extension_uuid,
        "extension" => $body->extension,
        "password" => generate_password(10, 4),
        "accountcode" => $domain_name,
        "effective_caller_id_name" => $caller_id_name,
        "effective_caller_id_number" => $caller_id_number,
        "outbound_caller_id_name" => $caller_id_name,
        "outbound_caller_id_number" => $caller_id_number,
        "emergency_caller_id_name" => $caller_id_name,
        "emergency_caller_id_number" => $caller_id_number,
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
        "domain_uuid" => $body->domain_uuid,
        "voicemail_uuid" => uuid(),
        "voicemail_id" => $body->extension,
        "voicemail_password" => generate_password(8, 1),
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
        return array("error" => "error adding extension");
    }

    $sql = "SELECT * FROM v_extensions WHERE extension_uuid = :extension_uuid";
    $parameters['extension_uuid'] = $extension_uuid;
    $database = new database;
    return $database->select($sql, $parameters, 'row');
}

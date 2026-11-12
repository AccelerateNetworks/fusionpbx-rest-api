<?php
$required_params = array("domain_uuid", "extension_uuid");

function do_action($body) {
    // fields that may be changed. Only fields present in the request body are updated.
    // The extension number itself can't be changed here: the voicemail box, ring groups and
    // dialplans reference it by number, so delete and recreate the extension instead.
    $fields = array(
        "number_alias",
        "password",
        "effective_caller_id_name",
        "effective_caller_id_number",
        "outbound_caller_id_name",
        "outbound_caller_id_number",
        "emergency_caller_id_name",
        "emergency_caller_id_number",
        "directory_first_name",
        "directory_last_name",
        "directory_visible",
        "directory_exten_visible",
        "limit_max",
        "limit_destination",
        "missed_call_app",
        "missed_call_data",
        "toll_allow",
        "call_timeout",
        "call_group",
        "call_screen_enabled",
        "user_record",
        "hold_music",
        "auth_acl",
        "cidr",
        "sip_force_contact",
        "sip_force_expires",
        "mwi_account",
        "sip_bypass_media",
        "absolute_codec_string",
        "force_ping",
        "dial_string",
        "do_not_disturb",
        "forward_all_destination",
        "forward_all_enabled",
        "forward_busy_destination",
        "forward_busy_enabled",
        "forward_no_answer_destination",
        "forward_no_answer_enabled",
        "forward_user_not_registered_destination",
        "forward_user_not_registered_enabled",
        "max_registrations",
        "enabled",
        "description"
    );

    $sql = "SELECT * FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension_uuid = :extension_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $parameters['extension_uuid'] = $body->extension_uuid;
    $database = new database;
    $existing = $database->select($sql, $parameters, 'row');
    unset($parameters);
    if(!$existing) {
        return array("error" => "extension not found", "code" => 404);
    }

    if(isset($body->extension) && $body->extension != $existing['extension']) {
        return array("error" => "the extension number cannot be changed, delete and recreate the extension instead", "code" => 400);
    }

    $extension = array(
        "domain_uuid" => $body->domain_uuid,
        "extension_uuid" => $body->extension_uuid
    );

    // caller_id_name and caller_id_number set the effective, outbound and emergency caller ID together,
    // the same as extension-create. Explicitly specified *_caller_id_* fields take precedence.
    foreach(array("caller_id_name", "caller_id_number") as $caller_id_field) {
        if(isset($body->{$caller_id_field})) {
            foreach(array("effective", "outbound", "emergency") as $prefix) {
                $extension[$prefix."_".$caller_id_field] = (string)$body->{$caller_id_field};
            }
        }
    }

    foreach($fields as $field) {
        if(!property_exists($body, $field)) {
            continue;
        }
        $value = $body->{$field};
        if(is_array($value) || is_object($value)) {
            return array("error" => $field." must be a string, number or boolean", "code" => 400);
        }
        // FusionPBX stores booleans as the strings "true" and "false"
        if(is_bool($value)) {
            $value = $value ? "true" : "false";
        }
        $extension[$field] = $value === null ? null : (string)$value;
    }

    if(sizeof($extension) == 2) {
        return array("error" => "no fields to update", "code" => 400);
    }

    if(isset($extension['password']) && strlen($extension['password']) < 8) {
        return array("error" => "password must be at least 8 characters", "code" => 400);
    }

    if(isset($extension['number_alias']) && $extension['number_alias'] !== "") {
        $sql = "SELECT extension_uuid FROM v_extensions WHERE domain_uuid = :domain_uuid";
        $sql .= " AND (extension = :number_alias OR number_alias = :number_alias) AND extension_uuid <> :extension_uuid";
        $parameters['domain_uuid'] = $body->domain_uuid;
        $parameters['number_alias'] = $extension['number_alias'];
        $parameters['extension_uuid'] = $body->extension_uuid;
        $database = new database;
        if($database->select($sql, $parameters, 'column')) {
            return array("error" => "number_alias is already in use by another extension", "code" => 409);
        }
        unset($parameters);
    }

    $array["extensions"][] = $extension;

    $_SESSION["permissions"]["extension_edit"] = true;

    $database = new database;
    $database->app_name = 'rest_api';
    $database->app_uuid = '2bfe71d9-e112-4b8b-bcff-75aeb0e06302';
    if(!$database->save($array)) {
        return array("error" => "error updating extension");
    }

    // FreeSWITCH caches directory entries, clear them so the changes take effect.
    // The number alias is cleared both before and after the change in case it was changed.
    $cache = new cache;
    $cache->delete("directory:".$existing['extension']."@".$existing['user_context']);
    if($existing['number_alias']) {
        $cache->delete("directory:".$existing['number_alias']."@".$existing['user_context']);
    }
    if(isset($extension['number_alias']) && $extension['number_alias']) {
        $cache->delete("directory:".$extension['number_alias']."@".$existing['user_context']);
    }

    $sql = "SELECT * FROM v_extensions WHERE extension_uuid = :extension_uuid";
    $parameters['extension_uuid'] = $body->extension_uuid;
    $database = new database;
    $updated = $database->select($sql, $parameters, 'row');
    unset($updated['password']);
    return $updated;
}

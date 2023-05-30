<?php
$required_params = array("domain_uuid", "extension");

function do_action($body) {
    $fields = array(
        "extension_uuid",
        "extension",
        "number_alias",
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
        "user_context",
        "toll_allow",
        "call_timeout",
        "call_group",
        "call_screen_enabled",
        "user_record",
        "hold_music",
        "auth_acl",
        "cidr",
        "sip_force_contact",
        "nibble_account",
        "sip_force_expires",
        "mwi_account",
        "sip_bypass_media",
        "unique_id",
        "dial_string",
        "dial_user",
        "dial_domain",
        "do_not_disturb",
        "forward_all_destination",
        "forward_all_enabled",
        "forward_busy_destination",
        "forward_busy_enabled",
        "forward_no_answer_destination",
        "forward_no_answer_enabled",
        "forward_user_not_registered_destination",
        "forward_user_not_registered_enabled",
        "follow_me_uuid",
        "enabled",
        "description",
        "forward_caller_id_uuid",
        "absolute_codec_string",
        "force_ping",
        "follow_me_enabled",
        "follow_me_destinations",
        "max_registrations",
        "insert_date",
        "insert_user",
        "update_date",
        "update_user"
    );

    $sql = "SELECT v_extensions.".implode(", v_extensions.", $fields);
    $sql .= " FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension_uuid = :extension";
    $parameters['domain'] = $body->domain;
    $parameters['extension'] = $body->extension;
    $database = new database;
    $extension = $database->select($sql, $parameters, 'row');
    if(!$extension) {
        return array("error" => "extension not found", "code" => 404);
    }
    echo $extension;
}

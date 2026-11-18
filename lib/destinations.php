<?php
// Shared code for the destination-* actions. Inbound destinations are saved the same way
// FusionPBX's own destination_edit.php saves them: a v_destinations row plus an inbound route
// dialplan (dialplan_xml and dialplan_details) in the public context.

define("DESTINATION_INBOUND_APP_UUID", "c03b422e-13a8-bd1b-e42b-b6b9b4d27ce4"); // inbound routes must have this app_uuid
define("DESTINATION_REST_API_APP_UUID", "2bfe71d9-e112-4b8b-bcff-75aeb0e06302");

// Applications a destination action may use. These are the ones FusionPBX's destination picker
// produces (extensions, ring groups, IVRs, voicemail, etc. are all "transfer").
function destination_allowed_apps() {
    return array("transfer", "bridge", "lua", "hangup", "playback", "phrase", "sleep", "answer");
}

// Text fields destination-create and destination-update accept, stored as given.
function destination_text_fields() {
    return array(
        "destination_prefix",
        "destination_number",
        "destination_caller_id_name",
        "destination_caller_id_number",
        "destination_cid_name_prefix",
        "destination_hold_music",
        "destination_distinctive_ring",
        "destination_accountcode",
        "destination_order",
        "destination_description"
    );
}

// Boolean fields, stored by FusionPBX as the strings "true" and "false"
function destination_boolean_fields() {
    return array("destination_enabled", "destination_record");
}

// Destination type flags, stored by FusionPBX as 1 or null
function destination_type_fields() {
    return array("destination_type_voice", "destination_type_fax", "destination_type_text");
}

function destination_grant_permissions($permissions) {
    $p = permissions::new();
    foreach($permissions as $permission) {
        $_SESSION["permissions"][$permission] = true;
        $p->add($permission, "temp");
    }
}

function destination_is_true($value) {
    return $value === true || $value === 1 || $value === "true" || $value === "1";
}

// Find a destination on a domain by destination_uuid or by number.
function destination_find($domain_uuid, $destination_uuid, $number) {
    $sql = "SELECT * FROM v_destinations WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    if($destination_uuid) {
        if(!is_uuid($destination_uuid)) {
            return null;
        }
        $sql .= " AND destination_uuid = :destination_uuid";
        $parameters['destination_uuid'] = $destination_uuid;
    } else {
        $sql .= " AND destination_number = :number";
        $parameters['number'] = (string)$number;
    }
    $database = new database;
    $row = $database->select($sql, $parameters, 'row');
    return $row ? $row : null;
}

// Find the destination a request refers to: domain_uuid plus destination_uuid or number.
// Returns array(row, error_response).
function destination_find_from_body($body) {
    $destination_uuid = isset($body->destination_uuid) ? (string)$body->destination_uuid : "";
    $number = isset($body->number) ? (string)$body->number : "";
    if($destination_uuid === "" && $number === "") {
        return array(null, array("error" => "missing required parameter(s)", "missing_parameters" => array("destination_uuid or number"), "code" => 400));
    }
    $row = destination_find($body->domain_uuid, $destination_uuid, $number);
    if(!$row) {
        return array(null, array("error" => "destination not found", "code" => 404));
    }
    return array($row, null);
}

function destination_number_in_use($number, $exclude_destination_uuid = null) {
    $sql = "SELECT destination_uuid FROM v_destinations WHERE destination_number = :number";
    $parameters['number'] = $number;
    if($exclude_destination_uuid) {
        $sql .= " AND destination_uuid <> :destination_uuid";
        $parameters['destination_uuid'] = $exclude_destination_uuid;
    }
    $database = new database;
    return (bool)$database->select($sql, $parameters, 'column');
}

function destination_domain_name($domain_uuid) {
    if(!is_uuid($domain_uuid)) {
        return null;
    }
    $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    $database = new database;
    return $database->select($sql, $parameters, 'column');
}

// Convert a v_destinations row from the database into the form destination_apply_body() and
// destination_save() work with: text fields as strings, booleans as "true"/"false", actions as an array.
function destination_normalize_row($row) {
    foreach(destination_text_fields() as $field) {
        $row[$field] = isset($row[$field]) ? (string)$row[$field] : "";
    }
    foreach(destination_boolean_fields() as $field) {
        $row[$field] = destination_is_true($row[$field] ?? null) ? "true" : "false";
    }
    foreach(destination_type_fields() as $field) {
        $row[$field] = destination_is_true($row[$field] ?? null) ? 1 : null;
    }
    if($row['destination_order'] === "") {
        $row['destination_order'] = "100";
    }
    $actions = json_decode($row['destination_actions'] ?? "", true);
    $row['destination_actions'] = is_array($actions) ? $actions : array();
    return $row;
}

// Format a v_destinations row for a response
function destination_format($row) {
    if(isset($row['destination_actions']) && $row['destination_actions']) {
        $row['destination_actions'] = json_decode($row['destination_actions']);
    }
    if(isset($row['destination_conditions']) && $row['destination_conditions']) {
        $row['destination_conditions'] = json_decode($row['destination_conditions']);
    }
    return $row;
}

// Parse the destination_actions parameter: a JSON array of {"destination_app": ..., "destination_data": ...},
// the same format destination-details returns. Returns array(actions, error).
function destination_parse_actions($value) {
    if(!is_array($value)) {
        return array(null, "destination_actions must be a JSON array");
    }
    $actions = array();
    foreach($value as $i => $action) {
        if(is_object($action)) {
            $action = (array)$action;
        }
        if(!is_array($action) || !isset($action['destination_app']) || !is_string($action['destination_app'])) {
            return array(null, "destination_actions[".$i."] must be an object with a destination_app");
        }
        $app = $action['destination_app'];
        $data = isset($action['destination_data']) ? $action['destination_data'] : "";
        if(!is_string($data) && !is_numeric($data)) {
            return array(null, "destination_actions[".$i."].destination_data must be a string");
        }
        $data = (string)$data;
        if(!in_array($app, destination_allowed_apps(), true)) {
            return array(null, "destination_actions[".$i."].destination_app must be one of: ".implode(", ", destination_allowed_apps()));
        }
        if(preg_match('/[\r\n]/', $data)) {
            return array(null, "destination_actions[".$i."].destination_data must not contain line breaks");
        }
        if($data === "" && !in_array($app, array("hangup", "answer"), true)) {
            return array(null, "destination_actions[".$i."].destination_data is required for ".$app);
        }
        $actions[] = array("destination_app" => $app, "destination_data" => $data);
    }
    return array($actions, null);
}

// Apply the fields in a request body to a destination row. Only fields present in the body are changed.
// "extension" is shorthand for a single transfer action to that extension on the destination's domain.
// Returns an error string, or null.
function destination_apply_body(&$row, $body, $domain_name) {
    foreach(destination_text_fields() as $field) {
        if(!property_exists($body, $field)) {
            continue;
        }
        $value = $body->{$field};
        if($value !== null && !is_string($value) && !is_int($value) && !is_float($value)) {
            return $field." must be a string or number";
        }
        $row[$field] = $value === null ? "" : (string)$value;
    }
    foreach(destination_boolean_fields() as $field) {
        if(property_exists($body, $field)) {
            $row[$field] = destination_is_true($body->{$field}) ? "true" : "false";
        }
    }
    foreach(destination_type_fields() as $field) {
        if(property_exists($body, $field)) {
            $row[$field] = destination_is_true($body->{$field}) ? 1 : null;
        }
    }

    if(property_exists($body, "destination_actions") && property_exists($body, "extension")) {
        return "specify either destination_actions or extension, not both";
    }
    if(property_exists($body, "extension")) {
        $extension = (string)$body->extension;
        if(!preg_match('/^[0-9A-Za-z*#+_.-]+$/', $extension)) {
            return "extension is not valid";
        }
        $row['destination_actions'] = array(array("destination_app" => "transfer", "destination_data" => $extension." XML ".$domain_name));
    } elseif(property_exists($body, "destination_actions")) {
        list($actions, $error) = destination_parse_actions($body->destination_actions);
        if($error) {
            return $error;
        }
        $row['destination_actions'] = $actions;
    }

    if(!preg_match('/^\+?[0-9]+$/', $row['destination_number'])) {
        return "destination_number must be digits, optionally starting with +";
    }
    if(!preg_match('/^[0-9]*$/', $row['destination_prefix'])) {
        return "destination_prefix must be digits";
    }
    if(!preg_match('/^[0-9]+$/', $row['destination_order'])) {
        return "destination_order must be a number";
    }
    return null;
}

// Build the dialplan steps that follow the destination number condition, matching what
// destination_edit.php generates. Each step is array(application, data, inline, xml_data):
// data goes in dialplan_details, xml_data is the already-escaped data for dialplan_xml.
// User supplied values are escaped with destination_xml_escape(), which also strips ${...}, so the
// variables FusionPBX itself adds are kept by escaping only the user supplied part.
function destination_dialplan_steps($row, $record_extension) {
    $s = "destination_xml_escape";
    $actions = $row['destination_actions'];
    $apps = array_column($actions, "destination_app");
    $steps = array();
    if(is_uuid($row['provider_uuid'] ?? "")) {
        $steps[] = array("set", "provider_uuid=".$row['provider_uuid'], true, "provider_uuid=".$row['provider_uuid']);
    }
    if(in_array("bridge", $apps, true)) {
        $presence_id = "presence_id=\$1@".$row['domain_name'];
        $steps[] = array("set", $presence_id, true, $s($presence_id));
        $steps[] = array("set", "hangup_after_bridge=true", true, "hangup_after_bridge=true");
        $steps[] = array("set", "continue_on_fail=true", true, "continue_on_fail=true");
    }
    if(in_array("phrase", $apps, true)) {
        $steps[] = array("answer", "", false, "");
    }
    if($row['destination_cid_name_prefix'] !== "") {
        $steps[] = array("set", "effective_caller_id_name=".$row['destination_cid_name_prefix']."#\${caller_id_name}", true,
            "effective_caller_id_name=".$s($row['destination_cid_name_prefix'])."#\${caller_id_name}");
    }
    if($row['destination_record'] === "true") {
        $record = array(
            array("set", "record_path=\${recordings_dir}/\${domain_name}/archive/\${strftime(%Y)}/\${strftime(%b)}/\${strftime(%d)}", true),
            array("set", "record_name=\${uuid}.".$record_extension, true),
            array("set", "record_append=true", true),
            array("set", "record_in_progress=true", true),
            array("set", "recording_follow_transfer=true", true),
            array("set", "record_stereo=true", true),
            array("record_session", "\${record_path}/\${record_name}", false)
        );
        foreach($record as $step) {
            $step[] = $step[1];
            $steps[] = $step;
        }
    }
    $exports = array(
        "hold_music" => $row['destination_hold_music'],
        "sip_h_Alert-Info" => $row['destination_distinctive_ring'],
        "ringback" => $row['destination_ringback'] ?? "",
        "transfer_ringback" => $row['destination_ringback'] ?? "",
        "accountcode" => $row['destination_accountcode']
    );
    foreach($exports as $variable => $value) {
        if($value !== null && $value !== "") {
            $steps[] = array("export", $variable."=".$value, true, $variable."=".$s($value));
        }
    }
    foreach($actions as $action) {
        $steps[] = array($action['destination_app'], $action['destination_data'], false, destination_xml_data($action['destination_data']));
    }
    return $steps;
}

// Escape a value for a dialplan XML attribute. xml::sanitize() strips ${...} but uses ENT_XML1
// without ENT_QUOTES, so double quotes would otherwise end the attribute early.
function destination_xml_escape($value) {
    return htmlspecialchars(preg_replace('/\$\{[^}]+\}/', '', (string)$value), ENT_XML1 | ENT_QUOTES);
}

// Escape action data for dialplan XML. Like destination_edit.php, ${...} is stripped from user
// supplied values except the regex and sofia_contact API calls, which bridges commonly use.
function destination_xml_data($data) {
    $data = preg_replace('/\$\{(regex|sofia_contact)/', '#{$1', $data);
    $data = destination_xml_escape($data);
    return preg_replace('/#\{(regex|sofia_contact)/', '\${$1', $data);
}

// Build the array to pass to $database->save() for an inbound destination row (with
// destination_actions as a PHP array) and its dialplan.
function destination_build_save_array($row, $domain_name, $dialplan_name) {
    $settings = new settings(array("domain_uuid" => $row['domain_uuid']));
    $record_extension = $settings->get('call_recordings', 'record_extension', 'mp3');

    $destinations = new destinations(array("domain_uuid" => $row['domain_uuid'], "domain_name" => $domain_name));
    $regex_parts = array(
        "destination_prefix" => $row['destination_prefix'],
        "destination_number" => $row['destination_number']
    );
    // trunk prefix and area code can only be set in the FusionPBX UI, but are kept if present
    foreach(array("destination_trunk_prefix", "destination_area_code") as $field) {
        if(!empty($row[$field])) {
            $regex_parts[$field] = $row[$field];
        }
    }
    $row['destination_number_regex'] = $destinations->to_regex($regex_parts);

    $condition_field = "destination_number";
    if(in_array($row['destination_condition_field'] ?? "", array('${sip_to_user}', '${sip_req_user}'), true)) {
        $condition_field = $row['destination_condition_field'];
    }

    $row['domain_name'] = $domain_name;
    $steps = destination_dialplan_steps($row, $record_extension);
    $dialplan_uuid = $row['dialplan_uuid'];

    $xml = "<extension name=\"".destination_xml_escape($dialplan_name)."\" continue=\"false\" uuid=\"".$dialplan_uuid."\">\n";
    $xml .= "\t<condition field=\"".$condition_field."\" expression=\"".destination_xml_escape($row['destination_number_regex'])."\">\n";
    $xml .= "\t\t<action application=\"export\" data=\"call_direction=inbound\" inline=\"true\"/>\n";
    $xml .= "\t\t<action application=\"set\" data=\"domain_uuid=".$row['domain_uuid']."\" inline=\"true\"/>\n";
    $xml .= "\t\t<action application=\"set\" data=\"domain_name=".destination_xml_escape($domain_name)."\" inline=\"true\"/>\n";
    foreach($steps as $step) {
        $xml .= "\t\t<action application=\"".destination_xml_escape($step[0])."\" data=\"".$step[3]."\"".($step[2] ? " inline=\"true\"" : "")."/>\n";
    }
    $xml .= "\t</condition>\n";
    $xml .= "</extension>\n";

    $order = 20;
    $details = array(array(
        "domain_uuid" => $row['domain_uuid'],
        "dialplan_uuid" => $dialplan_uuid,
        "dialplan_detail_tag" => "condition",
        "dialplan_detail_type" => $condition_field,
        "dialplan_detail_data" => $row['destination_number_regex'],
        "dialplan_detail_group" => 0,
        "dialplan_detail_order" => $order,
        "dialplan_detail_enabled" => "true"
    ));
    foreach($steps as $step) {
        $order += 10;
        $detail = array(
            "domain_uuid" => $row['domain_uuid'],
            "dialplan_uuid" => $dialplan_uuid,
            "dialplan_detail_tag" => "action",
            "dialplan_detail_type" => $step[0],
            "dialplan_detail_data" => $step[1],
            "dialplan_detail_group" => 0,
            "dialplan_detail_order" => $order,
            "dialplan_detail_enabled" => "true"
        );
        if($step[2] || $step[0] == "set" || $step[0] == "export") {
            $detail["dialplan_detail_inline"] = "true";
        }
        $details[] = $detail;
    }

    $actions = $row['destination_actions'];
    $destination = array(
        "domain_uuid" => $row['domain_uuid'],
        "destination_uuid" => $row['destination_uuid'],
        "dialplan_uuid" => $dialplan_uuid,
        "destination_type" => "inbound",
        "destination_number" => $row['destination_number'],
        "destination_number_regex" => $row['destination_number_regex'],
        "destination_prefix" => $row['destination_prefix'],
        "destination_caller_id_name" => $row['destination_caller_id_name'],
        "destination_caller_id_number" => $row['destination_caller_id_number'],
        "destination_cid_name_prefix" => $row['destination_cid_name_prefix'],
        "destination_context" => "public",
        "destination_hold_music" => $row['destination_hold_music'],
        "destination_distinctive_ring" => $row['destination_distinctive_ring'],
        "destination_record" => $row['destination_record'],
        "destination_accountcode" => $row['destination_accountcode'],
        "destination_type_voice" => $row['destination_type_voice'],
        "destination_type_fax" => $row['destination_type_fax'],
        "destination_type_text" => $row['destination_type_text'],
        "destination_actions" => json_encode($actions),
        "destination_app" => $actions[0]['destination_app'] ?? null,
        "destination_data" => $actions[0]['destination_data'] ?? null,
        "destination_alternate_app" => $actions[1]['destination_app'] ?? null,
        "destination_alternate_data" => $actions[1]['destination_data'] ?? null,
        "destination_order" => $row['destination_order'],
        "destination_enabled" => $row['destination_enabled'],
        "destination_description" => $row['destination_description']
    );

    $dialplan = array(
        "app_uuid" => DESTINATION_INBOUND_APP_UUID,
        "dialplan_uuid" => $dialplan_uuid,
        "domain_uuid" => $row['domain_uuid'],
        "dialplan_name" => $dialplan_name,
        "dialplan_number" => $row['destination_number'],
        "dialplan_context" => "public",
        "dialplan_continue" => "false",
        "dialplan_order" => $row['destination_order'],
        "dialplan_enabled" => $row['destination_enabled'],
        "dialplan_description" => $row['destination_description'],
        "dialplan_xml" => $xml,
        "dialplan_details" => $details
    );

    return array("destinations" => array($destination), "dialplans" => array($dialplan));
}

// Save a destination row (create or update) and its inbound route dialplan.
// Any existing dialplan details are replaced. Returns true on success.
function destination_save($row, $domain_name, $dialplan_name) {
    $array = destination_build_save_array($row, $domain_name, $dialplan_name);

    // the dialplan details are rebuilt from scratch, like destination_edit.php does
    $sql = "DELETE FROM v_dialplan_details WHERE dialplan_uuid = :dialplan_uuid";
    $parameters['dialplan_uuid'] = $row['dialplan_uuid'];
    $database = new database;
    $database->execute($sql, $parameters);

    destination_grant_permissions(array(
        "destination_add", "destination_edit",
        "dialplan_add", "dialplan_edit",
        "dialplan_detail_add", "dialplan_detail_edit"
    ));

    $database = new database;
    $database->app_name = 'rest_api';
    $database->app_uuid = DESTINATION_REST_API_APP_UUID;
    if(!$database->save($array)) {
        return false;
    }
    return true;
}

// Clear FreeSWITCH's cached dialplan for a destination so changes take effect right away.
// Clears the keys for both dialplan modes and every form the number may arrive in.
function destination_clear_cache($row) {
    $context = $row['destination_context'] ? $row['destination_context'] : "public";
    $number = $row['destination_number'];
    $prefix = $row['destination_prefix'] ?? "";
    $cache = new cache;
    $cache->delete("dialplan:".$context);
    $numbers = array($number, ltrim($number, "+"), "+".ltrim($number, "+"));
    if($prefix !== "") {
        $numbers[] = $prefix.$number;
        $numbers[] = "+".$prefix.$number;
    }
    foreach(array_unique($numbers) as $n) {
        $cache->delete("dialplan:".$context.":".$n);
    }
}

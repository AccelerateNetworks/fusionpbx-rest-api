<?php
// shared helpers for the linphone-* actions. These manage the tables created by the
// fusionpbx-linphone app (installed at app/linphone), mirroring what its web UI does.

// returns an error response if the linphone app's tables aren't present, otherwise false
function linphone_check_installed() {
    $sql = "SELECT COUNT(DISTINCT table_name) FROM information_schema.tables WHERE table_name IN ('linphone_devices', 'linphone_profiles', 'linphone_profile_settings', 'linphone_profile_devices')";
    $database = new database;
    if($database->select($sql, array(), 'column') < 4) {
        return array("error" => "linphone app is not installed", "code" => 501);
    }
    return false;
}

// validates that each named parameter that is present in the body is a UUID
function linphone_check_uuids($body, $params) {
    foreach($params as $param) {
        if(isset($body->{$param}) && (!is_string($body->{$param}) || !is_uuid($body->{$param}))) {
            return array("error" => $param." must be a UUID", "code" => 400);
        }
    }
    return false;
}

// database->select() returns false rather than an empty array when nothing matches
function linphone_rows($rows) {
    return is_array($rows) ? $rows : array();
}

function linphone_domain_name($domain_uuid) {
    $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    $database = new database;
    return $database->select($sql, $parameters, 'column');
}

function linphone_provisioning_url($domain_name, $provisioning_secret) {
    return "https://".$domain_name."/app/linphone/provision/index.php?token=".rawurlencode($provisioning_secret);
}

function linphone_parse_bool($value) {
    return $value === true || $value === "true" || $value === 1 || $value === "1";
}

// accepts a JSON array/object in the body, or a JSON-encoded string of one
function linphone_parse_json_param($value) {
    if(is_string($value)) {
        return json_decode($value);
    }
    return $value;
}

function linphone_extension_in_domain($domain_uuid, $extension_uuid) {
    $sql = "SELECT extension_uuid FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension_uuid = :extension_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    $parameters['extension_uuid'] = $extension_uuid;
    $database = new database;
    return (bool)$database->select($sql, $parameters, 'column');
}

// validates a list of profile UUIDs: all must be UUIDs belonging to the domain.
// returns array(error_or_false, deduplicated_profile_uuids)
function linphone_validate_profiles($domain_uuid, $profiles) {
    $profiles = linphone_parse_json_param($profiles);
    if(!is_array($profiles)) {
        return array(array("error" => "profiles must be a JSON array of profile UUIDs", "code" => 400), null);
    }
    $profiles = array_values(array_unique($profiles, SORT_REGULAR));
    if(sizeof($profiles) == 0) {
        return array(false, array());
    }

    foreach($profiles as $profile) {
        if(!is_string($profile) || !is_uuid($profile)) {
            return array(array("error" => "profiles must be a JSON array of profile UUIDs", "code" => 400), null);
        }
    }

    $sql = "SELECT profile_uuid FROM linphone_profiles WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    $database = new database;
    $domain_profiles = array();
    foreach(linphone_rows($database->select($sql, $parameters, 'all')) as $row) {
        $domain_profiles[$row['profile_uuid']] = true;
    }

    $unknown = array();
    foreach($profiles as $profile) {
        if(!isset($domain_profiles[$profile])) {
            $unknown[] = $profile;
        }
    }
    if(sizeof($unknown) > 0) {
        return array(array("error" => "profile(s) not found in domain", "profiles" => $unknown, "code" => 400), null);
    }

    return array(false, $profiles);
}

// replaces the set of profiles a device is assigned to. $profiles must already be validated.
function linphone_set_device_profiles($domain_uuid, $device_uuid, $profiles) {
    $sql = "SELECT profile FROM linphone_profile_devices WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    $parameters['device_uuid'] = $device_uuid;
    $database = new database;
    $current = array();
    foreach(linphone_rows($database->select($sql, $parameters, 'all')) as $row) {
        $current[$row['profile']] = true;
    }
    unset($parameters);

    foreach($profiles as $profile) {
        if(isset($current[$profile])) {
            unset($current[$profile]);
            continue;
        }
        $sql = "INSERT INTO linphone_profile_devices (device_uuid, domain_uuid, profile) VALUES (:device_uuid, :domain_uuid, :profile)";
        $parameters['device_uuid'] = $device_uuid;
        $parameters['domain_uuid'] = $domain_uuid;
        $parameters['profile'] = $profile;
        $database = new database;
        $database->execute($sql, $parameters);
        unset($parameters);
    }

    // anything left in $current was not in the requested list
    foreach(array_keys($current) as $profile) {
        $sql = "DELETE FROM linphone_profile_devices WHERE device_uuid = :device_uuid AND domain_uuid = :domain_uuid AND profile = :profile";
        $parameters['device_uuid'] = $device_uuid;
        $parameters['domain_uuid'] = $domain_uuid;
        $parameters['profile'] = $profile;
        $database = new database;
        $database->execute($sql, $parameters);
        unset($parameters);
    }
}

// shapes a linphone_devices row (joined with extension) for API output. Secrets are left out,
// except as part of the provisioning URL, which is what clients need to be set up.
function linphone_format_device($row, $domain_name, $profiles) {
    return array(
        "device_uuid" => $row['device_uuid'],
        "domain_uuid" => $row['domain_uuid'],
        "extension_uuid" => $row['extension_uuid'],
        "extension" => $row['extension'],
        "name" => $row['name'],
        "user_agent" => $row['user_agent'],
        "provisioning_url" => linphone_provisioning_url($domain_name, $row['provisioning_secret']),
        "profiles" => $profiles
    );
}

// looks up devices on a domain. Optional filters: device_uuid, extension_uuid
function linphone_get_devices($domain_uuid, $domain_name, $device_uuid = null, $extension_uuid = null) {
    $sql = "SELECT linphone_devices.device_uuid, linphone_devices.domain_uuid, linphone_devices.extension_uuid, linphone_devices.name, linphone_devices.user_agent, linphone_devices.provisioning_secret, v_extensions.extension";
    $sql .= " FROM linphone_devices LEFT JOIN v_extensions ON v_extensions.extension_uuid = linphone_devices.extension_uuid AND v_extensions.domain_uuid = linphone_devices.domain_uuid";
    $sql .= " WHERE linphone_devices.domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    if($device_uuid) {
        $sql .= " AND linphone_devices.device_uuid = :device_uuid";
        $parameters['device_uuid'] = $device_uuid;
    }
    if($extension_uuid) {
        $sql .= " AND linphone_devices.extension_uuid = :extension_uuid";
        $parameters['extension_uuid'] = $extension_uuid;
    }
    $sql .= " ORDER BY v_extensions.extension, linphone_devices.name";
    $database = new database;
    $rows = $database->select($sql, $parameters, 'all');
    unset($parameters);
    if(!$rows) {
        return array();
    }

    $sql = "SELECT device_uuid, profile FROM linphone_profile_devices WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    if($device_uuid) {
        $sql .= " AND device_uuid = :device_uuid";
        $parameters['device_uuid'] = $device_uuid;
    }
    $database = new database;
    $device_profiles = array();
    foreach(linphone_rows($database->select($sql, $parameters, 'all')) as $link) {
        $device_profiles[$link['device_uuid']][] = $link['profile'];
    }

    $devices = array();
    foreach($rows as $row) {
        $profiles = isset($device_profiles[$row['device_uuid']]) ? $device_profiles[$row['device_uuid']] : array();
        $devices[] = linphone_format_device($row, $domain_name, $profiles);
    }
    return $devices;
}

// validates a settings object of {"section.key": "value"}, as stored in linphone_profile_settings.
// A null value means delete the setting. returns array(error_or_false, settings_array)
function linphone_validate_settings($settings) {
    $settings = linphone_parse_json_param($settings);
    if(!is_object($settings) && !(is_array($settings) && sizeof($settings) == 0)) {
        return array(array("error" => "settings must be a JSON object of {\"section.key\": \"value\"}", "code" => 400), null);
    }

    $valid = array();
    $invalid_keys = array();
    foreach($settings as $setting => $value) {
        // the provisioning template splits on "." into exactly a section and a key
        if(!preg_match('/^[^.\s]+\.[^.\s]+$/', $setting)) {
            $invalid_keys[] = $setting;
            continue;
        }
        if(is_bool($value)) {
            $value = $value ? "1" : "0";
        } elseif(!is_null($value) && !is_scalar($value)) {
            $invalid_keys[] = $setting;
            continue;
        }
        $valid[$setting] = is_null($value) ? null : (string)$value;
    }

    if(sizeof($invalid_keys) > 0) {
        return array(array(
            "error" => "invalid setting(s): names must be \"section.key\" and values must be strings, numbers, booleans or null",
            "settings" => $invalid_keys,
            "code" => 400
        ), null);
    }
    return array(false, $valid);
}

// applies validated settings to a profile: null deletes, anything else is upserted
function linphone_apply_settings($domain_uuid, $profile_uuid, $settings) {
    foreach($settings as $setting => $value) {
        $parameters['profile_uuid'] = $profile_uuid;
        $parameters['domain_uuid'] = $domain_uuid;
        $parameters['setting'] = $setting;
        $database = new database;

        if(is_null($value)) {
            $sql = "DELETE FROM linphone_profile_settings WHERE profile_uuid = :profile_uuid AND domain_uuid = :domain_uuid AND setting = :setting";
            $database->execute($sql, $parameters);
            unset($parameters);
            continue;
        }

        $parameters['value'] = $value;
        $sql = "UPDATE linphone_profile_settings SET value = :value WHERE profile_uuid = :profile_uuid AND domain_uuid = :domain_uuid AND setting = :setting RETURNING 1";
        if(!$database->execute($sql, $parameters)) {
            $sql = "INSERT INTO linphone_profile_settings (profile_uuid, domain_uuid, setting, value) VALUES (:profile_uuid, :domain_uuid, :setting, :value)";
            $database = new database;
            $database->execute($sql, $parameters);
        }
        unset($parameters);
    }
}

// returns a profile with its settings and assigned devices, or false if it doesn't exist in the domain
function linphone_get_profile($domain_uuid, $profile_uuid) {
    $sql = "SELECT profile_uuid, domain_uuid, name FROM linphone_profiles WHERE domain_uuid = :domain_uuid AND profile_uuid = :profile_uuid";
    $parameters['domain_uuid'] = $domain_uuid;
    $parameters['profile_uuid'] = $profile_uuid;
    $database = new database;
    $profile = $database->select($sql, $parameters, 'row');
    if(!$profile) {
        return false;
    }

    $sql = "SELECT setting, value FROM linphone_profile_settings WHERE domain_uuid = :domain_uuid AND profile_uuid = :profile_uuid ORDER BY setting";
    $database = new database;
    $settings = array();
    foreach(linphone_rows($database->select($sql, $parameters, 'all')) as $row) {
        $settings[$row['setting']] = $row['value'];
    }
    // encode as {} rather than [] when there are no settings
    $profile['settings'] = (object)$settings;

    $sql = "SELECT device_uuid FROM linphone_profile_devices WHERE domain_uuid = :domain_uuid AND profile = :profile_uuid";
    $database = new database;
    $profile['devices'] = array();
    foreach(linphone_rows($database->select($sql, $parameters, 'all')) as $row) {
        $profile['devices'][] = $row['device_uuid'];
    }

    return $profile;
}

// generates the random secrets a new device needs, matching the linphone app's own device_edit.php
function linphone_new_provisioning_secret() {
    return generate_password(20, 3); // random 20 character alphanumeric string
}

function linphone_new_upload_secret() {
    // must match the CHECK constraint '^[a-f0-9]{32}$' the linphone app applies
    return bin2hex(random_bytes(16));
}

<?php
require_once __DIR__."/../lib/linphone.php";
$required_params = array("domain_uuid", "profile_uuid");

function do_action($body) {
    if($error = linphone_check_installed()) {
        return $error;
    }
    if($error = linphone_check_uuids($body, array("domain_uuid", "profile_uuid"))) {
        return $error;
    }

    if(!linphone_get_profile($body->domain_uuid, $body->profile_uuid)) {
        return array("error" => "profile not found", "code" => 404);
    }

    // validate everything before changing anything
    $has_name = isset($body->name);
    if($has_name && !is_scalar($body->name)) {
        return array("error" => "name must be a string", "code" => 400);
    }

    $settings = null;
    if(isset($body->settings)) {
        list($error, $settings) = linphone_validate_settings($body->settings);
        if($error) {
            return $error;
        }
    }

    $replace_settings = isset($body->replace_settings) && linphone_parse_bool($body->replace_settings);
    if($replace_settings && is_null($settings)) {
        return array("error" => "replace_settings requires settings", "code" => 400);
    }

    if(!$has_name && is_null($settings)) {
        return array("error" => "nothing to update", "code" => 400);
    }

    if($has_name) {
        $sql = "UPDATE linphone_profiles SET name = :name WHERE domain_uuid = :domain_uuid AND profile_uuid = :profile_uuid";
        $parameters['name'] = (string)$body->name;
        $parameters['domain_uuid'] = $body->domain_uuid;
        $parameters['profile_uuid'] = $body->profile_uuid;
        $database = new database;
        $database->execute($sql, $parameters);
        unset($parameters);
    }

    if(!is_null($settings)) {
        if($replace_settings) {
            // delete any existing setting not in the request, so the profile ends up with exactly these
            $existing = linphone_get_profile($body->domain_uuid, $body->profile_uuid);
            foreach(array_keys((array)$existing['settings']) as $setting) {
                if(!array_key_exists($setting, $settings)) {
                    $settings[$setting] = null;
                }
            }
        }
        linphone_apply_settings($body->domain_uuid, $body->profile_uuid, $settings);
    }

    return linphone_get_profile($body->domain_uuid, $body->profile_uuid);
}

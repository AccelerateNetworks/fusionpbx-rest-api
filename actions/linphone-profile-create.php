<?php
require_once __DIR__."/../lib/linphone.php";
$required_params = array("domain_uuid", "name");

function do_action($body) {
    if($error = linphone_check_installed()) {
        return $error;
    }
    if($error = linphone_check_uuids($body, array("domain_uuid"))) {
        return $error;
    }
    if(!is_scalar($body->name)) {
        return array("error" => "name must be a string", "code" => 400);
    }

    if(!linphone_domain_name($body->domain_uuid)) {
        return array("error" => "domain not found", "code" => 404);
    }

    $settings = array();
    if(isset($body->settings)) {
        list($error, $settings) = linphone_validate_settings($body->settings);
        if($error) {
            return $error;
        }
    }

    $profile_uuid = uuid();
    $sql = "INSERT INTO linphone_profiles (profile_uuid, domain_uuid, name) VALUES (:profile_uuid, :domain_uuid, :name)";
    $parameters['profile_uuid'] = $profile_uuid;
    $parameters['domain_uuid'] = $body->domain_uuid;
    $parameters['name'] = (string)$body->name;
    $database = new database;
    $database->execute($sql, $parameters);
    unset($parameters);

    // database->execute() doesn't reliably report failure, so check the profile was actually added
    if(!linphone_get_profile($body->domain_uuid, $profile_uuid)) {
        return array("error" => "error adding profile");
    }

    linphone_apply_settings($body->domain_uuid, $profile_uuid, $settings);
    return linphone_get_profile($body->domain_uuid, $profile_uuid);
}

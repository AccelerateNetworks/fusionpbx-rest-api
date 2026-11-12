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

    $profile = linphone_get_profile($body->domain_uuid, $body->profile_uuid);
    if(!$profile) {
        return array("error" => "profile not found", "code" => 404);
    }

    // same order as the linphone app's own profile delete: device links, settings, then the profile
    $parameters['domain_uuid'] = $body->domain_uuid;
    $parameters['profile_uuid'] = $body->profile_uuid;
    $database = new database;
    $database->execute("DELETE FROM linphone_profile_devices WHERE profile = :profile_uuid AND domain_uuid = :domain_uuid", $parameters);
    $database = new database;
    $database->execute("DELETE FROM linphone_profile_settings WHERE profile_uuid = :profile_uuid AND domain_uuid = :domain_uuid", $parameters);
    $database = new database;
    $database->execute("DELETE FROM linphone_profiles WHERE profile_uuid = :profile_uuid AND domain_uuid = :domain_uuid", $parameters);

    // database->execute() doesn't reliably report failure, so check the profile is actually gone
    if(linphone_get_profile($body->domain_uuid, $body->profile_uuid)) {
        return array("error" => "error deleting profile");
    }

    return array(
        "profile_uuid" => $body->profile_uuid,
        "deleted" => true,
        "devices_unassigned" => $profile['devices']
    );
}

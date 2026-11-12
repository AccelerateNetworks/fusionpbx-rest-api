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
    return $profile;
}

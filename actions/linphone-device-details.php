<?php
require_once __DIR__."/../lib/linphone.php";
$required_params = array("domain_uuid", "device_uuid");

function do_action($body) {
    if($error = linphone_check_installed()) {
        return $error;
    }
    if($error = linphone_check_uuids($body, array("domain_uuid", "device_uuid"))) {
        return $error;
    }

    $domain_name = linphone_domain_name($body->domain_uuid);
    if(!$domain_name) {
        return array("error" => "domain not found", "code" => 404);
    }

    $devices = linphone_get_devices($body->domain_uuid, $domain_name, $body->device_uuid);
    if(sizeof($devices) == 0) {
        return array("error" => "device not found", "code" => 404);
    }
    return $devices[0];
}

<?php
require_once __DIR__."/../lib/linphone.php";
$required_params = array("domain_uuid");

function do_action($body) {
    if($error = linphone_check_installed()) {
        return $error;
    }
    if($error = linphone_check_uuids($body, array("domain_uuid", "extension_uuid"))) {
        return $error;
    }

    $domain_name = linphone_domain_name($body->domain_uuid);
    if(!$domain_name) {
        return array("error" => "domain not found", "code" => 404);
    }

    $extension_uuid = isset($body->extension_uuid) ? $body->extension_uuid : null;
    return linphone_get_devices($body->domain_uuid, $domain_name, null, $extension_uuid);
}

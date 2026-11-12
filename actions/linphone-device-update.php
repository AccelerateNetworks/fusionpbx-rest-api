<?php
require_once __DIR__."/../lib/linphone.php";
$required_params = array("domain_uuid", "device_uuid");

function do_action($body) {
    if($error = linphone_check_installed()) {
        return $error;
    }
    if($error = linphone_check_uuids($body, array("domain_uuid", "device_uuid", "extension_uuid"))) {
        return $error;
    }

    $domain_name = linphone_domain_name($body->domain_uuid);
    if(!$domain_name) {
        return array("error" => "domain not found", "code" => 404);
    }

    if(sizeof(linphone_get_devices($body->domain_uuid, $domain_name, $body->device_uuid)) == 0) {
        return array("error" => "device not found", "code" => 404);
    }

    // validate everything before changing anything
    $set = array();
    $parameters = array();
    if(isset($body->extension_uuid)) {
        if(!linphone_extension_in_domain($body->domain_uuid, $body->extension_uuid)) {
            return array("error" => "extension not found in domain", "code" => 400);
        }
        $set[] = "extension_uuid = :extension_uuid";
        $parameters['extension_uuid'] = $body->extension_uuid;
    }

    if(property_exists($body, "name")) {
        if(!is_null($body->name) && !is_scalar($body->name)) {
            return array("error" => "name must be a string", "code" => 400);
        }
        $set[] = "name = :name";
        $parameters['name'] = (string)$body->name;
    }

    if(isset($body->regenerate_provisioning_secret) && linphone_parse_bool($body->regenerate_provisioning_secret)) {
        // invalidates the old provisioning URL/QR code; already-provisioned clients keep their config
        // until they next fetch it, at which point the old URL returns 401
        $set[] = "provisioning_secret = :provisioning_secret";
        $parameters['provisioning_secret'] = linphone_new_provisioning_secret();
    }

    $profiles = null;
    if(property_exists($body, "profiles")) {
        list($error, $profiles) = linphone_validate_profiles($body->domain_uuid, is_null($body->profiles) ? array() : $body->profiles);
        if($error) {
            return $error;
        }
    }

    if(sizeof($set) == 0 && is_null($profiles)) {
        return array("error" => "nothing to update", "code" => 400);
    }

    if(sizeof($set) > 0) {
        $sql = "UPDATE linphone_devices SET ".implode(", ", $set)." WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid";
        $parameters['domain_uuid'] = $body->domain_uuid;
        $parameters['device_uuid'] = $body->device_uuid;
        $database = new database;
        $database->execute($sql, $parameters);
        unset($parameters);
    }

    if(!is_null($profiles)) {
        linphone_set_device_profiles($body->domain_uuid, $body->device_uuid, $profiles);
    }

    $devices = linphone_get_devices($body->domain_uuid, $domain_name, $body->device_uuid);
    return $devices[0];
}

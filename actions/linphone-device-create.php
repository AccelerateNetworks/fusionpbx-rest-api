<?php
require_once __DIR__."/../lib/linphone.php";
$required_params = array("domain_uuid", "extension_uuid");

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

    if(!linphone_extension_in_domain($body->domain_uuid, $body->extension_uuid)) {
        return array("error" => "extension not found in domain", "code" => 400);
    }

    $profiles = array();
    if(isset($body->profiles)) {
        list($error, $profiles) = linphone_validate_profiles($body->domain_uuid, $body->profiles);
        if($error) {
            return $error;
        }
    }

    $name = "";
    if(isset($body->name)) {
        if(!is_scalar($body->name)) {
            return array("error" => "name must be a string", "code" => 400);
        }
        $name = (string)$body->name;
    }

    $device_uuid = uuid();
    $sql = "INSERT INTO linphone_devices (device_uuid, provisioning_secret, upload_secret, name, domain_uuid, extension_uuid) VALUES (:device_uuid, :provisioning_secret, :upload_secret, :name, :domain_uuid, :extension_uuid)";
    $parameters['device_uuid'] = $device_uuid;
    $parameters['provisioning_secret'] = linphone_new_provisioning_secret();
    $parameters['name'] = $name;
    $parameters['domain_uuid'] = $body->domain_uuid;
    $parameters['extension_uuid'] = $body->extension_uuid;

    // upload_secret is UNIQUE. A collision of 128 random bits is astronomically unlikely,
    // but retry like the linphone app's own device_edit.php does.
    for($attempt = 0; $attempt < 5; $attempt++) {
        $parameters['upload_secret'] = linphone_new_upload_secret();
        try {
            $database = new database;
            $database->execute($sql, $parameters);
            break;
        } catch(PDOException $e) {
            if($e->getCode() === '23505' && strpos($e->getMessage(), 'upload_secret') !== false) {
                continue;
            }
            error_log("linphone-device-create: ".$e->getMessage());
            return array("error" => "error adding device");
        }
    }
    unset($parameters);

    // database->execute() doesn't reliably report failure, so check the device was actually added
    $devices = linphone_get_devices($body->domain_uuid, $domain_name, $device_uuid);
    if(sizeof($devices) == 0) {
        return array("error" => "error adding device");
    }

    if(sizeof($profiles) > 0) {
        linphone_set_device_profiles($body->domain_uuid, $device_uuid, $profiles);
        $devices = linphone_get_devices($body->domain_uuid, $domain_name, $device_uuid);
    }

    return $devices[0];
}

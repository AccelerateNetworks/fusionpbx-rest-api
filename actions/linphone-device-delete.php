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

    $sql = "SELECT device_uuid FROM linphone_devices WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $parameters['device_uuid'] = $body->device_uuid;
    $database = new database;
    if(!$database->select($sql, $parameters, 'column')) {
        return array("error" => "device not found", "code" => 404);
    }

    $sql = "DELETE FROM linphone_profile_devices WHERE device_uuid = :device_uuid AND domain_uuid = :domain_uuid";
    $database = new database;
    $database->execute($sql, $parameters);

    $sql = "DELETE FROM linphone_devices WHERE device_uuid = :device_uuid AND domain_uuid = :domain_uuid";
    $database = new database;
    $database->execute($sql, $parameters);

    // database->execute() doesn't reliably report failure, so check the device is actually gone
    $sql = "SELECT device_uuid FROM linphone_devices WHERE domain_uuid = :domain_uuid AND device_uuid = :device_uuid";
    $database = new database;
    if($database->select($sql, $parameters, 'column')) {
        return array("error" => "error deleting device");
    }

    return array("device_uuid" => $body->device_uuid, "deleted" => true);
}

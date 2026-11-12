<?php
require_once __DIR__."/../lib/linphone.php";
$required_params = array("domain_uuid");

function do_action($body) {
    if($error = linphone_check_installed()) {
        return $error;
    }
    if($error = linphone_check_uuids($body, array("domain_uuid"))) {
        return $error;
    }

    $sql = "SELECT p.profile_uuid, p.name,";
    $sql .= " (SELECT COUNT(*) FROM linphone_profile_settings s WHERE s.profile_uuid = p.profile_uuid AND s.domain_uuid = p.domain_uuid) AS setting_count,";
    $sql .= " (SELECT COUNT(*) FROM linphone_profile_devices d WHERE d.profile = p.profile_uuid AND d.domain_uuid = p.domain_uuid) AS device_count";
    $sql .= " FROM linphone_profiles p WHERE p.domain_uuid = :domain_uuid ORDER BY p.name";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    $profiles = $database->select($sql, $parameters, 'all');
    if(!$profiles) {
        return array();
    }

    foreach($profiles as &$profile) {
        $profile['setting_count'] = (int)$profile['setting_count'];
        $profile['device_count'] = (int)$profile['device_count'];
    }
    return $profiles;
}

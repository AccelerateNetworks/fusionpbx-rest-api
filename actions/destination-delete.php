<?php
require_once __DIR__."/../lib/destinations.php";
$required_params = array("domain_uuid");

function do_action($body) {
    list($destination, $error) = destination_find_from_body($body);
    if($error) {
        return $error;
    }

    // remove the destination and its inbound route, like FusionPBX's own destination delete
    $array["destinations"][0]["destination_uuid"] = $destination['destination_uuid'];
    $array["destinations"][0]["domain_uuid"] = $destination['domain_uuid'];
    if(is_uuid($destination['dialplan_uuid'] ?? "")) {
        $array["dialplan_details"][0]["dialplan_uuid"] = $destination['dialplan_uuid'];
        $array["dialplans"][0]["dialplan_uuid"] = $destination['dialplan_uuid'];
    }

    destination_grant_permissions(array("destination_delete", "dialplan_delete", "dialplan_detail_delete"));

    $database = new database;
    $database->app_name = 'rest_api';
    $database->app_uuid = DESTINATION_REST_API_APP_UUID;
    $database->delete($array);

    // database->delete() doesn't reliably report failure, so check the destination is actually gone
    $sql = "SELECT destination_uuid FROM v_destinations WHERE destination_uuid = :destination_uuid";
    $parameters['destination_uuid'] = $destination['destination_uuid'];
    $database = new database;
    if($database->select($sql, $parameters, 'column')) {
        return array("error" => "error deleting destination");
    }

    destination_clear_cache($destination);

    return array(
        "destination_uuid" => $destination['destination_uuid'],
        "destination_number" => $destination['destination_number'],
        "deleted" => true
    );
}

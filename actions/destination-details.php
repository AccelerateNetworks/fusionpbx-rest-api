<?php
$required_params = array("number");
function do_action($body) {
    $sql = "SELECT * FROM v_destinations WHERE destination_number = :number";
    $parameters['number'] = $body->number;
    $database = new database;
    $extension = $database->select($sql, $parameters, 'row');
    if(!$extension) {
        return array("error" => "no such destination", "code" => 404);
    }

    // destination_actions is JSON-encoded in the DB. parse it here (#5)
    if($extension['destination_actions']) {
        $extension['destination_actions'] = json_decode($extension['destination_actions']);
    }

    return $extension;
}

<?php
$required_params = array();

function do_action($body) {
    if(!$body->domain_uuid && !$body->domain_name) {
        return array("error" => "missing required parameter domain_uuid or domain_name", "code" => 400);
    }

    if($body->domain_uuid) {
        $sql = "SELECT * FROM v_domains WHERE domain_uuid = :domain_uuid";
        $parameters['domain_uuid'] = $body->domain_uuid;
    } else {
        $sql = "SELECT * FROM v_domains WHERE domain_name = :domain_name";
        $parameters['domain_name'] = $body->domain_name;
    }
    $database = new database;
    $domain = $database->select($sql, $parameters, 'row');
    if(!$domain) {
        return array("error" => "domain not found", "code" => 404);
    }
    return $domain;
}

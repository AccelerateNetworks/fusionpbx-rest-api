<?php
if(!$_POST["domain_uuid"] && !$_POST['domain_name']) {
    echo json_encode(array("error" => "missing required parameter domain_uuid or domain_name"));
    die();
}

if($_POST['domain_uuid']) {
    $sql = "SELECT * FROM v_domains WHERE domain_uuid = :domain_uuid";
    $parameters['domain_uuid'] = $_POST['domain_uuid'];
} else {
    $sql = "SELECT * FROM v_domains WHERE domain_name = :domain_name";
    $parameters['domain_name'] = $_POST['domain_name'];
}
$database = new database;
$extension = $database->select($sql, $parameters, 'row');
echo json_encode($extension);

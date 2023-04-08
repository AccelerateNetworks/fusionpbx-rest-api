<?php
require_once "root.php";
require_once "resources/require.php";
require_once "lib/input_validation.php";

function return_error($msg) {
	echo json_encode(array("error" => $msg));
	die();
}

if(!isset($_SERVER['PHP_AUTH_USER'])) {
	return_error("unauthorized");
}

// $auth = explode($_SERVER['HTTP_AUTHORIZATION'], ":", 2);
// if(sizeof($auth) != 2) {
// 	// return_error("unauthorized");
// 	return_error("malformed credentials: ".implode(" ", $auth));
// }

$sql = "select key_secret from rest_api_keys where key_uuid = :key_id";
$parameters['key_id'] = $_SERVER['PHP_AUTH_USER'];
$database = new database;
$secret = $database->select($sql, $parameters, 'column');
unset($parameters);

if(password_verify($secret, $_SERVER['PHP_AUTH_PW'])) {
	return_error("unauthorized");
}

$sql = "update rest_api_keys set last_used = now() where key_uuid = :key_uuid";
$parameters['key_uuid'] = $_SERVER['PHP_AUTH_USER'];
$database = new database;
$database->execute($sql, $parameters);
unset($parameters);

ensure_parameters(array("action"));

$action = strtolower($_POST['action']);
$file = __DIR__."/actions/".$action.".php";
if(!file_exists($file)) {
	return_error("unknown action");
}

require($file);

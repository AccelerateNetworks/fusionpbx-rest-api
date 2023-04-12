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

// get the hash of the secret key for this key id out of the database
$sql = "SELECT key_secret FROM rest_api_keys WHERE key_uuid = :key_id";
$parameters['key_id'] = $_SERVER['PHP_AUTH_USER'];
$database = new database;
$secret = $database->select($sql, $parameters, 'column');
if(!$secret) {
	return_error("unauthorized");
}

// verify the hash
if(password_verify($secret, $_SERVER['PHP_AUTH_PW'])) {
	return_error("unauthorized");
}

// set the key last used time
$sql = "UPDATE rest_api_keys SET last_used = NOW() WHERE key_uuid = :key_id";
$database = new database;
$result = $database->execute($sql, $parameters);
unset($parameters);

ensure_parameters(array("action"));

$action = strtolower($_POST['action']);
$file = __DIR__."/actions/".$action.".php";
if(!file_exists($file)) {
	return_error("unknown action");
}

require($file);

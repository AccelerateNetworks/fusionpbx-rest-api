<?php
require_once "root.php";
require_once "resources/require.php";
require_once "lib/input_validation.php";

function return_error($msg, $code=500) {
	http_response_code($code);
	echo json_encode(array("error" => $msg));
	die();
}

if(!isset($_SERVER['PHP_AUTH_USER'])) {
	return_error("unauthorized", 401);
}

// get the hash of the secret key for this key id out of the database
$sql = "SELECT key_secret FROM rest_api_keys WHERE key_uuid = :key_id";
$parameters['key_id'] = $_SERVER['PHP_AUTH_USER'];
$database = new database;
$secret = $database->select($sql, $parameters, 'column');
if(!$secret) {
	return_error("unauthorized", 401);
}

// verify the hash
if(password_verify($secret, $_SERVER['PHP_AUTH_PW'])) {
	return_error("unauthorized", 401);
}

// set the key last used time
$sql = "UPDATE rest_api_keys SET last_used = NOW() WHERE key_uuid = :key_id";
$database = new database;
$result = $database->execute($sql, $parameters);
unset($parameters);

$body = json_decode(file_get_contents('php://input'));

$validation_errors = ensure_parameters($body, array("action"));
if($validation_errors) {
	return_error($validation_errors, 400);
}

$action = strtolower($body->action);
$file = __DIR__."/actions/".$action.".php";
if($body->app) {
	$app = $body->app;
	$app_dir = __DIR__."/../".$app;
	$app_index = $app_dir."/app_api.php";
	if(!file_exists($app_index)) {
		return_error(array("error" => "unknown app"), 400);
	}
	include($app_index);
	if($app_api[$app][$action]) {
		$file = $app_dir."/".$app_api[$app][$action];
	}
}

if(!file_exists($file)) {
	return_error("unknown action", 400);
}

include($file);
$validation_errors = ensure_parameters($body, $required_params);
if($validation_errors) {
	return_error($validation_errors, 400);
}

if(function_exists('do_action')) {
	$resp = do_action($body);
	if($resp['code']) {
		http_response_code($resp['code']);
		unset($resp['code']);
	} elseif($resp['error']) {
		http_response_code(500);
	}

	echo json_encode($resp);
}

<?php
require_once "root.php";
require_once "resources/require.php";
$app_settings = json_decode(file_get_contents(__DIR__."/settings.json"), true);

if(!isset($_POST['token']) || in_array($_POST['token'], $settings['slack']['token'])) {
	error_log("Invalid token ".$_POST['token']);
	die("Invalid token");
}

if(isset($_POST['action'])) {
	$action = strtolower($_POST['action']);
	$settings = array();
	if(isset($app_settings[$action])) {
		$settings = $app_settings[$action];
	} else {
		$settings = $app_settings;
	}
	$file = __DIR__."/actions/".$action.".php";
	if(file_exists($file)) {
		require($file);
	} else {
			echo json_encode(array("error" => "Unknown action!"))."\n";
	}
} else {
	echo json_encode(array("error" => "Unknown action!"))."\n";
}

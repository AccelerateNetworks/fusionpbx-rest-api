<?php
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {die("Failed to connect");}


$response = event_socket_request($fp, "api callcenter_config queue list members ".$_POST['queue']);

echo json_encode(array("result" => explode("\n", $response)));

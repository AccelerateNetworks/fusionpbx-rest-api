<?php
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {die("Failed to connect");}

$response = json_decode(event_socket_request($fp, "api show registrations"." as json"), true);
if($response['row_count'] > 0) {
  echo json_encode($response['rows'][0]);
} else {
  die(json_encode(array("error"=>"No registrations found")));
}

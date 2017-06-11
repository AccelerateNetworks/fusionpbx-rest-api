<?php
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {die("Failed to connect");}

$from = "{effective_caller_id_number=".$_POST['cid']."}sofia/gateway/".$settings['gateway']."/".$settings['prefix'].intval($_POST['from']);
$to = "[effictive_caller_id_number=".$_POST['cid']."]sofia/gateway/".$settings['gateway']."/".$settings['prefix'].intval($_POST['to']);
$command = "api originate ".$from." '&bridge(".$to.")' XML default LEGIT_CALL ".$_POST['cid'];
$response = event_socket_request($fp, $command);
$response = explode(" ", $response, 2);
$success = False;
$call_uuid = NULL;
if($response[0] == "+OK") {
  $success = True;
  $call_uuid = trim($response[1]);
}

echo json_encode(array("command" => $command, "success"=>$success, "call_uuid"=>$call_uuid));

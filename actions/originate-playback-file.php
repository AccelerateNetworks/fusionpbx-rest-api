<?php
// NOTE: Doesn't work anymore because $settings is gone
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {die("Failed to connect");}

$to = "{ignore_early_media=true,originate_timeout=30,effective_caller_id_number=".$_POST['cid']."}sofia/gateway/".$settings['gateway']."/".$settings['prefix'].intval($_POST['to']);
if (substr($_POST['file'], 0, 28) === "/usr/share/freeswitch/sounds") {
  $file = $_POST['file'];
} elseif(substr($_POST['file'], 0, 4) === "http") {
  $file = $_POST['file'];
} else {
  $file = "/usr/share/freeswitch/sounds/en/us/callie/ivr/8000/ivr-congratulations_you_pressed_star.wav";
}

$command = "api originate ".$to." '&playback(".$file.")' XML default LEGIT_CALL ".$_POST['cid'];
$response = event_socket_request($fp, $command);
$response = explode(" ", $response, 2);
$success = False;
$call_uuid = NULL;
if($response[0] == "+OK") {
  $success = True;
  $call_uuid = trim($response[1]);
}

echo json_encode(array("command" => $command, "success"=>$success, "call_uuid"=>$call_uuid));

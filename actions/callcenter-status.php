<?php
$fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
if (!$fp) {die("Failed to connect");}


$response = event_socket_request($fp, "api callcenter_config queue list members ".$_POST['queue']);

$lines = explode("\n", trim($response));

$status = array_pop($lines);
if($status != "+OK") {
  die(json_encode(array("success" => False, "reason" => $status)));
}

$keys = explode("|", array_shift($lines));

$out = array();

foreach($lines as $orig_line) {
  $line = array();
  foreach(explode("|", $orig_line) as $key => $value) {
    $line[$keys[$key]] = $value;
  }
  $out[] = $line;
}


echo json_encode($out);

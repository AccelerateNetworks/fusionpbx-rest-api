<?php
function parse_fs($command) {
  $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
  if (!$fp) {
    die(json_encode(array("success" => false, "reason" => "Failed to connect to event socket")));
  }

  $response = event_socket_request($fp, $command);

  $lines = explode("\n", trim($response));

  $status = array_pop($lines);

  if($status != "+OK") {
    die(json_encode(array("success" => false, "reason" => $status)));
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

  return $out;
}

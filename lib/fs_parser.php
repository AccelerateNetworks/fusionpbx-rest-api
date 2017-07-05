<?php
function parse_fs($command) {
  $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
  if (!$fp) {
    die(json_encode(array("success" => False, "reason" => "Failed to connect to event socket")));
  }
  $response = event_socket_request($fp, $command);
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

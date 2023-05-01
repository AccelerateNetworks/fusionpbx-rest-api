<?php
$required_params = array("domain_uuid", "caller_id_number", "destination_a", "destination_b");

function do_action($body) {
  $sql = "SELECT domain_name FROM v_domains WHERE domain_uuid = :domain_uuid";
  $parameters['domain_uuid'] = $body->domain_uuid;
  $database = new database;
  $domain_name = $database->select($sql, $parameters, 'column');
  unset($parameters);
  if(!$domain_name) {
      return array("error" => "domain not found");
  }

  $fp = event_socket_create($_SESSION['event_socket_ip_address'], $_SESSION['event_socket_port'], $_SESSION['event_socket_password']);
  if (!$fp) {
    return json_encode(array("error" => "internal_server_error", "message" => "failed to connect to FreeSWITCH"));
  }


  $cid_name = "";
  if($body->caller_id_name) {
    $cid_name = ",effective_caller_id_name=".$body->caller_id_name;
  }
  $leg_prefix = "{ignore_early_media=true,originate_timeout=30,effective_caller_id_number=".$body->caller_id.$cid_name."}loopback/";
  $leg_suffix = "/".$domain_name;

  $command = "api originate ".$leg_prefix.$body->destination_b.$leg_suffix." '&bridge(".$leg_prefix.$body->destination_a.$leg_suffix.")'";
  $response = event_socket_request($fp, $command);
  $response = explode(" ", $response, 2);
  $success = False;
  $call_uuid = NULL;
  if($response[0] == "+OK") {
    $success = True;
    $call_uuid = trim($response[1]);
  }

  return array("success"=>$success, "call_uuid"=>$call_uuid);
}

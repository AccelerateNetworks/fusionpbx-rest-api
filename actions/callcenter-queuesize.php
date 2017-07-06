<?php
require('lib/fs_parser.php');
$counts = array();
$users = parse_fs("api callcenter_config queue list members ".$_POST['queue']);
foreach($users as $user) {
  $state = $user['state'];
  if(!isset($counts[$state])) {
    $counts[$state] = 0;
  }
  $counts[$state]++;
}
echo json_encode($counts);

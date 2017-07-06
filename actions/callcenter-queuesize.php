<?php
require('lib/fs_parser.php');
$counts = array();
$users = parse_fs("api callcenter_config queue list members ".$_POST['queue']);
foreach($users as $user) {
  if(!in_array($user['state'], $counts)) {
    $counts[$user['state']] = 0;
  }
  $counts[$user['state']]++;
}
echo json_encode($counts);

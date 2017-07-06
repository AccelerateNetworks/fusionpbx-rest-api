<?php
require('lib/fs_parser.php');
$counts = array();
foreach(parse_fs("api callcenter_config queue list members ".$_POST['queue']) as $user) {
  if(!in_array($counts, $user['state'])) {
    $counts[$user['state']] = 0;
  }
  $counts[$user['state']]++;
}
echo json_encode($counts);

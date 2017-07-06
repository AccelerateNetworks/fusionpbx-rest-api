<?php
require('lib/fs_parser.php');
echo json_encode(parse_fs("api callcenter_config agent list"));

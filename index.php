<?php
/*
	GNU Public License
	Version: GPL 3
*/
require_once "root.php";
require_once "resources/require.php";
require_once "resources/check_auth.php";
require_once "resources/header.php";
require_once "resources/paging.php";

?>
<code>https://<?php echo $_SERVER['HTTP_HOST']; ?>/app/rest_api/rest.php</code>
<?php
require_once "footer.php";

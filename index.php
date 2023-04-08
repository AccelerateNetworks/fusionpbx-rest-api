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

if(!permission_exists('rest_api_manage_keys')) {
	echo "permission denied";
    require_once "resources/footer.php";
    die();
}

echo modal::create([
	'id'=>'modal-delete',
	'type'=>'delete',
	'actions'=>button::create([
		'type'=>'submit',
		'label'=>"delete",
		'icon'=>'check',
		'id'=>'btn_delete',
		'style'=>'float: right; margin-left: 15px;',
		'collapse'=>'never',
		'name'=>'action',
		'value'=>'delete',
		'onclick'=>"modal_close();"
	]
)]);

echo "<div class='action_bar' id='action_bar'>\n";
echo "	<div class='heading'><b>REST API Keys</b></div>\n";
echo "	<div class='actions'>\n";
echo button::create(['type'=>'button','label'=>"New",'icon'=>$_SESSION['theme']['button_icon_add'],'id'=>'btn_add','name'=>'btn_add','link'=>'key_edit.php']);
echo "	</div>\n";
echo "	<div style='clear: both;'></div>\n";
echo "</div>\n";
echo "<br /><br />\n";
echo "endpoint: <code>https://".$_SERVER['HTTP_HOST']."/app/rest_api/rest.php</code>\n";

$sql = "select key_uuid, name, created, last_used from rest_api_keys";
$database = new database;
$keys = $database->select($sql, null, 'all');
unset($parameters);
?>
<table class="table">
<tr>
    <th>Name</th>
	<th>Key ID</th>
	<th>Created</th>
	<th>Last Used</th>
	<th>Actions</th>
</tr>
<?php
foreach($keys as $key) {
?>
<tr>
	<td><a href="key_edit.php?key_uuid=<?php echo $key['key_uuid']; ?>"><?php echo $key['name']; ?></a></td>
	<td><a href="key_edit.php?key_uuid=<?php echo $key['key_uuid']; ?>"><?php echo $key['key_uuid']; ?></a></td>
	<td><?php echo $key['created']; ?></td>
	<td><?php echo $key['last_used']; ?></td>
	<td class="middle button"><?php
		echo button::create(['type'=>'button','label'=>'doesnt work','icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"modal_open('modal-delete','btn_delete');"]);
	?></td>
</tr>
<?php
}

echo "</table>";

require_once "footer.php";

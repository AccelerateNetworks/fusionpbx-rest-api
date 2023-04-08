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

if($_POST['key_uuid']) { // update
    $sql = "UPDATE rest_api_keys SET name = :name WHERE key_uuid = :key_uuid";
    $parameters['key_uuid'] = $_POST['key_uuid'];
    $parameters['name'] = $_POST['name'];
    $database = new database;
    $database->execute($sql, $parameters);
    
    header('Location: key_edit.php?key_uuid='.$parameters['key_uuid'], false, 302);
    unset($parameters);
    die();
}

$key_uuid = $_GET['key_uuid'];
$name = "";
$key_secret = null;

if($_GET['key_uuid']) {
    $sql = "SELECT name FROM rest_api_keys WHERE key_uuid = :key_uuid";
    $parameters['key_uuid'] = $key_uuid;
    $database = new database;
    $name = $database->select($sql, $parameters, 'column');
    unset($parameters);
} else { // key_uuid is unset, generate a new key
    $key_uuid = uuid();
    $key_secret = generate_password(20, 3);

    $sql = "INSERT INTO rest_api_keys (key_uuid, key_secret, created) VALUES (:key_uuid, :key_secret, now())";
    $parameters['key_uuid'] = $key_uuid;
    $parameters['key_secret'] = password_hash($key_secret, PASSWORD_DEFAULT, array('cost' => 10));
    $database = new database;
    $database->execute($sql, $parameters);
    unset($parameters);
}

echo "<form method='post' action='index.php'>";
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
echo "<input type='hidden' name='key_uuid' id='key_uuid' value='".$key_uuid."' />";
echo "</form>";

echo "<form method='post' name='frm' id='frm'>\n";
echo "<input type='hidden' name='key_uuid' value='".$key_uuid."' />";

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
echo button::create(['type'=>'button','label'=>"back",'icon'=>$_SESSION['theme']['button_icon_back'],'id'=>'btn_back','style'=>'margin-right: 15px;','link'=>'index.php']);
echo button::create(['type'=>'button','label'=>'Delete','icon'=>$_SESSION['theme']['button_icon_delete'],'onclick'=>"modal_open('modal-delete','btn_delete');"]);
echo button::create(['type'=>'submit','label'=>"save", 'icon'=>$_SESSION['theme']['button_icon_save'],'id'=>'btn_save','style'=>'margin-left: 15px;']);
echo "	</div>\n";
echo "	<div style='clear: both;'></div>\n";
echo "</div>\n";
echo "<br /><br />\n";
echo "<table width='100%' border='0' cellpadding='0' cellspacing='0'>\n";
if($key_secret) {
    $token = $key_uuid.":".$key_secret;
?>
    <tr>
        <td width="30%" class="vncellreq" valign="top" align="left" nowrap="nowrap">Secret</td>
        <td width="70%" class="vtable" align="left"><b><code><?php echo $token; ?></code></b><?php
            echo button::create(['type'=>'button','icon'=>'clipboard', 'onclick'=>'copy("'.$token.'")']);
        ?><br />will never be shown again</td>
        </td>
    </tr>
<?php } ?>
    <tr>
        <td width="30%" class="vncellreq" valign="top" align="left" nowrap="nowrap">Name</td>
        <td width="70%" class="vtable" align="left">
            <input class="formfld" type="text" name="name" value="<?php echo $name; ?>" /><br />
        </td>
    </tr>
</table>

</form>
<?php
require_once "footer.php";

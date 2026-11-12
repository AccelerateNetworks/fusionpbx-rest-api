<?php
$required_params = array("domain_uuid", "extension_uuid");

function do_action($body) {
    $sql = "SELECT * FROM v_extensions WHERE domain_uuid = :domain_uuid AND extension_uuid = :extension_uuid";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $parameters['extension_uuid'] = $body->extension_uuid;
    $database = new database;
    $extension = $database->select($sql, $parameters, 'row');
    unset($parameters);
    if(!$extension) {
        return array("error" => "extension not found", "code" => 404);
    }

    // remove the same things FusionPBX's own extension delete removes along with the extension
    $array["extensions"][0]["extension_uuid"] = $body->extension_uuid;
    $array["extensions"][0]["domain_uuid"] = $body->domain_uuid;
    $array["extension_users"][0]["extension_uuid"] = $body->extension_uuid;
    $array["extension_users"][0]["domain_uuid"] = $body->domain_uuid;
    if(isset($extension['follow_me_uuid']) && is_uuid($extension['follow_me_uuid'])) {
        $array["follow_me"][0]["follow_me_uuid"] = $extension['follow_me_uuid'];
        $array["follow_me_destinations"][0]["follow_me_uuid"] = $extension['follow_me_uuid'];
    }
    $array["ring_group_destinations"][0]["destination_number"] = $extension['extension'];
    $array["ring_group_destinations"][0]["domain_uuid"] = $body->domain_uuid;
    if($extension['number_alias']) {
        $array["ring_group_destinations"][1]["destination_number"] = $extension['number_alias'];
        $array["ring_group_destinations"][1]["domain_uuid"] = $body->domain_uuid;
    }

    $_SESSION["permissions"]["extension_delete"] = true;
    $_SESSION["permissions"]["extension_user_delete"] = true;
    $_SESSION["permissions"]["follow_me_delete"] = true;
    $_SESSION["permissions"]["follow_me_destination_delete"] = true;
    $_SESSION["permissions"]["ring_group_destination_delete"] = true;

    // the voicemail box and its messages are kept unless delete_voicemail is set
    $voicemail_deleted = false;
    if(isset($body->delete_voicemail) && ($body->delete_voicemail === true || $body->delete_voicemail === "true")) {
        $sql = "SELECT voicemail_uuid FROM v_voicemails WHERE domain_uuid = :domain_uuid AND voicemail_id = :voicemail_id";
        $parameters['domain_uuid'] = $body->domain_uuid;
        $parameters['voicemail_id'] = $extension['extension'];
        $database = new database;
        $voicemail_uuid = $database->select($sql, $parameters, 'column');
        unset($parameters);

        if($voicemail_uuid) {
            foreach(array("voicemail_messages", "voicemail_options", "voicemail_destinations", "voicemails") as $table) {
                $array[$table][0]["voicemail_uuid"] = $voicemail_uuid;
                $array[$table][0]["domain_uuid"] = $body->domain_uuid;
            }
            // greetings are linked by mailbox number, not voicemail_uuid
            $array["voicemail_greetings"][0]["voicemail_id"] = $extension['extension'];
            $array["voicemail_greetings"][0]["domain_uuid"] = $body->domain_uuid;

            $_SESSION["permissions"]["voicemail_delete"] = true;
            $_SESSION["permissions"]["voicemail_message_delete"] = true;
            $_SESSION["permissions"]["voicemail_option_delete"] = true;
            $_SESSION["permissions"]["voicemail_destination_delete"] = true;
            $_SESSION["permissions"]["voicemail_greeting_delete"] = true;
            $voicemail_deleted = true;
        }
    }

    $database = new database;
    $database->app_name = 'rest_api';
    $database->app_uuid = '2bfe71d9-e112-4b8b-bcff-75aeb0e06302';
    $database->delete($array);

    // database->delete() doesn't reliably report failure, so check the extension is actually gone
    $sql = "SELECT extension_uuid FROM v_extensions WHERE extension_uuid = :extension_uuid";
    $parameters['extension_uuid'] = $body->extension_uuid;
    $database = new database;
    if($database->select($sql, $parameters, 'column')) {
        return array("error" => "error deleting extension");
    }

    // FreeSWITCH caches directory entries, clear them so the extension stops working right away
    $cache = new cache;
    $cache->delete("directory:".$extension['extension']."@".$extension['user_context']);
    if($extension['number_alias']) {
        $cache->delete("directory:".$extension['number_alias']."@".$extension['user_context']);
    }

    return array(
        "extension_uuid" => $body->extension_uuid,
        "extension" => $extension['extension'],
        "deleted" => true,
        "voicemail_deleted" => $voicemail_deleted
    );
}

# FusionPBX REST API
An HTTP API for [FusionPBX](http://www.fusionpbx.com/).

# Install
To install it, clone into fusionpbx's `app/` folder. Make sure this repo clones into a folder called `rest_api`.
Then log into the FusionPBX web interface, select Advanced -> Upgrade, check Schema and Menu Defaults, press Execute.

# Use

To use the API you must have an API token. superadmins can generate these by opening the REST API app from the FusionPBX application menu. All requests must include a token in the HTTP Authorization header.

Note that when generating a token, the token is shown once and cannot be shown again (you can always generate another one and delete the missed one)

The API endpoint is displayed on the API token page, and is generally in the format `https://<your fusionpbx>/app/rest_api/rest.php`. All requests to it are HTTP POST requests with a JSON
body that must include an `action` parameter with a value of the action you wish to use (see below). For example, the `domain-details` action with parameter `domain_name=fusionpbx.example.net`:

```
$ curl -s --user "5bc14e83-fc4e-4578-99b8-c7151eb2ec54:jM2GQuYgQTkIGE6nJ2SP" -d '{"action": "domain-details", "domain_name": "fusionpbx.example.net"}' https://fusionpbx.example.net/app/rest_api/rest.php | jq
{
  "domain_uuid": "3a644e67-de8f-4798-b07e-6f22c33a656e",
  "domain_parent_uuid": null,
  "domain_name": "fusionpbx.example.net",
  "domain_enabled": true,
  "domain_description": "",
  "insert_date": null,
  "insert_user": null,
  "update_date": null,
  "update_user": null
}
```

# Actions
All actions are defined in the `actions/` directory of this repo. What follows is a best effort attempt to document them.

## `destination-create`

| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain to add the destination to |
| `number`      | yes      | Phone number to add | 
| `extension`   | yes      | Extension to transfer calls for this number to |

Creates a new destination in FusionPBX.

## `destination-details`
| Parameter     | Required | Description |
|---------------|----------|-------------|
| `number`      | yes      | Inbound number to look up |

looks up details for a particular destination

## `domain-details`

| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | no       | UUID of the domain to look up. Required if `domain_name` is not specified. |
| `domain_name` | no       | Name of the domain to look up. Required if `domain_uuid` is not specified. |

looks up details of a domain. Mostly useful for converting between domain uuid and domain name.

## `extension-create`

| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain to add extension to |
| `extension`   | yes      | Extension (number) to create |
| `caller_id_name` | no    | Caller ID name to set for outbound calls from the extension |
| `caller_id_number` | no  | Caller ID number to set for outbound calls from the extension |

create an extension

## `extension-details`

| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | Domain look up extension on |
| `extension_uuid`   | yes      | Extension (by UUID) to look up  |

get all details of an extension

## `extension-list`
| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain to list extensions on |

List number, UUID and a few other details of all extensions on a given domain.

## `extension-update`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | Domain the extension is on |
| `extension_uuid`   | yes      | Extension (by UUID) to update |
| `caller_id_name`   | no       | Sets the effective, outbound and emergency caller ID name, like `extension-create`. An explicit `*_caller_id_name` takes precedence |
| `caller_id_number` | no       | Same as `caller_id_name`, for the caller ID number |

Update an extension. Any of the following fields may also be included, and only the fields included are changed:
`number_alias`, `password` (at least 8 characters), `effective_caller_id_name`, `effective_caller_id_number`, `outbound_caller_id_name`, `outbound_caller_id_number`,
`emergency_caller_id_name`, `emergency_caller_id_number`, `directory_first_name`, `directory_last_name`, `directory_visible`, `directory_exten_visible`, `limit_max`,
`limit_destination`, `missed_call_app`, `missed_call_data`, `toll_allow`, `call_timeout`, `call_group`, `call_screen_enabled`, `user_record`, `hold_music`, `auth_acl`,
`cidr`, `sip_force_contact`, `sip_force_expires`, `mwi_account`, `sip_bypass_media`, `absolute_codec_string`, `force_ping`, `dial_string`, `do_not_disturb`,
`forward_all_destination`, `forward_all_enabled`, `forward_busy_destination`, `forward_busy_enabled`, `forward_no_answer_destination`, `forward_no_answer_enabled`,
`forward_user_not_registered_destination`, `forward_user_not_registered_enabled`, `max_registrations`, `enabled`, `description`. Boolean fields accept JSON `true`/`false`.

The extension number itself cannot be changed; delete and recreate the extension instead. Returns the updated extension, without its SIP password.

```
$ curl -s --user "$KEY_ID:$KEY_SECRET" -d '{"action": "extension-update", "domain_uuid": "3a644e67-de8f-4798-b07e-6f22c33a656e", "extension_uuid": "5a848d4e-a3c2-4bd6-8159-6b1366baffdd", "do_not_disturb": true}' https://fusionpbx.example.net/app/rest_api/rest.php
```

## `extension-delete`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | Domain the extension is on |
| `extension_uuid`   | yes      | Extension (by UUID) to delete |
| `delete_voicemail` | no       | `true` to also delete the extension's voicemail box, messages and greetings. Defaults to keeping them |

Delete an extension, along with what FusionPBX's own extension delete removes: its user assignments, follow me, and ring group destinations that point at its number or number alias.
Recording files on disk are not removed.


## `ringgroup-create`
| Parameter      | Required | Description |
|----------------|----------|-------------|
| `domain_uuid`  | yes      | domain to create the ring group on |
| `name`         | yes      | name for the ring group |
| `extension`    | yes      | Extension to route TO the ring group |
| `destinations` | yes      | JSON array of extensions to send calls from the ring group. Example: `[{"number": "100"}, {"number": "101"}, {"number": "102"}]` |
| `strategy`     | yes      | one of: `simultaneous`, `sequence`, `enterprise`, `rollover` or `random` |

Create a ring group

## `originate`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | domain to originate the call from |
| `caller_id_number` | yes      | caller ID number to display for both legs of the call |
| `caller_id_name`   | no       | an optional caller ID name to request. typically will be delivered for internal calls and stripped by the upstream provider for external calls |
| `destination_a`    | yes      | the number to call first  |
| `destination_b`    | yes      | the number to call second |

Call one number (destination_a) and connect the call to another number (destination_b) when it's picked up. The selected domain's internal dialplan is used, so internal extensions may be dialed.

Note that the call is ended when destination_a ends the call, so if one leg isn't expected to hang up, make it destination_b.

Use `destination_b=*9664` to indefinitely play hold music to destination_a.

## `cdr-list`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | domain to list call detail records from |

returns the the last 100 call detail records.

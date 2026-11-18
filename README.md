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

## Destinations
These actions manage inbound destinations (inbound routes), and save them the same way the FusionPBX web interface does: a destination plus its inbound route dialplan.
FreeSWITCH's cached dialplan is cleared after each change, so changes take effect on the next call.

Where a destination is returned, `destination_actions` is a list of what happens to the call, in order, in the same format `destination-create` and `destination-update` accept:

```json
[{"destination_app": "transfer", "destination_data": "100 XML fusionpbx.example.net"}]
```

`destination_app` must be one of `transfer`, `bridge`, `lua`, `hangup`, `playback`, `phrase`, `sleep` or `answer`. Calls to an extension, ring group, IVR, voicemail box etc. are all
`transfer`s; the easiest way to find the right `destination_data` is to set one up in the web interface and look at it with `destination-details`.

### Destination fields
`destination-create` and `destination-update` accept these optional fields:

| Field                          | Description |
|--------------------------------|-------------|
| `destination_number`           | The phone number. Digits, optionally starting with `+` |
| `destination_prefix`           | Country code. Calls match with or without a leading `+` and this prefix. `destination-create` defaults to `1` |
| `destination_actions`          | Where calls go, see above. Must not be empty |
| `extension`                    | Shorthand for `destination_actions` that transfers calls to this extension on the destination's domain. Can't be combined with `destination_actions` |
| `destination_caller_id_name`   | Caller ID name for outbound calls that use this number |
| `destination_caller_id_number` | Caller ID number for outbound calls that use this number. `destination-create` defaults to the number |
| `destination_cid_name_prefix`  | Text added before the caller ID name of incoming calls |
| `destination_hold_music`       | Hold music for calls to this number |
| `destination_distinctive_ring` | Alert-Info header to send for distinctive ringing |
| `destination_accountcode`      | Account code for calls to this number |
| `destination_record`           | `true` to record calls to this number |
| `destination_type_voice`, `destination_type_fax`, `destination_type_text` | `true` to mark the number as used for voice, fax or text |
| `destination_order`            | Dialplan order, defaults to `100` |
| `destination_enabled`          | `false` to disable the destination. Defaults to `true` |
| `destination_description`      | Description |

## `destination-create`

| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain to add the destination to |
| `number`      | yes      | Phone number to add. `destination_number` may be used instead |
| `extension`   | yes, or `destination_actions` | Extension to transfer calls for this number to |

Creates a new inbound destination and returns it. Any of the [destination fields](#destination-fields) may also be included.
Numbers must be unique across all domains.

```
$ curl -s --user "$KEY_ID:$KEY_SECRET" -d '{"action": "destination-create", "domain_uuid": "3a644e67-de8f-4798-b07e-6f22c33a656e", "number": "2065550100", "extension": "100", "destination_description": "main line"}' https://fusionpbx.example.net/app/rest_api/rest.php
```

## `destination-details`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `number`           | no       | Number to look up. Required if `destination_uuid` is not specified |
| `destination_uuid` | no       | Destination to look up. Required if `number` is not specified |
| `domain_uuid`      | no       | Domain the destination is on. Required with `destination_uuid` |

looks up details for a particular destination

## `destination-list`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | Domain to list destinations on |
| `destination_type` | no       | Only list destinations of this type: `inbound`, `outbound` or `local` |

List the destinations on a domain, with their number, actions and a few other details.

## `destination-update`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | Domain the destination is on |
| `destination_uuid` | no       | Destination to update. Required if `number` is not specified |
| `number`           | no       | Number of the destination to update. Required if `destination_uuid` is not specified. To change the number, use `destination_number` |

Update an inbound destination. Include any of the [destination fields](#destination-fields); only the fields included are changed. Returns the updated destination.

The inbound route is rebuilt from the destination's fields, so destinations that use fax detection or conditions, which this API can't rebuild, have to be edited in the web interface.

```
$ curl -s --user "$KEY_ID:$KEY_SECRET" -d '{"action": "destination-update", "domain_uuid": "3a644e67-de8f-4798-b07e-6f22c33a656e", "number": "2065550100", "extension": "101"}' https://fusionpbx.example.net/app/rest_api/rest.php
```

## `destination-delete`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | Domain the destination is on |
| `destination_uuid` | no       | Destination to delete. Required if `number` is not specified |
| `number`           | no       | Number of the destination to delete. Required if `destination_uuid` is not specified |

Delete a destination and its inbound route.

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

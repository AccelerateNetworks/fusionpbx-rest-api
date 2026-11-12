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

## Linphone
These actions manage Linphone clients and profiles for the [fusionpbx-linphone](https://github.com/AccelerateNetworks/fusionpbx-linphone) app, and do the same
things as its web UI. They return HTTP 501 if that app isn't installed. Devices and profiles always belong to a domain, so every action takes a `domain_uuid`.

Devices are returned in this format. `provisioning_url` is the URL (or QR code contents) a Linphone client uses to set itself up, and contains the device's
provisioning secret, so treat it like a password. `profiles` is a list of the UUIDs of the profiles assigned to the device.

```json
{
  "device_uuid": "0b3ac2b4-7d5f-4b61-9a6e-0f3b5d8e2c11",
  "domain_uuid": "3a644e67-de8f-4798-b07e-6f22c33a656e",
  "extension_uuid": "5a848d4e-a3c2-4bd6-8159-6b1366baffdd",
  "extension": "100",
  "name": "front desk laptop",
  "user_agent": "Linphone-Desktop/5.2.6 (front-desk) Windows/10 Qt/5.15.2 LinphoneSDK/5.3.72",
  "provisioning_url": "https://fusionpbx.example.net/app/linphone/provision/index.php?token=...",
  "profiles": ["e1d0c7f8-2a4b-4c39-8f1e-6d2b9a7c5e30"]
}
```

## `linphone-device-list`
| Parameter        | Required | Description |
|------------------|----------|-------------|
| `domain_uuid`    | yes      | Domain to list Linphone devices on |
| `extension_uuid` | no       | Only list devices for this extension |

List Linphone devices on a domain.

## `linphone-device-details`
| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain the device is on |
| `device_uuid` | yes      | Device to look up |

## `linphone-device-create`
| Parameter        | Required | Description |
|------------------|----------|-------------|
| `domain_uuid`    | yes      | Domain to add the device to |
| `extension_uuid` | yes      | Extension the device registers as. Must be on the same domain |
| `name`           | no       | Name for the device. If left blank, it is filled in from the client's hostname the first time it provisions |
| `profiles`       | no       | JSON array of profile UUIDs to assign to the device |

Create a Linphone device and return it, including the `provisioning_url` to give to the client.

```
$ curl -s --user "$KEY_ID:$KEY_SECRET" -d '{"action": "linphone-device-create", "domain_uuid": "3a644e67-de8f-4798-b07e-6f22c33a656e", "extension_uuid": "5a848d4e-a3c2-4bd6-8159-6b1366baffdd"}' https://fusionpbx.example.net/app/rest_api/rest.php
```

## `linphone-device-update`
| Parameter                        | Required | Description |
|----------------------------------|----------|-------------|
| `domain_uuid`                    | yes      | Domain the device is on |
| `device_uuid`                    | yes      | Device to update |
| `extension_uuid`                 | no       | Move the device to a different extension on the same domain |
| `name`                           | no       | New name for the device |
| `profiles`                       | no       | JSON array of profile UUIDs. Replaces the device's profiles with exactly this list; `[]` removes them all |
| `regenerate_provisioning_secret` | no       | `true` to issue a new provisioning secret. The old provisioning URL and QR code stop working |

Update a Linphone device. Only the parameters included are changed. Returns the updated device.
Changes reach a client the next time it fetches its provisioning URL.

## `linphone-device-delete`
| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain the device is on |
| `device_uuid` | yes      | Device to delete |

Delete a Linphone device and its profile assignments. Its provisioning URL stops working. The extension itself is not touched,
so a client that is already set up can still register until the extension's password is changed.

## `linphone-profile-list`
| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain to list profiles on |

List Linphone profiles on a domain, with how many settings each has and how many devices it is assigned to.

## `linphone-profile-details`
| Parameter      | Required | Description |
|----------------|----------|-------------|
| `domain_uuid`  | yes      | Domain the profile is on |
| `profile_uuid` | yes      | Profile to look up |

Returns a profile, its settings, and the UUIDs of the devices it is assigned to:

```json
{
  "profile_uuid": "e1d0c7f8-2a4b-4c39-8f1e-6d2b9a7c5e30",
  "domain_uuid": "3a644e67-de8f-4798-b07e-6f22c33a656e",
  "name": "video off",
  "settings": {"video.automatically_accept": "0", "video.automatically_initiate": "0"},
  "devices": ["0b3ac2b4-7d5f-4b61-9a6e-0f3b5d8e2c11"]
}
```

## `linphone-profile-create`
| Parameter     | Required | Description |
|---------------|----------|-------------|
| `domain_uuid` | yes      | Domain to create the profile on |
| `name`        | yes      | Name for the profile |
| `settings`    | no       | JSON object of Linphone config settings, keyed `section.key`, e.g. `{"sip.media_encryption": "srtp"}`. Values may be strings, numbers or booleans (sent as `1`/`0`) |

Create a Linphone profile and return it in the same format as `linphone-profile-details`.

## `linphone-profile-update`
| Parameter          | Required | Description |
|--------------------|----------|-------------|
| `domain_uuid`      | yes      | Domain the profile is on |
| `profile_uuid`     | yes      | Profile to update |
| `name`             | no       | New name for the profile |
| `settings`         | no       | JSON object of settings to add or change, in the same format as `linphone-profile-create`. A `null` value deletes that setting. Settings not included are left alone |
| `replace_settings` | no       | `true` to delete every existing setting not included in `settings`, so the profile ends up with exactly `settings` |

Update a Linphone profile. Returns the updated profile.

## `linphone-profile-delete`
| Parameter      | Required | Description |
|----------------|----------|-------------|
| `domain_uuid`  | yes      | Domain the profile is on |
| `profile_uuid` | yes      | Profile to delete |

Delete a Linphone profile and its settings, and unassign it from all devices. The response's `devices_unassigned` lists the devices it was removed from.

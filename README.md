# FusionPBX REST API
This README is lacking. For now. This is an app for [FusionPBX](http://www.fusionpbx.com/).

# Install
To install it, clone into fusionpbx's `app/` folder and run the menu upgrades (Log into the web
  interface, Advanced -> Upgrade, check Schema and Menu Defaults, press Execute).

# Use

To use the API you must have an API token. superadmins can generate these by opening the REST API app from the FusionPBX application menu. All requests must include a token in the HTTP Authorization header.

Note that when generating a token, the token is shown once and cannot be shown again (you can always generate another one and delete the missed one)

The API endpoint is displayed on the API token page, and is generally in the format `https://<your fusionpbx>/app/rest_api/rest.php`. All requests to it are HTTP POST requests,
and must include an `action` parameter with a value of the action you wish to use (see below). For example, the `domain-details` action with parameter `domain_name=fusionpbx.example.net`:

```
$ curl -s --user "5bc14e83-fc4e-4578-99b8-c7151eb2ec54:jM2GQuYgQTkIGE6nJ2SP" -d action=domain-details -d domain_name=fusionpbx.example.net https://fusionpbx.example.net/app/rest_api/rest.php | jq
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

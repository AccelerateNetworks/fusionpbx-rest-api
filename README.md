# FusionPBX REST API
This README is lacking. For now. This is an app for [FusionPBX](http://www.fusionpbx.com/).

# Install
To install it, clone into fusionpbx's `app/` folder and run the menu upgrades (Log into the web
  interface, Advanced -> Upgrade, check off Menu Defaults and hit Execute).

Define a settings.json file containing the authorized API keys along with the gateway and any
prefix needed for calls to route if required.

Example settings.json with multiple API tokens using gateway that needs no prefix to make calls:
```
{
  "tokens": [
    "uoF3SoonHuiw9eewahKoh1ooFoov9eiFOhjiey8o",
    "cc5e74ea-6b94-41e8-b743-56abc008d934",
    "random-applications-api-key"
  ],
  "originate": {
    "prefix": "",
    "gateway": "cc5e74ea-6b94-41e8-b743-56abc008d934"
  }
}
```

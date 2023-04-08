<?php
//application details
$apps[$x]['name'] = "REST API";
$apps[$x]['uuid'] = "2bfe71d9-e112-4b8b-bcff-75aeb0e06302";
$apps[$x]['category'] = "App";
$apps[$x]['subcategory'] = "";
$apps[$x]['version'] = "0.1";
$apps[$x]['license'] = "GNU General Public License v3";
$apps[$x]['url'] = "https://github.com/thefinn93/FusionPBX-RESTAPI";
$apps[$x]['description']['en-us'] = "FusionPBX REST API";
$apps[$x]['description']['es-cl'] = "";
$apps[$x]['description']['de-de'] = "";
$apps[$x]['description']['de-ch'] = "";
$apps[$x]['description']['de-at'] = "";
$apps[$x]['description']['fr-fr'] = "";
$apps[$x]['description']['fr-ca'] = "";
$apps[$x]['description']['fr-ch'] = "";
$apps[$x]['description']['pt-pt'] = "";
$apps[$x]['description']['pt-br'] = "";


$y = 0;
$z = 0;

$apps[$x]['db'][$y]['table']['name'] = "rest_api_keys";
$apps[$x]['db'][$y]['table']['parent'] = "";

$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'key_uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'uuid';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'text';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'char(36)';
$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'key identifier';
$z++;

$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'name';
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "longtext";
$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'optional human readable identifier';
$z++;

$apps[$x]['db'][$y]['fields'][$z]['name']['text'] = 'key_secret';
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = "text";
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = "text";
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = "longtext";
$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = 'the secret for authenticating';
$z++;

$apps[$x]['db'][$y]['fields'][$z]['name'] = "created";
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "date this key was created";
$z++;

$apps[$x]['db'][$y]['fields'][$z]['name'] = "last_used";
$apps[$x]['db'][$y]['fields'][$z]['type']['pgsql'] = 'timestamptz';
$apps[$x]['db'][$y]['fields'][$z]['type']['sqlite'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['type']['mysql'] = 'date';
$apps[$x]['db'][$y]['fields'][$z]['description']['en-us'] = "date this key was last used";
$z++;

$y=0;

$apps[$x]['permissions'][$y]['name'] = "rest_api_manage_keys";
$apps[$x]['permissions'][$y]['groups'][] = "superadmin";
$y++;

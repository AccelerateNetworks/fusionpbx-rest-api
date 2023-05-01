<?php
$required_params = array("domain_uuid");

function do_action($body) {
    $fields = array(
        "xml_cdr_uuid",
        "extension_uuid",
        "direction",
        "start_stamp",
        "end_stamp",
        "hangup_cause",
        "duration",
        "missed_call",
        "record_name",
        "bridge_uuid",
        "caller_id_name",
        "caller_id_number",
        "caller_destination",
        "source_number",
        "destination_number",
        "leg",
    );
    $sql = "SELECT ".implode(", ", $fields)." FROM v_xml_cdr WHERE domain_uuid = :domain_uuid ORDER BY end_stamp DESC LIMIT 100";
    $parameters['domain_uuid'] = $body->domain_uuid;
    $database = new database;
    return $database->select($sql, $parameters, 'all');
}

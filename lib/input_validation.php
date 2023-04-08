<?php

function ensure_parameters(array $params) {
    $missing = array();
    foreach($params as $param) {
        if(!$_POST[$param]) {
            $missing = $param;
        }
    }
    if(sizeof($missing) == 0) {
        return true;
    }

    echo json_encode(array("error" => "missing required parameter(s)", "missing_parameters" => $missing));
    die();
}

<?php

function ensure_parameters($body, $required) {
    $missing = array();
    foreach($required as $param) {
        if(!$body->{$param}) {
            $missing = $param;
        }
    }
    if(sizeof($missing) == 0) {
        return false;
    }

    return json_encode(array("error" => "missing required parameter(s)", "missing_parameters" => $missing));
}

<?php

function response_error($data) {
    $response = json_decode($data);
    $error_code = $response->errors[0]->code;
    return $error_code;
}

function write_to_log_error($data) {
    file_put_contents("logs/ddns_error.log", $data, FILE_APPEND | LOCK_EX);
}

function write_to_log_info($data) {
    file_put_contents("logs/ddns_info.log", $data, FILE_APPEND | LOCK_EX);
}

function write_to_cache_zone($data, $zone_id) {
    file_put_contents("cache/zone_".$zone_id.".json", $data, LOCK_EX);
}

function Cache_get_zone($zone_id) {

    if (file_exists("cache/zone_".$zone_id.".json")) {
        $cache = file_get_contents("cache/zone_".$zone_id.".json");
        return $cache;
    } else {
        write_to_log_error("[".time()."] " . 
        "Failed to get zone: ".$zone_id." records from cache.\n"
        );
        return false;
    }

    /*
    $cache = '';
    try {
        $cache = file_get_contents("cache/zone_".$zone_id.".json");
        return $cache;
    } catch (Exception $e) {
        write_to_log_error("[".time()."] " . 
            "Failed to get zone: ".$zone_id." records from cache.\n".
            "Caught exception: " . $e->getMessage() . "\n"
        );
        return false;
    }
    */
}

function API_get_records($auth_email, $auth_key, $zone_id) {

    //-- curl get part ----------------------------------------------
    //
    $header = array(
        'X-Auth-Email: ' . $auth_email,
        'X-Auth-Key: '   . $auth_key,
        'Content-Type: ' . 'application/json'
    );
    
    $curl = curl_init();
    
    curl_setopt($curl, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/". $zone_id ."/dns_records");
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    
    $response = curl_exec($curl);
    curl_close($curl);
    //
    //-- End of curl get part ----------------------------------------

    $errors = response_error($response);
    if ($errors) {
        write_to_log_error("[".time()."] " . 
                           "Failed to get records of zone: " . $zone_id . " (".$errors.")" . " .\n" . 
                           "Response:\n\n".
                           $response .
                           "\n\n"
        );
        return false;
    }

    write_to_cache_zone($response, $zone_id);
    write_to_log_info("[".time()."] " . "Get records of zone: " . $zone_id . " successfully.\n");
    return $response;
}

function init_users($users_array) {
    global $_users;
    foreach ($users_array as $auth_email => $auth_key) {
        $_users[$auth_email] = new User($auth_email, $auth_key);
    }
}

function init_zones($zones_array) {
    global $_zones;
    foreach ($zones_array as $id => $info) {
        $_zones[$id] = new Zone($id, $info);
    }

}

function format_zone_detail($zone_detail_source) {

    $zone_detail = array();
    foreach (json_decode($zone_detail_source)->result as $record) {
        $zone_detail[str_replace('.h-kys.com', '', $record->name)] = $record;
    }
//    $zone_detail["_zone_name"] = $zone_detail_source->result[0]->zone_name;

    return $zone_detail;
}

function has_non_id($record_array) {

    $non_id_keys = array();

    foreach ($record_array as $record_name => $record_id) {
        if (strlen($record_id) != 32 || !ctype_xdigit($record_id) ) {
            array_push($non_id_keys, $record_name);
        }
    }

    return $non_id_keys;

}

function filter_non_id_record($record_id_array, $non_id_array) {
    write_to_log_error("[".time()."] " . 
        "Failed to get record id of record name: " .  implode(" ",$non_id_array) . ", these record will be ignored while the update." . "\n"
    );
    foreach ($non_id_array as $non_id_record_name) {
        unset($record_id_array[$non_id_record_name]);
        return $record_id_array;
    }
}

function generate_final_record_id_array($auth_key,  $zone) {
    $source = API_get_records($zone->P_get_auth_email(), $auth_key, $zone->id);
    $zone_detail = format_zone_detail($source);

    foreach ($zone->P_get_update_record() as $record_name => $record_id) {
        $zone->P_patch_update_record($record_name, $zone_detail[$record_name]->id);
    }

    $final_record_id_array = $zone->P_get_update_record();

    if ($non_id_record_name = has_non_id($final_record_id_array)) {
        $final_record_id_array = filter_non_id_record($final_record_id_array, $non_id_record_name);
        return $final_record_id_array;
    }
    return $final_record_id_array;
}

function patch_to_cloudflare($auth_email, $auth_key, $zone_id, $record_id, $record_name) {

    global $ip;

    $header = array(
        'X-Auth-Email: ' . $auth_email,
        'X-Auth-Key: '   . $auth_key,
        'Content-Type: ' . 'application/json'
    );

    $data = array(
        'content' => $ip
    );
    $data = json_encode($data);

    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, "https://api.cloudflare.com/client/v4/zones/".$zone_id."/dns_records/".$record_id);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PATCH');
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

    $output = curl_exec($curl);
    curl_close($curl);

    if ($errors = response_error($output)) {
        write_to_log_error("[".time()."] " . 
        "Failed to patch record ID ".$record_id." (".$record_name."). Error code:".$errors." \n"
    );
    } else {
        write_to_log_info("[".time()."] " . "Successfully updated record ID ".$record_id." (".$record_name.") \n");
    }

}

?>
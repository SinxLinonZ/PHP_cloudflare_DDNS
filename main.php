<?php

include_once "classes.php";
include_once "functions.php";

include_once "config/users.php";
include_once "config/zones.php";


$_users = array();
init_users($c_users);
$_zones = array();
init_zones($c_zones);

$externalContent = file_get_contents('http://checkip.dyndns.com/');
preg_match('/Current IP Address: \[?([:.0-9a-fA-F]+)\]?/', $externalContent, $m);
$ip = $m[1];


foreach ($_zones as $id => $zone) {

    $final_record_array = array();

    if ($cache = Cache_get_zone($id)) {

        $zone_detail = format_zone_detail($cache);

        foreach ($zone->P_get_update_record() as $record_name => $record_id) {
            $zone->P_patch_update_record($record_name, $zone_detail[$record_name]->id);
        }

        if (has_non_id($zone->P_get_update_record())) {
            $final_record_array = generate_final_record_id_array(
                  $_users[$zone->P_get_auth_email()]->P_get_key(),
                  $zone
            );
        } else {
            $final_record_array = $zone->P_get_update_record();
        }

    } else {
        $final_record_array = generate_final_record_id_array(
              $_users[$zone->P_get_auth_email()]->P_get_key(),
              $zone
        );
    }

    foreach ($final_record_array as $record_name => $record_id) {
        patch_to_cloudflare(
            $zone->P_get_auth_email(),
            $_users[$zone->P_get_auth_email()]->P_get_key(),
            $zone->id,
            $record_id,
            $record_name
        );
    }

}

?>
<?php

class User
{
    public $auth_email;
    private $auth_key;

    function __construct($email, $key) {
        $this->auth_email = $email;
        $this->auth_key = $key;
    }

    public function P_get_key() {
        return $this->auth_key;
    }
}

class Zone
{
    public $id;
    private $auth_email;
    private $update_record = array();

    function __construct($id, $info) {
        $this->id = $id;
        $this->auth_email = $info["auth_email"];
        foreach ($info["update_record"] as $record_name) {
            $this->update_record[$record_name] = '';
        }
    }

    public function P_get_auth_email() {
        return $this->auth_email;
    }

    public function P_get_update_record() {
        return $this->update_record;
    }

    public function P_patch_update_record($record_name, $record_id) {
        $this->update_record[$record_name] = $record_id;
    }

}


?>
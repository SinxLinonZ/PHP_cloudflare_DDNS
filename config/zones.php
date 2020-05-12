<?php

/* ===========================================================
 *  Set your cloudflare zone info here
 * 
 *  syntax:
 *    "zone1_id" => 
 *        array("auth_email" => "user_email_be_used_to_auth", 
 *              "update_record" => 
 *                  array('record_name_to_be_updated1',
 *                        'record_name_to_be_updated2'
 *                  )
 *        ),
 *    "zone2_id" => 
 *        array("auth_email" => "user_email_be_used_to_auth", 
 *              "update_record" => 
 *                  array('record_name_to_be_updated1',
 *                        'record_name_to_be_updated2'
 *                  )
 *        )
 *  
 * 
 * PS: the structure will be like:
 *     Zone_1
 *       |____ auth_email => "email@example.com"
 *       |____ update_record
 *               |____ 'record_name_to_be_updated1'
 *               |____ 'record_name_to_be_updated2'
 * 
 *     Zone_2
 *       |____ auth_email => "email@example.com"
 *       |____ update_record
 *               |____ 'record_name_to_be_updated1'
 *               |____ 'record_name_to_be_updated2'
 * 
 * ==========================================================*/

$c_zones = array(

    "0123456789abcdef0123456789abcdef" => 
        array("auth_email" => "mail@example.com", 
              "update_record" => 
                  array('www',
                  'blog',
                  'anyother'
                  )
        )

);
              
?>
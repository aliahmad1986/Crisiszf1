<?php
class Application_Model_Func_Crypt
{


   static function Encrypt($data)
    {
        $cipher = "aes-256-cbc";
        $encryption_key = '6kl4r6k66uko4dq2jmic122f09c00ew9';
        $iv = '73045h837209u66l';
        $encrypted_data = openssl_encrypt($data, $cipher, $encryption_key, OPENSSL_RAW_DATA, $iv);
        return $encrypted_data;
    }

    static function Decrypt($encrypted_data){
        $cipher = "aes-256-cbc";
        $encryption_key = '6kl4r6k66uko4dq2jmic122f09c00ew9';
        $iv = '73045h837209u66l';
        $decrypted_data = openssl_decrypt($encrypted_data, $cipher, $encryption_key, OPENSSL_RAW_DATA, $iv); 
        return $decrypted_data;
    }


}
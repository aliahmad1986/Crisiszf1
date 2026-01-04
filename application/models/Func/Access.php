<?php

/*
 * Click nbfs://nbhost/SystemFileSystem/Templates/Licenses/license-default.txt to change this license
 * Click nbfs://nbhost/SystemFileSystem/Templates/Scripting/PHPClass.php to edit this template
 */

/**
 * Description of access
 *
 * @author Client 5
 */
class Application_Model_Func_Access
{

    static public function Istimeout()
    {
        $authNamespace = Zend_Registry::get("authNamespace");

        $timestamp = mktime(date("H"), date("i"), date("s"), date('m'), date('d'), date('Y'));
        if ($timestamp - $authNamespace->loginTime > 10000) {
            return  true;
        }
        $authNamespace->loginTime=mktime(date("H"), date("i"), date("s"), date('m'), date('d'), date('Y'));
        return false;
    }
    static public function hasAccess($controller, $action, $module)
    {
        $authNamespace = Zend_Registry::get("authNamespace");
        if ($authNamespace->isAdmin):
            return true;
        endif;
        $userModel = new Application_Model_Func_Users();
        $users = $userModel->getUserById($authNamespace->userid);
        if (empty($users[0]['ACCESS'])) {
            return false;
        }
        $access = json_decode(Application_Model_Func_Crypt::Decrypt(base64_decode($users[0]['ACCESS'])), true);
        $authNamespace->ACCESS = $access;
        $detail = array('module' => $module, 'controller' => $controller, 'action' => strtolower($action));
        $actionNameCerypted = base64_encode(Application_Model_Func_Crypt::Encrypt(json_encode($detail)));

        return $access['action'][$actionNameCerypted] == '1';


    }
    static public function hasToolbarAccess($toolbar)
    {
        $authNamespace = Zend_Registry::get("authNamespace");
        $access = $authNamespace->ACCESS;
       // var_export($toolbar);exit;
        $accessTolbar = array();
        foreach ($toolbar as $key => $val) {
            if ($access['action'][$key] == '1' || $authNamespace->isAdmin) {
                $accessTolbar[] = $val;
            }
        }
        return $accessTolbar;

    }
}

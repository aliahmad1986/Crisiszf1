<?php

class IndexController extends Zend_Controller_Action {

    public function init() {
        /* Initialize action controller here */
        
    }

    public function indexAction() {
        $this->_helper->layout->disableLayout();      
        Zend_Session::forgetMe();
        Zend_Session::destroy();
        $post = $this->getRequest()->getParams();
        $this->view->error = $post['error'];
        $this->view->translate=Zend_Registry::get('Zend_Translate');
    }
    public function getcityAction(){
        $m=new Coding_Model_coding();
        $proviance=$m->GetProvinceList();
     
        foreach($proviance as $pro){
            $provi=$pro['CODE'];
            $city=$m->GetCityList($provi);
         
            $data[$provi]=$city;

        }
        echo json_encode(array("data"=>$data));exit;
    }

    public function loginAction() {
        
        $post = $this->getRequest()->getPost();
        if ($post['usr'] == 'Administrator') {
            if ($post['pwd'] == '123456') {
                $authNamespace = Zend_Registry::get("authNamespace");
                $authNamespace->isLogin = true;
                $authNamespace->isAdmin = true;
                $authNamespace->name = "مدیر سیستم";
                $authNamespace->userid=0;
                $authNamespace->personid =0;
                $authNamespace->loginTime = mktime(date("H"), date("i"), date("s"), date('m'), date('d'), date('Y'));

                $this->_redirect('/Dashboard');
            } else {
                $this->_redirect('/index?error=1');
            }
        } else {
            $userfunc = new Application_Model_Func_Users();

            $user = $userfunc->checkpass($post['usr'], $post['pwd']);

            if (count($user) > 0) {
                if(!$user[0]['ACTIVE']){
                    $this->_redirect('/index?error=4');
                }
                $authNamespace = Zend_Registry::get("authNamespace");
                $authNamespace->isLogin = true;
                $authNamespace->name = $user[0]['NAME_PERSON'].'  '.$user[0]['FAMIL'];
                $authNamespace->userid = $user[0]['ID_USER'];
                $authNamespace->personid = $user[0]['ID_PERSON'];
                $authNamespace->groupId = $user[0]['ID'];
                $authNamespace->companyId = $user[0]['COMPANYID'];
                $authNamespace->ACCESS = json_decode(Application_Model_Func_Crypt::Decrypt(base64_decode($user[0]['ACCESS'])), true);
                $authNamespace->loginTime = mktime(date("H"), date("i"), date("s"), date('m'), date('d'), date('Y'));

       
                $this->_redirect('/Dashboard');

            } else {
                $this->_redirect('/index?error=1');
            }
        }
    }

    public function logoutAction() {
        Zend_Session::forgetMe();
        Zend_Session::destroy();
        $this->_redirect('/');
    }

}

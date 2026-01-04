<?php

class Coding_MngcodingController extends zend_controller_action
{
    public function init()
    {
        $this->_helper->layout->disableLayout();
        define('ROOT_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
        $authNamespace = Zend_Registry::get("authNamespace");
        if (!$authNamespace->isLogin) {
            $this->_redirect('/');
        }

    }
    
    public function indexAction()
    {

        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong> کدینگ </strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Coding', 'controller' => 'Mngcoding', 'action' => 'addcodding')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addCodingModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Coding', 'controller' => 'Mngcoding', 'action' => 'editcodding')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editCodingModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'Product', 'controller' => 'Mngcategory', 'action' => 'deletecategory')
                )
            )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button" onclick="deletemodal()">
        <i class="far fa-trash-alt  fa-lg "></i> خذف
          </button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $msg = $this->getRequest()->getParam("msg", null);
        $msgDecrypt = null;
        if (!empty($msg)) {
            $msgDecrypt = json_decode(Application_Model_Func_Crypt::decrypt(base64_decode($msg)), true);
        }

        $this->view->msg = $msgDecrypt;


    }
    public function getcityAction()
    {
        $encryptProvince = $this->getRequest()->getParam('province');
        $provience = Application_Model_Func_Crypt::Decrypt(base64_decode($encryptProvince));
        $modelCoding = new Coding_Model_coding();
        $citys = $modelCoding->GetCityList($provience);
        foreach ($citys as $key => $city) {
            $encryptCity[$key]['SUBCODE'] = base64_encode(Application_Model_Func_Crypt::Encrypt($city['SUBCODE']));
            $encryptCity[$key]['SUBCATNAME'] = $city['SUBCATNAME'];
        }
        echo json_encode($encryptCity);
        exit;
    }
    public function getmodelAction()
    {
        $encryptfactory = $this->getRequest()->getParam('factory');
        $factory = Application_Model_Func_Crypt::Decrypt(base64_decode($encryptfactory));
        $modelCoding = new Coding_Model_coding();
        $models = $modelCoding->GetModelList($factory);
        foreach ($models as $key => $model) {
            $encryptCity[$key]['SUBCODE'] = base64_encode(Application_Model_Func_Crypt::Encrypt($model['SUBCODE']));
            $encryptCity[$key]['SUBCATNAME'] = $model['SUBCATNAME'];
        }
        echo json_encode($encryptCity);
        exit;
    }
}


<?php

class Setting_MngcoponController extends zend_controller_action
{
    public function init()
    {
        $this->_helper->layout->setLayout("layout");
        define('ROOT_URL', Zend_Controller_Front::getInstance()->getBaseUrl());
        $authNamespace = Zend_Registry::get("authNamespace");
        if (!$authNamespace->isLogin) {
            $this->_redirect('/');
        } else {
            $action = $this->getRequest()->getActionName();
            $controller = $this->getRequest()->getControllerName();
            $module = $this->getRequest()->getModuleName();
            if (!Application_Model_Func_Access::hasAccess($controller, $action, $module)) {
                Zend_Session::forgetMe();
                Zend_Session::destroy();
                $this->_redirect('/index?error=3');
            }
            if (Application_Model_Func_Access::Istimeout()) {
                $this->_redirect('/index?error=2');
            }

        }


    }
    public function indexAction()
    {
        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>تنظیمات کوپن محصولات  </strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcopon', 'action' => 'addcopon')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addCoponModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcopon', 'action' => 'editcopon')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editCoponModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcopon', 'action' => 'deletecopon')
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
    public function addcoponAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcopon', 'action' => 'savecopon')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;

    }
    public function editcoponAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcopon', 'action' => 'savecopon')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        $coponModal = new Setting_Model_copon();
        if ($post['decryptDataPosted']['ID_COPON']) {
            $id = $post['decryptDataPosted']['ID_COPON'];
            $this->view->ID = $id;
            $copons = $coponModal->fetchCoponbyID($id);
            $copon = $copons[0];
            $this->view->Copon = $copon;
            // var_export($copon);
        }
      
    }
    public function deletecoponAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_COPON']) {
            $coponModal = new Setting_Model_copon();
            $flag = $coponModal->deleteCopon($post['decryptDataPosted']['ID_COPON']);
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'کوپن مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'کوپن مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function getcoponlistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = '(select ROW_NUMBER() OVER (Order by ID_COPON) AS row,ID_COPON AS COPON ,* from TBL_SHOPMNG_COPON)tbl ';
        $primaryKey = "ID_COPON";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID_COPON',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $value = base64_encode(Application_Model_Func_Crypt::Encrypt($row['ID_COPON']));
                    return '<input type="radio" name="choicedcopon" class="form-radio-input choicedcopon" value="' . $value . '" />';
                }
            ),
            array('db' => 'Name_COPON', 'dt' => 2),
            array('db' => 'TXT_COPON', 'dt' => 3),
            array('db' => 'OFFER_COPON', 'dt' => 4),
            array('db' => 'ACTIVE_COPON', 'dt' => 5,
            'formatter' => function ($d, $row) {
                if($d){
                    $selected=" checked ";
                    $active="فعال";
                }
                else{
                    $selected=" ";
                    $active="غیرفعال";
                }

                return '<input disabled type="checkbox" '.$selected.' class="form-radio-input choicedcopon"  />&nbsp;'.$active;
            }),
            array('db' => 'DESCRIPTION_COPON', 'dt' => 6),
            array('db' => 'ID_COPON', 'dt' => 7),
    

        );
        $db = Zend_Registry::get("db");
        try {
            echo json_encode(
                Application_Model_Func_SSP::simple($post, $db, $table, $primaryKey, $columns)
            );
        } catch (Exception $e) {
            var_export($e->getMessage());
        }
        exit;
    }
    public function savecoponAction()
    {
        $post = $this->getRequest()->getPost();
        $modelSetting = new Setting_Model_copon();
        $modelCrypt = new Application_Model_Func_Crypt();
        $flag = false;
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelSetting->Savecopon($post);
        }
        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی مشخصات محصول', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی مشخصات محصول', 'message' => 'خطا در ذخیره کردن مشخصات محصول.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode($modelCrypt->Encrypt(json_encode($msg)));

        $this->redirect('/Setting/Mngcopon/index?msg=' . urlencode($msgEncrypt));

    }
}

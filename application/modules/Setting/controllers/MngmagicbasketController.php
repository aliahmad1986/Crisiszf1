<?php

class Setting_MngmagicbasketController extends zend_controller_action
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
        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>تنظیمات سبد شگفت انگیز محصولات  </strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngmagicbasket', 'action' => 'addmagicbasket')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addMagicbasketModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngmagicbasket', 'action' => 'editmagicbasket')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editMagicbasketModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngmagicbasket', 'action' => 'deletemagicbasket')
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
    public function addmagicbasketAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngmagicbasket', 'action' => 'savemagicbasket')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $modelMagicBasket=new Setting_Model_Magicbasket();
        $products=$modelMagicBasket->Fetch_All('TBL_SHOPMNG_PRODUCT');
        $this->view->products=$products;
    

    }
    public function editmagicbasketAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngmagicbasket', 'action' => 'savemagicbasket')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        $magicbasketModal = new Setting_Model_magicbasket();
        if ($post['decryptDataPosted']['ID_MAGICBASKET']) {
            $id = $post['decryptDataPosted']['ID_MAGICBASKET'];
            $this->view->ID = $id;
            $magicbaskets = $magicbasketModal->fetchMagicbasketbyID($id);
            $magicbasket = $magicbaskets[0];
            $this->view->Magicbasket = $magicbasket;
            // var_export($magicbasket);
        }
        $modelMagicBasket=new Setting_Model_Magicbasket();
        $products=$modelMagicBasket->Fetch_All('TBL_SHOPMNG_PRODUCT');
        $this->view->products=$products;
      
    }
    public function deletemagicbasketAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_MAGICBASKET']) {
            $magicbasketModal = new Setting_Model_magicbasket();
            $flag = $magicbasketModal->deleteMagicbasket($post['decryptDataPosted']['ID_MAGICBASKET']);
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'سبد شگفت انگیز مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'سبد شگفت انگیز مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function getmagicbasketlistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = '(select ROW_NUMBER() OVER (Order by ID_MAGICBASKET) AS row,ID_MAGICBASKET AS MAGICBASKET ,* from TBL_SHOPMNG_MAGICBASKET
        LEFT JOIN TBL_SHOPMNG_PRODUCT ON TBL_SHOPMNG_MAGICBASKET.PRODUCT=TBL_SHOPMNG_PRODUCT.ID_PRODUCT)tbl ';
        $primaryKey = "ID_MAGICBASKET";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID_MAGICBASKET',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $value = base64_encode(Application_Model_Func_Crypt::Encrypt($d));
                    return '<input type="radio" name="choicedmagicbasket" class="form-radio-input choicedmagicbasket" value="' . $value . '" />';
                }
            ),
            array('db' => 'Name_PRODUCT', 'dt' => 2),
            array('db' => 'DESCRIPTION_MAGICBASKET', 'dt' => 3),
    

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
    public function savemagicbasketAction()
    {
        $post = $this->getRequest()->getPost();
        $modelSetting = new Setting_Model_magicbasket();
        $modelCrypt = new Application_Model_Func_Crypt();
        $flag = false;
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelSetting->Savemagicbasket($post);
        }
        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی مشخصات محصول', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی مشخصات محصول', 'message' => 'خطا در ذخیره کردن مشخصات محصول.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode($modelCrypt->Encrypt(json_encode($msg)));

        $this->redirect('/Setting/Mngmagicbasket/index?msg=' . urlencode($msgEncrypt));

    }
}

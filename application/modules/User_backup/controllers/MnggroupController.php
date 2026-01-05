<?php

class User_MnggroupController extends zend_controller_action
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

        }
        if (Application_Model_Func_Access::Istimeout()) {
            $this->_redirect('/index?error=2');
        }

    }
    public function indexAction()
    {

        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>مشاهده گروهها</strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mnggroup', 'action' => 'addgroup')
            )
        ))] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addGroupModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mnggroup', 'action' => 'editgroup')
            )
        ))] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editGroupModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mnggroup', 'action' => 'deletegroup')
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
    public function editgroupAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mnggroup', 'action' => 'savegroup')
                )
            )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID']) {
            $userModal = new User_Model_user();
            $groups = $userModal->fetchGroupbyID($post['decryptDataPosted']['ID']);
            $group = $groups[0];
            $this->view->Group = $group;
        }
        include_once (APPLICATION_PATH . "/configs/permission.php");
        $this->view->accessList = $permission;
        $accessTranslate = array('module' => array(), 'action' => array());
        if (isset($group["ACCESS"])) {
            $decodeAccessstep1 = base64_decode($group["ACCESS"]);
            $decodeJsonAccessstep2 = json_decode(Application_Model_Func_Crypt::Decrypt($decodeAccessstep1), true);
            $this->view->savedAccessList = $decodeJsonAccessstep2;         

        }
    }

    public function addgroupAction()
    {
        $this->_helper->layout->disableLayout();
        include_once (APPLICATION_PATH . "/configs/permission.php");
        $this->view->accessList = $permission;
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mnggroup', 'action' => 'savegroup')
                )
            )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
    }
    public function getgrouplistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = '(select ROW_NUMBER() OVER (Order by Id) AS row,* from TBL_SHOPMNG_GROUP)tbl ';
        $primaryKey = "NAME";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $value = base64_encode(Application_Model_Func_Crypt::Encrypt($d));
                    return '<input type="radio" name="choicedgroup" class="form-radio-input" value="' . $value . '" />';
                }
            ),
            array('db' => 'name', 'dt' => 2),

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
    public function deletegroupAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID']) {
            $userModal = new User_Model_user();
            try {

                $flag = $userModal->deleteGroup($post['decryptDataPosted']['ID']);
            } catch (Exception $e) {
                $flag = false;
            }
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'گروه مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'گروه مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function savegroupAction()
    {

        $post = $this->getRequest()->getPost();
        $modelUser = new User_Model_user();
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelUser->SaveGroup($post["decryptDataPosted"]);
        }

        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی گروه کاربری ', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی گروه کاربری ', 'message' => 'خطا در ذخیره کردن مشخصات فردی.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode(Application_Model_Func_Crypt::Encrypt(json_encode($msg)));

        $this->redirect('/User/Mnggroup/index?msg=' . urlencode($msgEncrypt));


    }
}


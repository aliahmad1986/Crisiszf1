<?php
use PhpMyAdmin\Plugins\TwoFactor\Application;

class User_MngusersController extends zend_controller_action
{
    private $modelCrypt;

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
        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>مشاهده کاربران</strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngusers', 'action' => 'adduser')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addUserModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngusers', 'action' => 'edituser')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editUserModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngusers', 'action' => 'deleteuser')
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
    public function deleteuserAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_USER']) {
            $userModal = new User_Model_user();

            try {
                $flag = $userModal->deleteUser($post['decryptDataPosted']['ID_USER']);
            } catch (Exception $e) {
                $flag = false;
            }
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'کاربر مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'کاربر مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function edituserAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngusers', 'action' => 'saveuser')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_USER']) {
            $userModal = new User_Model_user();
            $id = $post['decryptDataPosted']['ID_USER'];
            $users = $userModal->fetchUserbyID($id);
            $user = $users[0];
            $this->view->USER = $user;
            $persons = $userModal->Fetch_All('TBL_SHOPMNG_PERSON');
            $this->view->persons = $persons;
            $companys = $userModal->Fetch_All('TBL_SHOPMNG_COMPANY');
            $this->view->companys = $companys;
            $groups = $userModal->Fetch_All('TBL_SHOPMNG_GROUP');
            $this->view->groups = $groups;
            // var_export($person);
        }
    }
    public function adduserAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngusers', 'action' => 'saveuser')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $modelUser = new User_Model_user();
        $persons = $modelUser->Fetch_All('TBL_SHOPMNG_PERSON');
        $this->view->persons = $persons;
        $companys = $modelUser->Fetch_All('TBL_SHOPMNG_COMPANY');
        $this->view->companys = $companys;
        $groups = $modelUser->Fetch_All('TBL_SHOPMNG_GROUP');
        $this->view->groups = $groups;

    }
    public function saveuserAction()
    {
        $post = $this->getRequest()->getPost();
     
        $modelUser = new User_Model_user();
        $modelCrypt = new Application_Model_Func_Crypt();
        $flag = false;
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelUser->SaveUser($post);
        }
        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی مشخصات فردی', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی مشخصات فردی', 'message' => 'خطا در ذخیره کردن مشخصات کاربری.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode($modelCrypt->Encrypt(json_encode($msg)));

        $this->redirect('/User/Mngusers/index?msg=' . urlencode($msgEncrypt));

    }
    public function getuserlistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = '(
            select  ROW_NUMBER() OVER (Order by TBL_SHOPMNG_USER.ID_USER) AS row,* from TBL_SHOPMNG_USER Left JOIN TBL_SHOPMNG_PERSON	ON TBL_SHOPMNG_USER.PERSONID=TBL_SHOPMNG_PERSON.ID_PERSON Left JOIN TBL_SHOPMNG_GROUP ON 
            TBL_SHOPMNG_USER.ACL=TBL_SHOPMNG_GROUP.ID Left JOIN (select TBL_SHOPMNG_USER.ID_USER UID,TBL_SHOPMNG_USER.USERNAME NUSER  from TBL_SHOPMNG_USER) U ON  U.UID=TBL_SHOPMNG_USER.CREATEDBY)tbl ';
        $primaryKey = "ID_USER";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID_USER',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $encryptedID = base64_encode(Application_Model_Func_Crypt::Encrypt($d));
                    return '<input type="radio" name="choiceduser" class="form-radio-input" value="' . $encryptedID . '" />';
                }
            ),
            array(
                'db' => 'NAME_PERSON',
                'dt' => 2,
                'formatter' => function ($d, $row) {

                    return $d . " " . $row['FAMIL'];
                }
            ),
            array('db' => 'CODEMELI', 'dt' => 3),
            array('db' => 'USERNAME', 'dt' => 4),
            array('db' => 'NAME', 'dt' => 5),
            array(
                'db' => 'ACTIVE',
                'dt' => 6,
                'formatter' => function ($d, $row) {
                    if ($d == 1) {
                        $returnItem = '<span>فعال</span>';
                    } else {
                        $returnItem = '<span>غیر فعال</span>';
                    }
                    return $returnItem;
                }
            ),
            array('db' => 'NUSER', 'dt' => 7),
            array('db' => 'DATECREATED', 'dt' => 8),
            array('db' => 'FAMIL', 'dt' => 9),

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
}

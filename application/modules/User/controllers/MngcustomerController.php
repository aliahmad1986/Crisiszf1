<?php
use PhpMyAdmin\Plugins\TwoFactor\Application;

class User_MngcustomerController extends zend_controller_action
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
        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>مشاهده مشتریان</strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngcustomer', 'action' => 'addcustomer')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addCustomerModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngcustomer', 'action' => 'editcustomer')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editCustomerModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngcustomer', 'action' => 'deletecustomer')
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
    public function deletecustomerAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_CUSTOMER']) {
            $userModal = new User_Model_user();

            try {
                $flag = $userModal->deleteCustomer($post['decryptDataPosted']['ID_CUSTOMER']);
            } catch (Exception $e) {
                $flag = false;
            }
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'مشتری مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'مشتری مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function editcustomerAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngcustomer', 'action' => 'savecustomer')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_CUSTOMER']) {
            $userModal = new User_Model_user();
            $id = $post['decryptDataPosted']['ID_CUSTOMER'];
            $customers = $userModal->fetchCustomerbyID($id);
            $customer = $customers[0];
            $this->view->CUSTOMER = $customer;
            $persons = $userModal->Fetch_All('TBL_SHOPMNG_PERSON');
            $this->view->persons = $persons;
            $groups = $userModal->Fetch_All('TBL_SHOPMNG_GROUP');
            $this->view->groups = $groups;
             //var_export($customer);
        }
    }
    public function addcustomerAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'User', 'controller' => 'Mngcustomer', 'action' => 'savecustomer')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $modelUser = new User_Model_user();
        $persons = $modelUser->Fetch_All('TBL_SHOPMNG_PERSON');
        $this->view->persons = $persons;
        $groups = $modelUser->Fetch_All('TBL_SHOPMNG_GROUP');
        $this->view->groups = $groups;

    }
    public function savecustomerAction()
    {
        $post = $this->getRequest()->getPost();
        $modelUser = new User_Model_user();
        $modelCrypt = new Application_Model_Func_Crypt();
        $flag = false;
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelUser->SaveCustomer($post);
        }
        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی مشتری', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی مشتری', 'message' => 'خطا در ذخیره کردن مشخصات مشتریی.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode($modelCrypt->Encrypt(json_encode($msg)));

        $this->redirect('/User/Mngcustomer/index?msg=' . urlencode($msgEncrypt));

    }
    public function getcustomerlistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = '(
            select  ROW_NUMBER() OVER (Order by TBL_SHOPMNG_CUSTOMER.ID_CUSTOMER) AS row,* from TBL_SHOPMNG_CUSTOMER Left JOIN TBL_SHOPMNG_PERSON	ON TBL_SHOPMNG_CUSTOMER.PERSONID_CUSTOMER=TBL_SHOPMNG_PERSON.ID_PERSON Left JOIN TBL_SHOPMNG_GROUP ON 
            TBL_SHOPMNG_CUSTOMER.ACL_CUSTOMER=TBL_SHOPMNG_GROUP.ID Left JOIN  TBL_SHOPMNG_USER ON  TBL_SHOPMNG_USER.ID_USER=TBL_SHOPMNG_CUSTOMER.CREATEDBY_CUSTOMER)tbl ';
        $primaryKey = "ID_CUSTOMER";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID_CUSTOMER',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $encryptedID = base64_encode(Application_Model_Func_Crypt::Encrypt($d));
                    return '<input type="radio" name="choicedcustomer" class="form-radio-input" value="' . $encryptedID . '" />';
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
            array('db' => 'USERNAME_CUSTOMER', 'dt' => 4),
            array('db' => 'NAME', 'dt' => 5),
            array(
                'db' => 'ACTIVE_CUSTOMER',
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
            array('db' => 'USERNAME', 'dt' => 7),
            array('db' => 'DATECREATED_CUSTOMER', 'dt' => 8),
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

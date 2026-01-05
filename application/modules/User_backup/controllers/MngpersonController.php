<?php

class User_MngpersonController extends zend_controller_action
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
        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>مشاهده افراد</strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngperson', 'action' => 'addperson')
                )
            )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addPersonModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngperson', 'action' => 'editperson')
                )
            )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editPersonModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngperson', 'action' => 'deleteperson')
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
    public function addpersonAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngperson', 'action' => 'saveperson')
                )
            )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $modelCoding = new Coding_Model_coding();
        $provinces = $modelCoding->GetProvinceList();
        $encryptProvinces = [];
        foreach ($provinces as $key => $province) {
            $encryptProvinces[$key]['CODE'] = base64_encode(Application_Model_Func_Crypt::Encrypt($province['CODE']));
            $encryptProvinces[$key]['CATNAME'] = $province['CATNAME'];
        }
        $carmodel=new Setting_Model_Car();
        $cargroups=$carmodel->Fetch_All('TBL_SHOPMNG_CARGROUP');
        $this->view->cargroups=$cargroups;
        $this->view->provinces = $encryptProvinces;

    }
    public function editpersonAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngperson', 'action' => 'saveperson')
                )
            )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_PERSON']) {
            $userModal = new User_Model_user();
            $id = $post['decryptDataPosted']['ID_PERSON'];
            $this->view->ID = $id;
            $persons = $userModal->fetchPersonbyID($id);
            $person = $persons[0];
            $this->view->PERSON = $person;
            // var_export($person);
        }
        $modelCoding = new Coding_Model_coding();
        $provinces = $modelCoding->GetProvinceList();
        $encryptProvinces = [];
        foreach ($provinces as $key => $province) {
            $encryptProvinces[$key]['CODE'] = base64_encode(Application_Model_Func_Crypt::Encrypt($province['CODE']));
            $encryptProvinces[$key]['CATNAME'] = $province['CATNAME'];
        }
        $carmodel=new Setting_Model_Car();
        $cargroups=$carmodel->Fetch_All('TBL_SHOPMNG_CARGROUP');
        foreach ($cargroups as $key => $cargroup) {
            $cargroups[$key]['ID_CARGROUP'] = base64_encode(Application_Model_Func_Crypt::Encrypt($cargroup['ID_CARGROUP']));           
        }
        $this->view->cargroups=$cargroups;
        $this->view->provinces = $encryptProvinces;
    }
    public function deletepersonAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_PERSON']) {
            $userModal = new User_Model_user();
            try {
                $flag = $userModal->deletePerson($post['decryptDataPosted']['ID_PERSON']);
            } catch (Exception $e) {
                $flag = false;
            }
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'فرد مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'فرد مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function getpersonlistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = "(select ROW_NUMBER() OVER (Order by ID_PERSON) AS row,convert(varchar,P.ID_PERSON)+'.jpg' AS AVATAR,* from
        TBL_SHOPMNG_PERSON P  LEFT JOIN  TBL_SHOPMNG_ADDRESSESS A 
       ON P.ADDRESSID=A.ID_ADDRESS LEFT JOIN TBL_SHOPMNG_CODING C ON C.CODE=A.PROVINCE AND C.SUBCODE=A.CITY )tbl ";
        $primaryKey = "ID_PERSON";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID_PERSON',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $value = base64_encode(Application_Model_Func_Crypt::Encrypt($row['ID_PERSON']));
                    return '<input type="radio" name="choicedperson" class="form-radio-input choicedPerson" value="' . $value . '" />';
                }
            ),
            array(
                'db' => 'AVATAR',
                'dt' => 2,
                'formatter' => function ($d, $row) {

                    return '<img src="/img/person/' . $d . '" style="width:40px;height:40px" onerror="this.src=\'/img/usr.jpg\'"alt="..." class="rounded-circle screen-user-profile">';

                }
            ),
            array('db' => 'NAME_PERSON', 'dt' => 3),
            array('db' => 'FAMIL', 'dt' => 4),
            array('db' => 'MOBILE', 'dt' => 5),
            array(
                'db' => 'ADDRTEXT',
                'dt' => 6,
                'formatter' => function ($d, $row) {
                    return 'استان: '.$row['CATNAME'] . "- شهرستان:" . $row['SUBCATNAME'] . " - آدرس: " . $d;
                }
            ),
            array('db' => 'CATNAME', 'dt' => 7),
            array('db' => 'SUBCATNAME', 'dt' => 8),

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
    public function savepersonAction()
    {
        $post = $this->getRequest()->getPost();
        $modelUser = new User_Model_user();
        $modelCrypt = new Application_Model_Func_Crypt();
        $flag = false;
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelUser->SavePerson($post);
        }
        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی مشخصات فردی', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی مشخصات فردی', 'message' => 'خطا در ذخیره کردن مشخصات فردی.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode($modelCrypt->Encrypt(json_encode($msg)));

        $this->redirect('/User/Mngperson/index?msg=' . urlencode($msgEncrypt));

    }
}

<?php

class User_MngcompanyController extends zend_controller_action
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
        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>مشاهده حقوقی</strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngcompany', 'action' => 'addcompany')
                )
            )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addCompanyModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngcompany', 'action' => 'editcompany')
                )
            )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editCompanyModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngcompany', 'action' => 'deletecompany')
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
    public function addcompanyAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngcompany', 'action' => 'savecompany')
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
        
        $this->view->provinces = $encryptProvinces;

    }
    public function editcompanyAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(
            Application_Model_Func_Crypt::Encrypt(
                json_encode(
                    array('module' => 'User', 'controller' => 'Mngcompany', 'action' => 'savecompany')
                )
            )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_COMPANY']) {
            $userModal = new User_Model_user();
            $id = $post['decryptDataPosted']['ID_COMPANY'];
            $this->view->ID = $id;
            $companys = $userModal->fetchCompanybyID($id);
            $company = $companys[0];
            $this->view->COMPANY = $company;
            // var_export($company);
        }
        $modelCoding = new Coding_Model_coding();
        $provinces = $modelCoding->GetProvinceList();
        $encryptProvinces = [];
        foreach ($provinces as $key => $province) {
            $encryptProvinces[$key]['CODE'] = base64_encode(Application_Model_Func_Crypt::Encrypt($province['CODE']));
            $encryptProvinces[$key]['CATNAME'] = $province['CATNAME'];
        }
        
        $this->view->provinces = $encryptProvinces;
    }
    public function deletecompanyAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_COMPANY']) {
            $userModal = new User_Model_user();
            try {
                $flag = $userModal->deleteCompany($post['decryptDataPosted']['ID_COMPANY']);
            } catch (Exception $e) {
              //  echo $e->getMessage();exit;
                $flag = false;
            }
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'شرکت مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'شرکت مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function getcompanylistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = "(select ROW_NUMBER() OVER (Order by ID_COMPANY) AS row,* from
        TBL_SHOPMNG_COMPANY P  LEFT JOIN  TBL_SHOPMNG_ADDRESSESS A 
       ON P.ADDRESSID=A.ID_ADDRESS LEFT JOIN TBL_SHOPMNG_CODING C ON C.CODE=A.PROVINCE AND C.SUBCODE=A.CITY )tbl ";
        $primaryKey = "ID_COMPANY";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID_COMPANY',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $value = base64_encode(Application_Model_Func_Crypt::Encrypt($row['ID_COMPANY']));
                    return '<input type="radio" name="choicedcompany" class="form-radio-input choicedCompany" value="' . $value . '" />';
                }
            ),
            array('db' => 'NAME_COMPANY', 'dt' => 2),
            array('db' => 'TEL', 'dt' => 3),
            array(
                'db' => 'ADDRTEXT',
                'dt' => 4,
                'formatter' => function ($d, $row) {
                    return 'استان: '.$row['CATNAME'] . "- شهرستان:" . $row['SUBCATNAME'] . " - آدرس: " . $d;
                }
            ),
            array('db' => 'CATNAME', 'dt' => 5),
            array('db' => 'SUBCATNAME', 'dt' => 6),

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
    public function savecompanyAction()
    {
        $post = $this->getRequest()->getPost();
        $modelUser = new User_Model_user();
        $modelCrypt = new Application_Model_Func_Crypt();
        $flag = false;
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelUser->SaveCompany($post);
        }
        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی مشخصات شرکتی', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی مشخصات شرکتی', 'message' => 'خطا در ذخیره کردن مشخصات شرکتی.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode($modelCrypt->Encrypt(json_encode($msg)));

        $this->redirect('/User/Mngcompany/index?msg=' . urlencode($msgEncrypt));

    }
}

<?php

class Setting_MngcarController extends zend_controller_action
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
        $this->view->sitemap = "<ul class='sitemap'><li><a target='_self' href='" . ROOT_URL . "/Dashboard'>داشبورد</a>&nbsp;</li><li>  <strong>تنظیمات گروه خودرو گروه خودروات  </strong></li></ul>";
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcar', 'action' => 'addcar')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#addCarModal" data-toggle="modal">
        <i class="far fa-plus-square  fa-lg "></i> افزودن
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcar', 'action' => 'editcar')
            )
        )
        )] = '<button class="btn dropdown-toggle m-0 zoom" type="button"
        data-target="#editCarModal" data-toggle="modal">
        <i class=" far fa-edit fa-lg "></i> ویرایش
         </button>';
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcar', 'action' => 'deletecar')
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
    public function addcarAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcar', 'action' => 'savecar')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $codingModel=new Coding_Model_coding();
        $factorys=$codingModel->GetFactorylList();
        $this->view->factorys=$factorys;
        $groupscar=$codingModel->GetGroupCarList();
        $this->view->groups=$groupscar;


    }
    public function editcarAction()
    {
        $this->_helper->layout->disableLayout();
        $toolbars = array();
        $toolbars[base64_encode(Application_Model_Func_Crypt::Encrypt(
            json_encode(
                array('module' => 'Setting', 'controller' => 'Mngcar', 'action' => 'savecar')
            )
        )
        )] = '<button type="submit" class="btn dropdown-toggle m-0 zoom"><i class="far fa-save  fa-lg "></i>&nbsp;ذخیره</button>';
        $accessToolbar = Application_Model_Func_Access::hasToolbarAccess($toolbars);
        $this->view->toolbars = $accessToolbar;
        $post = $this->getRequest()->getPost();
        $carModal = new Setting_Model_car();
        if ($post['decryptDataPosted']['ID_CARGROUP']) {
            $id = $post['decryptDataPosted']['ID_CARGROUP'];
            $this->view->ID = $id;
            $cars = $carModal->fetchCarbyID($id);
            $car = $cars[0];
            $this->view->Car = $car;
            // var_export($car);
        }
      
        $codingModel=new Coding_Model_coding();
        $factorys=$codingModel->GetFactorylList();
        $this->view->factorys=$factorys;
        $groupscar=$codingModel->GetGroupCarList();
        $this->view->groups=$groupscar;
    }
    public function deletecarAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getPost();
        if ($post['decryptDataPosted']['ID_CARGROUP']) {
            $carModal = new Setting_Model_car();
            $flag = $carModal->deleteCar($post['decryptDataPosted']['ID_CARGROUP']);
            if ($flag) {
                echo json_encode(array('flag' => true, 'msg' => 'گروه خودرو مورد نظر حذف شد'));
            } else {
                echo json_encode(array('flag' => false, 'msg' => 'گروه خودرو مورد نظر حذف نشد'));
            }
        } else
            echo json_encode(array('flag' => false, 'msg' => 'دادهای ارسالی برای انجام عملیات ناقص میباشد.'));

        exit;
    }
    public function getcarlistAction()
    {
        $this->_helper->layout->disableLayout();
        $post = $this->getRequest()->getParams();
        $SSP = new Application_Model_Func_SSP();
        $table = '(select ROW_NUMBER() OVER (Order by ID_CARGROUP) AS row,[ID_CARGROUP]
        ,[NAME_CARGROUP]
        ,DBO.CONVERTCODE(100,[FACTORY_CARGROUP]) as FACTORY_CARGROUP
        ,DBO.CONVERTCODE(101,[MODEL_CARGROUP]) as MODEL_CARGROUP
        ,DBO.CONVERTCODE(102,[GROUP_CARGROUP]) as GROUP_CARGROUP
        ,[YEAR_CARGROUP] from TBL_SHOPMNG_CARGROUP)tbl ';
        $primaryKey = "ID_CARGROUP";
        $columns = array(
            array('db' => 'row', 'dt' => 0),
            array(
                'db' => 'ID_CARGROUP',
                'dt' => 1,
                'formatter' => function ($d, $row) {
                    $value = base64_encode(Application_Model_Func_Crypt::Encrypt($row['ID_CARGROUP']));
                    return '<input type="radio" name="choicedcar" class="form-radio-input choicedcar" value="' . $value . '" />';
                }
            ),
            array('db' => 'NAME_CARGROUP', 'dt' => 2),
            array('db' => 'FACTORY_CARGROUP', 'dt' => 3),
            array('db' => 'MODEL_CARGROUP', 'dt' => 4),
            array('db' => 'GROUP_CARGROUP', 'dt' => 5),
            array('db' => 'YEAR_CARGROUP', 'dt' => 6),
    

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
    public function savecarAction()
    {
        $post = $this->getRequest()->getPost();
        $modelSetting = new Setting_Model_car();
        $modelCrypt = new Application_Model_Func_Crypt();
        $flag = false;
        if (isset($post['decryptDataPosted'])) {
            $flag = $modelSetting->Savecar($post);
        }
        if ($flag) {
            $msg = array('code' => 'success', 'title' => 'ذخیره سازی مشخصات گروه خودرو', 'message' => 'عملیات با موفقیت انجام شد');
        } else {
            $msg = array('code' => 'error', 'title' => 'ذخیره سازی مشخصات گروه خودرو', 'message' => 'خطا در ذخیره کردن مشخصات گروه خودرو.با پشتیبانی تماس بگیرید');
        }
        $msgEncrypt = base64_encode($modelCrypt->Encrypt(json_encode($msg)));

        $this->redirect('/Setting/Mngcar/index?msg=' . urlencode($msgEncrypt));

    }
}

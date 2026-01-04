<?php

/**
 * Description of users
 *
 * @author Client 5
 */
class Application_Model_Func_Users {

    public function getallusersbyname($name = null) {
        $db = Zend_Registry::get('db');
        $select = $db->select()->from(array('u' => 'TBL_SHOPMNG_USER'), array('NAME','PASSWORD','ACL','ACTIVE','CREATEDBY','DATECREATED'));
        $select = $select->where('NAME=?', array($name)); 
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    public function getallusers($name = null) {
        $db = Zend_Registry::get('db');
        $select = $db->select()->from(array('u' => 'TBL_SHOPMNG_USER'), array('NAME','PASSWORD','ACL','ACTIVE','CREATEDBY','DATECREATED'));
        if (isset($name)) {
            $select = $select->where('NAME=?', array($name));
        }
        //echo $select->__toString();
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    

    public function checkpass($user, $pass) {
        $db = Zend_Registry::get('db');
        $select = $db->
        select()
        ->from(array('u' => 'TBL_SHOPMNG_USER'))
        ->joinLeft(array('g'=>'TBL_SHOPMNG_GROUP'),"u.ACL=g.ID")
        ->joinLeft(array('p'=>'TBL_SHOPMNG_PERSON'),"p.ID_PERSON=u.PERSONID")
        ->where('USERNAME=?', array($user))
        ->where('PASSWORD=?', base64_encode(Application_Model_Func_Crypt::Encrypt($pass)));  
       // echo $select->__toString();  exit; 
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        //  var_export($result);exit;
        return $result;
    }
    function update_custom_password($user,$newpassword)
    {
    
       
        $db = Zend_Registry::get("db");
        $db->beginTransaction();
        try {
            $db->update('TBL_SHOPMNG_CUSTOMER',array('PASSWORD_CUSTOMER'=>base64_encode(Application_Model_Func_Crypt::Encrypt($newpassword))),"USERNAME_CUSTOMER='".$user."'");
            $db->commit();           
            return array('flag'=>true,'message'=>'اطلاعات شخصی شما با موفقیت ویرایش شد.از طریق حساب کاربری خود می توانید این تغییرات را مشاهده کنید');
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
            $db->rollBack();
            return array('flag'=>false,'message'=>'متاسفانه درخواست شما انجام نشد.با پشتیبانی تماس بگیرید');
        }
    }
    public function checkpass_customer($user, $pass) {
        $db = Zend_Registry::get('db');
        $select = $db->
        select()
        ->from(array('u' => 'TBL_SHOPMNG_CUSTOMER'))
        ->joinLeft(array('p'=>'TBL_SHOPMNG_PERSON'),"p.ID_PERSON=u.PERSONID_CUSTOMER")
        ->joinLeft(array('a'=>'TBL_SHOPMNG_ADDRESSESS'),"p.ADDRESSID=a.ID_ADDRESS") 
        ->joinLeft(array('cg'=>'TBL_SHOPMNG_CARGROUPITEM'),"cg.ID_CARGROUPITEM=p.CARGROUPITEMID") 
        ->joinLeft(array('c'=>'TBL_SHOPMNG_CARGROUP'),"c.ID_CARGROUP=cg.CARGROUPID") 
        ->where('USERNAME_CUSTOMER=?', array($user))
        ->where('PASSWORD_CUSTOMER=?', base64_encode(Application_Model_Func_Crypt::Encrypt($pass)));  
       // echo $select->__toString();  exit; 
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
         // var_export($result);exit;
        return $result;
    }
    public function fetchCustomerbyID($id) {
        $db = Zend_Registry::get('db');
        $select = $db->
        select()
        ->from(array('u' => 'TBL_SHOPMNG_CUSTOMER'))
        ->joinLeft(array('g'=>'TBL_SHOPMNG_GROUP'),"u.ACL_CUSTOMER=g.ID")
        ->joinLeft(array('p'=>'TBL_SHOPMNG_PERSON'),"p.ID_PERSON=u.PERSONID_CUSTOMER")
        ->where('ID_CUSTOMER=?', array($id));  
       // echo $select->__toString();  exit; 
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        //  var_export($result);exit;
        return $result;
    }
    public function getUserById($id) {
        $db = Zend_Registry::get('db');
        $select = $db->
        select()
        ->from(array('u' => 'TBL_SHOPMNG_USER'))
        ->joinLeft(array('g'=>'TBL_SHOPMNG_GROUP'),"u.ACL=g.ID")
        ->joinLeft(array('p'=>'TBL_SHOPMNG_PERSON'),"p.ID_PERSON=u.PERSONID")
        ->where('ID_USER=?', array($id));  
       // echo $select->__toString();  exit; 
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        //  var_export($result);exit;
        return $result;
    }
    public function getPersonByMelicode($codemeli) {
        $db = Zend_Registry::get('db');
        $select = $db->
        select()
        ->from(array('u' => 'TBL_SHOPMNG_PERSON'))
        ->where('CODEMELI=?', array($codemeli));  
       // echo $select->__toString();  exit; 
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        //  var_export($result);exit;
        return $result;
    }

    public function fetchRequestPersonbyID($id) {
        $db = Zend_Registry::get('db');
        $select = $db->select()
        ->from(array('u' => 'TBL_SHOPMNG_REQUEST_PERSON_REGISTER'))
        ->join(array("c"=>'TBL_SHOPMNG_CODING '),"u.CITY=c.SUBCODE and u.PROVINCE=c.CODE")
        ->where('ID_RGP=?', array($id));  
      // echo $select->__toString();  exit; 
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        //  var_export($result);exit;
        return $result;
    }



}

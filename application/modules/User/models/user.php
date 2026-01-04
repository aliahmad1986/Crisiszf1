<?php

class User_Model_user
{
    public function Fetch_All($tbl)
    {
        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => $tbl));
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchGroupbyID($ID)
    {
        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => ' TBL_SHOPMNG_GROUP'))
            ->where('d.ID=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function deleteGroup($ID)
    {
        $db = Zend_Registry::get("db");
        $users = $this->fetchUsersbyGroupID($ID);
        if (count($users) == 0) {
            try {

                $result = $db->delete('TBL_SHOPMNG_GROUP', 'ID=\'' . $ID . '\'');
                return true;
            } catch (Exception $e) {
                echo $e->getMessage();
                return false;
            }
        } else {
            echo json_encode(array('flag' => false, 'msg' => 'گروه مورد نظر دارای یک حساب کاربری می باشد نمی توان آنرا حذف کرد'));

            exit;
        }
    }
    public function deleteCompany($ID)
    {
        $db = Zend_Registry::get("db");
        $result = false;
        $userCompany=$this->fetchUsersbyCompanyID($ID);
        if (count($userCompany) == 0 && $ID>1) {
            try {
                $db->beginTransaction();
                $persons = $this->fetchCompanybyID($ID);
                $addresID = $persons[0]['ADDRESSID'];
                $flag = $db->delete('TBL_SHOPMNG_ADDRESSESS', 'ID_ADDRESS=\'' . $addresID . '\'');
                $result = $db->delete('TBL_SHOPMNG_COMPANY', 'ID_COMPANY=\'' . $ID . '\'');             
                $db->commit();
                return true;
            } catch (Exception $e) {
                $db->rollBack();
                echo $e->getMessage();
                return false;
            }
        } else {
            echo json_encode(array('flag' => false, 'msg' => 'شرکت مورد نظر دارای یک حساب کاربری می باشد نمی توان آنرا حذف کرد'));

            exit;
        }
    }

    public function deletePerson($ID)
    {
        $db = Zend_Registry::get("db");
        $result = false;
        $users = $this->fetchUsersbyPersonID($ID);
        if (count($users) == 0) {
            try {
                $db->beginTransaction();
                $persons = $this->fetchPersonbyID($ID);
                $addresID = $persons[0]['ADDRESSID'];
                $flag = $db->delete('TBL_SHOPMNG_ADDRESSESS', 'ID_ADDRESS=\'' . $addresID . '\'');
                $result = $db->delete('TBL_SHOPMNG_PERSON', 'ID_PERSON=\'' . $ID . '\'');
                $avatar = realpath(APPLICATION_PATH . "/../public") . "/img/person/" . $ID . '.' . 'jpg';
                if (file_exists($avatar)) {
                    unlink($avatar);
                }
                $db->commit();
                return true;
            } catch (Exception $e) {
                $db->rollBack();
                echo $e->getMessage();
                return false;
            }
        } else {
            echo json_encode(array('flag' => false, 'msg' => 'فرد مورد نظر دارای یک حساب کاربری می باشد نمی توان آنرا حذف کرد'));

            exit;
        }
    }
    public function deleteUser($ID)
    {
        $db = Zend_Registry::get("db");
        try {
            $db->delete('TBL_SHOPMNG_USER', 'ID_USER=\'' . $ID . '\'');
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
    public function fetchUsersbyGroupID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_USER'))
            ->where('d.ACL=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchUsersbyPersonID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_USER'))
            ->where('d.PERSONID=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchUsersbyCompanyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_USER'))
            ->where('d.COMPANYID=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchCustomerbyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_CUSTOMER'))
            ->where('d.ID_CUSTOMER=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchUserbyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_USER'))
            ->where('d.ID_USER=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchUserbyUsername($username)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_USER'))
            ->where('d.USERNAME=?', array($username));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchPersonbyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('P' => 'TBL_SHOPMNG_PERSON'))
            ->joinLeft(array('A' => 'TBL_SHOPMNG_ADDRESSESS'), 'P.ADDRESSID=A.ID_ADDRESS')
            ->joinLeft(array('C'=>'TBL_SHOPMNG_CARGROUPITEM'),'P.CARGROUPITEMID=C.ID_CARGROUPITEM')
            ->where('P.ID_PERSON=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function fetchCompanybyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('P' => 'TBL_SHOPMNG_COMPANY'))
            ->joinLeft(array('A' => 'TBL_SHOPMNG_ADDRESSESS'), 'P.ADDRESSID=A.ID_ADDRESS')
            ->where('P.ID_COMPANY=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    function SaveGroup($group)
    {

        $db = Zend_Registry::get("db");
        try {
            $arrayGroup = array(
                'TASK_GROUP'=>$group['TASK_GROUP'],
                'NAME' => $group['NAME'],
                'ACCESS' => base64_encode(
                    Application_Model_Func_Crypt::Encrypt(
                        json_encode(
                            array('module' => $group['module'], 'action' => $group['action'])
                        )
                    )
                )
            );
            if (!empty($group['ID'])) {
                $db->update(
                    'TBL_SHOPMNG_GROUP',
                    $arrayGroup,
                    'ID=\'' . $group['ID'] . '\''
                );
            } else {
                $ins = $db->insert('TBL_SHOPMNG_GROUP', $arrayGroup);
            }
            return true;
        } catch (Exception $e) {

            echo $e->getMessage();
            return null;
        }
    }
    function SaveAddress($address, $db)
    {

        if (isset($address['ID_ADDRESS'])) {
            $lastInsertedId = $address['ID_ADDRESS'];
            unset($address['ID_ADDRESS']);
            $db->update(
                'TBL_SHOPMNG_ADDRESSESS',
                $address,
                'ID_ADDRESS=\'' . $lastInsertedId . '\''
            );

        } else {
            $db->insert('TBL_SHOPMNG_ADDRESSESS', $address);
            $lastInsertedId = $db->lastInsertId();

        }
        return $lastInsertedId;
    }
    function SaveCargroup($cargroupitem, $db)
    {

        if (isset($cargroupitem['ID_CARGROUPITEM'])) {
            $lastInsertedId = $cargroupitem['ID_CARGROUPITEM'];
            unset($cargroupitem['ID_CARGROUPITEM']);
            $db->update(
                'TBL_SHOPMNG_CARGROUPITEM',
                $cargroupitem,
                'ID_CARGROUPITEM=\'' . $lastInsertedId . '\''
            );

        } else {
            $db->insert('TBL_SHOPMNG_CARGROUPITEM', $cargroupitem);
            $lastInsertedId = $db->lastInsertId();

        }
        return $lastInsertedId;
    }
    function SavePerson($post)
    {

        $person = $post['decryptDataPosted'];
        $personItems = array(
            'NAME_PERSON' => $person['NAME_PERSON'],
            'FAMIL' => $person['FAMIL'],
            'CODEMELI' => $person['CODEMELI'],
            'MOBILE' => $person['MOBILE'],
            'TEL' => $person['TEL'],
            'EMAIL' => $person['EMAIL'],
        );

        $AdressItem = array(
            'MELICODE' => $person['CODEMELI'],
            'PROVINCE' => $person['PROVINCE'],
            'CITY' => $person['CITY'],
            'POSTCODE' => $person['POSTCODE'],
            'ADDRTEXT' => $person['ADDRTEXT'],
           
        );
        $CargroupItem=array(
            'MELICODE' => $person['CODEMELI'],
            'CARGROUPID'=>$person['CARGROUPID'],
            'TRANSIT'=>$person['TRANSIT']
        );
        if (!empty($person['ID_ADDRESS'])) {
            $AdressItem['ID_ADDRESS'] = $person['ID_ADDRESS'];
        }
        if (!empty($person['ID_CARGROUPITEM'])) {
            $CargroupItem['ID_CARGROUPITEM']=$person['ID_CARGROUPITEM'];
        }
        if (!empty($person['ID_PERSON'])) {
            $personItems['ID_PERSON'] = $person['ID_PERSON'];
        }

        $files = $post['filePosted'];
        $db = Zend_Registry::get("db");
        $db->beginTransaction();
        try {
            $personItems['ADDRESSID'] = $this->SaveAddress($AdressItem, $db);
            $personItems['CARGROUPITEMID']=$this->SaveCargroup($CargroupItem,$db);
            if (isset($personItems['ID_PERSON'])) {
                $lastInsertedId = $personItems['ID_PERSON'];
                unset($personItems['ID_PERSON']);
                //   var_export($personItems);exit;
                $db->update(
                    'TBL_SHOPMNG_PERSON',
                    $personItems,
                    'ID_PERSON=\'' . $lastInsertedId . '\''
                );

            } else {
                $db->insert('TBL_SHOPMNG_PERSON', $personItems);
                $lastInsertedId = $db->lastInsertId();

            }
            $db->commit();
            if ($files['AVATAR']) {
                $modelfunc = new Application_Model_Func_Function();
                $detilFile = explode('.', $_FILES[$files['AVATAR']]['name']);
                $flag = $modelfunc->getAdaptorFileUploaded(realpath(APPLICATION_PATH . "/../public") . "/img/person/", $lastInsertedId . '.' . $detilFile[1]);
            }
            if ($person['avatarbase64']) {
                $desination = realpath(APPLICATION_PATH . "/../public") . "/img/person";
                if (!is_dir($desination))
                    mkdir($desination, 0777, true);
                Api_Model_api::base64_to_jpeg($person['avatarbase64'], $desination . "/" . $lastInsertedId . '.jpg');
            }
             //var_export($person);exit;

            return $lastInsertedId;
        } catch (Exception $e) {
          // echo $e->getMessage();exit;
            $db->rollBack();
            return false;
        }
    }

    function SaveCompany($post)
      {
  
          $company = $post['decryptDataPosted'];
          $companyItems = array(
              'NAME_COMPANY' => $company['NAME_COMPANY'],
              'CODEMELI' => $company['CODEMELI'],
              'TEL' => $company['TEL'],
              'EMAIL' => $company['EMAIL'],
          );
  
          $AdressItem = array(
              'MELICODE' => $company['CODEMELI'],
              'PROVINCE' => $company['PROVINCE'],
              'CITY' => $company['CITY'],
              'POSTCODE' => $company['POSTCODE'],
              'ADDRTEXT' => $company['ADDRTEXT'],
             
          );
    
          if (!empty($company['ID_ADDRESS'])) {
              $AdressItem['ID_ADDRESS'] = $company['ID_ADDRESS'];
          }
      
          if (!empty($company['ID_COMPANY'])) {
              $companyItems['ID_COMPANY'] = $company['ID_COMPANY'];
          }
  
          $db = Zend_Registry::get("db");
          $db->beginTransaction();
          try {
              $companyItems['ADDRESSID'] = $this->SaveAddress($AdressItem, $db);
              if (isset($companyItems['ID_COMPANY'])) {
                  $lastInsertedId = $companyItems['ID_COMPANY'];
                  unset($companyItems['ID_COMPANY']);
                  //   var_export($companyItems);exit;
                  $db->update(
                      'TBL_SHOPMNG_COMPANY',
                      $companyItems,
                      'ID_COMPANY=\'' . $lastInsertedId . '\''
                  );
  
              } else {
                  $db->insert('TBL_SHOPMNG_COMPANY', $companyItems);
                  $lastInsertedId = $db->lastInsertId();
  
              }
              $db->commit();
     
  
              return $lastInsertedId;
          } catch (Exception $e) {
            // echo $e->getMessage();exit;
              $db->rollBack();
              return false;
          }
      }

    function SaveUser($post)
    {
        $user = $post['decryptDataPosted'];
        if ($user['USERNAME'] == 'Administrator') {
            return false;
        }
        $db = Zend_Registry::get("db");
        $authNamespace = Zend_Registry::get("authNamespace");
        try {
            $user['CREATEDBY'] = $authNamespace->userid;
            $user['DATECREATED'] = Application_Model_Func_Function::today($db);
            $user['PASSWORD'] = base64_encode(Application_Model_Func_Crypt::Encrypt($user['PASSWORD']));
            if (!isset($user['ACTIVE'])) {
                $user['ACTIVE'] = 0;
            }
            if (!empty($user['ID_USER'])) {
                $ID_USER_DECRYPTED = $user['ID_USER'];
                unset($user['ID_USER']);
                $db->update(
                    'TBL_SHOPMNG_USER',
                    $user,
                    'ID_USER=\'' . $ID_USER_DECRYPTED . '\''
                );
            } else {

                $db->insert('TBL_SHOPMNG_USER', $user);

            }


            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
    function SaveCustomer($post)
    {
      //  var_export($post);exit;

        $customer = $post['decryptDataPosted'];
        if ($customer['Administrator']) {
            return false;
        }
        $db = Zend_Registry::get("db");
        $authNamespace = Zend_Registry::get("authNamespace");
        try {
            $customer['CREATEDBY_CUSTOMER'] = $customer['CREATEDBY_CUSTOMER']?$customer['CREATEDBY_CUSTOMER']:$authNamespace->userid;
            $customer['DATECREATED_CUSTOMER'] = Application_Model_Func_Function::today($db);
            $customer['PASSWORD_CUSTOMER'] = base64_encode(
                Application_Model_Func_Crypt::Encrypt($customer['PASSWORD_CUSTOMER']));
            if (!isset($customer['ACTIVE_CUSTOMER'])) {
                $customer['ACTIVE_CUSTOMER'] = 0;
            }
            if (!empty($customer['ID_CUSTOMER'])) {
                unset($customer['DATECREATED_CUSTOMER']);
                $ID_CUSTOMER_DECRYPTED = $customer['ID_CUSTOMER'];
                unset($customer['ID_CUSTOMER']);
                $db->update(
                    'TBL_SHOPMNG_CUSTOMER',
                    $customer,
                    'ID_CUSTOMER=\'' . $ID_CUSTOMER_DECRYPTED . '\''
                );
            } else {

                $db->insert('TBL_SHOPMNG_CUSTOMER', $customer);

            }


            return true;
        } catch (Exception $e) {
            echo $e->getMessage();exit;
            return false;
        }
    }
}
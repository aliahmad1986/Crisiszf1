<?php

class Setting_Model_Car
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

 

    public function deleteCar($ID)
    {
        $db = Zend_Registry::get("db");
        try {
            $db->delete('TBL_SHOPMNG_CARGROUP', 'ID_CARGROUP=\'' . $ID . '\'');
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function fetchCarbyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_CARGROUP'))
            ->where('d.ID_CARGROUP=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function SaveCar($post)
    {
        $Car = $post['decryptDataPosted'];       
        $files = $post['filePosted'];
        $db = Zend_Registry::get("db");
        $authNamespace = Zend_Registry::get("authNamespace");
        try {
               if (!empty($Car['ID_CARGROUP'])) {
                $ID_CARGROUP_DECRYPTED = $Car['ID_CARGROUP'];
                $lastInsertedId=$ID_CARGROUP_DECRYPTED;
                unset($Car['ID_CARGROUP']);
                $db->update(
                    'TBL_SHOPMNG_CARGROUP',
                    $Car,
                    'ID_CARGROUP=\'' . $ID_CARGROUP_DECRYPTED . '\''
                );
            } else {

                $db->insert('TBL_SHOPMNG_CARGROUP', $Car);
                $lastInsertedId = $db->lastInsertId();

            }
            if(count($files)>0)
            {                
                $modelfunc = new Application_Model_Func_Function();
                $desination=realpath(APPLICATION_PATH . "/../public") . "/img/car/".$lastInsertedId ;
                $flag = $modelfunc->getAdaptorFilesDownloaded($desination,array_flip($files));

            }
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
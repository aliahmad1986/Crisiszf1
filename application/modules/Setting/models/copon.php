<?php

class Setting_Model_Copon
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

 

    public function deleteCopon($ID)
    {
        $db = Zend_Registry::get("db");
        try {
            $db->delete('TBL_SHOPMNG_COPON', 'ID_COPON=\'' . $ID . '\'');
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function fetchCoponbyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_COPON'))
            ->where('d.ID_COPON=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function SaveCopon($post)
    {
       
        $Copon = $post['decryptDataPosted']; 
        $Copon['ACTIVE_COPON']= $Copon['ACTIVE_COPON']?1:0; 
        $files = $post['filePosted'];
        $db = Zend_Registry::get("db");
        $authNamespace = Zend_Registry::get("authNamespace");
        try {
               if (!empty($Copon['ID_COPON'])) {
                $ID_COPON_DECRYPTED = $Copon['ID_COPON'];
                $lastInsertedId=$ID_COPON_DECRYPTED;
                unset($Copon['ID_COPON']);
                $db->update(
                    'TBL_SHOPMNG_COPON',
                    $Copon,
                    'ID_COPON=\'' . $ID_COPON_DECRYPTED . '\''
                );
            } else {

                $db->insert('TBL_SHOPMNG_COPON', $Copon);
                $lastInsertedId = $db->lastInsertId();

            }
            if(count($files)>0)
            {                
                $modelfunc = new Application_Model_Func_Function();
                $desination=realpath(APPLICATION_PATH . "/../public") . "/img/copon/".$lastInsertedId ;
                $flag = $modelfunc->getAdaptorFilesDownloaded($desination,array_flip($files));

            }
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
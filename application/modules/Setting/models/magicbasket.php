<?php

class Setting_Model_Magicbasket
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

 

    public function deleteMagicbasket($ID)
    {
        $db = Zend_Registry::get("db");
        try {
            $db->delete('TBL_SHOPMNG_MAGICBASKET', 'ID_MAGICBASKET=\'' . $ID . '\'');
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function fetchMagicbasketbyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_MAGICBASKET'))
            ->where('d.ID_MAGICBASKET=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function SaveMagicbasket($post)
    {
       
        $Magicbasket = $post['decryptDataPosted']; 
        $files = $post['filePosted'];
        $db = Zend_Registry::get("db");
        $authNamespace = Zend_Registry::get("authNamespace");
        try {
               if (!empty($Magicbasket['ID_MAGICBASKET'])) {
                $ID_MAGICBASKET_DECRYPTED = $Magicbasket['ID_MAGICBASKET'];
                $lastInsertedId=$ID_MAGICBASKET_DECRYPTED;
                unset($Magicbasket['ID_MAGICBASKET']);
                $db->update(
                    'TBL_SHOPMNG_MAGICBASKET',
                    $Magicbasket,
                    'ID_MAGICBASKET=\'' . $ID_MAGICBASKET_DECRYPTED . '\''
                );
            } else {
                $select=$db->select()->from('TBL_SHOPMNG_MAGICBASKET')
                ->where('PRODUCT=?',$Magicbasket['PRODUCT']);
                $stm=$db->query($select);
                $result=$stm->fetchAll();
                if(count($result)>0){
                    throw new Exception('محصولات تکراری: این محصول قبلا درج شده است');
                }

                $db->insert('TBL_SHOPMNG_MAGICBASKET', $Magicbasket);
                $lastInsertedId = $db->lastInsertId();

            }
          
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
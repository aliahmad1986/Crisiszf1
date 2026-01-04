<?php

class Setting_Model_Slider
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

 

    public function deleteSlider($ID)
    {
        $db = Zend_Registry::get("db");
        try {
            $db->delete('TBL_SHOPMNG_SLIDER', 'ID_SLIDER=\'' . $ID . '\'');
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    public function fetchSliderbyID($ID)
    {

        $db = Zend_Registry::get("db");
        $select = $db->select()
            ->from(array('d' => 'TBL_SHOPMNG_SLIDER'))
            ->where('d.ID_SLIDER=?', array($ID));

        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }

    function SaveSlider($post)
    {
       
        $Slider = $post['decryptDataPosted'];       
        $files = $post['filePosted'];
        $db = Zend_Registry::get("db");
        $authNamespace = Zend_Registry::get("authNamespace");
        try {
               if (!empty($Slider['ID_SLIDER'])) {
                $ID_SLIDER_DECRYPTED = $Slider['ID_SLIDER'];
                $lastInsertedId=$ID_SLIDER_DECRYPTED;
                unset($Slider['ID_SLIDER']);
                $db->update(
                    'TBL_SHOPMNG_SLIDER',
                    $Slider,
                    'ID_SLIDER=\'' . $ID_SLIDER_DECRYPTED . '\''
                );
            } else {

                $db->insert('TBL_SHOPMNG_SLIDER', $Slider);
                $lastInsertedId = $db->lastInsertId();

            }
            if(count($files)>0)
            {                
                $modelfunc = new Application_Model_Func_Function();
                $desination=realpath(APPLICATION_PATH . "/../public") . "/img/slider/".$lastInsertedId ;
                $flag = $modelfunc->getAdaptorFilesDownloaded($desination,array_flip($files));

            }
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }
}
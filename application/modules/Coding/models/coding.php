<?php

class Coding_Model_coding
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
    public function GetProvinceList()
    {
        $db = Zend_Registry::get("db");
        $select = $db->select()->distinct()
            ->from(array('d' => 'TBL_SHOPMNG_CODING'),array('CODE','CATNAME'))
            ->where('CATEGURY=\'PROVINCE\'');
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result;
    }
    public function GetCityList($provience)
    {
        $db = Zend_Registry::get("db");
        $select = $db->select()->distinct()
            ->from(array('d' => 'TBL_SHOPMNG_CODING'),array('SUBCODE','SUBCATNAME'))
            ->where('CATEGURY=\'PROVINCE\'')
            ->where('d.CODE=?',array($provience));
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
       // echo $select->__toString();
        return $result;
    }
    public function GetModelList($factory)
    {
        $db = Zend_Registry::get("db");
        $select = $db->select()->distinct()
            ->from(array('d' => 'TBL_SHOPMNG_CODING'),array('SUBCODE','SUBCATNAME'))
            ->where('d.CODE=?',array(101))
            ->where('d.CATEGURY=?',array("100-".$factory));
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
       // echo $select->__toString();
        return $result;
    }

    public function GetFactorylList()
    {
        $db = Zend_Registry::get("db");
        $select = $db->select()->distinct()
            ->from(array('d' => 'TBL_SHOPMNG_CODING'),array('SUBCODE','SUBCATNAME'))
            ->where('d.CODE=?',array(100))
            ->where('d.CATEGURY=?',array("FACTORY"));
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
       // echo $select->__toString();
        return $result;
    }
    public function GetGroupCarList()
    {
        $db = Zend_Registry::get("db");
        $select = $db->select()->distinct()
            ->from(array('d' => 'TBL_SHOPMNG_CODING'),array('SUBCODE','SUBCATNAME'))
            ->where('d.CODE=?',array(102))
            ->where('d.CATEGURY=?',array("Group"));
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
       // echo $select->__toString();
        return $result;
    }
  

}
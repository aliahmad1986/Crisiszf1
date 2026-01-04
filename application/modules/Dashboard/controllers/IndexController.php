<?php

class Dashboard_IndexController extends zend_controller_action
{
    public function init()
    {
         $this->view->translate=Zend_Registry::get('Zend_Translate');

    }
    public function indexAction()
    {

    }
}

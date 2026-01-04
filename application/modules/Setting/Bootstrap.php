<?php
class Setting_Bootstrap extends Zend_Application_Module_Bootstrap
{
	protected function _initAutoload()
	{
		$moduleLoader = new Zend_Application_Module_Autoloader(array(
		'namespace' => 'shop',
		'basePath' => APPLICATION_PATH
		));
	    return $moduleLoader;
	}

	protected function _initViewHelpers()
	{
	}

}


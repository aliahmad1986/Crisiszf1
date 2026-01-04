<?php

class Application_Model_Func_Function
{
        static public function get_baseUrl(){
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'staging');

        return $config->rest->baseUrl;
    }
    static public function get_api_key(){
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', 'staging');

        return $config->rest->api_key;
    }

   static public function unlikListFiles($files,$directorys)
    {
        try {
            foreach ($files as $file) {
              echo  $selectFile = $directorys."\\" . $file;              
                if (file_exists($selectFile)) {
                    unlink($selectFile);
                }
                rmdir($directorys);
            }
            return true;

        } catch (Exception $e) {
            return false;
        }
    }
    function yesterday()
    {
        $db = Zend_Registry::get("db");
        $select = "select FORMAT( DATEADD(DAY,-1,GETDATE()), 'yyyy/MM/dd', 'fa') as yesterday";
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result[0]['yesterday'];
    }
    static function today($db)
    {
        $select = "select  FORMAT(GETDATE(), 'yyyy/MM/dd hh:mm:ss', 'en') as today";
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result[0]['today'];
    }
    static function todayinsh($db)
    {
        $select = "select  FORMAT(GETDATE(), 'yyyy/MM/dd hh:mm:ss', 'fa') as today";
        $stmt = $db->query($select);
        $result = $stmt->fetchAll();
        return $result[0]['today'];
    }
    public function getAdaptorFileUploaded($desination, $newname)
    {
        try {
            $adapter = new Zend_File_Transfer_Adapter_Http();
            if (!is_dir($desination))
                mkdir($desination, 0777, true);
            $adapter->setDestination($desination);

            if (!$adapter->receive()) {
                $messages = $adapter->getMessages();
                return false;
            }
            rename($adapter->getFileName(), $desination . $newname);
            return true;
        } catch (Exception $e) {
            //  var_export($e->getMessage());

            return false;
        }

    }
    public function getAdaptorFilesDownloaded($desination, $newNames)
    {
        try {
            if (!is_dir($desination))
                mkdir($desination, 0777, true);
            $upload = new Zend_File_Transfer_Adapter_Http();
            $upload->setDestination($desination);
            $files = $upload->getFileInfo();
            // var_export($files);
            foreach ($files as $file => $fileInfo) {
                if ($upload->isUploaded($file)) {
                    if ($upload->isValid($file)) {
                        if ($upload->receive($file)) {
                            $info = $upload->getFileInfo($file);

                            $detilFile = explode('.', $info[$file]['name']);

                            rename($info[$file]['tmp_name'], $desination . '/' . $newNames[$file] . "." . $detilFile[1]);

                        } else {
                            $messages = $upload->getMessages();
                            return false;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

    }

    static public function getMenu()
    {
        $authNamespace = Zend_Registry::get("authNamespace");
        include_once (APPLICATION_PATH . "/configs/permission.php");


        //var_export($authNamespace->ACCESS['action']);
        foreach ($permission as $item) {
            $modulesEncrypt = base64_encode(Application_Model_Func_Crypt::Encrypt($item['moduleName']));
            if ($authNamespace->ACCESS['module'][$modulesEncrypt] == 1 || $authNamespace->isAdmin) {
                $itemconcat = $itemconcat . '<ul class="side a-collapse short "> <a class="ul-text">
                <i class="fas fa-cog fa-spin mr-1"></i> ' . $item['title'] .

                    '<i class="fas fa-chevron-down arrow"></i></a><div class="side-item-container ">';
                foreach ($item['controllerList'] as $controller) {
                    $detail = array('module' => $item['moduleName'], 'controller' => $controller['controllersName'], 'action' => 'index');
                    $actionNameCerypted = base64_encode(Application_Model_Func_Crypt::Encrypt(json_encode($detail)));


                    if ($authNamespace->ACCESS['action'][$actionNameCerypted] == 1 || $authNamespace->isAdmin) {
                        $itemconcat = $itemconcat . '<li class="side-item"><a href="' .
                            Zend_Controller_Front::getInstance()->getBaseUrl() . '/' . $item['moduleName'] . '/'
                            . $controller['controllersName'] . '/' . $controller['actions']['index']['actionName']
                            . '"><i class="fas fa-angle-right mr-2"></i>'
                            . $controller['actions']['index']['title'] . '</a></li>';
                    }
                }
                $itemconcat = $itemconcat . '</div></ul>';
            }

        }

        return $itemconcat;
    }


}

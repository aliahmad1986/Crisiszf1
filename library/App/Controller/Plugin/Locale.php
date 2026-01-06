<?php
class App_Controller_Plugin_Locale extends Zend_Controller_Plugin_Abstract
{

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $post = $request->getPost();
        $post2 = array();
        $post3 = array();
        if ($post) {
            $modalCrypt = new Application_Model_Func_Crypt();
            if (count($post) > 0) {
                foreach ($post as $key => $value) {
                    $detailKeyDecrypted = json_decode($modalCrypt->Decrypt(base64_decode($key)), true);
                    //var_export($detailKeyDecrypted['required']);
                    if (is_string($value)) {
                        if ($detailKeyDecrypted['required'] == '1' && trim($value) == '') {
                            $this->getRequest()->setControllerName('error')->setActionName('error')->setDispatched(true);
                            $error = new Zend_Controller_Plugin_ErrorHandler();
                            $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
                            $error->request = clone ($request);
                            //  $error->exception = $e; // If you have caught the exception to $e, set it. 
                            $request->setParam('error_handler', $error);

                        }
                        if ($detailKeyDecrypted['encrypt'] == '1') {
                        
                            $valDecrypted = $modalCrypt->Decrypt(base64_decode($value));
                        } else {
                            $valDecrypted = $value;
                        }
                    } else {
                        $valDecrypted = $valDecrypted = $value;
                    }
                    $post2[$detailKeyDecrypted['name']] = $valDecrypted;

                }
            }
            if (count($_FILES) > 0) {
                foreach ($_FILES as $keyFile => $file) {
                    if ($file['name'] != '') {
                        $detailKeyFileDecrypted = json_decode($modalCrypt->Decrypt(base64_decode($keyFile)), true);
                        if (
                            !in_array($file["type"], $detailKeyFileDecrypted['filetype']) || $file['size'] >
                            $detailKeyFileDecrypted['volumfile']
                        ) {

                            $this->getRequest()->setControllerName('error')->setActionName('error')->setDispatched(true);
                            $error = new Zend_Controller_Plugin_ErrorHandler();
                            $error->type = Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER;
                            $error->request = clone ($request);
                            $error->exception = "مشخصات فایل را چک کنید"; // If you have caught the exception to $e, set it. 
                            $request->setParam('error_handler', $error);
                            $request->setParam('errorTxt', "مشخصات فایل را چک کنید");

                        } else {
                            $post3[$detailKeyFileDecrypted['name']] = $keyFile;
                        }
                    }
                }
            }

            $postModified = array('encryptDataPosted' => $post, 'decryptDataPosted' => $post2, 'filePosted' => $post3);
           // $request->setPost($postModified);

        }


        if ($this->checkAccess($request)) {

        } else {
            $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
            $redirector->gotoSimple("index", "index", "default");
            exit;
        }


    }

    private function checkAccess(Zend_Controller_Request_Abstract $request)
    {
        return true;
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {

    }



}

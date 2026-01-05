<?php
class Center_CenterController extends Zend_Controller_Action
{
    protected $_model;
    
    public function init()
    {
        $this->_model = new Center_Model_Centers();
        //$this->_helper->layout->disableLayout();
    }
    
    /**
     * Main action - identical to UserController::indexAction
     */
    public function indexAction()
    {
        // Handle AJAX request for DataTable - same as users
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_ajaxGetCenters();
        } else {
            // Show the main page - same pattern as users
            $this->view->headTitle('Centers Management');
            
            // Get statistics - same pattern as users
            $stats = $this->_model->getCentersCount();
            $this->view->stats = $stats;
            
            // Get center types for dropdown
            $centerTypes = $this->_model->getCenterTypes();
            $this->view->centerTypes = $centerTypes;
        }
    }
    
    /**
     * AJAX: Get centers for DataTable - identical to users
     */
    private function _ajaxGetCenters()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            $centers = $this->_model->getAllCenters();
            
            $data = array();
            foreach ($centers as $center) {
                $data[] = array(
                    'CenterID' => $center['CenterID'],
                    'CenterName' => htmlspecialchars($center['CenterName'], ENT_QUOTES, 'UTF-8'),
                    'CenterTypeName' => htmlspecialchars($center['CenterTypeName'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                    'Address' => htmlspecialchars($center['Address'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'Phone1' => htmlspecialchars($center['Phone1'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'Website' => $center['Website'] ? 
                        '<a href="' . htmlspecialchars($center['Website'], ENT_QUOTES, 'UTF-8') . '" target="_blank">' . 
                        htmlspecialchars($center['Website'], ENT_QUOTES, 'UTF-8') . '</a>' : 'N/A',
                    'IsActive' => $center['IsActive'] ? 
                        '<span class="badge badge-success">Active</span>' : 
                        '<span class="badge badge-danger">Inactive</span>',
                    'CreatedAt' => date('Y-m-d H:i', strtotime($center['CreatedAt'])),
                    'CreatedByName' => $center['CreatedByName'] ? 
                        htmlspecialchars($center['CreatedByName'], ENT_QUOTES, 'UTF-8') : 'N/A',
                    'Actions' => '
                        <button class="btn btn-sm btn-primary edit-center" data-id="' . $center['CenterID'] . '">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-center" data-id="' . $center['CenterID'] . '">
                            <i class="fa fa-trash"></i> Delete
                        </button>'
                );
            }
            
            $response = array(
                'draw' => (int)$this->getRequest()->getParam('draw', 1),
                'recordsTotal' => count($data),
                'recordsFiltered' => count($data),
                'data' => $data
            );
            
            echo json_encode($response);
            
        } catch (Exception $e) {
            $response = array(
                'draw' => (int)$this->getRequest()->getParam('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => array(),
                'error' => $e->getMessage()
            );
            
            echo json_encode($response);
        }
    }
    
    /**
     * Add center action - identical to UserController::addUserAction
     */
    public function addCenterAction()
    {
		 $this->_helper->layout->disableLayout();
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session (same as users)
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['CreatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $id = $this->_model->addCenter($data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Center added successfully!',
                    'id' => $id
                ));
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form - same pattern as users
            $this->view->headTitle('Add New Center');
            $this->view->centerTypes = $this->_model->getCenterTypes();
        }
    }
    
    /**
     * Edit center action - identical to UserController::editUserAction
     */
    public function editCenterAction()
    {
		 $this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam('id');
        
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session (same as users)
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['UpdatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $this->_model->updateCenter($id, $data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Center updated successfully!'
                ));
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form - same pattern as users
            if ($id) {
                $this->view->center = $this->_model->getCenter($id);
            }
            $this->view->headTitle('Edit Center');
            $this->view->centerTypes = $this->_model->getCenterTypes();
        }
    }
    
    /**
     * Delete center action - identical to UserController::deleteAction
     */
    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        if ($this->getRequest()->isPost()) {
            try {
                $id = $this->getRequest()->getPost('id');
                $this->_model->deleteCenter($id);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Center deleted successfully!'
                ));
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        }
    }
    
    /**
     * Check center name availability - similar to username check
     */
    public function checkCenterNameAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $centerName = $this->getRequest()->getParam('centerName', '');
        $centerId = $this->getRequest()->getParam('centerId', 0);
        
        try {
            $sql = "SELECT COUNT(*) as count FROM org.tbl_Centers 
                    WHERE CenterName = ? AND CenterID != ?";
            
            $db = Zend_Registry::get('db');
            $stmt = $db->query($sql, array($centerName, $centerId));
            $result = $stmt->fetch();
            
            $available = ($result['count'] == 0);
            
            echo json_encode(array(
                'available' => $available,
                'message' => $available ? 'Center name available' : 'Center name already exists'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking center name'
            ));
        }
    }
}
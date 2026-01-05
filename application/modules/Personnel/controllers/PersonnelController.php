<?php
class Personnel_PersonnelController extends Zend_Controller_Action
{
    protected $_model;
    
    public function init()
    {
        $this->_model = new Personnel_Model_Personnel();
       
    }
    
    /**
     * Main action - shows personnel list
     */
    public function indexAction()
    {
        // Handle AJAX request for DataTable
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_ajaxGetPersonnel();
        } else {
            // Show the main page
            $this->view->headTitle('Personnel Management');
            
            // Get statistics
            $stats = $this->_model->getPersonnelCount();
            $this->view->stats = $stats;
        }
    }
    
    /**
     * AJAX: Get personnel for DataTable
     */
    private function _ajaxGetPersonnel()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            $personnelList = $this->_model->getAllPersonnel();
            
            $data = array();
            foreach ($personnelList as $person) {
                $data[] = array(
                    'PersonnelID' => $person['PersonnelID'],
                    'NationalCode' => htmlspecialchars($person['NationalCode'], ENT_QUOTES, 'UTF-8'),
                    'FullName' => htmlspecialchars($person['FullName'], ENT_QUOTES, 'UTF-8'),
                    'FirstName' => htmlspecialchars($person['FirstName'], ENT_QUOTES, 'UTF-8'),
                    'LastName' => htmlspecialchars($person['LastName'], ENT_QUOTES, 'UTF-8'),
                    'Mobile1' => $this->_formatPhone($person['Mobile1']),
                    'Phone2' => $this->_formatPhone($person['Phone2']),
                    'PersonnelNumber' => htmlspecialchars($person['PersonnelNumber'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'IsActive' => $person['IsActive'] ? 
                        '<span class="badge badge-success">Active</span>' : 
                        '<span class="badge badge-danger">Inactive</span>',
                    'EndDate' => $person['EndDate'] ?? '',
                    'Description' => htmlspecialchars($person['Description'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'CreatedAt' => date('Y-m-d H:i', strtotime($person['CreatedAt'])),
                    'CreatedByName' => $person['CreatedByName'] ? 
                        htmlspecialchars($person['CreatedByName'], ENT_QUOTES, 'UTF-8') : 'N/A',
                    'Actions' => '
                        <button class="btn btn-sm btn-primary edit-personnel" data-id="' . $person['PersonnelID'] . '">
                            <i class="fa fa-edit"></i> Edit
                        </button>
                        <button class="btn btn-sm btn-danger delete-personnel" data-id="' . $person['PersonnelID'] . '">
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
     * Add personnel action
     */
    public function addPersonnelAction()
    {
		 $this->_helper->layout->disableLayout();
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['CreatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $id = $this->_model->addPersonnel($data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Personnel added successfully!',
                    'id' => $id
                ));
				 $this->_redirect('/Personnel/personnel/index');
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form
            $this->view->headTitle('Add New Personnel');
        }
    }
    
    /**
     * Edit personnel action
     */
    public function editPersonnelAction()
    {
		 $this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam('id');
        
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['UpdatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $this->_model->updatePersonnel($id, $data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Personnel updated successfully!'
                ));
				 $this->_redirect('/Personnel/personnel/index');
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form
            if ($id) {
                $this->view->personnel = $this->_model->getPersonnel($id);
            }
            $this->view->headTitle('Edit Personnel');
        }
    }
    
    /**
     * Delete personnel action
     */
    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        if ($this->getRequest()->isPost()) {
            try {
                $id = $this->getRequest()->getPost('id');
                $this->_model->deletePersonnel($id);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Personnel deleted successfully!'
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
     * Check National Code availability
     */
    public function checkNationalCodeAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $nationalCode = $this->getRequest()->getParam('nationalCode', '');
        $personnelId = $this->getRequest()->getParam('personnelId', 0);
        
        try {
            $exists = $this->_model->checkNationalCodeExists($nationalCode, $personnelId);
            
            echo json_encode(array(
                'available' => !$exists,
                'message' => $exists ? 'National Code already exists' : 'National Code available'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking National Code'
            ));
        }
    }
    
    /**
     * Check Personnel Number availability
     */
    public function checkPersonnelNumberAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $personnelNumber = $this->getRequest()->getParam('personnelNumber', '');
        $personnelId = $this->getRequest()->getParam('personnelId', 0);
        
        try {
            $exists = $this->_model->checkPersonnelNumberExists($personnelNumber, $personnelId);
            
            echo json_encode(array(
                'available' => !$exists,
                'message' => $exists ? 'Personnel Number already exists' : 'Personnel Number available'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking Personnel Number'
            ));
        }
    }
    
    /**
     * Search personnel for autocomplete
     */
    public function searchAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $term = $this->getRequest()->getParam('term', '');
        
        try {
            $personnel = $this->_model->searchPersonnel($term);
            
            $results = array();
            foreach ($personnel as $person) {
                $results[] = array(
                    'id' => $person['PersonnelID'],
                    'text' => $person['FirstName'] . ' ' . $person['LastName'] . 
                              ' (' . $person['NationalCode'] . ')',
                    'nationalCode' => $person['NationalCode'],
                    'personnelNumber' => $person['PersonnelNumber']
                );
            }
            
            echo json_encode(array('results' => $results));
            
        } catch (Exception $e) {
            echo json_encode(array('results' => array()));
        }
    }
    
    /**
     * Format phone number for display
     */
    private function _formatPhone($phone)
    {
        if (empty($phone)) {
            return 'N/A';
        }
        
        // Format Iranian mobile numbers
        if (preg_match('/^09\d{9}$/', $phone)) {
            return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
        }
        
        // Format Iranian landline numbers
        if (preg_match('/^02\d{9}$/', $phone)) {
            return substr($phone, 0, 3) . ' ' . substr($phone, 3, 4) . ' ' . substr($phone, 7);
        }
        
        return $phone;
    }
}
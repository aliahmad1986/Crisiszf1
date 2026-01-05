<?php
class User_UserController extends Zend_Controller_Action
{
    protected $_model;
    
    public function init()
    {
        $this->_model = new User_Model_Users();
   
    }
    
    /**
     * Main action - shows user list
     */
    public function indexAction()
    {
        // Handle AJAX request for DataTable
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_ajaxGetUsers();
        } else {
            // Show the main page
            $this->view->headTitle('User Management');
            
            // Get statistics
            $stats = $this->_model->getUsersCount();
            $this->view->stats = $stats;
        }
    }
    
    /**
     * AJAX: Get users for DataTable with personnel info
     */
    private function _ajaxGetUsers()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            $users = $this->_model->getAllUsers();
            
            $data = array();
            foreach ($users as $user) {
                $personnelInfo = '';
                if ($user['PersonnelFullName']) {
                    $personnelInfo = '
                        <div class="small">
                            <strong>Personnel:</strong> ' . htmlspecialchars($user['PersonnelFullName'], ENT_QUOTES, 'UTF-8') . '<br>
                            <strong>National Code:</strong> ' . htmlspecialchars($user['PersonnelNationalCode'], ENT_QUOTES, 'UTF-8') . '<br>
                            <strong>Personnel No:</strong> ' . htmlspecialchars($user['PersonnelNumber'], ENT_QUOTES, 'UTF-8') . '
                        </div>
                    ';
                }
                
                $data[] = array(
                    'UserID' => $user['UserID'],
                    'Username' => htmlspecialchars($user['Username'], ENT_QUOTES, 'UTF-8'),
                    'FullName' => htmlspecialchars($user['FullName'], ENT_QUOTES, 'UTF-8'),
                    'PersonnelInfo' => $personnelInfo,
                    'Email' => htmlspecialchars($user['Email'], ENT_QUOTES, 'UTF-8'),
                    'Mobile' => htmlspecialchars($user['Mobile'], ENT_QUOTES, 'UTF-8'),
                    'IsActive' => $user['IsActive'] ? 
                        '<span class="badge badge-success">Active</span>' : 
                        '<span class="badge badge-danger">Inactive</span>',
                    'CreatedAt' => date('Y-m-d H:i', strtotime($user['CreatedAt'])),
                    'CreatedByName' => $user['CreatedByName'] ? 
                        htmlspecialchars($user['CreatedByName'], ENT_QUOTES, 'UTF-8') : 'N/A',
                    'Actions' => '
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-primary edit-user" data-id="' . $user['UserID'] . '">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-user" data-id="' . $user['UserID'] . '">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>'
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
     * Add user action
     */
    public function addUserAction()
    {
		            $this->_helper->layout->disableLayout();

        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['CreatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $id = $this->_model->addUser($data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'User added successfully!',
                    'id' => $id
                ));
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form
            $this->view->headTitle('Add New User');
            
            // Get available personnel for dropdown
            $availablePersonnel = $this->_model->getAvailablePersonnel();
            $this->view->availablePersonnel = $availablePersonnel;
        }
    }
    
    /**
     * Edit user action
     */
    public function editUserAction()
    {
		$this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam('id');
        
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);
          
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['UpdatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $this->_model->updateUser($id, $data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'User updated successfully!'
                ));
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form
            if ($id) {
                $user = $this->_model->getUser($id);
                $this->view->user = $user;
                
                // Get available personnel for dropdown (including current personnel)
                $availablePersonnel = $this->_model->getAvailablePersonnel();
                
                // If user has personnel, add it to the list
                if ($user['PersonelID']) {
                    $currentPersonnel = $this->_model->getPersonnel($user['PersonelID']);
                    if ($currentPersonnel) {
                        // Check if current personnel is already in the list
                        $found = false;
                        foreach ($availablePersonnel as $personnel) {
                            if ($personnel['PersonnelID'] == $currentPersonnel['PersonnelID']) {
                                $found = true;
                                break;
                            }
                        }
                        
                        // If not found, add it
                        if (!$found) {
                            $availablePersonnel[] = array(
                                'PersonnelID' => $currentPersonnel['PersonnelID'],
                                'NationalCode' => $currentPersonnel['NationalCode'],
                                'FirstName' => $currentPersonnel['FirstName'],
                                'LastName' => $currentPersonnel['LastName'],
                                'Mobile1' => $currentPersonnel['Mobile1']
                            );
                        }
                    }
                }
                
                $this->view->availablePersonnel = $availablePersonnel;
            }
            $this->view->headTitle('Edit User');
        }
    }
    
    /**
     * Delete user action
     */
    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        if ($this->getRequest()->isPost()) {
            try {
                $id = $this->getRequest()->getPost('id');
                $this->_model->deleteUser($id);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'User deleted successfully!'
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
     * Check username availability
     */
    public function checkUsernameAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $username = $this->getRequest()->getParam('username', '');
        $userId = $this->getRequest()->getParam('userId', 0);
        
        try {
            $exists = $this->_model->checkUsernameExists($username, $userId);
            
            echo json_encode(array(
                'available' => !$exists,
                'message' => $exists ? 'Username already taken' : 'Username available'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking username'
            ));
        }
    }
    
    /**
     * Check personnel linking availability
     */
    public function checkPersonnelAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $personnelId = $this->getRequest()->getParam('personnelId', 0);
        $userId = $this->getRequest()->getParam('userId', 0);
        
        try {
            $linked = $this->_model->isPersonnelLinked($personnelId, $userId);
            
            echo json_encode(array(
                'available' => !$linked,
                'message' => $linked ? 'Personnel already linked to another user' : 'Personnel available'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking personnel'
            ));
        }
    }
    
    /**
     * Get personnel details for autofill
     */
    public function getPersonnelDetailsAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $personnelId = $this->getRequest()->getParam('personnelId', 0);
        
        try {
            if ($personnelId) {
                $personnel = $this->_model->getPersonnel($personnelId);
                if ($personnel) {
                    echo json_encode(array(
                        'success' => true,
                        'personnel' => array(
                            'fullName' => $personnel['FirstName'] . ' ' . $personnel['LastName'],
                            'mobile' => $personnel['Mobile1'],
                            'nationalCode' => $personnel['NationalCode']
                        )
                    ));
                } else {
                    echo json_encode(array(
                        'success' => false,
                        'message' => 'Personnel not found'
                    ));
                }
            } else {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'No personnel ID provided'
                ));
            }
            
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ));
        }
    }
    
    /**
     * Search users for autocomplete
     */
    public function searchAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $term = $this->getRequest()->getParam('term', '');
        
        try {
            $users = $this->_model->searchUsers($term);
            
            $results = array();
            foreach ($users as $user) {
                $displayName = $user['FullName'] . ' (' . $user['Username'] . ')';
                if ($user['PersonnelFirstName']) {
                    $displayName .= ' - Personnel: ' . $user['PersonnelFirstName'] . ' ' . $user['PersonnelLastName'];
                }
                
                $results[] = array(
                    'id' => $user['UserID'],
                    'text' => $displayName
                );
            }
            
            echo json_encode(array('results' => $results));
            
        } catch (Exception $e) {
            echo json_encode(array('results' => array()));
        }
    }
}
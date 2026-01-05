<?php
class User_UserController extends Zend_Controller_Action
{
    protected $_model;
    
    public function init()
    {
        $this->_model = new User_Model_Users();
       // $this->_helper->layout->disableLayout();
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
     * AJAX: Get users for DataTable
     */
    private function _ajaxGetUsers()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            $users = $this->_model->getAllUsers();
            
            $data = array();
            foreach ($users as $user) {
                $data[] = array(
                    'UserID' => $user['UserID'],
                    'Username' => htmlspecialchars($user['Username'], ENT_QUOTES, 'UTF-8'),
                    'FullName' => htmlspecialchars($user['FullName'], ENT_QUOTES, 'UTF-8'),
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
                            <button type="button" class="btn btn-sm btn-primary edit-user" 
                                    data-id="' . $user['UserID'] . '" title="Edit">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-user" 
                                    data-id="' . $user['UserID'] . '" title="Delete">
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
     * Add user action - shows form or processes submission
     */
    public function addUserAction()
    {
        $this->_helper->layout->disableLayout();
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);
            $this->_helper->layout->disableLayout();
            
            $response = array('success' => false);
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session (if available)
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['CreatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $id = $this->_model->addUser($data);
                
                $response = array(
                    'success' => true,
                    'message' => 'User added successfully!',
                    'id' => $id
                );
                
            } catch (Exception $e) {
                $response = array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                );
            }
            
            echo json_encode($response);
            
        } else {
            // Show form
            $this->view->headTitle('Add New User');
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
            $this->_helper->layout->disableLayout();
            
            $response = array('success' => false);
            
            try {
                $data = $this->getRequest()->getPost();
                
                // Get current user ID from session (if available)
                $auth = Zend_Auth::getInstance();
                if ($auth->hasIdentity()) {
                    $data['UpdatedBy'] = $auth->getIdentity()->UserID;
                }
                
                $this->_model->updateUser($id, $data);
                
                $response = array(
                    'success' => true,
                    'message' => 'User updated successfully!'
                );
                
            } catch (Exception $e) {
                $response = array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                );
            }
            
            echo json_encode($response);
            
        } else {
            // Show form
            if ($id) {
                $user = $this->_model->getUser($id);
                $this->view->user = $user;
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
        
        $response = array('success' => false);
        
        if ($this->getRequest()->isPost()) {
            try {
                $id = $this->getRequest()->getPost('id');
                $this->_model->deleteUser($id);
                
                $response = array(
                    'success' => true,
                    'message' => 'User deleted successfully!'
                );
                
            } catch (Exception $e) {
                $response = array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                );
            }
        }
        
        echo json_encode($response);
    }
    
    /**
     * Search users action
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
                $results[] = array(
                    'id' => $user['UserID'],
                    'text' => $user['FullName'] . ' (' . $user['Username'] . ')'
                );
            }
            
            echo json_encode(array('results' => $results));
            
        } catch (Exception $e) {
            echo json_encode(array('results' => array()));
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
            $sql = "SELECT COUNT(*) as count FROM auth.tbl_Users 
                    WHERE Username = ? AND UserID != ?";
            
            $db = Zend_Registry::get('db');
            $stmt = $db->query($sql, array($username, $userId));
            $result = $stmt->fetch();
            
            $available = ($result['count'] == 0);
            
            echo json_encode(array(
                'available' => $available,
                'message' => $available ? 'Username available' : 'Username already taken'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking username'
            ));
        }
    }
}
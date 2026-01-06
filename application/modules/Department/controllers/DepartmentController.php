<?php
class Department_DepartmentController extends Zend_Controller_Action
{
    protected $_model;
    
    public function init()
    {
        $this->_model = new Department_Model_Departments();
       
    }
    
    /**
     * Main action - shows departments list
     */
    public function indexAction()
    {
        // Handle AJAX request for DataTable
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_ajaxGetDepartments();
        } else {
            // Show the main page
            $this->view->headTitle('Departments Management');
            
            // Get statistics
            $stats = $this->_model->getDepartmentsStatistics();
            $this->view->stats = $stats;
            
            // Get departments by center for statistics
            $byCenter = $this->_model->getDepartmentsCountByCenter();
            $this->view->byCenter = $byCenter;
            
            // Get active centers for filter
            $centers = $this->_model->getActiveCenters();
            $this->view->centers = $centers;
            
            // Get department types
            $departmentTypes = $this->_model->getDepartmentTypes();
            $this->view->departmentTypes = $departmentTypes;
        }
    }
    
    /**
     * AJAX: Get departments for DataTable
     */
    private function _ajaxGetDepartments()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            $departments = $this->_model->getAllDepartments();
            
            $data = array();
            foreach ($departments as $dept) {
                $data[] = array(
                    'DepartmentID' => $dept['DepartmentID'],
                    'CenterName' => htmlspecialchars($dept['CenterName'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                    'DepartmentName' => htmlspecialchars($dept['DepartmentName'], ENT_QUOTES, 'UTF-8'),
                    'DepartmentTypeName' => htmlspecialchars($dept['DepartmentTypeName'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                    'DepartmentCode' => htmlspecialchars($dept['DepartmentCode'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'Phone1' => $this->_formatPhone($dept['Phone1']),
                    'IsActive' => $dept['IsActive'] ? 
                        '<span class="badge badge-success">Active</span>' : 
                        '<span class="badge badge-danger">Inactive</span>',
                    'CreatedAt' => date('Y-m-d H:i', strtotime($dept['CreatedAt'])),
                    'CreatedByName' => $dept['CreatedByName'] ? 
                        htmlspecialchars($dept['CreatedByName'], ENT_QUOTES, 'UTF-8') : 'N/A',
                    'Actions' => '
                        <div class="btn-group" role="group">
                            <button class="btn btn-sm btn-primary edit-department" data-id="' . $dept['DepartmentID'] . '">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button class="btn btn-sm btn-danger delete-department" data-id="' . $dept['DepartmentID'] . '">
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
     * Get departments by center (for AJAX)
     */
    public function byCenterAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $centerId = $this->getRequest()->getParam('centerId', 0);
        
        try {
            $departments = $this->_model->getDepartmentsByCenter($centerId);
            
            $results = array();
            foreach ($departments as $dept) {
                $results[] = array(
                    'id' => $dept['DepartmentID'],
                    'text' => $dept['DepartmentName'] . ' (' . ($dept['DepartmentTypeName'] ?? '') . ')'
                );
            }
            
            echo json_encode(array('departments' => $results));
            
        } catch (Exception $e) {
            echo json_encode(array('departments' => array()));
        }
    }
    
    /**
     * Add department action
     */
    public function addDepartmentAction()
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
                
                $id = $this->_model->addDepartment($data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Department added successfully!',
                    'id' => $id
                ));
				 $this->_redirect('/Department/department/index');
                
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form
            $this->view->headTitle('Add New Department');
            
            // Get active centers for dropdown
            $centers = $this->_model->getActiveCenters();
            $this->view->centers = $centers;
            
            // Get department types
            $departmentTypes = $this->_model->getDepartmentTypes();
            $this->view->departmentTypes = $departmentTypes;
        }
    }
    
    /**
     * Edit department action
     */
    public function editDepartmentAction()
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
                
                $this->_model->updateDepartment($id, $data);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Department updated successfully!'
                ));
                $this->_redirect('/Department/department/index');
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form
            if ($id) {
                $department = $this->_model->getDepartment($id);
                $this->view->department = $department;
            }
            $this->view->headTitle('Edit Department');
            
            // Get active centers for dropdown
            $centers = $this->_model->getActiveCenters();
            $this->view->centers = $centers;
            
            // Get department types
            $departmentTypes = $this->_model->getDepartmentTypes();
            $this->view->departmentTypes = $departmentTypes;
        }
    }
    
    /**
     * Delete department action
     */
    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        if ($this->getRequest()->isPost()) {
            try {
                $id = $this->getRequest()->getPost('id');
                $this->_model->deleteDepartment($id);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Department deleted successfully!'
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
     * Check department code availability
     */
    public function checkDepartmentCodeAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $departmentCode = $this->getRequest()->getParam('departmentCode', '');
        $departmentId = $this->getRequest()->getParam('departmentId', 0);
        
        try {
            $exists = $this->_model->checkDepartmentCodeExists($departmentCode, $departmentId);
            
            echo json_encode(array(
                'available' => !$exists,
                'message' => $exists ? 'Department Code already exists' : 'Department Code available'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking Department Code'
            ));
        }
    }
    
    /**
     * Check department name in center
     */
    public function checkDepartmentNameAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $centerId = $this->getRequest()->getParam('centerId', 0);
        $departmentName = $this->getRequest()->getParam('departmentName', '');
        $departmentId = $this->getRequest()->getParam('departmentId', 0);
        
        try {
            $exists = $this->_model->checkDepartmentNameInCenter($centerId, $departmentName, $departmentId);
            
            echo json_encode(array(
                'available' => !$exists,
                'message' => $exists ? 'Department name already exists in this center' : 'Department name available'
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'available' => false,
                'message' => 'Error checking department name'
            ));
        }
    }
    
    /**
     * Search departments
     */
    public function searchAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        $term = $this->getRequest()->getParam('term', '');
        $centerId = $this->getRequest()->getParam('centerId', null);
        
        try {
            $departments = $this->_model->searchDepartments($term, $centerId);
            
            $results = array();
            foreach ($departments as $dept) {
                $results[] = array(
                    'id' => $dept['DepartmentID'],
                    'text' => $dept['DepartmentName'] . ' - ' . $dept['CenterName'] . 
                              ($dept['DepartmentCode'] ? ' (' . $dept['DepartmentCode'] . ')' : '')
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
        
        // Format for display
        if (preg_match('/^09\d{9}$/', $phone)) {
            return substr($phone, 0, 4) . ' ' . substr($phone, 4, 3) . ' ' . substr($phone, 7);
        } elseif (preg_match('/^02\d{9}$/', $phone)) {
            return substr($phone, 0, 3) . ' ' . substr($phone, 3, 4) . ' ' . substr($phone, 7);
        }
        
        return $phone;
    }
}
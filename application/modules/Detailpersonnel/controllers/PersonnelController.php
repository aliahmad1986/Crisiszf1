<?php
class Detailpersonnel_PersonnelController extends Zend_Controller_Action
{
    protected $_model;

    public function init()
    {
        $this->_model = new Detailpersonnel_Model_PersonnelDetails();
     
    }

    public function indexAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_ajaxGetPersonnelDetails();
        } else {
            $this->view->headTitle('Personnel Department Assignments');
        }
    }
private function _ajaxGetPersonnelDetails()
{
    $this->_helper->viewRenderer->setNoRender(true);
    $this->_helper->layout->disableLayout();

    $details = $this->_model->getAllPersonnel();

    $data = array();
    foreach ($details as $detail) {
        $statusBadge = '';
        if ($detail['IsCurrent']) {
            $statusBadge = '<span class="badge badge-success">Current</span>';
        } else if (!empty($detail['ToDate']) && $detail['ToDate'] < date('Y-m-d')) {
            $statusBadge = '<span class="badge badge-secondary">Past</span>';
        } else if (!empty($detail['ToDate']) && $detail['ToDate'] > date('Y-m-d')) {
            $statusBadge = '<span class="badge badge-info">Future</span>';
        }
        
        $data[] = array(
            'DetailID' => $detail['DetailID'],
            'PersonnelName' => $detail['PersonnelName'] ?? 'Unknown',
            'DepartmentName' => $detail['DepartmentName'] ?? '',
            'PositionName' => $detail['PositionName'] ?? '',
            'FromDate' => $detail['FromDate'],
            'ToDate' => $detail['ToDate'] ?? '<em>Present</em>',
            'Status' => $statusBadge,
            'IsActive' => $detail['IsActive'] ? 
                '<span class="badge badge-success">Active</span>' : 
                '<span class="badge badge-danger">Inactive</span>',
            'Description' => $detail['Description'] ?? '',
            'CreatedAt' => isset($detail['CreatedAt']) ? date('Y-m-d H:i', strtotime($detail['CreatedAt'])) : '',
            'CreatedByName' => $detail['CreatedByName'] ?? '',
            'Actions' => '
                <button class="btn btn-sm btn-primary edit-personnel" data-id="' . $detail['DetailID'] . '">
                    <i class="fa fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger delete-personnel" data-id="' . $detail['DetailID'] . '">
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
}

    public function addPersonnelAction()
    {
		   $this->_helper->layout->disableLayout();
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);

            $data = $this->getRequest()->getPost();

            try {
                $id = $this->_model->addPersonnelDetail($data);
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Personnel assignment added successfully!',
                    'id' => $id
                ));
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form in modal with dropdown data
            $this->view->personnelList = $this->_model->getAllPersonnel();
            $this->view->departments = $this->_model->getDepartments();
            $this->view->positions = $this->_model->getPositions();
            $this->view->headTitle('Add New Personnel Assignment');
        }
    }

    public function editPersonnelAction()
    {
		   $this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam('id');

        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);

            $data = $this->getRequest()->getPost();

            try {
                $this->_model->updatePersonnelDetail($id, $data);
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Personnel assignment updated successfully!'
                ));
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        } else {
            // Show form in modal with dropdown data
            if ($id) {
                $this->view->personnelDetail = $this->_model->getPersonnelDetail($id);
            }
            $this->view->personnelList = $this->_model->getAllPersonnel();
            $this->view->departments = $this->_model->getDepartments();
            $this->view->positions = $this->_model->getPositions();
            $this->view->headTitle('Edit Personnel Assignment');
        }
    }

    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        if ($this->getRequest()->isPost()) {
            $id = $this->getRequest()->getPost('id');

            try {
                $this->_model->deletePersonnelDetail($id);
                echo json_encode(array(
                    'success' => true,
                    'message' => 'Personnel assignment deleted successfully!'
                ));
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage()
                ));
            }
        }
    }

    // New action to view personnel assignment history
    public function historyAction()
    {
		   $this->_helper->layout->disableLayout();
        $personnelId = $this->getRequest()->getParam('personnel_id');
        
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_helper->viewRenderer->setNoRender(true);
            
            if ($personnelId) {
                $history = $this->_model->getPersonnelHistory($personnelId);
                echo json_encode(array(
                    'success' => true,
                    'data' => $history
                ));
            } else {
                echo json_encode(array(
                    'success' => false,
                    'message' => 'Personnel ID is required'
                ));
            }
        }
    }
}
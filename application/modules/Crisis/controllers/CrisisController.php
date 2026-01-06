<?php
class Crisis_CrisisController extends Zend_Controller_Action
{
    protected $_model;
    protected $_translate;

    public function init()
    {
        $this->_model = new Crisis_Model_Crisis();
        $this->_translate = Zend_Registry::get('Zend_Translate');
        
    }

    private function _ajaxGetCrises()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        $crises = $this->_model->getAllCrises();

        $data = array();
        foreach ($crises as $crisis) {
            $data[] = array(
                'CrisisID' => $crisis['CrisisID'],
                'CrisisCode' => $crisis['CrisisCode'],
                'CrisisTitle' => $crisis['CrisisTitle'],
                'CrisisTypeName' => $crisis['CrisisTypeName'] ?? '',
                'IsActive' => $crisis['IsActive'] ? 
                    '<span class="badge badge-success">' . $this->_translate->_('Active') . '</span>' : 
                    '<span class="badge badge-danger">' . $this->_translate->_('Inactive') . '</span>',
                'CreatedAt' => date('Y-m-d H:i', strtotime($crisis['CreatedAt'])),
                'CreatedByName' => $crisis['CreatedByName'],
                'Actions' => '
                    <button class="btn btn-sm btn-primary edit-crisis" data-id="' . $crisis['CrisisID'] . '">
                        <i class="fa fa-edit"></i> ' . $this->_translate->_('Edit') . '
                    </button>
                    <button class="btn btn-sm btn-danger delete-crisis" data-id="' . $crisis['CrisisID'] . '">
                        <i class="fa fa-trash"></i> ' . $this->_translate->_('Delete') . '
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

    public function addCrisisAction()
    {
		$this->_helper->layout->disableLayout();
        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);

            $data = $this->getRequest()->getPost();

            try {
                $id = $this->_model->addCrisis($data);
                echo json_encode(array(
                    'success' => true,
                    'message' => $this->_translate->_('Crisis added successfully!'),
                    'id' => $id
                ));
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => $this->_translate->_('Error: ') . $e->getMessage()
                ));
            }
        } else {
            // Show form in modal
            $this->view->crisisTypes = $this->_model->getCrisisTypes();
            $this->view->translate = $this->_translate;
            $this->view->headTitle($this->_translate->_('Add New Crisis'));
        }
    }

    public function editCrisisAction()
    {
		$this->_helper->layout->disableLayout();
        $id = $this->getRequest()->getParam('id');
        $this->view->translate = $this->_translate;

        if ($this->getRequest()->isPost()) {
            $this->_helper->viewRenderer->setNoRender(true);

            $data = $this->getRequest()->getPost();

            try {
                $this->_model->updateCrisis($id, $data);
                echo json_encode(array(
                    'success' => true,
                    'message' => $this->_translate->_('Crisis updated successfully!')
                ));
				exit;
				// $this->_redirect('/Crisis/crisis/index');
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => $this->_translate->_('Error: ') . $e->getMessage()
                ));
            }
        } else {
            // Show form in modal
            if ($id) {
                $this->view->crisis = $this->_model->getCrisis($id);
            }
            $this->view->crisisTypes = $this->_model->getCrisisTypes();
            $this->view->headTitle($this->_translate->_('Edit Crisis'));
        }
    }

    public function deleteAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();

        if ($this->getRequest()->isPost()) {
            $id = $this->getRequest()->getPost('id');
            $translate = Zend_Registry::get('Zend_Translate');

            try {
                $this->_model->deleteCrisis($id);
                echo json_encode(array(
                    'success' => true,
                    'message' => $translate->_('Crisis deleted successfully!')
                ));
            } catch (Exception $e) {
                echo json_encode(array(
                    'success' => false,
                    'message' => $translate->_('Error: ') . $e->getMessage()
                ));
            }
        }
    }

    public function indexAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_ajaxGetCrises();
        } else {
            $this->view->translate = Zend_Registry::get('Zend_Translate');
            $this->view->headTitle($this->view->translate->_('Crisis Management'));
        }
    }
}
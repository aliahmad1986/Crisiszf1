<?php
class Center_CenterController extends Zend_Controller_Action
{
    protected $_model;
    
    public function init()
    {
        $this->_model = new Center_Model_Centers();
        $this->_helper->layout->disableLayout();
    }
    
    /**
     * Main action - shows centers list with server-side processing
     */
    public function indexAction()
    {
        // Handle AJAX request for DataTable with server-side processing
        if ($this->getRequest()->isXmlHttpRequest()) {
            $this->_ajaxGetCenters();
        } else {
            // Show the main page
            $this->view->headTitle('Centers Management - SQL Server');
            
            // Get statistics
            $stats = $this->_model->getCentersStatistics();
            $this->view->stats = $stats;
            
            // Get centers by type for chart
            $centersByType = $this->_model->getCentersByType();
            $this->view->centersByType = $centersByType;
            
            // Get center types for filter
            $centerTypes = $this->_model->getCenterTypes();
            $this->view->centerTypes = $centerTypes;
        }
    }
    
    /**
     * AJAX: Get centers for DataTable with server-side processing
     */
    private function _ajaxGetCenters()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            // Get DataTable parameters
            $draw = (int)$this->getRequest()->getParam('draw', 1);
            $start = (int)$this->getRequest()->getParam('start', 0);
            $length = (int)$this->getRequest()->getParam('length', 10);
            $search = $this->getRequest()->getParam('search', array('value' => ''));
            $order = $this->getRequest()->getParam('order', array(array('column' => 0, 'dir' => 'desc')));
            
            // Calculate page
            $page = ($start / $length) + 1;
            
            // Prepare filters
            $filters = array();
            
            // Search filter
            if (!empty($search['value'])) {
                $filters['search'] = $search['value'];
            }
            
            // Type filter
            $typeFilter = $this->getRequest()->getParam('filter_type', '');
            if ($typeFilter !== '') {
                $filters['CenterTypeID'] = (int)$typeFilter;
            }
            
            // Status filter
            $statusFilter = $this->getRequest()->getParam('filter_status', '');
            if ($statusFilter !== '') {
                $filters['IsActive'] = (int)$statusFilter;
            }
            
            // Get data with pagination
            $centers = $this->_model->getAllCenters($page, $length, $filters);
            $total = $this->_model->getTotalCenters();
            $filtered = $this->_model->getTotalCenters($filters);
            
            $data = array();
            foreach ($centers as $center) {
                $data[] = array(
                    'CenterID' => $center['CenterID'],
                    'CenterName' => htmlspecialchars($center['CenterName'], ENT_QUOTES, 'UTF-8'),
                    'CenterTypeName' => htmlspecialchars($center['CenterTypeName'] ?? 'N/A', ENT_QUOTES, 'UTF-8'),
                    'Address' => htmlspecialchars($center['Address'] ?? '', ENT_QUOTES, 'UTF-8'),
                    'Phone1' => $this->_formatPhone($center['Phone1']),
                    'Website' => $center['Website'] ? 
                        '<a href="' . htmlspecialchars($center['Website'], ENT_QUOTES, 'UTF-8') . 
                        '" target="_blank" class="btn btn-sm btn-outline-info">' . 
                        '<i class="fa fa-external-link-alt"></i></a>' : 'N/A',
                    'IsActive' => $center['IsActive'] ? 
                        '<span class="badge badge-success">Active</span>' : 
                        '<span class="badge badge-danger">Inactive</span>',
                    'CreatedAt' => date('Y-m-d H:i', strtotime($center['CreatedAt'])),
                    'Actions' => '
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-primary edit-center" 
                                    data-id="' . $center['CenterID'] . '" title="Edit">
                                <i class="fa fa-edit"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-danger delete-center" 
                                    data-id="' . $center['CenterID'] . '" title="Delete">
                                <i class="fa fa-trash"></i>
                            </button>
                        </div>'
                );
            }
            
            $response = array(
                'draw' => $draw,
                'recordsTotal' => $total,
                'recordsFiltered' => $filtered,
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
     * Export centers to CSV
     */
    public function exportAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            $filters = array();
            $typeFilter = $this->getRequest()->getParam('type', '');
            $statusFilter = $this->getRequest()->getParam('status', '');
            
            if ($typeFilter !== '') {
                $filters['CenterTypeID'] = (int)$typeFilter;
            }
            if ($statusFilter !== '') {
                $filters['IsActive'] = (int)$statusFilter;
            }
            
            $centers = $this->_model->exportCenters($filters);
            
            // Set headers for CSV download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=centers_' . date('Y-m-d_H-i') . '.csv');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fwrite($output, "\xEF\xBB\xBF");
            
            // Write header
            fputcsv($output, array(
                'ID', 'Center Name', 'Type', 'Address', 'Phone 1', 'Phone 2', 
                'Website', 'Status', 'Created At', 'Created By'
            ));
            
            // Write data
            foreach ($centers as $center) {
                fputcsv($output, $center);
            }
            
            fclose($output);
            
        } catch (Exception $e) {
            echo "Error exporting centers: " . $e->getMessage();
        }
    }
    
    /**
     * Bulk update action
     */
    public function bulkUpdateAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        if ($this->getRequest()->isPost()) {
            $centerIds = $this->getRequest()->getPost('centerIds', array());
            $status = $this->getRequest()->getPost('status', 1);
            
            try {
                $affected = $this->_model->bulkUpdateStatus($centerIds, $status);
                
                echo json_encode(array(
                    'success' => true,
                    'message' => "Updated {$affected} centers successfully!",
                    'affected' => $affected
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
     * Get centers statistics for dashboard
     */
    public function statisticsAction()
    {
        $this->_helper->viewRenderer->setNoRender(true);
        $this->_helper->layout->disableLayout();
        
        try {
            $stats = $this->_model->getCentersStatistics();
            $byType = $this->_model->getCentersByType();
            
            echo json_encode(array(
                'success' => true,
                'stats' => $stats,
                'byType' => $byType
            ));
            
        } catch (Exception $e) {
            echo json_encode(array(
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ));
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
        
        // Format Iranian phone numbers
        if (preg_match('/^\+98(\d{10})$/', $phone, $matches)) {
            return '0' . $matches[1];
        }
        
        return $phone;
    }
    
    // ... Rest of the controller methods (addCenter, editCenter, delete, etc.)
    // ... remain the same as previous version
}
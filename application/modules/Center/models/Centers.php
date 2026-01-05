<?php
class Center_Model_Centers
{
    protected $_db;
    protected $_tableName = 'org.tbl_Centers';
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
        
        if (!$this->_db) {
            throw new Exception('Database adapter not found in registry');
        }
    }
    
    /**
     * Get all centers (identical to Users::getAllUsers pattern)
     */
    public function getAllCenters()
    {
        try {
            $sql = "
                SELECT 
                    c.*,
                    u1.FullName as CreatedByName,
                    u2.FullName as UpdatedByName,
                    ct.CenterTypeName
                FROM {$this->_tableName} c
                LEFT JOIN auth.tbl_Users u1 ON c.CreatedBy = u1.UserID
                LEFT JOIN auth.tbl_Users u2 ON c.UpdatedBy = u2.UserID
                LEFT JOIN org.tbl_CenterTypes ct ON c.CenterTypeID = ct.CenterTypeID
                ORDER BY c.CreatedAt DESC
            ";
            
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching centers: " . $e->getMessage());
        }
    }
    
    /**
     * Get single center by ID (identical to Users::getUser pattern)
     */
    public function getCenter($id)
    {
        $id = (int)$id;
        
        try {
            $sql = "SELECT * FROM {$this->_tableName} WHERE CenterID = ?";
            $stmt = $this->_db->query($sql, array($id));
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("Center with ID {$id} not found");
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception("Error fetching center: " . $e->getMessage());
        }
    }
    
    /**
     * Add new center (identical to Users::addUser pattern)
     */
    public function addCenter($data)
    {
        try {
            // Prepare data
            $centerData = array(
                'CenterName' => trim($data['CenterName']),
                'CenterTypeID' => (int)$data['CenterTypeID'],
                'Address' => isset($data['Address']) ? trim($data['Address']) : null,
                'Phone1' => isset($data['Phone1']) ? $this->formatPhoneForStorage($data['Phone1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->formatPhoneForStorage($data['Phone2']) : null,
                'Website' => isset($data['Website']) ? trim($data['Website']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'CreatedBy' => isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : -1
            );
            
            // Validate phone numbers
            if (!empty($centerData['Phone1']) && !$this->validatePhoneNumber($centerData['Phone1'])) {
                throw new Exception("Invalid primary phone number format");
            }
            
            if (!empty($centerData['Phone2']) && !$this->validatePhoneNumber($centerData['Phone2'])) {
                throw new Exception("Invalid secondary phone number format");
            }
            
            // Check if center name exists
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE CenterName = ?";
            $checkStmt = $this->_db->query($checkSql, array($centerData['CenterName']));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Center name already exists");
            }
            
            $this->_db->insert($this->_tableName, $centerData);
            return $this->_db->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Error adding center: " . $e->getMessage());
        }
    }
    
    /**
     * Update existing center with phone validation
     */
    public function updateCenter($id, $data)
    {
        $id = (int)$id;
        
        try {
            // Prepare data
            $centerData = array(
                'CenterName' => trim($data['CenterName']),
                'CenterTypeID' => (int)$data['CenterTypeID'],
                'Address' => isset($data['Address']) ? trim($data['Address']) : null,
                'Phone1' => isset($data['Phone1']) ? $this->formatPhoneForStorage($data['Phone1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->formatPhoneForStorage($data['Phone2']) : null,
                'Website' => isset($data['Website']) ? trim($data['Website']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'UpdatedAt' => date('Y-m-d H:i:s'),
                'UpdatedBy' => isset($data['UpdatedBy']) ? (int)$data['UpdatedBy'] : -1
            );
            
            // Validate phone numbers
            if (!empty($centerData['Phone1']) && !$this->validatePhoneNumber($centerData['Phone1'])) {
                throw new Exception("Invalid primary phone number format");
            }
            
            if (!empty($centerData['Phone2']) && !$this->validatePhoneNumber($centerData['Phone2'])) {
                throw new Exception("Invalid secondary phone number format");
            }
            
            // Check if center name exists (excluding current center)
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                        WHERE CenterName = ? AND CenterID != ?";
            $checkStmt = $this->_db->query($checkSql, array($centerData['CenterName'], $id));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Center name already exists");
            }
            
            $where = $this->_db->quoteInto('CenterID = ?', $id);
            $this->_db->update($this->_tableName, $centerData, $where);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error updating center: " . $e->getMessage());
        }
    }
    
    /**
     * Format phone number for storage
     */
    private function formatPhoneForStorage($phone)
    {
        if (empty($phone)) {
            return null;
        }
        
        // Remove all non-digit characters except plus sign
        $phone = preg_replace('/[^\d+]/', '', $phone);
        
        // If it starts with 0, convert to international format
        if (strlen($phone) == 11 && $phone[0] == '0') {
            $phone = '+98' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    /**
     * Validate phone number format
     */
    private function validatePhoneNumber($phone)
    {
        if (empty($phone)) {
            return true;
        }
        
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phone);
        
        // Check length
        if (strlen($digits) < 10 || strlen($digits) > 15) {
            return false;
        }
        
        // Check if it's a valid Iranian number
        if (preg_match('/^(98)?9\d{9}$/', $digits)) {
            return true; // Iranian mobile
        }
        
        if (preg_match('/^(98)?2\d{9}$/', $digits)) {
            return true; // Iranian landline
        }
        
        // Check if it's a valid international number
        if (preg_match('/^\d{10,15}$/', $digits)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete center (identical to Users::deleteUser pattern)
     */
    public function deleteCenter($id)
    {
        $id = (int)$id;
        
        try {
            // Check if center exists - same pattern as users
            $center = $this->getCenter($id);
            if (!$center) {
                throw new Exception("Center not found");
            }
            
            $where = $this->_db->quoteInto('CenterID = ?', $id);
            $this->_db->delete($this->_tableName, $where);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error deleting center: " . $e->getMessage());
        }
    }
    
    /**
     * Get center types for dropdown (similar to getting roles in users module)
     */
    public function getCenterTypes()
    {
        try {
            // Check if table exists
            $sql = "SELECT CenterTypeID, CenterTypeName FROM org.tbl_CenterTypes WHERE IsActive = 1 ORDER BY CenterTypeName";
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            // If table doesn't exist, return default types
            return array(
                array('CenterTypeID' => 1, 'CenterTypeName' => 'Hospital'),
                array('CenterTypeID' => 2, 'CenterTypeName' => 'Clinic'),
                array('CenterTypeID' => 3, 'CenterTypeName' => 'Laboratory'),
                array('CenterTypeID' => 4, 'CenterTypeName' => 'Pharmacy'),
            );
        }
    }
    
    /**
     * Get centers count by status (identical to Users::getUsersCount pattern)
     */
    public function getCentersCount()
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) as inactive
                FROM {$this->_tableName}
            ";
            
            $stmt = $this->_db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error getting centers count: " . $e->getMessage());
        }
    }
}
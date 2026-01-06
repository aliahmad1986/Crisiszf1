<?php
class Department_Model_Departments
{
    protected $_db;
    protected $_tableName = 'org.tbl_Departments';
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
        
        if (!$this->_db) {
            throw new Exception('Database adapter not found in registry');
        }
    }
    
    /**
     * Get all departments with center and type information
     */
    public function getAllDepartments()
    {
        try {
            $sql = "
                SELECT 
                    d.*,
                    c.CenterName,
                    dt.DepartmentTypeName,
                    u1.FullName as CreatedByName,
                    u2.FullName as UpdatedByName
                FROM {$this->_tableName} d
                LEFT JOIN org.tbl_Centers c ON d.CenterID = c.CenterID
                LEFT JOIN org.tbl_DepartmentTypes dt ON d.DepartmentTypeID = dt.DepartmentTypeID
                LEFT JOIN auth.tbl_Users u1 ON d.CreatedBy = u1.UserID
                LEFT JOIN auth.tbl_Users u2 ON d.UpdatedBy = u2.UserID
                ORDER BY c.CenterName, d.DepartmentName
            ";
            
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching departments: " . $e->getMessage());
        }
    }
    
    /**
     * Get departments by center ID
     */
    public function getDepartmentsByCenter($centerId)
    {
        $centerId = (int)$centerId;
        
        try {
            $sql = "
                SELECT d.*, dt.DepartmentTypeName
                FROM {$this->_tableName} d
                LEFT JOIN org.tbl_DepartmentTypes dt ON d.DepartmentTypeID = dt.DepartmentTypeID
                WHERE d.CenterID = ? AND d.IsActive = 1
                ORDER BY d.DepartmentName
            ";
            
            $stmt = $this->_db->query($sql, array($centerId));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching departments by center: " . $e->getMessage());
        }
    }
    
    /**
     * Get single department by ID
     */
    public function getDepartment($id)
    {
        $id = (int)$id;
        
        try {
            $sql = "SELECT * FROM {$this->_tableName} WHERE DepartmentID = ?";
            $stmt = $this->_db->query($sql, array($id));
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("Department with ID {$id} not found");
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception("Error fetching department: " . $e->getMessage());
        }
    }
    
    /**
     * Get all active centers for dropdown
     */
    public function getActiveCenters()
    {
        try {
            $sql = "SELECT CenterID, CenterName FROM org.tbl_Centers WHERE IsActive = 1 ORDER BY CenterName";
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching centers: " . $e->getMessage());
        }
    }
    
    /**
     * Get department types
     */
    public function getDepartmentTypes()
    {
        try {
            // First check if table exists
            $sql = "SELECT DepartmentTypeID, DepartmentTypeName FROM org.tbl_DepartmentTypes WHERE IsActive = 1 ORDER BY DepartmentTypeName";
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            // If table doesn't exist, return default types
            return array(
                array('DepartmentTypeID' => 1, 'DepartmentTypeName' => 'Internal Unit'),
                array('DepartmentTypeID' => 2, 'DepartmentTypeName' => 'External Organization'),
                array('DepartmentTypeID' => 3, 'DepartmentTypeName' => 'Management'),
                array('DepartmentTypeID' => 4, 'DepartmentTypeName' => 'Technical'),
                array('DepartmentTypeID' => 5, 'DepartmentTypeName' => 'Administrative'),
            );
        }
    }
    
    /**
     * Add new department
     */
    public function addDepartment($data)
    {
        try {
            // Prepare data
            $departmentData = array(
                'CenterID' => (int)$data['CenterID'],
                'DepartmentTypeID' => (int)$data['DepartmentTypeID'],
                'DepartmentCode' => isset($data['DepartmentCode']) ? trim($data['DepartmentCode']) : null,
                'DepartmentName' => trim($data['DepartmentName']),
                'Phone1' => isset($data['Phone1']) ? $this->validatePhone($data['Phone1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->validatePhone($data['Phone2']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'CreatedBy' => isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : -1
            );
            
            // Check if department code is unique (if provided)
            if (!empty($departmentData['DepartmentCode'])) {
                $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE DepartmentCode = ?";
                $checkStmt = $this->_db->query($checkSql, array($departmentData['DepartmentCode']));
                $checkResult = $checkStmt->fetch();
                
                if ($checkResult['count'] > 0) {
                    throw new Exception("Department Code already exists");
                }
            }
            
            // Check if department name is unique within the same center
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                        WHERE CenterID = ? AND DepartmentName = ?";
            $checkStmt = $this->_db->query($checkSql, array(
                $departmentData['CenterID'], 
                $departmentData['DepartmentName']
            ));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Department name already exists in this center");
            }
            
            $this->_db->insert($this->_tableName, $departmentData);
            return $this->_db->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Error adding department: " . $e->getMessage());
        }
    }
    
    /**
     * Update existing department
     */
    public function updateDepartment($id, $data)
    {
        $id = (int)$id;
        
        try {
            // Prepare data
            $departmentData = array(
                'CenterID' => (int)$data['CenterID'],
                'DepartmentTypeID' => (int)$data['DepartmentTypeID'],
                'DepartmentCode' => isset($data['DepartmentCode']) ? trim($data['DepartmentCode']) : null,
                'DepartmentName' => trim($data['DepartmentName']),
                'Phone1' => isset($data['Phone1']) ? $this->validatePhone($data['Phone1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->validatePhone($data['Phone2']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'UpdatedAt' => date('Y-m-d H:i:s'),
                'UpdatedBy' => isset($data['UpdatedBy']) ? (int)$data['UpdatedBy'] : -1
            );
            
            // Check if department code is unique (if provided, excluding current)
            if (!empty($departmentData['DepartmentCode'])) {
                $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                            WHERE DepartmentCode = ? AND DepartmentID != ?";
                $checkStmt = $this->_db->query($checkSql, array($departmentData['DepartmentCode'], $id));
                $checkResult = $checkStmt->fetch();
                
                if ($checkResult['count'] > 0) {
                    throw new Exception("Department Code already exists");
                }
            }
            
            // Check if department name is unique within the same center (excluding current)
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                        WHERE CenterID = ? AND DepartmentName = ? AND DepartmentID != ?";
            $checkStmt = $this->_db->query($checkSql, array(
                $departmentData['CenterID'], 
                $departmentData['DepartmentName'],
                $id
            ));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Department name already exists in this center");
            }
            
            $where = $this->_db->quoteInto('DepartmentID = ?', $id);
            $this->_db->update($this->_tableName, $departmentData, $where);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error updating department: " . $e->getMessage());
        }
    }
    
    /**
     * Delete department
     */
    public function deleteDepartment($id)
    {
        $id = (int)$id;
        
        try {
            // Check if department exists
            $department = $this->getDepartment($id);
            if (!$department) {
                throw new Exception("Department not found");
            }
            
            // Check if department has related records (add checks here if needed)
            // For example: if department has personnel assigned, etc.
            
            $where = $this->_db->quoteInto('DepartmentID = ?', $id);
            $this->_db->delete($this->_tableName, $where);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error deleting department: " . $e->getMessage());
        }
    }
    
    /**
     * Get departments count by center
     */
    public function getDepartmentsCountByCenter()
    {
        try {
            $sql = "
                SELECT 
                    c.CenterName,
                    COUNT(d.DepartmentID) as department_count,
                    SUM(CASE WHEN d.IsActive = 1 THEN 1 ELSE 0 END) as active_departments
                FROM org.tbl_Centers c
                LEFT JOIN {$this->_tableName} d ON c.CenterID = d.CenterID
                WHERE c.IsActive = 1
                GROUP BY c.CenterID, c.CenterName
                ORDER BY c.CenterName
            ";
            
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error getting departments count by center: " . $e->getMessage());
        }
    }
    
    /**
     * Get departments statistics
     */
    public function getDepartmentsStatistics()
    {
        try {
            $sql = "
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) as inactive,
                    COUNT(DISTINCT CenterID) as centers_count,
                    COUNT(DISTINCT DepartmentTypeID) as types_count
                FROM {$this->_tableName}
            ";
            
            $stmt = $this->_db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error getting departments statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Search departments
     */
    public function searchDepartments($term, $centerId = null)
    {
        try {
            $sql = "
                SELECT d.*, c.CenterName, dt.DepartmentTypeName
                FROM {$this->_tableName} d
                LEFT JOIN org.tbl_Centers c ON d.CenterID = c.CenterID
                LEFT JOIN org.tbl_DepartmentTypes dt ON d.DepartmentTypeID = dt.DepartmentTypeID
                WHERE (d.DepartmentName LIKE ? OR d.DepartmentCode LIKE ? OR c.CenterName LIKE ?)
            ";
            
            $params = array("%{$term}%", "%{$term}%", "%{$term}%");
            
            if ($centerId) {
                $sql .= " AND d.CenterID = ?";
                $params[] = $centerId;
            }
            
            $sql .= " ORDER BY c.CenterName, d.DepartmentName";
            
            $stmt = $this->_db->query($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error searching departments: " . $e->getMessage());
        }
    }
    
    /**
     * Validate phone number
     */
    private function validatePhone($phone)
    {
        if (empty($phone)) {
            return null;
        }
        
        $phone = trim($phone);
        
        // Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        // Check length
        if (strlen($digits) < 8 || strlen($digits) > 15) {
            throw new Exception("Phone number must be 8-15 digits");
        }
        
        return $digits;
    }
    
    /**
     * Check if department code exists
     */
    public function checkDepartmentCodeExists($departmentCode, $excludeId = 0)
    {
        if (empty($departmentCode)) {
            return false;
        }
        
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                    WHERE DepartmentCode = ? AND DepartmentID != ?";
            
            $stmt = $this->_db->query($sql, array($departmentCode, $excludeId));
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if department name exists in center
     */
    public function checkDepartmentNameInCenter($centerId, $departmentName, $excludeId = 0)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                    WHERE CenterID = ? AND DepartmentName = ? AND DepartmentID != ?";
            
            $stmt = $this->_db->query($sql, array($centerId, $departmentName, $excludeId));
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
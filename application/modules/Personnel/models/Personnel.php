<?php
class Personnel_Model_Personnel
{
    protected $_db;
    protected $_tableName = 'org.tbl_Personnel';
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
        
        if (!$this->_db) {
            throw new Exception('Database adapter not found in registry');
        }
    }
    
    /**
     * Get all personnel with creator information
     */
    public function getAllPersonnel()
    {
        try {
            $sql = "
                SELECT 
                    p.*,
                    u1.FullName as CreatedByName,
                    u2.FullName as UpdatedByName,
                    CONCAT(p.FirstName, ' ', p.LastName) as FullName
                FROM {$this->_tableName} p
                LEFT JOIN auth.tbl_Users u1 ON p.CreatedBy = u1.UserID
                LEFT JOIN auth.tbl_Users u2 ON p.UpdatedBy = u2.UserID
                ORDER BY p.CreatedAt DESC
            ";
            
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Get single personnel by ID
     */
    public function getPersonnel($id)
    {
        $id = (int)$id;
        
        try {
            $sql = "SELECT * FROM {$this->_tableName} WHERE PersonnelID = ?";
            $stmt = $this->_db->query($sql, array($id));
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("Personnel with ID {$id} not found");
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception("Error fetching personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Add new personnel with validation
     */
    public function addPersonnel($data)
    {
        try {
            // Validate and prepare data
            $personnelData = array(
                'NationalCode' => $this->validateNationalCode($data['NationalCode']),
                'FirstName' => $this->validateName($data['FirstName'], 'First Name'),
                'LastName' => $this->validateName($data['LastName'], 'Last Name'),
                'Mobile1' => isset($data['Mobile1']) ? $this->validateMobile($data['Mobile1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->validatePhone($data['Phone2']) : null,
                'PersonnelNumber' => isset($data['PersonnelNumber']) ? trim($data['PersonnelNumber']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'EndDate' => isset($data['EndDate']) ? $this->validateDate($data['EndDate']) : null,
                'Description' => isset($data['Description']) ? trim($data['Description']) : null,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'CreatedBy' => isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : -1
            );
            
            // Check if National Code exists
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE NationalCode = ?";
            $checkStmt = $this->_db->query($checkSql, array($personnelData['NationalCode']));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("National Code already exists");
            }
            
            // Check if Personnel Number exists (if provided)
            if (!empty($personnelData['PersonnelNumber'])) {
                $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE PersonnelNumber = ?";
                $checkStmt = $this->_db->query($checkSql, array($personnelData['PersonnelNumber']));
                $checkResult = $checkStmt->fetch();
                
                if ($checkResult['count'] > 0) {
                    throw new Exception("Personnel Number already exists");
                }
            }
            
            $this->_db->insert($this->_tableName, $personnelData);
            return $this->_db->lastInsertId();
            
        } catch (Exception $e) {
            throw new Exception("Error adding personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Update existing personnel with validation
     */
    public function updatePersonnel($id, $data)
    {
        $id = (int)$id;
        
        try {
            // Validate and prepare data
            $personnelData = array(
                'NationalCode' => $this->validateNationalCode($data['NationalCode']),
                'FirstName' => $this->validateName($data['FirstName'], 'First Name'),
                'LastName' => $this->validateName($data['LastName'], 'Last Name'),
                'Mobile1' => isset($data['Mobile1']) ? $this->validateMobile($data['Mobile1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->validatePhone($data['Phone2']) : null,
                'PersonnelNumber' => isset($data['PersonnelNumber']) ? trim($data['PersonnelNumber']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'EndDate' => isset($data['EndDate']) ? $this->validateDate($data['EndDate']) : null,
                'Description' => isset($data['Description']) ? trim($data['Description']) : null,
                'UpdatedAt' => date('Y-m-d H:i:s'),
                'UpdatedBy' => isset($data['UpdatedBy']) ? (int)$data['UpdatedBy'] : -1
            );
            
            // Check if National Code exists (excluding current personnel)
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                        WHERE NationalCode = ? AND PersonnelID != ?";
            $checkStmt = $this->_db->query($checkSql, array($personnelData['NationalCode'], $id));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("National Code already exists");
            }
            
            // Check if Personnel Number exists (excluding current personnel)
            if (!empty($personnelData['PersonnelNumber'])) {
                $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                            WHERE PersonnelNumber = ? AND PersonnelID != ?";
                $checkStmt = $this->_db->query($checkSql, array($personnelData['PersonnelNumber'], $id));
                $checkResult = $checkStmt->fetch();
                
                if ($checkResult['count'] > 0) {
                    throw new Exception("Personnel Number already exists");
                }
            }
            
            $where = $this->_db->quoteInto('PersonnelID = ?', $id);
            $this->_db->update($this->_tableName, $personnelData, $where);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error updating personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Delete personnel
     */
    public function deletePersonnel($id)
    {
        $id = (int)$id;
        
        try {
            // Check if personnel exists
            $personnel = $this->getPersonnel($id);
            if (!$personnel) {
                throw new Exception("Personnel not found");
            }
            
            $where = $this->_db->quoteInto('PersonnelID = ?', $id);
            $this->_db->delete($this->_tableName, $where);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error deleting personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Get personnel count by status
     */
    public function getPersonnelCount()
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
            throw new Exception("Error getting personnel count: " . $e->getMessage());
        }
    }
    
    /**
     * Search personnel by name or national code
     */
    public function searchPersonnel($term)
    {
        try {
            $sql = "
                SELECT PersonnelID, NationalCode, FirstName, LastName, PersonnelNumber 
                FROM {$this->_tableName} 
                WHERE FirstName LIKE ? OR LastName LIKE ? OR NationalCode LIKE ? OR PersonnelNumber LIKE ?
                ORDER BY FirstName, LastName
            ";
            
            $searchTerm = "%{$term}%";
            $stmt = $this->_db->query($sql, array($searchTerm, $searchTerm, $searchTerm, $searchTerm));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error searching personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Validate Iranian National Code (کد ملی)
     */
    private function validateNationalCode($code)
    {
        $code = trim($code);
        
        // Remove any non-digit characters
        $code = preg_replace('/[^0-9]/', '', $code);
        
        // Check length
        if (strlen($code) != 10) {
            throw new Exception("National Code must be 10 digits");
        }
        
        // Check for same digits (like 1111111111)
        if (preg_match('/^(\d)\1{9}$/', $code)) {
            throw new Exception("Invalid National Code");
        }
        
        // Validate Iranian National Code algorithm
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += (int)$code[$i] * (10 - $i);
        }
        
        $remainder = $sum % 11;
        $controlDigit = (int)$code[9];
        
        if (($remainder < 2 && $controlDigit == $remainder) || 
            ($remainder >= 2 && $controlDigit == (11 - $remainder))) {
            return $code;
        }
        
        throw new Exception("Invalid National Code");
    }
    
    /**
     * Validate name (First/Last Name)
     */
    private function validateName($name, $fieldName)
    {
        $name = trim($name);
        
        if (empty($name)) {
            throw new Exception("{$fieldName} is required");
        }
        
        if (strlen($name) > 100) {
            throw new Exception("{$fieldName} must be less than 100 characters");
        }
        
        // Allow Persian/Arabic characters, spaces, and dashes
        if (!preg_match('/^[\p{Arabic}\s\-]+$/u', $name)) {
            throw new Exception("{$fieldName} can only contain Persian/Arabic letters, spaces, and dashes");
        }
        
        return $name;
    }
    
    /**
     * Validate mobile number
     */
    private function validateMobile($mobile)
    {
        if (empty($mobile)) {
            return null;
        }
        
        $mobile = trim($mobile);
        
        // Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $mobile);
        
        // Check if it's an Iranian mobile number
        if (preg_match('/^(09|9|989|\+989)\d{9}$/', $digits)) {
            // Format to 0912XXXXXXX
            if (strlen($digits) == 10 && $digits[0] == '9') {
                return '0' . $digits;
            } elseif (strlen($digits) == 11 && substr($digits, 0, 2) == '98') {
                return '0' . substr($digits, 2);
            } elseif (strlen($digits) == 12 && substr($digits, 0, 3) == '989') {
                return '0' . substr($digits, 3);
            }
            return $digits;
        }
        
        throw new Exception("Invalid mobile number format. Use 0912XXXXXXX or +98912XXXXXXX");
    }
    
    /**
     * Validate phone number (landline)
     */
    private function validatePhone($phone)
    {
        if (empty($phone)) {
            return null;
        }
        
        $phone = trim($phone);
        
        // Remove all non-digit characters
        $digits = preg_replace('/[^0-9]/', '', $phone);
        
        // Check if it's an Iranian landline number
        if (preg_match('/^(0|98|\+98)?2\d{9}$/', $digits)) {
            // Format to 021XXXXXXX
            if (strlen($digits) == 10 && $digits[0] == '2') {
                return '0' . $digits;
            } elseif (strlen($digits) == 11 && substr($digits, 0, 2) == '98') {
                return '0' . substr($digits, 2);
            } elseif (strlen($digits) == 12 && substr($digits, 0, 3) == '982') {
                return '0' . substr($digits, 3);
            }
            return $digits;
        }
        
        // Generic phone validation (8-15 digits)
        if (preg_match('/^\d{8,15}$/', $digits)) {
            return $digits;
        }
        
        throw new Exception("Invalid phone number format");
    }
    
    /**
     * Validate date format
     */
    private function validateDate($date)
    {
        if (empty($date)) {
            return null;
        }
        
        // Check if it's a valid date (YYYY-MM-DD format)
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $dateParts = explode('-', $date);
            if (checkdate($dateParts[1], $dateParts[2], $dateParts[0])) {
                return $date;
            }
        }
        
        // Check if it's in Jalali (Persian) date format (YYYY/MM/DD)
        if (preg_match('/^\d{4}\/\d{2}\/\d{2}$/', $date)) {
            return $date; // Store as string for Jalali dates
        }
        
        throw new Exception("Invalid date format. Use YYYY-MM-DD or YYYY/MM/DD");
    }
    
    /**
     * Check if National Code exists
     */
    public function checkNationalCodeExists($nationalCode, $excludeId = 0)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                    WHERE NationalCode = ? AND PersonnelID != ?";
            
            $stmt = $this->_db->query($sql, array($nationalCode, $excludeId));
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if Personnel Number exists
     */
    public function checkPersonnelNumberExists($personnelNumber, $excludeId = 0)
    {
        if (empty($personnelNumber)) {
            return false;
        }
        
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                    WHERE PersonnelNumber = ? AND PersonnelID != ?";
            
            $stmt = $this->_db->query($sql, array($personnelNumber, $excludeId));
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
}
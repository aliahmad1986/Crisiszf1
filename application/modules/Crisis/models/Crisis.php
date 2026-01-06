<?php
class Crisis_Model_Crisis
{
    protected $_db;
    protected $_translate;

    public function __construct()
    {
        // Get database adapter from Zend_Registry
        $this->_db = Zend_Registry::get('db');
        
        // Alternative: If not in registry, get from default adapter
        if (!$this->_db) {
            $this->_db = Zend_Db_Table_Abstract::getDefaultAdapter();
        }
        
        // Get translator from registry
        $this->_translate = Zend_Registry::get('Zend_Translate');
        
        // If translator not found, create a dummy one
        if (!$this->_translate) {
            $this->_translate = new Zend_Translate('array', array());
        }
    }

    public function getAllCrises()
    {
        $sql = "
            SELECT 
                c.*,
                ct.Name as CrisisTypeName,
                uc.FullName as CreatedByName,
                uu.FullName as UpdatedByName
            FROM Crs.tbl_Crisis c
            LEFT JOIN org.tbl_General_Base_Info ct ON c.CrisisTypeID = ct.BaseInfoID 
                AND ct.TableName = 'CrisisType'
            LEFT JOIN auth.tbl_Users uc ON c.CreatedBy = uc.UserID
            LEFT JOIN auth.tbl_Users uu ON c.UpdatedBy = uu.UserID
            ORDER BY c.CreatedAt DESC
        ";

        return $this->_db->fetchAll($sql);
    }

    public function getCrisis($id)
    {
        $id = (int)$id;
        $sql = "SELECT * FROM Crs.tbl_Crisis WHERE CrisisID = ?";
        $result = $this->_db->fetchRow($sql, $id);
        
        if (!$result) {
            throw new Exception($this->_translate->_('Crisis not found with ID: ') . $id);
        }
        
        return $result;
    }

    public function addCrisis($data)
    {
        // Set default values
        $data['CreatedAt'] = date('Y-m-d H:i:s');
        $data['IsActive'] = isset($data['IsActive']) ? 1 : 0;

        // Validate required fields
        $required = [
            'CrisisCode' => $this->_translate->_('Crisis Code'),
            'CrisisTitle' => $this->_translate->_('Crisis Title'),
            'CrisisTypeID' => $this->_translate->_('Crisis Type')
        ];
        
        foreach ($required as $field => $fieldName) {
            if (empty($data[$field])) {
                throw new Exception($fieldName . ' ' . $this->_translate->_('is required'));
            }
        }

        // Check for duplicate CrisisCode
        $sql = "SELECT COUNT(*) FROM Crs.tbl_Crisis WHERE CrisisCode = ?";
        $exists = $this->_db->fetchOne($sql, $data['CrisisCode']);
        
        if ($exists > 0) {
            throw new Exception($this->_translate->_('Crisis Code already exists'));
        }

        // Validate CrisisTypeID exists
        $sql = "SELECT COUNT(*) FROM org.tbl_General_Base_Info WHERE BaseInfoID = ? AND TableName = 'CrisisType' AND IsActive = 1";
        $typeExists = $this->_db->fetchOne($sql, $data['CrisisTypeID']);
        
        if (!$typeExists) {
            throw new Exception($this->_translate->_('Invalid Crisis Type selected'));
        }

        // Get valid user ID for CreatedBy
        $data['CreatedBy'] = $this->getValidUserId();

        // Prepare columns and values
        $columns = array_keys($data);
        $values = array_values($data);
        
        $placeholders = array_fill(0, count($columns), '?');
        
        $sql = "INSERT INTO Crs.tbl_Crisis (" . implode(', ', $columns) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        try {
            $this->_db->query($sql, $values);
            return $this->_db->lastInsertId();
        } catch (Exception $e) {
            // Handle specific database errors
            if (strpos($e->getMessage(), 'FOREIGN KEY constraint') !== false) {
                if (strpos($e->getMessage(), 'CreatedBy') !== false) {
                    throw new Exception($this->_translate->_('Invalid user account. Please contact administrator.'));
                } elseif (strpos($e->getMessage(), 'CrisisTypeID') !== false) {
                    throw new Exception($this->_translate->_('Invalid crisis type selected'));
                }
            }
            
            error_log("Database error: " . $e->getMessage());
            throw new Exception($this->_translate->_('Failed to save crisis. Please try again.'));
        }
    }

    public function updateCrisis($id, $data)
    {
        $id = (int)$id;
        
        // Get existing record
        $existing = $this->getCrisis($id);
        
        // Set update timestamp
        $data['UpdatedAt'] = date('Y-m-d H:i:s');
        $data['IsActive'] = isset($data['IsActive']) ? 1 : 0;

        // Check for duplicate CrisisCode (excluding current)
        if (isset($data['CrisisCode']) && $data['CrisisCode'] != $existing['CrisisCode']) {
            $sql = "SELECT COUNT(*) FROM Crs.tbl_Crisis WHERE CrisisCode = ? AND CrisisID != ?";
            $exists = $this->_db->fetchOne($sql, [$data['CrisisCode'], $id]);
            
            if ($exists > 0) {
                throw new Exception($this->_translate->_('Crisis Code already exists'));
            }
        }

        // Validate CrisisTypeID if provided
        if (isset($data['CrisisTypeID']) && $data['CrisisTypeID'] != $existing['CrisisTypeID']) {
            $sql = "SELECT COUNT(*) FROM org.tbl_General_Base_Info WHERE BaseInfoID = ? AND TableName = 'CrisisType' AND IsActive = 1";
            $typeExists = $this->_db->fetchOne($sql, $data['CrisisTypeID']);
            
            if (!$typeExists) {
                throw new Exception($this->_translate->_('Invalid Crisis Type selected'));
            }
        }

        // Get valid user ID for UpdatedBy
        $data['UpdatedBy'] = $this->getValidUserId();

        // Prepare SET clause
        $set = [];
        $values = [];
        unset($data['CrisisID']);
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
            $values[] = $value;
        }
        
        $values[] = $id; // For WHERE clause
        
        $sql = "UPDATE Crs.tbl_Crisis 
                SET " . implode(', ', $set) . "
                WHERE CrisisID = ?";
        
        try {
            $this->_db->query($sql, $values);
        } catch (Exception $e) {
            // Handle specific database errors
            if (strpos($e->getMessage(), 'FOREIGN KEY constraint') !== false) {
                if (strpos($e->getMessage(), 'UpdatedBy') !== false) {
                    throw new Exception($this->_translate->_('Invalid user account. Please contact administrator.'));
                } elseif (strpos($e->getMessage(), 'CrisisTypeID') !== false) {
                    throw new Exception($this->_translate->_('Invalid crisis type selected'));
                }
            }
            
            error_log("Database error: " . $e->getMessage());
            throw new Exception("Database error: " . $e->getMessage().$this->_translate->_('Failed to update crisis. Please try again.'));
        }
    }

    public function deleteCrisis($id)
    {
        $id = (int)$id;
        
        // First check if crisis exists
        try {
            $this->getCrisis($id);
        } catch (Exception $e) {
            throw new Exception($this->_translate->_('Crisis not found'));
        }
        
        // Check if crisis has related records (optional - add if you have foreign key constraints)
        // $sql = "SELECT COUNT(*) FROM related_table WHERE CrisisID = ?";
        // $hasRelated = $this->_db->fetchOne($sql, $id);
        // if ($hasRelated > 0) {
        //     throw new Exception($this->_translate->_('Cannot delete crisis because it has related records'));
        // }
        
        $sql = "DELETE FROM Crs.tbl_Crisis WHERE CrisisID = ?";
        
        try {
            $this->_db->query($sql, $id);
        } catch (Exception $e) {
            if (strpos($e->getMessage(), 'REFERENCE constraint') !== false) {
                throw new Exception($this->_translate->_('Cannot delete crisis because it is being used in other parts of the system'));
            }
            throw new Exception($this->_translate->_('Failed to delete crisis: ') . $e->getMessage());
        }
    }

    // Helper method to get valid user ID
    private function getValidUserId()
    {
        try {
            // Try to get current logged in user
            $auth = Zend_Auth::getInstance();
            if ($auth->hasIdentity()) {
                $identity = $auth->getIdentity();
                $userId = is_object($identity) ? ($identity->UserID ?? null) : ($identity['UserID'] ?? null);
                
                if ($userId) {
                    // Verify user exists
                    $sql = "SELECT COUNT(*) FROM auth.tbl_Users WHERE UserID = ? AND IsActive = 1";
                    if ($this->_db->fetchOne($sql, $userId)) {
                        return $userId;
                    }
                }
            }
            
            // Get first active user
            $sql = "SELECT TOP 1 UserID FROM auth.tbl_Users WHERE IsActive = 1 ORDER BY UserID";
            $user = $this->_db->fetchOne($sql);
            
            if ($user) {
                return $user;
            }
            
            // If no active users found
            throw new Exception($this->_translate->_('No active users found in the system'));
            
        } catch (Exception $e) {
            error_log("Error getting valid user ID: " . $e->getMessage());
            throw new Exception($this->_translate->_('Unable to determine a valid user for this operation'));
        }
    }

    // Helper methods for dropdowns
    public function getCrisisTypes()
    {
        $sql = "SELECT BaseInfoID, Name 
                FROM org.tbl_General_Base_Info 
                WHERE TableName = 'CrisisType' 
                AND IsActive = 1 
                ORDER BY SortOrder ASC, Name ASC";
        
        return $this->_db->fetchAll($sql);
    }

    // Search crises
    public function searchCrises($searchTerm)
    {
        $sql = "
            SELECT c.*, ct.Name as CrisisTypeName
            FROM Crs.tbl_Crisis c
            LEFT JOIN org.tbl_General_Base_Info ct ON c.CrisisTypeID = ct.BaseInfoID
            WHERE c.CrisisCode LIKE ? 
               OR c.CrisisTitle LIKE ?
               OR ct.Name LIKE ?
            ORDER BY c.CreatedAt DESC
        ";
        
        $searchParam = "%$searchTerm%";
        return $this->_db->fetchAll($sql, [$searchParam, $searchParam, $searchParam]);
    }

    // Validate crisis data before save/update
    public function validateCrisisData($data, $isUpdate = false, $crisisId = null)
    {
        $errors = [];
        
        // Check required fields
        if (empty($data['CrisisCode'])) {
            $errors[] = $this->_translate->_('Crisis Code is required');
        }
        
        if (empty($data['CrisisTitle'])) {
            $errors[] = $this->_translate->_('Crisis Title is required');
        }
        
        if (empty($data['CrisisTypeID'])) {
            $errors[] = $this->_translate->_('Crisis Type is required');
        }
        
        // Validate CrisisCode format (alphanumeric with optional dashes)
        if (!empty($data['CrisisCode']) && !preg_match('/^[A-Za-z0-9\-_]+$/', $data['CrisisCode'])) {
            $errors[] = $this->_translate->_('Crisis Code can only contain letters, numbers, dashes and underscores');
        }
        
        // Validate CrisisTitle length
        if (!empty($data['CrisisTitle']) && strlen($data['CrisisTitle']) > 200) {
            $errors[] = $this->_translate->_('Crisis Title cannot exceed 200 characters');
        }
        
        return $errors;
    }

    // Get crisis statistics
    public function getCrisisStats()
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) as inactive,
                COUNT(DISTINCT CrisisTypeID) as types_count
            FROM Crs.tbl_Crisis
        ";
        
        return $this->_db->fetchRow($sql);
    }

    // Get recent crises
    public function getRecentCrises($limit = 5)
    {
        $sql = "
            SELECT TOP $limit 
                c.CrisisID, c.CrisisCode, c.CrisisTitle, c.CreatedAt,
                ct.Name as CrisisTypeName
            FROM Crs.tbl_Crisis c
            LEFT JOIN org.tbl_General_Base_Info ct ON c.CrisisTypeID = ct.BaseInfoID
            ORDER BY c.CreatedAt DESC
        ";
        
        return $this->_db->fetchAll($sql);
    }
}
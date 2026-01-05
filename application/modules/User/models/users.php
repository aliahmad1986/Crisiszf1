<?php
class User_Model_Users
{
    protected $_db;
    protected $_tableName = 'auth.tbl_Users';
    
    public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
        
        if (!$this->_db) {
            throw new Exception('Database adapter not found in registry');
        }
    }
    
    /**
     * Get all users with personnel information
     */
    public function getAllUsers()
    {
        $sql = "
            SELECT 
                u.*,
                c.FullName as CreatedByName,
                up.FullName as UpdatedByName,
                p.FirstName as PersonnelFirstName,
                p.LastName as PersonnelLastName,
                p.NationalCode as PersonnelNationalCode,
                p.Mobile1 as PersonnelMobile,
                p.PersonnelNumber,
                CONCAT(p.FirstName, ' ', p.LastName) as PersonnelFullName
            FROM {$this->_tableName} u
            LEFT JOIN auth.tbl_Users c ON u.CreatedBy = c.UserID
            LEFT JOIN auth.tbl_Users up ON u.UpdatedBy = up.UserID
            LEFT JOIN org.tbl_Personnel p ON u.PersonelID = p.PersonnelID
            ORDER BY u.CreatedAt DESC
        ";
        
        try {
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching users: " . $e->getMessage());
        }
    }
    
    /**
     * Get single user by ID with personnel information
     */
    public function getUser($id)
    {
        $id = (int)$id;
        
        $sql = "
            SELECT u.*, p.* 
            FROM {$this->_tableName} u
            LEFT JOIN org.tbl_Personnel p ON u.PersonelID = p.PersonnelID
            WHERE u.UserID = ?
        ";
        
        try {
            $stmt = $this->_db->query($sql, array($id));
            $result = $stmt->fetch();
            
            if (!$result) {
                throw new Exception("User with ID {$id} not found");
            }
            
            return $result;
        } catch (Exception $e) {
            throw new Exception("Error fetching user: " . $e->getMessage());
        }
    }
    
    /**
     * Get available personnel (without user accounts)
     */
    public function getAvailablePersonnel()
    {
        try {
            $sql = "
                SELECT p.PersonnelID, p.NationalCode, p.FirstName, p.LastName, p.Mobile1
                FROM org.tbl_Personnel p
                LEFT JOIN auth.tbl_Users u ON u.PersonelID = p.PersonnelID
                WHERE u.UserID IS NULL AND p.IsActive = 1
                ORDER BY p.FirstName, p.LastName
            ";
            
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching available personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Get personnel by ID
     */
    public function getPersonnel($personnelId)
    {
        $personnelId = (int)$personnelId;
        
        try {
            $sql = "SELECT * FROM org.tbl_Personnel WHERE PersonnelID = ?";
            $stmt = $this->_db->query($sql, array($personnelId));
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error fetching personnel: " . $e->getMessage());
        }
    }
    
    /**
     * Add new user with personnel linking
     */
    public function addUser($data)
    {
        // Begin transaction
        $this->_db->beginTransaction();
        
        try {
            // Check if username exists
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE Username = ?";
            $checkStmt = $this->_db->query($checkSql, array(trim($data['Username'])));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Username already exists");
            }
            
            // Check if personnel is already linked to another user
            if (!empty($data['PersonelID'])) {
                $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE PersonelID = ?";
                $checkStmt = $this->_db->query($checkSql, array($data['PersonelID']));
                $checkResult = $checkStmt->fetch();
                
                if ($checkResult['count'] > 0) {
                    throw new Exception("Selected personnel already has a user account");
                }
                
                // Get personnel data to auto-fill user information
                $personnel = $this->getPersonnel($data['PersonelID']);
                if ($personnel) {
                    // Auto-fill user data from personnel
                    if (empty($data['FullName'])) {
                        $data['FullName'] = $personnel['FirstName'] . ' ' . $personnel['LastName'];
                    }
                    if (empty($data['Mobile'])) {
                        $data['Mobile'] = $personnel['Mobile1'];
                    }
                    if (empty($data['Email'])) {
                        $data['Email'] = null; // Personnel doesn't have email field
                    }
                }
            }
            
            // Prepare user data
            $userData = array(
                'PersonelID' => !empty($data['PersonelID']) ? (int)$data['PersonelID'] : null,
                'Username' => trim($data['Username']),
                'FullName' => trim($data['FullName']),
                'Mobile' => isset($data['Mobile']) ? trim($data['Mobile']) : null,
                'Email' => isset($data['Email']) ? trim($data['Email']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'CreatedBy' => isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : null
            );
            
            // Hash password
            if (!empty($data['Password'])) {
                $userData['PasswordHash'] = password_hash($data['Password'], PASSWORD_DEFAULT);
            } else {
                throw new Exception("Password is required");
            }
            
            // Insert the user
            $this->_db->insert($this->_tableName, $userData);
            $userId = $this->_db->lastInsertId();
            
            // Commit transaction
            $this->_db->commit();
            
            return $userId;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->_db->rollBack();
            throw new Exception("Error adding user: " . $e->getMessage());
        }
    }
    
    /**
     * Update existing user with personnel linking
     */
    public function updateUser($id, $data)
    {
        $id = (int)$id;
        
        // Begin transaction
        $this->_db->beginTransaction();
        
        try {
            // Get current user data
            $currentUser = $this->getUser($id);
            if (!$currentUser) {
                throw new Exception("User not found");
            }
            
            // Check if username exists (excluding current user)
            if (isset($data['Username'])) {
                $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                            WHERE Username = ? AND UserID != ?";
                $checkStmt = $this->_db->query($checkSql, array(trim($data['Username']), $id));
                $checkResult = $checkStmt->fetch();
                
                if ($checkResult['count'] > 0) {
                    throw new Exception("Username already exists");
                }
            }
            
            // Check if personnel is already linked to another user
            if (isset($data['PersonelID']) && $data['PersonelID'] != $currentUser['PersonelID']) {
                $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                            WHERE PersonelID = ? AND UserID != ?";
                $checkStmt = $this->_db->query($checkSql, array($data['PersonelID'], $id));
                $checkResult = $checkStmt->fetch();
                
                if ($checkResult['count'] > 0) {
                    throw new Exception("Selected personnel already has a user account");
                }
                
                // Get personnel data to auto-fill user information
                if (!empty($data['PersonelID'])) {
                    $personnel = $this->getPersonnel($data['PersonelID']);
                    if ($personnel) {
                        // Auto-fill user data from personnel
                        if (empty($data['FullName'])) {
                            $data['FullName'] = $personnel['FirstName'] . ' ' . $personnel['LastName'];
                        }
                        if (empty($data['Mobile'])) {
                            $data['Mobile'] = $personnel['Mobile1'];
                        }
                    }
                }
            }
            
            // Prepare update data
            $userData = array(
                'UpdatedAt' => date('Y-m-d H:i:s'),
                'UpdatedBy' => isset($data['UpdatedBy']) ? (int)$data['UpdatedBy'] : null
            );
            
            // Update fields if provided
            if (isset($data['PersonelID'])) {
                $userData['PersonelID'] = !empty($data['PersonelID']) ? (int)$data['PersonelID'] : null;
            }
            if (isset($data['Username'])) {
                $userData['Username'] = trim($data['Username']);
            }
            if (isset($data['FullName'])) {
                $userData['FullName'] = trim($data['FullName']);
            }
            if (isset($data['Mobile'])) {
                $userData['Mobile'] = trim($data['Mobile']);
            }
            if (isset($data['Email'])) {
                $userData['Email'] = trim($data['Email']);
            }
            if (isset($data['IsActive'])) {
                $userData['IsActive'] = $data['IsActive'] ? 1 : 0;
            }
            
            // Update password if provided
            if (!empty($data['Password'])) {
                $userData['PasswordHash'] = password_hash($data['Password'], PASSWORD_DEFAULT);
            }
            
            // Update the user
            $where = $this->_db->quoteInto('UserID = ?', $id);
            $this->_db->update($this->_tableName, $userData, $where);
            
            // Commit transaction
            $this->_db->commit();
            
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->_db->rollBack();
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $id = (int)$id;
        
        try {
            // Check if user exists
            $user = $this->getUser($id);
            if (!$user) {
                throw new Exception("User not found");
            }
            
            // Don't allow deletion of users who created other records
            // (You can add additional checks here if needed)
            
            $where = $this->_db->quoteInto('UserID = ?', $id);
            $this->_db->delete($this->_tableName, $where);
            return true;
            
        } catch (Exception $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }
    
    /**
     * Get users count by status
     */
    public function getUsersCount()
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) as inactive,
                SUM(CASE WHEN PersonelID IS NOT NULL THEN 1 ELSE 0 END) as linked_to_personnel
            FROM {$this->_tableName}
        ";
        
        try {
            $stmt = $this->_db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error getting users count: " . $e->getMessage());
        }
    }
    
    /**
     * Search users
     */
    public function searchUsers($term)
    {
        $sql = "
            SELECT 
                u.UserID, 
                u.Username, 
                u.FullName,
                p.FirstName as PersonnelFirstName,
                p.LastName as PersonnelLastName,
                p.NationalCode as PersonnelNationalCode
            FROM {$this->_tableName} u
            LEFT JOIN org.tbl_Personnel p ON u.PersonelID = p.PersonnelID
            WHERE u.Username LIKE ? OR u.FullName LIKE ? OR u.Email LIKE ? 
               OR p.FirstName LIKE ? OR p.LastName LIKE ? OR p.NationalCode LIKE ?
            ORDER BY u.FullName
        ";
        
        $searchTerm = "%{$term}%";
        
        try {
            $stmt = $this->_db->query($sql, array(
                $searchTerm, $searchTerm, $searchTerm,
                $searchTerm, $searchTerm, $searchTerm
            ));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error searching users: " . $e->getMessage());
        }
    }
    
    /**
     * Check username availability
     */
    public function checkUsernameExists($username, $excludeId = 0)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                    WHERE Username = ? AND UserID != ?";
            
            $stmt = $this->_db->query($sql, array($username, $excludeId));
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Check if personnel is linked to any user
     */
    public function isPersonnelLinked($personnelId, $excludeUserId = 0)
    {
        try {
            $sql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                    WHERE PersonelID = ? AND UserID != ?";
            
            $stmt = $this->_db->query($sql, array($personnelId, $excludeUserId));
            $result = $stmt->fetch();
            
            return $result['count'] > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get user by personnel ID
     */
    public function getUserByPersonnelId($personnelId)
    {
        try {
            $sql = "SELECT * FROM {$this->_tableName} WHERE PersonelID = ?";
            $stmt = $this->_db->query($sql, array($personnelId));
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }
}
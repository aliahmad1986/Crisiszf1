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
     * Get all users with creator information
     */
    public function getAllUsers()
    {
        $sql = "
            SELECT 
                u.*,
                c.FullName as CreatedByName,
                c2.FullName as UpdatedByName
            FROM {$this->_tableName} u
            LEFT JOIN {$this->_tableName} c ON u.CreatedBy = c.UserID
            LEFT JOIN {$this->_tableName} c2 ON u.UpdatedBy = c2.UserID
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
     * Get single user by ID
     */
    public function getUser($id)
    {
        $id = (int)$id;
        
        $sql = "SELECT * FROM {$this->_tableName} WHERE UserID = ?";
        
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
     * Add new user
     */
    public function addUser($data)
    {
        // Prepare data
        $userData = array(
            'Username' => trim($data['Username']),
            'FullName' => trim($data['FullName']),
            'Email' => isset($data['Email']) ? trim($data['Email']) : null,
            'Mobile' => isset($data['Mobile']) ? trim($data['Mobile']) : null,
            'PersonelID' => isset($data['PersonelID']) ? (int)$data['PersonelID'] : null,
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
        
        // Check if username exists
        $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE Username = ?";
        $checkStmt = $this->_db->query($checkSql, array($userData['Username']));
        $checkResult = $checkStmt->fetch();
        
        if ($checkResult['count'] > 0) {
            throw new Exception("Username already exists");
        }
        
        try {
            $this->_db->insert($this->_tableName, $userData);
            return $this->_db->lastInsertId();
        } catch (Exception $e) {
            throw new Exception("Error adding user: " . $e->getMessage());
        }
    }
    
    /**
     * Update existing user
     */
    public function updateUser($id, $data)
    {
        $id = (int)$id;
        
        // Prepare data
        $userData = array(
            'FullName' => trim($data['FullName']),
            'Email' => isset($data['Email']) ? trim($data['Email']) : null,
            'Mobile' => isset($data['Mobile']) ? trim($data['Mobile']) : null,
            'PersonelID' => isset($data['PersonelID']) ? (int)$data['PersonelID'] : null,
            'IsActive' => isset($data['IsActive']) ? 1 : 0,
            'UpdatedAt' => date('Y-m-d H:i:s'),
            'UpdatedBy' => isset($data['UpdatedBy']) ? (int)$data['UpdatedBy'] : null
        );
        
        // Update username if provided and different
        if (isset($data['Username']) && !empty($data['Username'])) {
            $userData['Username'] = trim($data['Username']);
        }
        
        // Update password if provided
        if (!empty($data['Password'])) {
            $userData['PasswordHash'] = password_hash($data['Password'], PASSWORD_DEFAULT);
        }
        
        try {
            $where = $this->_db->quoteInto('UserID = ?', $id);
            $this->_db->update($this->_tableName, $userData, $where);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error updating user: " . $e->getMessage());
        }
    }
    
    /**
     * Delete user
     */
    public function deleteUser($id)
    {
        $id = (int)$id;
        
        // Check if user exists
        $user = $this->getUser($id);
        if (!$user) {
            throw new Exception("User not found");
        }
        
        try {
            $where = $this->_db->quoteInto('UserID = ?', $id);
            $this->_db->delete($this->_tableName, $where);
            return true;
        } catch (Exception $e) {
            throw new Exception("Error deleting user: " . $e->getMessage());
        }
    }
    
    /**
     * Search users
     */
    public function searchUsers($term)
    {
        $sql = "
            SELECT * FROM {$this->_tableName} 
            WHERE Username LIKE ? OR FullName LIKE ? OR Email LIKE ?
            ORDER BY FullName
        ";
        
        $searchTerm = "%{$term}%";
        
        try {
            $stmt = $this->_db->query($sql, array($searchTerm, $searchTerm, $searchTerm));
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error searching users: " . $e->getMessage());
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
                SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) as inactive
            FROM {$this->_tableName}
        ";
        
        try {
            $stmt = $this->_db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error getting users count: " . $e->getMessage());
        }
    }
}
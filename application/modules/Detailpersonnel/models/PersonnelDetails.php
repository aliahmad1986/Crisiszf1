<?php
class DetailPersonnel_Model_PersonnelDetails
{
    protected $_db;

      public function __construct()
    {
        $this->_db = Zend_Registry::get('db');
        
        if (!$this->_db) {
            throw new Exception('Database adapter not found in registry');
        }
    }

    public function getAllPersonnelDetails()
    {
        $sql = "
            SELECT 
                pd.*,
                CONCAT(p.FirstName, ' ', p.LastName) as PersonnelName,
                d.DepartmentName,
                pos.Name as PositionName,
                c.FullName as CreatedByName,
                u.FullName as UpdatedByName,
                -- Calculate if current assignment
                CASE 
                    WHEN pd.ToDate IS NULL OR pd.ToDate >= CONVERT(nvarchar(10), GETDATE(), 120) THEN 1
                    ELSE 0
                END as IsCurrent
            FROM org.tbl_PersonnelDetails pd
            LEFT JOIN org.tbl_Personnel p ON pd.PersonnelID = p.PersonnelID
            LEFT JOIN org.tbl_Departments d ON pd.DepartmentID = d.DepartmentID
            LEFT JOIN org.tbl_General_Base_Info pos ON pd.PositionID = pos.BaseInfoID 
                AND pos.TableName = 'PositionType'
            LEFT JOIN auth.tbl_Users c ON pd.CreatedBy = c.UserID
            LEFT JOIN auth.tbl_Users u ON pd.UpdatedBy = u.UserID
            ORDER BY pd.PersonnelID, pd.FromDate DESC
        ";

        return $this->_db->fetchAll($sql);
    }

    public function getPersonnelAssignments($personnelId = null)
    {
        $where = '';
        $params = [];
        
        if ($personnelId) {
            $where = "WHERE pd.PersonnelID = ?";
            $params[] = $personnelId;
        }
        
        $sql = "
            SELECT 
                pd.*,
                CONCAT(p.FirstName, ' ', p.LastName) as PersonnelName,
                d.DepartmentName,
                pos.Name as PositionName,
                c.FullName as CreatedByName,
                -- Calculate assignment status
                CASE 
                    WHEN pd.ToDate IS NULL THEN 'Current'
                    WHEN pd.ToDate < CONVERT(nvarchar(10), GETDATE(), 120) THEN 'Past'
                    ELSE 'Future'
                END as AssignmentStatus
            FROM org.tbl_PersonnelDetails pd
            LEFT JOIN org.tbl_Personnel p ON pd.PersonnelID = p.PersonnelID
            LEFT JOIN org.tbl_Departments d ON pd.DepartmentID = d.DepartmentID
            LEFT JOIN org.tbl_General_Base_Info pos ON pd.PositionID = pos.BaseInfoID 
                AND pos.TableName = 'PositionType'
            LEFT JOIN auth.tbl_Users c ON pd.CreatedBy = c.UserID
            {$where}
            ORDER BY pd.FromDate DESC, pd.CreatedAt DESC
        ";

        return $this->_db->fetchAll($sql, $params);
    }

    public function getCurrentAssignments($personnelId = null)
    {
        $where = "WHERE (pd.ToDate IS NULL OR pd.ToDate >= CONVERT(nvarchar(10), GETDATE(), 120))";
        $params = [];
        
        if ($personnelId) {
            $where .= " AND pd.PersonnelID = ?";
            $params[] = $personnelId;
        }
        
        $sql = "
            SELECT 
                pd.*,
                CONCAT(p.FirstName, ' ', p.LastName) as PersonnelName,
                d.DepartmentName,
                pos.Name as PositionName
            FROM org.tbl_PersonnelDetails pd
            LEFT JOIN org.tbl_Personnel p ON pd.PersonnelID = p.PersonnelID
            LEFT JOIN org.tbl_Departments d ON pd.DepartmentID = d.DepartmentID
            LEFT JOIN org.tbl_General_Base_Info pos ON pd.PositionID = pos.BaseInfoID 
                AND pos.TableName = 'PositionType'
            {$where}
            ORDER BY pd.FromDate DESC
        ";

        return $this->_db->fetchAll($sql, $params);
    }

    public function getPersonnelDetail($id)
    {
        $id = (int)$id;
        $sql = "
            SELECT pd.*, CONCAT(p.FirstName, ' ', p.LastName) as PersonnelName
            FROM org.tbl_PersonnelDetails pd
            LEFT JOIN org.tbl_Personnel p ON pd.PersonnelID = p.PersonnelID
            WHERE pd.DetailID = ?
        ";
        $result = $this->_db->fetchRow($sql, $id);
        
        if (!$result) {
            throw new Exception("Could not find personnel detail with ID $id");
        }
        
        return $result;
    }

 public function addPersonnelDetail($data)
{
    // Validate date range
    if (!empty($data['ToDate']) && $data['ToDate'] < $data['FromDate']) {
        throw new Exception('To Date cannot be earlier than From Date');
    }

    // Set default values
    $data['CreatedAt'] = date('Y-m-d H:i:s');
    $data['IsActive'] = isset($data['IsActive']) ? 1 : 0;

    // Validate required fields
    $required = ['PersonnelID', 'DepartmentID', 'PositionID', 'FromDate'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            throw new Exception(ucfirst($field) . ' is required');
        }
    }

    // Get current user ID for CreatedBy - Handle properly
    $auth = Zend_Auth::getInstance();
    $currentUser = null;
    
    if ($auth->hasIdentity()) {
        $identity = $auth->getIdentity();
        if (is_object($identity)) {
            $currentUser = $identity->UserID ?? null;
        } elseif (is_array($identity)) {
            $currentUser = $identity['UserID'] ?? null;
        }
    }
    
    // If no valid user found, use a default existing user
    if (!$currentUser) {
        // Try to get the first active user from the database
        try {
            $sql = "SELECT TOP 1 UserID FROM auth.tbl_Users WHERE IsActive = 1 ORDER BY UserID";
            $defaultUser = $this->_db->fetchOne($sql);
            $currentUser = $defaultUser ?: 1; // Fallback to UserID 1
        } catch (Exception $e) {
            $currentUser = 1; // Ultimate fallback
        }
    }
    
    // Verify the user exists
    try {
        $sql = "SELECT COUNT(*) FROM auth.tbl_Users WHERE UserID = ? AND IsActive = 1";
        $userExists = $this->_db->fetchOne($sql, $currentUser);
        
        if (!$userExists) {
            // Get any active user
            $sql = "SELECT TOP 1 UserID FROM auth.tbl_Users WHERE IsActive = 1 ORDER BY UserID";
            $currentUser = $this->_db->fetchOne($sql) ?: 1;
        }
    } catch (Exception $e) {
        // If check fails, use default
        $currentUser = 1;
    }
    
    $data['CreatedBy'] = 2;

    // Prepare columns and values
    $columns = array_keys($data);
    $values = array_values($data);
    
    $placeholders = array_fill(0, count($columns), '?');
    
    $sql = "INSERT INTO org.tbl_PersonnelDetails (" . implode(', ', $columns) . ") 
            VALUES (" . implode(', ', $placeholders) . ")";
    
    try {
        $this->_db->query($sql, $values);
        return $this->_db->lastInsertId();
    } catch (Exception $e) {
        error_log("Insert error: " . $e->getMessage());
        throw new Exception('Failed to save assignment: ' . $e->getMessage());
    }
}
    public function updatePersonnelDetail($id, $data)
    {
        $id = (int)$id;
        
        // Get existing record to preserve some data
        $existing = $this->getPersonnelDetail($id);
        
        // Validate date range
        if (!empty($data['ToDate']) && $data['ToDate'] < $data['FromDate']) {
            throw new Exception('To Date cannot be earlier than From Date');
        }

        // Check for overlapping assignments (excluding current record)
        if (!$this->isAssignmentValid(
            $data['PersonnelID'] ?? $existing['PersonnelID'], 
            $data['DepartmentID'] ?? $existing['DepartmentID'], 
            $data['FromDate'] ?? $existing['FromDate'], 
            $data['ToDate'] ?? $existing['ToDate'],
            $id
        )) {
            throw new Exception('This personnel already has another assignment in this department during the specified date range');
        }

        // Set update timestamp
        $data['UpdatedAt'] = date('Y-m-d H:i:s');
        $data['IsActive'] = isset($data['IsActive']) ? 1 : 0;

        // Get current user ID for UpdatedBy
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $data['UpdatedBy'] = $auth->getIdentity()->UserID;
        } else {
            $data['UpdatedBy'] = 1; // Default admin user
        }

        // Prepare SET clause
        $set = [];
        $values = [];
        
        foreach ($data as $column => $value) {
            $set[] = "$column = ?";
            $values[] = $value;
        }
        
        $values[] = $id; // For WHERE clause
        
        $sql = "UPDATE org.tbl_PersonnelDetails 
                SET " . implode(', ', $set) . "
                WHERE DetailID = ?";
        
        $this->_db->query($sql, $values);
    }

    private function isAssignmentValid($personnelId, $departmentId, $fromDate, $toDate = null, $excludeId = null)
    {
        $params = [$personnelId, $departmentId, $fromDate];
        $excludeClause = $excludeId ? "AND DetailID != ?" : "";
        
        if ($excludeId) {
            $params[] = $excludeId;
        }
        
        if ($toDate) {
            $sql = "
                SELECT COUNT(*) as count 
                FROM org.tbl_PersonnelDetails 
                WHERE PersonnelID = ? 
                AND DepartmentID = ?
                AND (
                    (FromDate <= ? AND (ToDate IS NULL OR ToDate >= ?))
                    OR (FromDate <= ? AND (ToDate IS NULL OR ToDate >= ?))
                    OR (? <= FromDate AND (? >= FromDate OR ToDate IS NULL))
                )
                {$excludeClause}
            ";
            $params[] = $toDate;
            $params[] = $fromDate;
            $params[] = $toDate;
        } else {
            $sql = "
                SELECT COUNT(*) as count 
                FROM org.tbl_PersonnelDetails 
                WHERE PersonnelID = ? 
                AND DepartmentID = ?
                AND (ToDate IS NULL OR ToDate >= ?)
                {$excludeClause}
            ";
        }
        
        $result = $this->_db->fetchRow($sql, $params);
        return $result['count'] == 0;
    }

    public function deletePersonnelDetail($id)
    {
        $id = (int)$id;
        $sql = "DELETE FROM org.tbl_PersonnelDetails WHERE DetailID = ?";
        $this->_db->query($sql, $id);
    }

    // Updated Helper methods for dropdowns
    public function getAllPersonnel()
    {
        $sql = "SELECT PersonnelID, FirstName, LastName, 
                       CONCAT(FirstName, ' ', LastName) as FullName,
                       NationalCode, PersonnelNumber, IsActive
                FROM org.tbl_Personnel 
                WHERE IsActive = 1 
                ORDER BY FirstName, LastName ASC";
        
        return $this->_db->fetchAll($sql);
    }

    public function getDepartments()
    {
        $sql = "SELECT DepartmentID, DepartmentName, DepartmentCode, IsActive 
                FROM org.tbl_Departments 
                WHERE IsActive = 1 
                ORDER BY DepartmentName ASC";
        
        return $this->_db->fetchAll($sql);
    }

    public function getPositions()
    {
        $sql = "SELECT BaseInfoID, Name 
                FROM org.tbl_General_Base_Info 
                WHERE TableName = 'PositionType' 
                AND IsActive = 1 
                ORDER BY SortOrder ASC, Name ASC";
        
        return $this->_db->fetchAll($sql);
    }

    public function getUsers()
    {
        $sql = "SELECT UserID, FullName, Username 
                FROM auth.tbl_Users 
                WHERE IsActive = 1 
                ORDER BY FullName ASC";
        return $this->_db->fetchAll($sql);
    }

    public function getPersonnelHistory($personnelId)
    {
        $sql = "
            SELECT 
                pd.*,
                d.DepartmentName,
                pos.Name as PositionName,
                CASE 
                    WHEN pd.ToDate IS NULL THEN 'Current'
                    WHEN pd.ToDate < CONVERT(nvarchar(10), GETDATE(), 120) THEN 'Past'
                    ELSE 'Future'
                END as Status
            FROM org.tbl_PersonnelDetails pd
            LEFT JOIN org.tbl_Departments d ON pd.DepartmentID = d.DepartmentID
            LEFT JOIN org.tbl_General_Base_Info pos ON pd.PositionID = pos.BaseInfoID 
                AND pos.TableName = 'PositionType'
            WHERE pd.PersonnelID = ?
            ORDER BY pd.FromDate DESC
        ";
        
        return $this->_db->fetchAll($sql, $personnelId);
    }

    public function getPersonnelInfo($personnelId)
    {
        $sql = "
            SELECT PersonnelID, NationalCode, FirstName, LastName, 
                   CONCAT(FirstName, ' ', LastName) as FullName,
                   Mobile1, PersonnelNumber, IsActive
            FROM org.tbl_Personnel 
            WHERE PersonnelID = ?
        ";
        return $this->_db->fetchRow($sql, $personnelId);
    }

    // Get department info with center and type details
    public function getDepartmentInfo($departmentId)
    {
        $sql = "
            SELECT d.*, c.CenterName, dt.TypeName
            FROM org.tbl_Departments d
            LEFT JOIN org.tbl_Centers c ON d.CenterID = c.CenterID
            LEFT JOIN org.tbl_DepartmentTypes dt ON d.DepartmentTypeID = dt.DepartmentTypeID
            WHERE d.DepartmentID = ?
        ";
        return $this->_db->fetchRow($sql, $departmentId);
    }
}
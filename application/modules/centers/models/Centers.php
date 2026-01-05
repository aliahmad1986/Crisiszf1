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
     * Get all centers with pagination (SQL Server specific)
     */
    public function getAllCenters($page = 1, $limit = 10, $filters = array())
    {
        $offset = ($page - 1) * $limit;
        
        // Base query
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
            WHERE 1=1
        ";
        
        // Add filters
        $params = array();
        if (!empty($filters['CenterTypeID'])) {
            $sql .= " AND c.CenterTypeID = ?";
            $params[] = $filters['CenterTypeID'];
        }
        if (isset($filters['IsActive'])) {
            $sql .= " AND c.IsActive = ?";
            $params[] = $filters['IsActive'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (c.CenterName LIKE ? OR c.Address LIKE ? OR c.Phone1 LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        // Order by
        $sql .= " ORDER BY c.CreatedAt DESC";
        
        // For SQL Server 2012+ (OFFSET/FETCH)
        $sql .= " OFFSET {$offset} ROWS FETCH NEXT {$limit} ROWS ONLY";
        
        try {
            $stmt = $this->_db->query($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error fetching centers: " . $e->getMessage());
        }
    }
    
    /**
     * Get total count for pagination
     */
    public function getTotalCenters($filters = array())
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->_tableName} c WHERE 1=1";
        
        $params = array();
        if (!empty($filters['CenterTypeID'])) {
            $sql .= " AND c.CenterTypeID = ?";
            $params[] = $filters['CenterTypeID'];
        }
        if (isset($filters['IsActive'])) {
            $sql .= " AND c.IsActive = ?";
            $params[] = $filters['IsActive'];
        }
        if (!empty($filters['search'])) {
            $sql .= " AND (c.CenterName LIKE ? OR c.Address LIKE ? OR c.Phone1 LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        try {
            $stmt = $this->_db->query($sql, $params);
            $result = $stmt->fetch();
            return $result['total'];
        } catch (Exception $e) {
            throw new Exception("Error counting centers: " . $e->getMessage());
        }
    }
    
    /**
     * Get single center by ID (SQL Server specific - with NOLOCK for performance)
     */
    public function getCenter($id)
    {
        $id = (int)$id;
        
        $sql = "
            SELECT c.*, ct.CenterTypeName 
            FROM {$this->_tableName} c WITH (NOLOCK)
            LEFT JOIN org.tbl_CenterTypes ct WITH (NOLOCK) ON c.CenterTypeID = ct.CenterTypeID
            WHERE c.CenterID = ?
        ";
        
        try {
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
     * Get all center types with cache
     */
    public function getCenterTypes()
    {
        $cache = Zend_Registry::get('cache');
        $cacheKey = 'center_types_list';
        
        // Try to get from cache first
        if ($cache && ($centerTypes = $cache->load($cacheKey))) {
            return $centerTypes;
        }
        
        try {
            $sql = "SELECT CenterTypeID, CenterTypeName 
                    FROM org.tbl_CenterTypes 
                    WHERE IsActive = 1 
                    ORDER BY CenterTypeName";
            
            $stmt = $this->_db->query($sql);
            $centerTypes = $stmt->fetchAll();
            
            // Cache for 1 hour
            if ($cache) {
                $cache->save($centerTypes, $cacheKey, array(), 3600);
            }
            
            return $centerTypes;
        } catch (Exception $e) {
            // Log error but return empty array
            error_log("Error fetching center types: " . $e->getMessage());
            return array();
        }
    }
    
    /**
     * Add new center with transaction support
     */
    public function addCenter($data)
    {
        // Begin transaction
        $this->_db->beginTransaction();
        
        try {
            // Prepare data
            $centerData = array(
                'CenterName' => trim($data['CenterName']),
                'CenterTypeID' => (int)$data['CenterTypeID'],
                'Address' => isset($data['Address']) ? trim($data['Address']) : null,
                'Phone1' => isset($data['Phone1']) ? $this->formatPhoneNumber($data['Phone1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->formatPhoneNumber($data['Phone2']) : null,
                'Website' => isset($data['Website']) ? $this->formatWebsite($data['Website']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'CreatedAt' => new Zend_Db_Expr('GETDATE()'),
                'CreatedBy' => isset($data['CreatedBy']) ? (int)$data['CreatedBy'] : null
            );
            
            // Check if center name exists
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} WHERE CenterName = ?";
            $checkStmt = $this->_db->query($checkSql, array($centerData['CenterName']));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Center name already exists");
            }
            
            // Insert the center
            $this->_db->insert($this->_tableName, $centerData);
            $centerId = $this->_db->lastInsertId();
            
            // Clear cache
            $this->clearCache();
            
            // Commit transaction
            $this->_db->commit();
            
            return $centerId;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->_db->rollBack();
            throw new Exception("Error adding center: " . $e->getMessage());
        }
    }
    
    /**
     * Update existing center with transaction
     */
    public function updateCenter($id, $data)
    {
        $id = (int)$id;
        
        // Begin transaction
        $this->_db->beginTransaction();
        
        try {
            // Prepare data
            $centerData = array(
                'CenterName' => trim($data['CenterName']),
                'CenterTypeID' => (int)$data['CenterTypeID'],
                'Address' => isset($data['Address']) ? trim($data['Address']) : null,
                'Phone1' => isset($data['Phone1']) ? $this->formatPhoneNumber($data['Phone1']) : null,
                'Phone2' => isset($data['Phone2']) ? $this->formatPhoneNumber($data['Phone2']) : null,
                'Website' => isset($data['Website']) ? $this->formatWebsite($data['Website']) : null,
                'IsActive' => isset($data['IsActive']) ? 1 : 0,
                'UpdatedAt' => new Zend_Db_Expr('GETDATE()'),
                'UpdatedBy' => isset($data['UpdatedBy']) ? (int)$data['UpdatedBy'] : null
            );
            
            // Check if center name exists (excluding current center)
            $checkSql = "SELECT COUNT(*) as count FROM {$this->_tableName} 
                        WHERE CenterName = ? AND CenterID != ?";
            $checkStmt = $this->_db->query($checkSql, array($centerData['CenterName'], $id));
            $checkResult = $checkStmt->fetch();
            
            if ($checkResult['count'] > 0) {
                throw new Exception("Center name already exists");
            }
            
            // Update the center
            $where = $this->_db->quoteInto('CenterID = ?', $id);
            $this->_db->update($this->_tableName, $centerData, $where);
            
            // Clear cache
            $this->clearCache();
            
            // Commit transaction
            $this->_db->commit();
            
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->_db->rollBack();
            throw new Exception("Error updating center: " . $e->getMessage());
        }
    }
    
    /**
     * Delete center with transaction
     */
    public function deleteCenter($id)
    {
        $id = (int)$id;
        
        // Begin transaction
        $this->_db->beginTransaction();
        
        try {
            // Check if center exists
            $center = $this->getCenter($id);
            if (!$center) {
                throw new Exception("Center not found");
            }
            
            // Check if center has related records (optional)
            // $checkRelations = "SELECT COUNT(*) FROM other_table WHERE CenterID = ?";
            // Add relation checks here if needed
            
            // Delete the center
            $where = $this->_db->quoteInto('CenterID = ?', $id);
            $this->_db->delete($this->_tableName, $where);
            
            // Clear cache
            $this->clearCache();
            
            // Commit transaction
            $this->_db->commit();
            
            return true;
            
        } catch (Exception $e) {
            // Rollback on error
            $this->_db->rollBack();
            throw new Exception("Error deleting center: " . $e->getMessage());
        }
    }
    
    /**
     * Search centers with full-text search support
     */
    public function searchCenters($term, $limit = 20)
    {
        $term = trim($term);
        
        if (empty($term)) {
            return array();
        }
        
        // Check if full-text search is available
        $hasFullText = false;
        try {
            $checkSql = "SELECT FULLTEXTSERVICEPROPERTY('IsFullTextInstalled') as ft_installed";
            $stmt = $this->_db->query($checkSql);
            $result = $stmt->fetch();
            $hasFullText = ($result['ft_installed'] == 1);
        } catch (Exception $e) {
            $hasFullText = false;
        }
        
        if ($hasFullText) {
            // Use full-text search if available
            $sql = "
                SELECT c.*, ct.CenterTypeName 
                FROM {$this->_tableName} c
                LEFT JOIN org.tbl_CenterTypes ct ON c.CenterTypeID = ct.CenterTypeID
                WHERE CONTAINS((c.CenterName, c.Address), ?)
                ORDER BY c.CenterName
            ";
            $searchTerm = '"*' . $term . '*"';
        } else {
            // Use LIKE search
            $sql = "
                SELECT c.*, ct.CenterTypeName 
                FROM {$this->_tableName} c
                LEFT JOIN org.tbl_CenterTypes ct ON c.CenterTypeID = ct.CenterTypeID
                WHERE c.CenterName LIKE ? OR c.Address LIKE ? OR c.Phone1 LIKE ?
                ORDER BY c.CenterName
            ";
            $searchTerm = "%{$term}%";
        }
        
        try {
            if ($hasFullText) {
                $stmt = $this->_db->query($sql, array($searchTerm));
            } else {
                $stmt = $this->_db->query($sql, array($searchTerm, $searchTerm, $searchTerm));
            }
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error searching centers: " . $e->getMessage());
        }
    }
    
    /**
     * Get centers statistics with SQL Server specific functions
     */
    public function getCentersStatistics()
    {
        $sql = "
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN IsActive = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN IsActive = 0 THEN 1 ELSE 0 END) as inactive,
                COUNT(DISTINCT CenterTypeID) as types_count,
                MIN(CreatedAt) as oldest_center,
                MAX(CreatedAt) as newest_center
            FROM {$this->_tableName}
        ";
        
        try {
            $stmt = $this->_db->query($sql);
            return $stmt->fetch();
        } catch (Exception $e) {
            throw new Exception("Error getting centers statistics: " . $e->getMessage());
        }
    }
    
    /**
     * Get centers by type with statistics
     */
    public function getCentersByType()
    {
        $sql = "
            SELECT 
                ct.CenterTypeName,
                COUNT(c.CenterID) as center_count,
                SUM(CASE WHEN c.IsActive = 1 THEN 1 ELSE 0 END) as active_count
            FROM org.tbl_CenterTypes ct
            LEFT JOIN {$this->_tableName} c ON ct.CenterTypeID = c.CenterTypeID
            WHERE ct.IsActive = 1
            GROUP BY ct.CenterTypeName, ct.CenterTypeID
            ORDER BY ct.CenterTypeName
        ";
        
        try {
            $stmt = $this->_db->query($sql);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error getting centers by type: " . $e->getMessage());
        }
    }
    
    /**
     * Format phone number for SQL Server
     */
    private function formatPhoneNumber($phone)
    {
        if (empty($phone)) {
            return null;
        }
        
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        
        // If it starts with 0, add country code
        if (strlen($phone) == 10 && $phone[0] == '0') {
            $phone = '+98' . substr($phone, 1);
        }
        
        return $phone;
    }
    
    /**
     * Format website URL
     */
    private function formatWebsite($url)
    {
        if (empty($url)) {
            return null;
        }
        
        // Add http:// if not present
        if (!preg_match('/^https?:\/\//', $url)) {
            $url = 'http://' . $url;
        }
        
        return $url;
    }
    
    /**
     * Clear cache
     */
    private function clearCache()
    {
        $cache = Zend_Registry::get('cache');
        if ($cache) {
            $cache->clean(Zend_Cache::CLEANING_MODE_MATCHING_TAG, array('centers'));
        }
    }
    
    /**
     * Export centers to CSV
     */
    public function exportCenters($filters = array())
    {
        $sql = "
            SELECT 
                c.CenterID,
                c.CenterName,
                ct.CenterTypeName,
                c.Address,
                c.Phone1,
                c.Phone2,
                c.Website,
                CASE WHEN c.IsActive = 1 THEN 'Active' ELSE 'Inactive' END as Status,
                CONVERT(VARCHAR, c.CreatedAt, 120) as CreatedAt,
                u.FullName as CreatedBy
            FROM {$this->_tableName} c
            LEFT JOIN auth.tbl_Users u ON c.CreatedBy = u.UserID
            LEFT JOIN org.tbl_CenterTypes ct ON c.CenterTypeID = ct.CenterTypeID
            WHERE 1=1
        ";
        
        $params = array();
        if (!empty($filters['CenterTypeID'])) {
            $sql .= " AND c.CenterTypeID = ?";
            $params[] = $filters['CenterTypeID'];
        }
        if (isset($filters['IsActive'])) {
            $sql .= " AND c.IsActive = ?";
            $params[] = $filters['IsActive'];
        }
        
        $sql .= " ORDER BY c.CenterName";
        
        try {
            $stmt = $this->_db->query($sql, $params);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            throw new Exception("Error exporting centers: " . $e->getMessage());
        }
    }
    
    /**
     * Bulk update centers status
     */
    public function bulkUpdateStatus($centerIds, $status)
    {
        if (empty($centerIds)) {
            return 0;
        }
        
        $status = $status ? 1 : 0;
        $ids = implode(',', array_map('intval', $centerIds));
        
        $sql = "
            UPDATE {$this->_tableName} 
            SET IsActive = ?, UpdatedAt = GETDATE()
            WHERE CenterID IN ({$ids})
        ";
        
        try {
            $stmt = $this->_db->query($sql, array($status));
            return $stmt->rowCount();
        } catch (Exception $e) {
            throw new Exception("Error bulk updating centers: " . $e->getMessage());
        }
    }
}
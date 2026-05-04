<?php
/**
 * Correspondence Repository Interface
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Domain\Repository;

use PersianOfficeAutomation\Domain\Entity\Correspondence;

interface CorrespondenceRepositoryInterface {
    
    /**
     * Find correspondence by ID
     * 
     * @param int $id
     * @return Correspondence|null
     */
    public function findById($id);
    
    /**
     * Find correspondence by number
     * 
     * @param string $number
     * @return Correspondence|null
     */
    public function findByNumber($number);
    
    /**
     * Get all correspondence by type
     * 
     * @param string $type
     * @param array $filters
     * @param int $limit
     * @param int $offset
     * @return array
     */
    public function findByType($type, $filters = [], $limit = 50, $offset = 0);
    
    /**
     * Save correspondence
     * 
     * @param Correspondence $correspondence
     * @return int ID of saved correspondence
     */
    public function save(Correspondence $correspondence);
    
    /**
     * Update correspondence
     * 
     * @param Correspondence $correspondence
     * @return bool
     */
    public function update(Correspondence $correspondence);
    
    /**
     * Delete correspondence
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id);
    
    /**
     * Check if number exists
     * 
     * @param string $number
     * @param int|null $excludeId
     * @return bool
     */
    public function numberExists($number, $excludeId = null);
    
    /**
     * Generate next letter number
     * 
     * @param string $type
     * @param string $prefix
     * @return string
     */
    public function generateNextNumber($type, $prefix = '');
    
    /**
     * Get correspondence count by status
     * 
     * @param string $type
     * @param string $status
     * @return int
     */
    public function countByStatus($type, $status);
}
















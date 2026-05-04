<?php
/**
 * Correspondence Repository Implementation
 * Table name from $wpdb->prefix; all user input is passed via prepare().
 *
 * @package OfficeAutomation
 * @phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table from $wpdb->prefix.
 * @phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name is prefixed, params prepared.
 */

namespace PersianOfficeAutomation\Infrastructure\Repository;

use PersianOfficeAutomation\Domain\Entity\Correspondence;
use PersianOfficeAutomation\Domain\Repository\CorrespondenceRepositoryInterface;

class CorrespondenceRepository implements CorrespondenceRepositoryInterface {
    
    private $wpdb;
    private $table;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->table = $wpdb->prefix . 'persian_oa_correspondence';
    }
    
    /**
     * Find correspondence by ID
     */
    public function findById($id) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$result) {
            return null;
        }
        
        return Correspondence::fromArray($result);
    }
    
    /**
     * Find correspondence by number
     */
    public function findByNumber($number) {
        $result = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->table} WHERE number = %s", $number),
            ARRAY_A
        );
        
        if (!$result) {
            return null;
        }
        
        return Correspondence::fromArray($result);
    }
    
    /**
     * Get all correspondence by type
     */
    public function findByType($type, $filters = [], $limit = 50, $offset = 0) {
        $where = $this->wpdb->prepare("WHERE type = %s", $type);
        
        // Apply filters
        if (!empty($filters['status'])) {
            $where .= $this->wpdb->prepare(" AND status = %s", $filters['status']);
        }
        
        if (!empty($filters['priority'])) {
            $where .= $this->wpdb->prepare(" AND priority = %s", $filters['priority']);
        }
        
        if (!empty($filters['category'])) {
            $where .= $this->wpdb->prepare(" AND category = %s", $filters['category']);
        }
        
        if (!empty($filters['search'])) {
            $search = '%' . $this->wpdb->esc_like($filters['search']) . '%';
            $where .= $this->wpdb->prepare(" AND (subject LIKE %s OR number LIKE %s OR sender LIKE %s)", $search, $search, $search);
        }
        
        if (!empty($filters['date_from'])) {
            $where .= $this->wpdb->prepare(" AND letter_date >= %s", $filters['date_from']);
        }
        
        if (!empty($filters['date_to'])) {
            $where .= $this->wpdb->prepare(" AND letter_date <= %s", $filters['date_to']);
        }
        
        $sql = "SELECT * FROM {$this->wpdb->prefix}persian_oa_correspondence {$where} ORDER BY created_at DESC LIMIT %d OFFSET %d";
        $results = $this->wpdb->get_results(
            $this->wpdb->prepare($sql, $limit, $offset),
            ARRAY_A
        );
        
        $entities = [];
        foreach ($results as $result) {
            $entities[] = Correspondence::fromArray($result);
        }
        
        return $entities;
    }
    
    /**
     * Save correspondence
     */
    public function save(Correspondence $correspondence) {
        $data = $correspondence->toArray();
        
        // Remove id and timestamps for insert
        unset($data['id']);
        unset($data['updated_at']);
        
        // Set created_at if not set
        if (empty($data['created_at'])) {
            $data['created_at'] = current_time('mysql');
        }
        
        $result = $this->wpdb->insert($this->table, $data);
        
        if ($result) {
            return $this->wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update correspondence
     */
    public function update(Correspondence $correspondence) {
        $data = $correspondence->toArray();
        $id = $data['id'];
        
        // Remove id and created_at for update
        unset($data['id']);
        unset($data['created_at']);
        
        // Update updated_at
        $data['updated_at'] = current_time('mysql');
        
        $result = $this->wpdb->update(
            $this->table,
            $data,
            ['id' => $id]
        );
        
        return $result !== false;
    }
    
    /**
     * Delete correspondence
     */
    public function delete($id) {
        // Also delete related records
        $this->wpdb->delete($this->wpdb->prefix . 'persian_oa_attachments', ['correspondence_id' => $id]);
        $this->wpdb->delete($this->wpdb->prefix . 'persian_oa_referrals', ['correspondence_id' => $id]);
        $this->wpdb->delete($this->wpdb->prefix . 'persian_oa_cc_recipients', ['correspondence_id' => $id]);
        $this->wpdb->delete($this->wpdb->prefix . 'persian_oa_audit_log', ['correspondence_id' => $id]);
        
        $result = $this->wpdb->delete($this->table, ['id' => $id]);
        
        return $result !== false;
    }
    
    /**
     * Check if number exists
     */
    public function numberExists($number, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->wpdb->prefix}persian_oa_correspondence WHERE number = %s";
        $params = [$number];
        
        if ($excludeId !== null) {
            $sql .= " AND id != %d";
            $params[] = $excludeId;
        }
        
        $count = $this->wpdb->get_var($this->wpdb->prepare($sql, $params));
        
        return $count > 0;
    }
    
    /**
     * Generate next letter number
     */
    public function generateNextNumber($type, $prefix = '') {
        $sql = "SELECT number FROM {$this->wpdb->prefix}persian_oa_correspondence WHERE type = %s AND number LIKE %s ORDER BY id DESC LIMIT 1";
        $lastNumber = $this->wpdb->get_var(
            $this->wpdb->prepare($sql, $type, $prefix . '%')
        );
        
        if ($lastNumber) {
            // Extract numeric part
            $numericPart = preg_replace('/[^0-9]/', '', substr($lastNumber, strlen($prefix)));
            $nextNumber = intval($numericPart) + 1;
        } else {
            $nextNumber = 1;
        }
        
        return $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Get correspondence count by status
     */
    public function countByStatus($type, $status) {
        $sql = "SELECT COUNT(*) FROM {$this->wpdb->prefix}persian_oa_correspondence WHERE type = %s AND status = %s";
        return $this->wpdb->get_var($this->wpdb->prepare($sql, $type, $status));
    }

    /**
     * Find correspondence with deadline in range
     */
    public function findDeadlinesBetween($start, $end) {
        $sql = "SELECT * FROM {$this->wpdb->prefix}persian_oa_correspondence WHERE deadline >= %s AND deadline <= %s ORDER BY deadline ASC";
        $results = $this->wpdb->get_results($this->wpdb->prepare($sql, $start, $end), ARRAY_A);
        
        $entities = [];
        foreach ($results as $result) {
            $entities[] = Correspondence::fromArray($result);
        }
        return $entities;
    }

    /**
     * Get dashboard counts (total, incoming, outgoing, pending)
     *
     * @return array{total: int, incoming: int, outgoing: int, pending: int}
     */
    public function getDashboardCounts() {
        $total    = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table}");
        $incoming = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE type = 'incoming'");
        $outgoing = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE type = 'outgoing'");
        $pending  = (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->table} WHERE status = 'pending'");
        return compact('total', 'incoming', 'outgoing', 'pending');
    }

    /**
     * Get monthly counts for a date range (for chart)
     *
     * @param string $startDate Y-m-d H:i:s
     * @param string $endDate   Y-m-d H:i:s
     * @return array{incoming: int, outgoing: int}
     */
    public function getMonthlyCounts($startDate, $endDate) {
        $inc = (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE type = 'incoming' AND created_at BETWEEN %s AND %s",
            $startDate,
            $endDate
        ));
        $out = (int) $this->wpdb->get_var($this->wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table} WHERE type = 'outgoing' AND created_at BETWEEN %s AND %s",
            $startDate,
            $endDate
        ));
        return ['incoming' => $inc, 'outgoing' => $out];
    }

    /**
     * Get recent correspondence rows for dashboard (raw objects)
     *
     * @param int $limit
     * @return array<\stdClass>
     */
    public function getRecent($limit = 10) {
        $sql = "SELECT * FROM {$this->wpdb->prefix}persian_oa_correspondence ORDER BY created_at DESC LIMIT %d";
        return $this->wpdb->get_results($this->wpdb->prepare($sql, $limit), OBJECT);
    }
}
















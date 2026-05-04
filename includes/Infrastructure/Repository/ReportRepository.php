<?php
/**
 * Report Repository
 * Table names from $wpdb->prefix; no user input in SQL.
 *
 * @package OfficeAutomation
 * @phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * @phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace PersianOfficeAutomation\Infrastructure\Repository;

class ReportRepository {
    
    private $wpdb;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
    }
    
    /**
     * Get correspondence counts by type and status
     * 
     * @return array
     */
    public function getCorrespondenceStats() {
        $sql = "SELECT type, status, COUNT(*) as count 
                FROM {$this->wpdb->prefix}persian_oa_correspondence 
                GROUP BY type, status";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get correspondence counts by priority
     * 
     * @return array
     */
    public function getCorrespondenceByPriority() {
        $sql = "SELECT priority, COUNT(*) as count 
                FROM {$this->wpdb->prefix}persian_oa_correspondence 
                GROUP BY priority";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $this->wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Get monthly correspondence stats for the last 6 months
     * 
     * @return array
     */
    public function getMonthlyStats() {
        // Last 6 months
        $sql = "SELECT 
                    DATE_FORMAT(created_at, '%Y-%m') as month,
                    type,
                    COUNT(*) as count
                FROM {$this->wpdb->prefix}persian_oa_correspondence
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY month, type
                ORDER BY month ASC";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get task statistics
     * 
     * @return array
     */
    public function getTaskStats() {
        $sql = "SELECT status, COUNT(*) as count 
                FROM {$this->wpdb->prefix}persian_oa_tasks 
                GROUP BY status";
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
    
    /**
     * Get upcoming meetings
     * 
     * @param int $limit
     * @return array
     */
    public function getUpcomingMeetings($limit = 5) {
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = $this->wpdb->prepare(
            "SELECT * FROM {$this->wpdb->prefix}persian_oa_meetings 
             WHERE meeting_date >= NOW() 
             ORDER BY meeting_date ASC 
             LIMIT %d",
            $limit
        );
        return $this->wpdb->get_results($sql, ARRAY_A);
    }
}



<?php
/**
 * Cartable Service - Inbox Management
 * Table names used in queries are from $wpdb->prefix (safe, not user input).
 *
 * @package PersianOfficeAutomation\Application\Services
 * @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * @phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * @phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace PersianOfficeAutomation\Application\Services;

class CartableService {
    
    /**
     * Get inbox items for user
     */
    public static function getInbox($userId, $filters = [], $page = 1, $perPage = 20) {
        global $wpdb;
        
        $table_correspondence = $wpdb->prefix . 'persian_oa_correspondence';
        $table_referrals = $wpdb->prefix . 'persian_oa_referrals';
        $table_cc = $wpdb->prefix . 'persian_oa_cc_recipients';
        $table_read = $wpdb->prefix . 'persian_oa_read_receipts';
        
        $offset = ($page - 1) * $perPage;
        
        // Build WHERE clause
        $where = ["(ref.to_user = %d OR cc.user_id = %d OR c.primary_recipient = %d)"];
        $params = [$userId, $userId, $userId];
        
        // Apply filters
        if (!empty($filters['priority'])) {
            $where[] = "c.priority = %s";
            $params[] = $filters['priority'];
        }
        
        if (!empty($filters['status'])) {
            $where[] = "c.status = %s";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['unread'])) {
            $where[] = "rr.id IS NULL";
        }
        
        if (!empty($filters['starred'])) {
            $table_starred = $wpdb->prefix . 'persian_oa_starred';
            $where[] = "EXISTS (SELECT 1 FROM $table_starred WHERE correspondence_id = c.id AND user_id = %d)";
            $params[] = $userId;
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(c.subject LIKE %s OR c.number LIKE %s OR c.description LIKE %s)";
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Main query
        $sql = "
            SELECT DISTINCT c.*, 
                   rr.read_at,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}persian_oa_attachments WHERE correspondence_id = c.id) as attachment_count,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}persian_oa_comments WHERE correspondence_id = c.id) as comment_count
            FROM $table_correspondence c
            LEFT JOIN $table_referrals ref ON c.id = ref.correspondence_id
            LEFT JOIN $table_cc cc ON c.id = cc.correspondence_id
            LEFT JOIN $table_read rr ON c.id = rr.correspondence_id AND rr.user_id = %d
            WHERE $where_clause
            ORDER BY c.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $userId;
        $params[] = $perPage;
        $params[] = $offset;
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get inbox count
     */
    public static function getInboxCount($userId, $unreadOnly = false) {
        global $wpdb;
        
        $table_correspondence = $wpdb->prefix . 'persian_oa_correspondence';
        $table_referrals = $wpdb->prefix . 'persian_oa_referrals';
        $table_cc = $wpdb->prefix . 'persian_oa_cc_recipients';
        $table_read = $wpdb->prefix . 'persian_oa_read_receipts';
        
        $where = "(ref.to_user = %d OR cc.user_id = %d OR c.primary_recipient = %d)";
        
        if ($unreadOnly) {
            $where .= " AND rr.id IS NULL";
        }
        
        $sql = "
            SELECT COUNT(DISTINCT c.id)
            FROM $table_correspondence c
            LEFT JOIN $table_referrals ref ON c.id = ref.correspondence_id
            LEFT JOIN $table_cc cc ON c.id = cc.correspondence_id
            LEFT JOIN $table_read rr ON c.id = rr.correspondence_id AND rr.user_id = %d
            WHERE $where
        ";
        
        return (int) $wpdb->get_var($wpdb->prepare($sql, $userId, $userId, $userId, $userId));
    }
    
    /**
     * Get sent items
     */
    public static function getSentItems($userId, $page = 1, $perPage = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        $offset = ($page - 1) * $perPage;
        
        $table_read = $wpdb->prefix . 'persian_oa_read_receipts';
        $table_ref = $wpdb->prefix . 'persian_oa_referrals';
        $table_cc = $wpdb->prefix . 'persian_oa_cc_recipients';
        // read_count: only recipients (primary, referral to_user, cc) who have read
        $sql = "
            SELECT c.*,
                   (SELECT COUNT(*) FROM {$wpdb->prefix}persian_oa_attachments WHERE correspondence_id = c.id) as attachment_count,
                   (SELECT COUNT(*) FROM $table_read rr
                    WHERE rr.correspondence_id = c.id
                      AND ( rr.user_id = c.primary_recipient
                            OR rr.user_id IN (SELECT to_user FROM $table_ref WHERE correspondence_id = c.id)
                            OR rr.user_id IN (SELECT user_id FROM $table_cc WHERE correspondence_id = c.id) )
                   ) as read_count,
                   (SELECT COUNT(*) FROM $table_ref WHERE correspondence_id = c.id) as referral_count
            FROM $table c
            WHERE c.created_by = %d
            ORDER BY c.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $userId, $perPage, $offset));
    }
    
    /**
     * Get total count of sent items for pagination
     */
    public static function getSentItemsCount($userId) {
        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        $sql = "SELECT COUNT(*) FROM $table WHERE created_by = %d";
        return (int) $wpdb->get_var($wpdb->prepare($sql, $userId));
    }
    
    /**
     * Get replied items
     */
    public static function getRepliedItems($userId, $page = 1, $perPage = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT c.*
            FROM $table c
            WHERE c.primary_recipient = %d 
            AND c.replied_at IS NOT NULL
            ORDER BY c.replied_at DESC
            LIMIT %d OFFSET %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $userId, $perPage, $offset));
    }
    
    /**
     * Get pending items
     */
    public static function getPendingItems($userId, $page = 1, $perPage = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT c.*,
                   DATEDIFF(c.deadline, NOW()) as days_remaining
            FROM $table c
            WHERE c.primary_recipient = %d 
            AND c.status = 'pending'
            AND c.replied_at IS NULL
            ORDER BY c.deadline ASC
            LIMIT %d OFFSET %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $userId, $perPage, $offset));
    }
    
    /**
     * Get starred items
     */
    public static function getStarredItems($userId, $page = 1, $perPage = 20) {
        global $wpdb;
        
        $table_correspondence = $wpdb->prefix . 'persian_oa_correspondence';
        $table_starred = $wpdb->prefix . 'persian_oa_starred';
        $offset = ($page - 1) * $perPage;
        
        $sql = "
            SELECT c.*, s.color, s.note, s.created_at as starred_at
            FROM $table_correspondence c
            INNER JOIN $table_starred s ON c.id = s.correspondence_id
            WHERE s.user_id = %d
            ORDER BY s.created_at DESC
            LIMIT %d OFFSET %d
        ";
        
        return $wpdb->get_results($wpdb->prepare($sql, $userId, $perPage, $offset));
    }
    
    /**
     * Toggle star
     */
    public static function toggleStar($correspondenceId, $userId, $color = 'gold', $note = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_starred';
        
        // Check if already starred
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE correspondence_id = %d AND user_id = %d",
            $correspondenceId,
            $userId
        ));
        
        if ($exists) {
            // Unstar
            return $wpdb->delete($table, [
                'correspondence_id' => $correspondenceId,
                'user_id' => $userId
            ]);
        } else {
            // Star
            return $wpdb->insert($table, [
                'correspondence_id' => $correspondenceId,
                'user_id' => $userId,
                'color' => $color,
                'note' => $note,
                'created_at' => current_time('mysql')
            ]);
        }
    }
    
    /**
     * Mark as read
     */
    public static function markAsRead($correspondenceId, $userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_read_receipts';
        
        // Check if already read
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE correspondence_id = %d AND user_id = %d",
            $correspondenceId,
            $userId
        ));
        
        if (!$exists) {
            return $wpdb->insert($table, [
                'correspondence_id' => $correspondenceId,
                'user_id' => $userId,
                'read_at' => current_time('mysql'),
                'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : null
            ]);
        }
        
        return true;
    }
    
    /**
     * Get archive items
     */
    public static function getArchiveItems($userId, $filters = [], $page = 1, $perPage = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        $offset = ($page - 1) * $perPage;
        
        $where = ["(c.created_by = %d OR c.primary_recipient = %d)"];
        $where[] = "c.status IN ('approved', 'archived')";
        $params = [$userId, $userId];
        
        // Apply filters
        if (!empty($filters['date_from'])) {
            $where[] = "c.letter_date >= %s";
            $params[] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $where[] = "c.letter_date <= %s";
            $params[] = $filters['date_to'];
        }
        
        if (!empty($filters['category'])) {
            $where[] = "c.category = %s";
            $params[] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where[] = "(c.subject LIKE %s OR c.number LIKE %s OR c.archive_code LIKE %s)";
            $search = '%' . $wpdb->esc_like($filters['search']) . '%';
            $params[] = $search;
            $params[] = $search;
            $params[] = $search;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $sql = "
            SELECT c.*
            FROM $table c
            WHERE $where_clause
            ORDER BY c.letter_date DESC
            LIMIT %d OFFSET %d
        ";
        
        $params[] = $perPage;
        $params[] = $offset;
        
        return $wpdb->get_results($wpdb->prepare($sql, $params));
    }
    
    /**
     * Get statistics for dashboard
     */
    public static function getStatistics($userId) {
        $stats = [
            'inbox_total' => self::getInboxCount($userId),
            'inbox_unread' => self::getInboxCount($userId, true),
            'pending' => self::getPendingCount($userId),
            'starred' => self::getStarredCount($userId),
            'sent_today' => self::getSentTodayCount($userId),
            'overdue' => self::getOverdueCount($userId)
        ];
        
        return $stats;
    }
    
    /**
     * Get pending count (public for pagination and display)
     */
    public static function getPendingCount($userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE primary_recipient = %d AND status = 'pending' AND replied_at IS NULL",
            $userId
        ));
    }
    
    /**
     * Get starred count
     */
    private static function getStarredCount($userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_starred';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d",
            $userId
        ));
    }
    
    /**
     * Get sent today count
     */
    private static function getSentTodayCount($userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE created_by = %d AND DATE(created_at) = CURDATE()",
            $userId
        ));
    }
    
    /**
     * Get overdue count
     */
    private static function getOverdueCount($userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE primary_recipient = %d AND deadline < NOW() AND replied_at IS NULL",
            $userId
        ));
    }
}


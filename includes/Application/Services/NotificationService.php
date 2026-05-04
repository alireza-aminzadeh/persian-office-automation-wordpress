<?php
/**
 * Notification Service
 *
 * @package PersianOfficeAutomation\Application\Services
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery -- Table name from $wpdb->prefix; params prepared.
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching -- Notifications are real-time; caching not appropriate.
 * @phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix; all input via prepare().
 */

namespace PersianOfficeAutomation\Application\Services;

use PersianOfficeAutomation\Domain\Entity\Notification;

class NotificationService {
    
    /**
     * Create a notification
     */
    public static function create($userId, $type, $title, $message, $link = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        // Table name from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->insert($table, [
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'link' => $link,
            'is_read' => 0,
            'created_at' => current_time('mysql')
        ]);
        
        if ($result) {
            // Send email if user has email notifications enabled
            self::sendEmailNotification($userId, $title, $message, $link);
            
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Get unread notifications for user
     */
    public static function getUnread($userId, $limit = 10) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d AND is_read = 0 ORDER BY created_at DESC LIMIT %d",
            $userId,
            $limit
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        
        return $results;
    }
    
    /**
     * Get unread count
     */
    public static function getUnreadCount($userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
        $count = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE user_id = %d AND is_read = 0",
            $userId
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $count;
    }
    
    /**
     * Mark as read
     */
    public static function markAsRead($notificationId, $userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        return $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table from $wpdb->prefix.
            $table,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ],
            [
                'id' => $notificationId,
                'user_id' => $userId
            ]
        );
    }
    
    /**
     * Mark all as read
     */
    public static function markAllAsRead($userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        return $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table from $wpdb->prefix.
            $table,
            [
                'is_read' => 1,
                'read_at' => current_time('mysql')
            ],
            ['user_id' => $userId, 'is_read' => 0]
        );
    }
    
    /**
     * Get all notifications for user
     */
    public static function getAll($userId, $page = 1, $perPage = 20) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        $offset = ($page - 1) * $perPage;
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d ORDER BY created_at DESC LIMIT %d OFFSET %d",
            $userId,
            $perPage,
            $offset
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        
        return $results;
    }
    
    /**
     * Delete notification
     */
    public static function delete($notificationId, $userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        return $wpdb->delete( $table, [ // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Table from $wpdb->prefix.
            'id' => $notificationId,
            'user_id' => $userId
        ] );
    }
    
    /**
     * Delete old read notifications (older than 30 days)
     */
    public static function cleanOldNotifications() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_notifications';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
        $deleted = $wpdb->query(
            "DELETE FROM $table WHERE is_read = 1 AND read_at < DATE_SUB(NOW(), INTERVAL 30 DAY)"
        );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $deleted;
    }
    
    /**
     * Send email notification
     */
    private static function sendEmailNotification($userId, $title, $message, $link) {
        // Check if user has email notifications enabled
        $settings = self::getUserSettings($userId);
        if (!$settings || !$settings->notification_email) {
            return false;
        }
        
        $user = get_userdata($userId);
        if (!$user) {
            return false;
        }
        
        $to = $user->user_email;
        $subject = 'اتوماسیون اداری - ' . $title;
        
        $body = '<div dir="rtl" style="font-family: Tahoma, Arial; padding: 20px; background: #f5f5f5;">';
        $body .= '<div style="background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">';
        $body .= '<h2 style="color: #6366f1; margin-bottom: 20px;">' . esc_html($title) . '</h2>';
        $body .= '<p style="font-size: 16px; line-height: 1.8; color: #333;">' . nl2br(esc_html($message)) . '</p>';
        
        if ($link) {
            $body .= '<div style="margin-top: 30px;">';
            $body .= '<a href="' . esc_url(admin_url($link)) . '" style="display: inline-block; background: #6366f1; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px;">مشاهده جزئیات</a>';
            $body .= '</div>';
        }
        
        $body .= '<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e5e5; color: #999; font-size: 13px;">';
        $body .= '<p>این ایمیل به صورت خودکار از سیستم اتوماسیون اداری ارسال شده است.</p>';
        $body .= '</div>';
        $body .= '</div>';
        $body .= '</div>';
        
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        return wp_mail($to, $subject, $body, $headers);
    }
    
    /**
     * Get user settings
     */
    private static function getUserSettings($userId) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'persian_oa_user_settings';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE user_id = %d",
            $userId
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $row;
    }
    
    /**
     * Notification type constants
     */
    const TYPE_NEW_LETTER = 'new_letter';
    const TYPE_REFERRAL = 'referral';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_APPROVED = 'approved';
    const TYPE_REJECTED = 'rejected';
    const TYPE_COMMENT = 'comment';
    const TYPE_ATTACHMENT = 'attachment';
    const TYPE_TASK_ASSIGNED = 'task_assigned';
    const TYPE_TASK_COMPLETED = 'task_completed';
    const TYPE_MEETING = 'meeting';
    const TYPE_REQUEST = 'request';
}


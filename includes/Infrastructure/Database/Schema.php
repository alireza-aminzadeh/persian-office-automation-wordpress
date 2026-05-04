<?php
/**
 * Database Schema Manager
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Infrastructure\Database;

class Schema {
    
    /**
     * Create all required database tables
     */
    public static function createTables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        // Correspondence Table
        $table_correspondence = $wpdb->prefix . 'persian_oa_correspondence';
        $sql_correspondence = "CREATE TABLE $table_correspondence (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    type varchar(20) NOT NULL DEFAULT 'incoming',
    number varchar(100) NOT NULL,
    reference_number varchar(100) DEFAULT NULL,
    subject varchar(500) NOT NULL,
    description text DEFAULT NULL,
    content longtext DEFAULT NULL,
    sender varchar(255) DEFAULT NULL,
    recipient varchar(255) DEFAULT NULL,
    sender_department varchar(255) DEFAULT NULL,
    sender_phone varchar(50) DEFAULT NULL,
    sender_email varchar(100) DEFAULT NULL,
    category varchar(100) DEFAULT NULL,
    priority varchar(20) DEFAULT 'medium',
    confidentiality varchar(20) DEFAULT 'normal',
    status varchar(20) DEFAULT 'draft',
    letter_date date DEFAULT NULL,
    received_at datetime DEFAULT NULL,
    deadline datetime DEFAULT NULL,
    archive_code varchar(100) DEFAULT NULL,
    physical_location varchar(255) DEFAULT NULL,
    shelf_folder varchar(100) DEFAULT NULL,
    primary_recipient bigint(20) UNSIGNED DEFAULT NULL,
    instruction text DEFAULT NULL,
    reply_number varchar(100) DEFAULT NULL,
    replied_at datetime DEFAULT NULL,
    reply_content text DEFAULT NULL,
    action_type varchar(50) DEFAULT NULL,
    tags text DEFAULT NULL,
    keywords text DEFAULT NULL,
    notes text DEFAULT NULL,
    created_by bigint(20) UNSIGNED NOT NULL,
    updated_by bigint(20) UNSIGNED DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY number (number),
    KEY type (type),
    KEY status (status),
    KEY priority (priority),
    KEY letter_date (letter_date),
    KEY created_by (created_by),
    KEY primary_recipient (primary_recipient),
    KEY idx_pending_list (primary_recipient, status, replied_at, deadline)
) $charset_collate;";
        
        dbDelta($sql_correspondence);
        
        // Attachments Table
        $table_attachments = $wpdb->prefix . 'persian_oa_attachments';
        $sql_attachments = "CREATE TABLE $table_attachments (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    correspondence_id bigint(20) UNSIGNED NOT NULL,
    file_name varchar(255) NOT NULL,
    file_path varchar(500) NOT NULL,
    file_type varchar(100) DEFAULT NULL,
    file_size bigint(20) DEFAULT NULL,
    uploaded_by bigint(20) UNSIGNED NOT NULL,
    uploaded_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY correspondence_id (correspondence_id),
    KEY uploaded_by (uploaded_by)
) $charset_collate;";
        
        dbDelta($sql_attachments);
        
        // Referrals Table
        $table_referrals = $wpdb->prefix . 'persian_oa_referrals';
        $sql_referrals = "CREATE TABLE $table_referrals (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    correspondence_id bigint(20) UNSIGNED NOT NULL,
    from_user bigint(20) UNSIGNED NOT NULL,
    to_user bigint(20) UNSIGNED NOT NULL,
    referral_type varchar(50) DEFAULT 'forward',
    comments text DEFAULT NULL,
    status varchar(20) DEFAULT 'pending',
    referred_at datetime DEFAULT CURRENT_TIMESTAMP,
    responded_at datetime DEFAULT NULL,
    response_text text DEFAULT NULL,
    PRIMARY KEY (id),
    KEY correspondence_id (correspondence_id),
    KEY from_user (from_user),
    KEY to_user (to_user),
    KEY status (status)
) $charset_collate;";
        
        dbDelta($sql_referrals);
        
        // CC Recipients Table
        $table_cc = $wpdb->prefix . 'persian_oa_cc_recipients';
        $sql_cc = "CREATE TABLE $table_cc (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    correspondence_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    is_read tinyint(1) DEFAULT 0,
    read_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY correspondence_id (correspondence_id),
    KEY user_id (user_id)
) $charset_collate;";
        
        dbDelta($sql_cc);
        
        // Audit Log Table
        $table_audit = $wpdb->prefix . 'persian_oa_audit_log';
        $sql_audit = "CREATE TABLE $table_audit (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    correspondence_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    action varchar(100) NOT NULL,
    old_value text DEFAULT NULL,
    new_value text DEFAULT NULL,
    ip_address varchar(50) DEFAULT NULL,
    user_agent varchar(255) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY correspondence_id (correspondence_id),
    KEY user_id (user_id),
    KEY action (action)
) $charset_collate;";
        
        dbDelta($sql_audit);
        
        // Tasks Table
        $table_tasks = $wpdb->prefix . 'persian_oa_tasks';
        $sql_tasks = "CREATE TABLE $table_tasks (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    parent_id bigint(20) UNSIGNED DEFAULT NULL,
    title varchar(255) NOT NULL,
    description text DEFAULT NULL,
    correspondence_id bigint(20) UNSIGNED DEFAULT NULL,
    assigned_to bigint(20) UNSIGNED NOT NULL,
    assigned_by bigint(20) UNSIGNED NOT NULL,
    priority varchar(20) DEFAULT 'medium',
    status varchar(20) DEFAULT 'todo',
    start_date datetime DEFAULT NULL,
    deadline datetime DEFAULT NULL,
    estimated_time int DEFAULT 0 COMMENT 'In minutes',
    spent_time int DEFAULT 0 COMMENT 'In minutes',
    category varchar(100) DEFAULT NULL,
    is_recurring tinyint(1) DEFAULT 0,
    recurrence_pattern varchar(255) DEFAULT NULL,
    completed_at datetime DEFAULT NULL,
    checklist text DEFAULT NULL,
    progress int DEFAULT 0,
    tags text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY parent_id (parent_id),
    KEY assigned_to (assigned_to),
    KEY assigned_by (assigned_by),
    KEY status (status),
    KEY deadline (deadline),
    KEY correspondence_id (correspondence_id)
) $charset_collate;";
        
        dbDelta($sql_tasks);

        // Task Comments Table
        $table_task_comments = $wpdb->prefix . 'persian_oa_task_comments';
        $sql_task_comments = "CREATE TABLE $table_task_comments (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    comment text NOT NULL,
    file_attachment varchar(500) DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY task_id (task_id),
    KEY user_id (user_id)
) $charset_collate;";

        dbDelta($sql_task_comments);

        // Task Logs Table (Audit)
        $table_task_logs = $wpdb->prefix . 'persian_oa_task_logs';
        $sql_task_logs = "CREATE TABLE $table_task_logs (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    action varchar(50) NOT NULL,
    details text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY task_id (task_id),
    KEY user_id (user_id)
) $charset_collate;";

        dbDelta($sql_task_logs);

        // Task Time Logs Table
        $table_task_time_logs = $wpdb->prefix . 'persian_oa_task_time_logs';
        $sql_task_time_logs = "CREATE TABLE $table_task_time_logs (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    start_time datetime NOT NULL,
    end_time datetime DEFAULT NULL,
    duration int DEFAULT 0 COMMENT 'In minutes',
    description text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY task_id (task_id),
    KEY user_id (user_id)
) $charset_collate;";

        dbDelta($sql_task_time_logs);
        
        // Task Dependencies Table
        $table_task_dependencies = $wpdb->prefix . 'persian_oa_task_dependencies';
        $sql_task_dependencies = "CREATE TABLE $table_task_dependencies (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    task_id bigint(20) UNSIGNED NOT NULL,
    dependency_id bigint(20) UNSIGNED NOT NULL,
    type varchar(20) DEFAULT 'finish_to_start',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY task_id (task_id),
    KEY dependency_id (dependency_id)
) $charset_collate;";

        dbDelta($sql_task_dependencies);
        
        // Meetings Table
        $table_meetings = $wpdb->prefix . 'persian_oa_meetings';
        $sql_meetings = "CREATE TABLE $table_meetings (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    title varchar(255) NOT NULL,
    description text DEFAULT NULL,
    meeting_date datetime NOT NULL,
    end_date datetime DEFAULT NULL,
    location varchar(255) DEFAULT NULL,
    organizer_id bigint(20) UNSIGNED NOT NULL,
    minutes text DEFAULT NULL,
    decisions text DEFAULT NULL,
    status varchar(20) DEFAULT 'scheduled',
    recurrence varchar(50) DEFAULT 'none',
    color varchar(20) DEFAULT '#3b82f6',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY organizer_id (organizer_id),
    KEY meeting_date (meeting_date),
    KEY status (status)
) $charset_collate;";
        
        dbDelta($sql_meetings);
        
        // Meeting Participants Table
        $table_meeting_participants = $wpdb->prefix . 'persian_oa_meeting_participants';
        $sql_meeting_participants = "CREATE TABLE $table_meeting_participants (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    meeting_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    attendance varchar(20) DEFAULT 'pending',
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY meeting_id (meeting_id),
    KEY user_id (user_id)
) $charset_collate;";
        
        dbDelta($sql_meeting_participants);
        
        // Requests Table
        $table_requests = $wpdb->prefix . 'persian_oa_requests';
        $sql_requests = "CREATE TABLE $table_requests (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    type varchar(50) NOT NULL,
    title varchar(255) NOT NULL,
    description text DEFAULT NULL,
    requester_id bigint(20) UNSIGNED NOT NULL,
    status varchar(20) DEFAULT 'pending',
    amount decimal(15,2) DEFAULT NULL,
    from_date date DEFAULT NULL,
    to_date date DEFAULT NULL,
    attachments text DEFAULT NULL,
    workflow_step int DEFAULT 1,
    current_approver bigint(20) UNSIGNED DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY requester_id (requester_id),
    KEY status (status),
    KEY type (type),
    KEY current_approver (current_approver)
) $charset_collate;";
        
        dbDelta($sql_requests);
        
        // Workflow Approvals Table
        $table_workflow_approvals = $wpdb->prefix . 'persian_oa_workflow_approvals';
        $sql_workflow_approvals = "CREATE TABLE $table_workflow_approvals (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    request_id bigint(20) UNSIGNED NOT NULL,
    step_number int NOT NULL,
    approver_id bigint(20) UNSIGNED NOT NULL,
    status varchar(20) DEFAULT 'pending',
    comments text DEFAULT NULL,
    approved_at datetime DEFAULT NULL,
    PRIMARY KEY (id),
    KEY request_id (request_id),
    KEY approver_id (approver_id),
    KEY status (status)
) $charset_collate;";
        
        dbDelta($sql_workflow_approvals);
        
        // Comments Table
        $table_comments = $wpdb->prefix . 'persian_oa_comments';
        $sql_comments = "CREATE TABLE $table_comments (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    correspondence_id bigint(20) UNSIGNED NOT NULL,
    parent_id bigint(20) UNSIGNED DEFAULT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    comment text NOT NULL,
    is_private tinyint(1) DEFAULT 0,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY correspondence_id (correspondence_id),
    KEY parent_id (parent_id),
    KEY user_id (user_id)
) $charset_collate;";
        
        dbDelta($sql_comments);
        
        // Notifications Table
        $table_notifications = $wpdb->prefix . 'persian_oa_notifications';
        $sql_notifications = "CREATE TABLE $table_notifications (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    type varchar(50) NOT NULL,
    title varchar(255) NOT NULL,
    message text DEFAULT NULL,
    link varchar(500) DEFAULT NULL,
    is_read tinyint(1) DEFAULT 0,
    read_at datetime DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY user_id (user_id),
    KEY is_read (is_read),
    KEY type (type)
) $charset_collate;";
        
        dbDelta($sql_notifications);
        
        // Starred Items Table
        $table_starred = $wpdb->prefix . 'persian_oa_starred';
        $sql_starred = "CREATE TABLE $table_starred (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    correspondence_id bigint(20) UNSIGNED NOT NULL,
    color varchar(20) DEFAULT 'gold',
    note text DEFAULT NULL,
    created_at datetime DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY user_correspondence (user_id, correspondence_id),
    KEY user_id (user_id),
    KEY correspondence_id (correspondence_id)
) $charset_collate;";
        
        dbDelta($sql_starred);
        
        // Read Receipts Table
        $table_read_receipts = $wpdb->prefix . 'persian_oa_read_receipts';
        $sql_read_receipts = "CREATE TABLE $table_read_receipts (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    correspondence_id bigint(20) UNSIGNED NOT NULL,
    user_id bigint(20) UNSIGNED NOT NULL,
    read_at datetime DEFAULT CURRENT_TIMESTAMP,
    ip_address varchar(50) DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_correspondence_read (user_id, correspondence_id),
    KEY correspondence_id (correspondence_id),
    KEY user_id (user_id)
) $charset_collate;";
        
        dbDelta($sql_read_receipts);
        
        // User Settings Table
        $table_user_settings = $wpdb->prefix . 'persian_oa_user_settings';
        $sql_user_settings = "CREATE TABLE $table_user_settings (
    id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id bigint(20) UNSIGNED NOT NULL,
    theme varchar(20) DEFAULT 'light',
    notification_email tinyint(1) DEFAULT 1,
    notification_desktop tinyint(1) DEFAULT 1,
    notification_sms tinyint(1) DEFAULT 0,
    signature text DEFAULT NULL,
    dashboard_layout text DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY user_id (user_id)
) $charset_collate;";
        
        dbDelta($sql_user_settings);
        
        // Update version option
        update_option('persian_oa_db_version', PERSIAN_OA_VERSION);
    }
    
    /**
     * Drop all tables (for uninstall)
     */
    public static function dropTables() {
        global $wpdb;
        
$tables = [
    $wpdb->prefix . 'persian_oa_correspondence',
    $wpdb->prefix . 'persian_oa_attachments',
    $wpdb->prefix . 'persian_oa_referrals',
    $wpdb->prefix . 'persian_oa_cc_recipients',
    $wpdb->prefix . 'persian_oa_audit_log',
    $wpdb->prefix . 'persian_oa_tasks',
    $wpdb->prefix . 'persian_oa_meetings',
    $wpdb->prefix . 'persian_oa_meeting_participants',
    $wpdb->prefix . 'persian_oa_requests',
    $wpdb->prefix . 'persian_oa_workflow_approvals',
    $wpdb->prefix . 'persian_oa_comments',
    $wpdb->prefix . 'persian_oa_notifications',
    $wpdb->prefix . 'persian_oa_starred',
    $wpdb->prefix . 'persian_oa_read_receipts',
    $wpdb->prefix . 'persian_oa_user_settings'
];
        
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Uninstall: drop tables from whitelist.
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        
        delete_option('persian_oa_db_version');
    }
}






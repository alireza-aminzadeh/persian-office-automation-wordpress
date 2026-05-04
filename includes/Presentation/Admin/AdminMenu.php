<?php
/**
 * Admin menu - loaded by plugin bootstrap only.
 * phpcs:ignore PluginCheck.Security.MissingDirectFileAccessProtection -- ABSPATH check follows namespace (PHP requires namespace first).
 */
namespace PersianOfficeAutomation\Presentation\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PersianOfficeAutomation\Presentation\Controllers\IncomingLetterController;
use PersianOfficeAutomation\Presentation\Controllers\OutgoingLetterController;
use PersianOfficeAutomation\Presentation\Controllers\TaskController;
use PersianOfficeAutomation\Presentation\Controllers\MeetingController;
use PersianOfficeAutomation\Presentation\Controllers\CalendarController;
use PersianOfficeAutomation\Presentation\Controllers\InternalLetterController;
use PersianOfficeAutomation\Presentation\Controllers\ReportController;
use PersianOfficeAutomation\Presentation\Controllers\OrgChartController;
use PersianOfficeAutomation\Presentation\Controllers\CartableController;
use PersianOfficeAutomation\Presentation\Controllers\SettingsController;

class AdminMenu {
    
    private $incomingController;
    private $outgoingController;
    private $taskController;
    private $meetingController;
    private $calendarController;
    private $internalController;
    private $reportController;
    private $orgChartController;
    private $cartableController;
    private $settingsController;
    
    public function __construct() {
        $this->incomingController = new IncomingLetterController();
        $this->outgoingController = new OutgoingLetterController();
        $this->taskController = new TaskController();
        $this->meetingController = new MeetingController();
        $this->calendarController = new CalendarController();
        $this->internalController = new InternalLetterController();
        $this->reportController = new ReportController();
        $this->orgChartController = new OrgChartController();
        $this->cartableController = new CartableController();
        $this->settingsController = new SettingsController();
        
        add_action('admin_menu', [$this, 'registerMenus']);
        add_action('admin_post_persian_oa_save_incoming_letter', [$this->incomingController, 'handleSubmit']);
        add_action('admin_post_persian_oa_save_outgoing_letter', [$this->outgoingController, 'handleSubmit']);
        add_action('admin_post_persian_oa_delete_outgoing_letter', [$this->outgoingController, 'handleDelete']);
        add_action('admin_post_persian_oa_create_task', [$this->taskController, 'handleCreate']);
        add_action('admin_post_persian_oa_edit_task', [$this->taskController, 'handleEdit']);
        add_action('admin_post_persian_oa_create_meeting', [$this->meetingController, 'handleCreate']);
        add_action('admin_post_persian_oa_update_meeting', [$this->meetingController, 'handleEdit']);
        add_action('admin_post_persian_oa_delete_meeting', [$this->meetingController, 'handleDelete']);
        add_action('admin_post_persian_oa_save_referral', [$this->incomingController, 'handleReferral']);
        add_action('admin_post_persian_oa_save_general_settings', [$this->settingsController, 'handleGeneralSettings']);
        add_action('admin_post_persian_oa_save_upload_settings', [$this->settingsController, 'handleUploadSettings']);
        add_action('admin_post_persian_oa_save_workflow_settings', [$this->settingsController, 'handleWorkflowSettings']);
        add_action('admin_post_persian_oa_save_category_settings', [$this->settingsController, 'handleCategorySettings']);
        add_action('admin_post_persian_oa_create_internal_letter', [$this->internalController, 'handleCreate']);
        
        // AJAX actions for cartable
        add_action('wp_ajax_persian_oa_toggle_star', [$this->cartableController, 'ajaxToggleStar']);
        add_action('wp_ajax_persian_oa_mark_as_read', [$this->cartableController, 'ajaxMarkAsRead']);
        add_action('wp_ajax_persian_oa_get_unread_count', [$this->cartableController, 'ajaxGetUnreadCount']);
        add_action('wp_ajax_persian_oa_get_notifications', [$this->cartableController, 'ajaxGetNotifications']);
        add_action('wp_ajax_persian_oa_mark_notification_as_read', [$this->cartableController, 'ajaxMarkNotificationAsRead']);
        add_action('wp_ajax_persian_oa_mark_all_notifications_read', [$this->cartableController, 'ajaxMarkAllNotificationsAsRead']);
        add_action('wp_ajax_persian_oa_get_circulation_history', [$this->cartableController, 'ajaxGetCirculationHistory']);

        // AJAX actions for tasks
        add_action('wp_ajax_persian_oa_task_update_status', [$this->taskController, 'ajaxUpdateStatus']);
        add_action('wp_ajax_persian_oa_task_update_checklist', [$this->taskController, 'ajaxUpdateChecklist']);
        add_action('wp_ajax_persian_oa_task_delete', [$this->taskController, 'ajaxDelete']);
        add_action('wp_ajax_persian_oa_task_add_comment', [$this->taskController, 'ajaxAddComment']);
        add_action('wp_ajax_persian_oa_task_add_time_log', [$this->taskController, 'ajaxAddTimeLog']);
        add_action('wp_ajax_persian_oa_task_update_description', [$this->taskController, 'ajaxUpdateDescription']);
    }

    public function registerMenus() {
        // Main menu
        add_menu_page(
            'دبیرخانه اتوماسیون',
            'اتوماسیون اداری',
            'read',
            'office-automation',
            [$this, 'renderDashboard'],
            'dashicons-clipboard',
            30
        );

        add_submenu_page('office-automation', 'داشبورد', '📊 داشبورد', 'read', 'office-automation', [$this, 'renderDashboard']);
        
        // Cartable Menu
        add_submenu_page('office-automation', 'صندوق ورودی', '📥 صندوق ورودی', 'read', 'persian-oa-cartable-inbox', [$this, 'renderCartableInbox']);
        
        // Incoming letters - with two menu slugs for compatibility
        add_submenu_page('office-automation', 'ثبت نامه وارده', '➕ ثبت نامه وارده', 'read', 'persian-oa-incoming', [$this, 'renderIncomingNew']);
        add_submenu_page('office-automation', 'نامه‌های وارده', '📨 نامه‌های وارده', 'read', 'persian-oa-incoming-letters', [$this, 'renderIncoming']);
        
        add_submenu_page('office-automation', 'نامه‌های صادره', '📄 نامه‌های صادره', 'read', 'persian-oa-outgoing', [$this, 'renderOutgoing']);
        add_submenu_page('office-automation', 'مدیریت وظایف', '☑️ وظایف', 'read', 'persian-oa-tasks', [$this, 'renderTasks']);
        add_submenu_page('office-automation', 'مدیریت جلسات', '📅 جلسات', 'read', 'persian-oa-meetings', [$this, 'renderMeetings']);
        add_submenu_page('office-automation', 'مکاتبات داخلی', '📝 داخلی', 'read', 'persian-oa-internal', [$this->internalController, 'renderList']);
        add_submenu_page('office-automation', 'تقویم', '📅 تقویم', 'read', 'persian-oa-calendar', [$this->calendarController, 'render']);
        add_submenu_page('office-automation', 'گزارشات', '📈 گزارشات', 'manage_options', 'persian-oa-reports', [$this->reportController, 'render']);
        // add_submenu_page('office-automation', 'چارت سازمانی', '🌳 چارت', 'manage_options', 'persian-oa-org-chart', [$this->orgChartController, 'render']);

        // Secondary Cartable Items
        add_submenu_page('office-automation', 'ارسالی‌های من', '📤 ارسالی‌های من', 'read', 'persian-oa-cartable-sent', [$this, 'renderCartableSent']);
        add_submenu_page('office-automation', 'در انتظار', '⏳ در انتظار', 'read', 'persian-oa-cartable-pending', [$this, 'renderCartablePending']);
        add_submenu_page('office-automation', 'ستاره‌دار', '⭐ ستاره‌دار', 'read', 'persian-oa-cartable-starred', [$this, 'renderCartableStarred']);
        add_submenu_page('office-automation', 'آرشیو', '🗄️ آرشیو', 'read', 'persian-oa-cartable-archive', [$this, 'renderCartableArchive']);
        
        add_submenu_page('office-automation', 'مدیریت کاربران', '👥 کاربران', 'manage_options', 'persian-oa-users', [$this, 'renderUsers']);
        add_submenu_page('office-automation', 'تنظیمات', '⚙️ تنظیمات', 'manage_options', 'persian-oa-settings', [$this, 'renderSettings']);
    }

    public function renderDashboard() { 
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/dashboard.php'; 
    }
    
    public function renderIncoming() { 
        // Check basic view permission (persian_oa_view_letter from RoleService; manage_options for WP admins)
        if (!current_user_can('persian_oa_view_letter') && !current_user_can('manage_options')) {
            wp_die('شما مجوز دسترسی به این بخش را ندارید.');
        }
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET used for view routing; capability checked above.
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $get_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( $action === 'new' ) {
            $this->incomingController->renderForm();
        } elseif ( ( $action === 'edit' || $action === 'view' ) && $get_id ) {
            $this->incomingController->renderForm();
        } else {
            $this->incomingController->renderList();
        }
    }
    
    public function renderIncomingNew() {
        // This is specifically for the "persian-oa-incoming" page which should show the form
        // Check basic view permission (persian_oa_view_letter from RoleService; manage_options for WP admins)
        if (!current_user_can('persian_oa_view_letter') && !current_user_can('manage_options')) {
            wp_die('شما مجوز دسترسی به این بخش را ندارید.');
        }
        
        // Always show form for this page
        $this->incomingController->renderForm();
    }
    
    public function renderOutgoing() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET used for view routing; admin-only.
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $get_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( $action === 'new' ) {
            $this->outgoingController->renderForm();
        } elseif ( ( $action === 'view' || $action === 'edit' ) && $get_id ) {
            $this->outgoingController->renderForm();
        } else {
            $this->outgoingController->renderList();
        }
    }
    
    public function renderTasks() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET used for view routing; admin-only.
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        if ( $action === 'new' ) {
            $this->taskController->renderCreate();
        } else {
            $this->taskController->renderList();
        }
    }

    public function renderMeetings() {
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET used for view routing; admin-only.
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $get_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        if ( $action === 'new' ) {
            $this->meetingController->renderCreate();
        } elseif ( $action === 'edit' && $get_id ) {
            $this->meetingController->renderEdit();
        } else {
            $this->meetingController->renderList();
        }
    }
    
    public function renderUsers() { 
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/users.php'; 
    }
    
    public function renderSettings() {
        $this->settingsController->renderSettings();
    }
    
    // Cartable Views
    public function renderCartableInbox() {
        $this->cartableController->renderInbox();
    }
    
    public function renderCartableSent() {
        $this->cartableController->renderSent();
    }
    
    public function renderCartablePending() {
        $this->cartableController->renderPending();
    }
    
    public function renderCartableStarred() {
        $this->cartableController->renderStarred();
    }
    
    public function renderCartableArchive() {
        $this->cartableController->renderArchive();
    }
}


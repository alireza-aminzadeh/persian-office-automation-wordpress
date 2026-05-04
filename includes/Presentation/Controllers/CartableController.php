<?php
/**
 * Cartable Controller
 * 
 * @package PersianOfficeAutomation\Presentation\Controllers
 */

namespace PersianOfficeAutomation\Presentation\Controllers;

use PersianOfficeAutomation\Application\Services\CartableService;
use PersianOfficeAutomation\Application\Services\NotificationService;
use PersianOfficeAutomation\Application\Services\CorrespondenceService;
use PersianOfficeAutomation\Infrastructure\Repository\CorrespondenceRepository;

class CartableController {
    
    /**
     * Render inbox view
     */
    public function renderInbox() {
        $userId = get_current_user_id();
        
        $has_filter = isset($_GET['priority']) || isset($_GET['status']) || isset($_GET['unread']) || isset($_GET['starred']) || isset($_GET['s']);
        if ( $has_filter ) {
            if ( ! isset( $_GET['persian_oa_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['persian_oa_filter_nonce'] ) ), 'persian_oa_filter_inbox' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ) );
            }
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce checked above.
        $filters = [
            'priority' => isset( $_GET['priority'] ) ? sanitize_text_field( wp_unslash( $_GET['priority'] ) ) : '',
            'status'   => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
            'unread'   => isset( $_GET['unread'] ) ? 1 : 0,
            'starred'  => isset( $_GET['starred'] ) ? 1 : 0,
            'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
        ];
        $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        
        // Get inbox items
        $items = CartableService::getInbox($userId, $filters, $page, 20);
        $totalCount = CartableService::getInboxCount($userId);
        $unreadCount = CartableService::getInboxCount($userId, true);
        
        // Load view
        include PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/cartable/inbox.php';
    }
    
    /**
     * Render sent items view (15 per page with pagination)
     */
    public function renderSent() {
        $userId = get_current_user_id();
        $perPage = 15;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET used for pagination; sanitized.
        $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $totalCount = CartableService::getSentItemsCount($userId);
        $totalPages = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1;
        $page = min($page, $totalPages);
        
        $items = CartableService::getSentItems($userId, $page, $perPage);
        $pagination = [
            'total'        => $totalCount,
            'total_pages'  => $totalPages,
            'current_page' => $page,
            'per_page'     => $perPage,
        ];
        
        include PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/cartable/sent.php';
    }
    
    /**
     * Render replied items view
     */
    public function renderReplied() {
        $userId = get_current_user_id();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET used for pagination; sanitized.
        $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $items = CartableService::getRepliedItems($userId, $page, 20);
        
        include PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/cartable/replied.php';
    }
    
    /**
     * Render pending items view
     */
    public function renderPending() {
        $userId = get_current_user_id();
        $perPage = 20;
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET used for pagination; sanitized.
        $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $totalCount = CartableService::getPendingCount($userId);
        $items = CartableService::getPendingItems($userId, $page, $perPage);
        $totalPages = $totalCount > 0 ? (int) ceil($totalCount / $perPage) : 1;
        
        include PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/cartable/pending.php';
    }
    
    /**
     * Render starred items view
     */
    public function renderStarred() {
        $userId = get_current_user_id();
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET used for pagination; sanitized.
        $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $items = CartableService::getStarredItems($userId, $page, 20);
        
        include PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/cartable/starred.php';
    }
    
    /**
     * Render archive view
     */
    public function renderArchive() {
        $userId = get_current_user_id();
        
        $has_filter = isset($_GET['date_from']) || isset($_GET['date_to']) || isset($_GET['category']) || isset($_GET['s']);
        if ( $has_filter ) {
            if ( ! isset( $_GET['persian_oa_filter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['persian_oa_filter_nonce'] ) ), 'persian_oa_filter_archive' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ) );
            }
        }

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce checked above.
        $filters = [
            'date_from' => isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '',
            'date_to'   => isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '',
            'category'  => isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : '',
            'search'    => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
        ];
        $page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $items = CartableService::getArchiveItems($userId, $filters, $page, 20);
        
        include PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/cartable/archive.php';
    }
    
    /**
     * Handle AJAX - Toggle star
     */
    public function ajaxToggleStar() {
        check_ajax_referer('persian_oa_cartable_nonce', 'nonce');
        
        $correspondenceId = isset($_POST['correspondence_id']) ? absint(wp_unslash($_POST['correspondence_id'])) : 0;
        $userId = get_current_user_id();
        
        if (!$correspondenceId || !$userId) {
            wp_send_json_error(['message' => 'داده‌های نامعتبر']);
        }
        
        $result = CartableService::toggleStar($correspondenceId, $userId);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'با موفقیت انجام شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در انجام عملیات']);
        }
    }
    
    /**
     * Handle AJAX - Mark as read
     */
    public function ajaxMarkAsRead() {
        check_ajax_referer('persian_oa_cartable_nonce', 'nonce');
        
        $correspondenceId = isset($_POST['correspondence_id']) ? absint(wp_unslash($_POST['correspondence_id'])) : 0;
        $userId = get_current_user_id();
        
        if (!$correspondenceId || !$userId) {
            wp_send_json_error(['message' => 'داده‌های نامعتبر']);
        }
        
        $result = CartableService::markAsRead($correspondenceId, $userId);
        
        if ($result) {
            wp_send_json_success(['message' => 'با موفقیت انجام شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در انجام عملیات']);
        }
    }
    
    /**
     * Handle AJAX - Get unread count
     */
    public function ajaxGetUnreadCount() {
        $userId = get_current_user_id();
        
        $count = CartableService::getInboxCount($userId, true);
        
        wp_send_json_success(['count' => $count]);
    }
    
    /**
     * Handle AJAX - Get notifications
     */
    public function ajaxGetNotifications() {
        $userId = get_current_user_id();
        
        $notifications = NotificationService::getUnread($userId, 10);
        $count = NotificationService::getUnreadCount($userId);
        
        wp_send_json_success([
            'notifications' => $notifications,
            'count' => $count
        ]);
    }
    
    /**
     * Handle AJAX - Mark notification as read
     */
    public function ajaxMarkNotificationAsRead() {
        check_ajax_referer('persian_oa_cartable_nonce', 'nonce');
        
        $notificationId = isset($_POST['notification_id']) ? absint(wp_unslash($_POST['notification_id'])) : 0;
        $userId = get_current_user_id();
        
        if (!$notificationId || !$userId) {
            wp_send_json_error(['message' => 'داده‌های نامعتبر']);
        }
        
        $result = NotificationService::markAsRead($notificationId, $userId);
        
        if ($result !== false) {
            wp_send_json_success(['message' => 'با موفقیت انجام شد']);
        } else {
            wp_send_json_error(['message' => 'خطا در انجام عملیات']);
        }
    }

    /**
     * Handle AJAX - Mark all notifications as read (GET for admin bar link, then redirect)
     */
    public function ajaxMarkAllNotificationsAsRead() {
        if ( ! get_current_user_id() ) {
            wp_safe_redirect( admin_url() );
            exit;
        }
        if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['nonce'] ) ), 'persian_oa_cartable_nonce' ) ) {
            wp_die( esc_html__( 'امنیت نامعتبر است.', 'persian-office-automation' ), 403 );
        }
        NotificationService::markAllAsRead( get_current_user_id() );
        $redirect = isset( $_GET['redirect'] ) ? sanitize_url( wp_unslash( $_GET['redirect'] ) ) : admin_url( 'admin.php?page=persian-oa-cartable-inbox' );
        if ( ! $redirect || strpos( $redirect, admin_url() ) !== 0 ) {
            $redirect = admin_url( 'admin.php?page=persian-oa-cartable-inbox' );
        }
        wp_safe_redirect( $redirect );
        exit;
    }
    
    /**
     * Handle AJAX - Get circulation history for graph
     */
    public function ajaxGetCirculationHistory() {
        check_ajax_referer('persian_oa_cartable_nonce', 'nonce');
        
        $correspondenceId = isset($_POST['correspondence_id']) ? absint(wp_unslash($_POST['correspondence_id'])) : 0;
        
        if (!$correspondenceId) {
            wp_send_json_error(['message' => 'شناسه نامه نامعتبر است']);
        }
        
        // Initialize service
        $repository = new CorrespondenceRepository();
        $service = new CorrespondenceService($repository);
        
        $history = $service->getCirculationHistory($correspondenceId);
        
        wp_send_json_success(['history' => $history]);
    }
}


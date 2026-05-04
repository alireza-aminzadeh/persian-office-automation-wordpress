<?php
/**
 * Admin Bar Notifications
 * Adds a beautiful notifications dropdown to the WordPress admin bar.
 *
 * @package PersianOfficeAutomation\Presentation\Admin
 */

namespace PersianOfficeAutomation\Presentation\Admin;

use PersianOfficeAutomation\Application\Services\NotificationService;
use PersianOfficeAutomation\Common\JalaliDate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AdminBarNotifications {

	private const MAX_ITEMS = 10;
	private const NODE_ID   = 'persian-oa-notifications';

	public function __construct() {
		add_action( 'admin_bar_menu', [ $this, 'addNotificationsToAdminBar' ], 999 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueueAssetsFrontend' ] );
	}

	/**
	 * Add notifications node and children to the admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar
	 */
	public function addNotificationsToAdminBar( $wp_admin_bar ) {
		$userId = get_current_user_id();
		if ( ! $userId ) {
			return;
		}

		$count = NotificationService::getUnreadCount( $userId );
		$notifications = NotificationService::getUnread( $userId, self::MAX_ITEMS );

		$title = $this->buildTitle( $count );
		$wp_admin_bar->add_node( [
			'id'    => self::NODE_ID,
			'title' => $title,
			'href'  => admin_url( 'admin.php?page=persian-oa-cartable-inbox' ),
			'meta'  => [
				'class' => 'persian-oa-admin-bar-notifications',
				'html'  => true,
			],
		] );

		// Empty state
		if ( $count === 0 ) {
			$wp_admin_bar->add_node( [
				'parent' => self::NODE_ID,
				'id'     => 'persian-oa-notifications-empty',
				'title'  => '<span class="persian-oa-ab-notice persian-oa-ab-notice--empty">اعلان جدیدی ندارید</span>',
				'href'   => '#',
				'meta'   => [ 'class' => 'persian-oa-ab-empty', 'html' => true ],
			] );
		} else {
			foreach ( $notifications as $n ) {
				$itemTitle = $this->buildNotificationItemTitle( $n );
				$href = ! empty( $n->link ) ? admin_url( $n->link ) : admin_url( 'admin.php?page=persian-oa-cartable-inbox' );
				$wp_admin_bar->add_node( [
					'parent' => self::NODE_ID,
					'id'     => 'persian-oa-notification-' . (int) $n->id,
					'title'  => $itemTitle,
					'href'   => esc_url( $href ),
					'meta'   => [
						'class' => 'persian-oa-ab-notification-item',
						'title' => esc_attr( wp_strip_all_tags( $n->message ) ),
						'html'  => true,
					],
				] );
			}
		}

		// Footer: View all + Mark all read
		$viewAllUrl = admin_url( 'admin.php?page=persian-oa-cartable-inbox' );
		$redirect = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : $viewAllUrl;
		$markAllUrl = wp_nonce_url(
			admin_url( 'admin-ajax.php?action=persian_oa_mark_all_notifications_read&redirect=' . rawurlencode( $redirect ) ),
			'persian_oa_cartable_nonce',
			'nonce'
		);

		$wp_admin_bar->add_node( [
			'parent' => self::NODE_ID,
			'id'     => 'persian-oa-notifications-view-all',
			'title'  => '<span class="persian-oa-ab-footer-link">📥 مشاهده صندوق ورودی</span>',
			'href'   => $viewAllUrl,
			'meta'   => [ 'class' => 'persian-oa-ab-footer', 'html' => true ],
		] );

		if ( $count > 0 ) {
			$wp_admin_bar->add_node( [
				'parent' => self::NODE_ID,
				'id'     => 'persian-oa-notifications-mark-all',
				'title'  => '<span class="persian-oa-ab-footer-link persian-oa-ab-mark-all">✓ همه را خواندم</span>',
				'href'   => $markAllUrl,
				'meta'   => [ 'class' => 'persian-oa-ab-footer', 'html' => true ],
			] );
		}
	}

	/**
	 * Build the main admin bar title (icon + badge).
	 */
	private function buildTitle( int $count ): string {
		$badge = $count > 0
			? '<span class="persian-oa-ab-badge">' . min( $count, 99 ) . '</span>'
			: '';
		return '<span class="persian-oa-ab-icon" aria-hidden="true">🔔</span>' . $badge . '<span class="persian-oa-ab-label">اعلان‌ها</span>';
	}

	/**
	 * Build a single notification list item HTML.
	 */
	private function buildNotificationItemTitle( object $n ): string {
		$timeAgo = JalaliDate::timeAgo( $n->created_at );
		$title = esc_html( $n->title );
		$msg = wp_trim_words( wp_strip_all_tags( $n->message ), 8 );
		return '<span class="persian-oa-ab-notification-title">' . $title . '</span><span class="persian-oa-ab-notification-meta">' . esc_html( $timeAgo ) . '</span><span class="persian-oa-ab-notification-preview">' . esc_html( $msg ) . '</span>';
	}

	/**
	 * Enqueue styles and script in admin.
	 */
	public function enqueueAssets( $hook ) {
		$this->enqueueAdminBarAssets();
	}

	/**
	 * Enqueue on frontend when admin bar is showing.
	 */
	public function enqueueAssetsFrontend() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}
		$this->enqueueAdminBarAssets();
	}

	private function enqueueAdminBarAssets() {
		wp_enqueue_style(
			'vazirmatn',
			PERSIAN_OA_ASSETS_URL . 'fonts/vazirmatn/style.css',
			[],
			'33.003'
		);
		wp_enqueue_style(
			'persian-oa-admin-bar-notifications',
			PERSIAN_OA_ASSETS_URL . 'css/admin-bar-notifications.css',
			[ 'vazirmatn' ],
			PERSIAN_OA_VERSION
		);
		wp_enqueue_script(
			'persian-oa-admin-bar-notifications',
			PERSIAN_OA_ASSETS_URL . 'js/admin-bar-notifications.js',
			[ 'jquery' ],
			PERSIAN_OA_VERSION,
			true
		);
		wp_localize_script( 'persian-oa-admin-bar-notifications', 'persianOaAdminBar', [
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'persian_oa_cartable_nonce' ),
		] );
	}
}

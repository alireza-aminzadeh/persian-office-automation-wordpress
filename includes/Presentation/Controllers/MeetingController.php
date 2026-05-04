<?php
/**
 * Meeting Controller
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified in form handlers; GET used for display only and sanitized.
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Presentation\Controllers;

use PersianOfficeAutomation\Application\DTO\MeetingDTO;
use PersianOfficeAutomation\Application\Services\MeetingService;
use PersianOfficeAutomation\Infrastructure\Repository\MeetingRepository;

class MeetingController {
    
    private $service;
    
    public function __construct() {
        $repository = new MeetingRepository();
        $this->service = new MeetingService($repository);
    }
    
    /**
     * Handle create meeting form submission
     */
    public function handleCreate() {
        if (!current_user_can('read')) {
            wp_die('شما مجوز دسترسی به این بخش را ندارید.');
        }
        if (!isset($_POST['persian_oa_meeting_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['persian_oa_meeting_nonce'])), 'persian_oa_create_meeting')) {
            wp_die('امنیت فرم تایید نشد.');
        }
        
        $data = [
            'id' => isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : null,
            'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
            'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
            'meeting_date' => isset($_POST['meeting_date']) ? sanitize_text_field(wp_unslash($_POST['meeting_date'])) : '',
            'meeting_date_gregorian' => isset($_POST['meeting_date_gregorian']) ? sanitize_text_field(wp_unslash($_POST['meeting_date_gregorian'])) : '',
            'end_date_gregorian' => isset($_POST['end_date_gregorian']) ? sanitize_text_field(wp_unslash($_POST['end_date_gregorian'])) : '',
            'duration' => isset($_POST['duration']) ? sanitize_text_field(wp_unslash($_POST['duration'])) : '',
            'location' => isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '',
            'participants' => isset($_POST['participants']) && is_array($_POST['participants']) ? array_map('sanitize_text_field', wp_unslash($_POST['participants'])) : [],
            'status' => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'scheduled',
            'recurrence' => isset($_POST['recurrence']) ? sanitize_text_field(wp_unslash($_POST['recurrence'])) : 'none',
            'color' => isset($_POST['color']) ? sanitize_text_field(wp_unslash($_POST['color'])) : '#3b82f6'
        ];
        $dto = MeetingDTO::fromRequest($data);
        $result = $this->service->createMeeting($dto);
        
        if ($result['success']) {
            if (isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'calendar') {
                wp_safe_redirect( add_query_arg( array( 'page' => 'persian-oa-calendar', 'message' => 'created' ), admin_url( 'admin.php' ) ) );
                exit;
            }
            
            wp_safe_redirect( add_query_arg( array(
				'page'                  => 'persian-oa-meetings',
				'message'               => 'created',
				'persian_oa_list_nonce' => wp_create_nonce( 'persian_oa_meetings_list' ),
			), admin_url( 'admin.php' ) ) );
            exit;
        }
        
        $errors = isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : ['general' => 'خطا در ثبت جلسه.'];
        set_transient('persian_oa_meeting_create_errors', $errors, 60);
        $redirect_url = add_query_arg([
            'page' => 'persian-oa-meetings',
            'action' => 'new',
            'error' => '1'
        ], admin_url('admin.php'));
        if (!empty($_POST['redirect_to'])) {
            $redirect_url = add_query_arg('redirect_to', sanitize_text_field(wp_unslash($_POST['redirect_to'])), $redirect_url);
        }
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Nonce first: valid persian_oa_list_nonce, or no paged/message GET without it.
     */
    private function verify_meetings_list_get_nonce(): void {
        if ( isset( $_GET['persian_oa_list_nonce'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['persian_oa_list_nonce'] ) ), 'persian_oa_meetings_list' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
            }
            return;
        }
        if ( isset( $_GET['paged'] ) || ( isset( $_GET['message'] ) && $_GET['message'] !== '' ) ) {
            wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
        }
    }

    /**
     * Render Meeting List (paginated, 15 per page)
     */
    public function renderList() {
        $this->verify_meetings_list_get_nonce();

        $current_user_id = get_current_user_id();
        $paged = isset($_GET['paged']) ? max(1, absint($_GET['paged'])) : 1;
        $result = $this->service->getUserMeetingsPaginated($current_user_id, $paged, 15);

        $meetings = $result['meetings'];
        $pagination = [
            'total'        => $result['total'],
            'total_pages'  => $result['total_pages'],
            'current_page' => $result['current_page'],
            'per_page'     => $result['per_page'],
        ];

        $repository = new MeetingRepository();
        $participantsByMeeting = [];
        foreach ($meetings as $meeting) {
            $participantsByMeeting[$meeting->getId()] = $repository->getParticipants($meeting->getId());
        }

        $list_message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/meetings/list.php';
    }
    
    /**
     * Render Create Meeting Form
     */
    public function renderCreate() {
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/meetings/create.php';
    }

    /**
     * Render Edit Meeting Form (GET must include _wpnonce from wp_nonce_url(..., 'persian_oa_meeting_edit')).
     */
    public function renderEdit() {
        check_admin_referer( 'persian_oa_meeting_edit' );
        $id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        if (!$id) {
            wp_safe_redirect(admin_url('admin.php?page=persian-oa-meetings'));
            exit;
        }
        $current_user_id = get_current_user_id();
        $meeting = $this->service->getMeetingById($id, $current_user_id);
        if (!$meeting) {
            wp_die('جلسه یافت نشد یا شما مجوز ویرایش آن را ندارید.');
        }
        $repository = new MeetingRepository();
        $participants = $repository->getParticipants($id);
        $participantIds = array_map(function ($p) {
            return (int) (is_object($p) ? $p->user_id : $p['user_id']);
        }, $participants);
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/meetings/edit.php';
    }

    /**
     * Handle update meeting form submission
     */
    public function handleEdit() {
        if (!current_user_can('read')) {
            wp_die('شما مجوز دسترسی به این بخش را ندارید.');
        }
        if (!isset($_POST['persian_oa_meeting_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['persian_oa_meeting_nonce'])), 'persian_oa_update_meeting')) {
            wp_die('امنیت فرم تایید نشد.');
        }
        $data = [
            'id' => isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : null,
            'title' => isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '',
            'description' => isset($_POST['description']) ? wp_kses_post(wp_unslash($_POST['description'])) : '',
            'meeting_date' => isset($_POST['meeting_date']) ? sanitize_text_field(wp_unslash($_POST['meeting_date'])) : '',
            'meeting_date_gregorian' => isset($_POST['meeting_date_gregorian']) ? sanitize_text_field(wp_unslash($_POST['meeting_date_gregorian'])) : '',
            'end_date_gregorian' => isset($_POST['end_date_gregorian']) ? sanitize_text_field(wp_unslash($_POST['end_date_gregorian'])) : '',
            'duration' => isset($_POST['duration']) ? sanitize_text_field(wp_unslash($_POST['duration'])) : '',
            'location' => isset($_POST['location']) ? sanitize_text_field(wp_unslash($_POST['location'])) : '',
            'participants' => isset($_POST['participants']) && is_array($_POST['participants']) ? array_map('sanitize_text_field', wp_unslash($_POST['participants'])) : [],
            'status' => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'scheduled',
            'recurrence' => isset($_POST['recurrence']) ? sanitize_text_field(wp_unslash($_POST['recurrence'])) : 'none',
            'color' => isset($_POST['color']) ? sanitize_text_field(wp_unslash($_POST['color'])) : '#3b82f6'
        ];
        $dto = MeetingDTO::fromRequest($data);
        $result = $this->service->updateMeeting($dto);
        if ($result['success']) {
            wp_safe_redirect( add_query_arg( [
				'page'                  => 'persian-oa-meetings',
				'message'               => 'updated',
				'persian_oa_list_nonce' => wp_create_nonce( 'persian_oa_meetings_list' ),
			], admin_url( 'admin.php' ) ) );
            exit;
        }
        $errors = isset($result['errors']) && is_array($result['errors']) ? $result['errors'] : ['general' => 'خطا در به‌روزرسانی جلسه.'];
        set_transient('persian_oa_meeting_edit_errors', $errors, 60);
        $edit_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;
        $redirect_url = add_query_arg(
            [ 'page' => 'persian-oa-meetings', 'action' => 'edit', 'id' => $edit_id, 'error' => '1' ],
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( wp_nonce_url( $redirect_url, 'persian_oa_meeting_edit' ) );
        exit;
    }

    /**
     * Handle delete meeting (GET with nonce)
     */
    public function handleDelete() {
        if (!current_user_can('read')) {
            wp_die('شما مجوز دسترسی به این بخش را ندارید.');
        }
        if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'persian_oa_delete_meeting_' . absint($_GET['id']))) {
            wp_die('امنیت عملیات تایید نشد.');
        }
        $id = absint($_GET['id']);
        $userId = get_current_user_id();
        if ($this->service->deleteMeeting($id, $userId)) {
            wp_safe_redirect( add_query_arg( [
				'page'                  => 'persian-oa-meetings',
				'message'               => 'deleted',
				'persian_oa_list_nonce' => wp_create_nonce( 'persian_oa_meetings_list' ),
			], admin_url( 'admin.php' ) ) );
            exit;
        }
        wp_die('جلسه یافت نشد یا شما مجوز حذف آن را ندارید.');
    }
}



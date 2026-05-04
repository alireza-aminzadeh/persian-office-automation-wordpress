<?php
/**
 * Internal Memos Controller
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified in form handlers; GET used for display only and sanitized.
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Presentation\Controllers;

use PersianOfficeAutomation\Application\DTO\InternalLetterDTO;
use PersianOfficeAutomation\Application\Services\CorrespondenceService;
use PersianOfficeAutomation\Infrastructure\Repository\CorrespondenceRepository;
use PersianOfficeAutomation\Domain\Entity\Correspondence;
use PersianOfficeAutomation\Common\JalaliDate;

class InternalLetterController {
    
    private $service;
    private $repository;
    
    public function __construct() {
        $this->repository = new CorrespondenceRepository();
        $this->service = new CorrespondenceService($this->repository);
    }

    /**
     * List/internal inbox - GET params used for display only (action, tab, s); nonce not required for read.
     * phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display; params sanitized.
     */
    public function renderList() {
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : 'list';
        
        switch ( $action ) {
            case 'new':
                $this->renderCreate();
                break;
            case 'view':
                $this->renderView();
                break;
            default:
                $this->renderInbox();
                break;
        }
    }
    
    private function verify_internal_list_get_nonce(): void {
        if ( isset( $_GET['persian_oa_list_nonce'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['persian_oa_list_nonce'] ) ), 'persian_oa_internal_list' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
            }
            return;
        }
        if (
            ( isset( $_GET['message'] ) && $_GET['message'] !== '' )
            || ( isset( $_GET['s'] ) && (string) $_GET['s'] !== '' )
        ) {
            wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
        }
    }

    private function renderInbox() {
        $this->verify_internal_list_get_nonce();

        $activeTab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'inbox';
        $search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
        $currentUserId = get_current_user_id();
        
        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_correspondence';
        $tableCc = $wpdb->prefix . 'persian_oa_cc_recipients';
        
        $where = ["type = 'internal'"];
        $params = [];
        
        if ($activeTab === 'sent') {
            $where[] = "created_by = %d";
            $params[] = $currentUserId;
        } else {
            // Inbox: primary_recipient OR user in CC recipients (multi-recipient letters)
            $where[] = "(primary_recipient = %d OR id IN (SELECT correspondence_id FROM $tableCc WHERE user_id = %d))";
            $params[] = $currentUserId;
            $params[] = $currentUserId;
        }
        
        if ($search) {
            $where[] = "(subject LIKE %s OR content LIKE %s OR number LIKE %s)";
            $term = '%' . $wpdb->esc_like($search) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        
        $whereSql = implode( ' AND ', $where );
        $sql = "SELECT * FROM $table WHERE $whereSql ORDER BY created_at DESC LIMIT 50";
        if ( ! empty( $params ) ) {
            // Table/whereSql from prefix and esc_like; params passed to prepare.
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Safe: table from prefix, search via esc_like.
            $query = $wpdb->prepare( $sql, $params );
        } else {
            $query = $sql;
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results( $query );
        
        $letters = [];
        foreach ($results as $row) {
             $letters[] = Correspondence::fromArray((array)$row);
        }

        $message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';

        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/internal/list.php';
    }

    private function renderCreate() {
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/internal/create.php';
    }

    /**
     * Single-letter view; requires _wpnonce from wp_nonce_url(..., 'persian_oa_internal_view') on all view links.
     */
    private function renderView() {
        check_admin_referer( 'persian_oa_internal_view' );
        $id = isset( $_GET['id'] ) ? absint( wp_unslash( $_GET['id'] ) ) : 0;
        $letter = $this->service->getInternalLetter($id);
        
        if ($letter) {
            global $wpdb;
            $currentUserId = get_current_user_id();
            $tableCc = $wpdb->prefix . 'persian_oa_cc_recipients';
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
            $isCcRecipient = (int) $wpdb->get_var($wpdb->prepare(
                "SELECT 1 FROM $tableCc WHERE correspondence_id = %d AND user_id = %d LIMIT 1",
                $id,
                $currentUserId
            ));
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
            // Permission: sender, primary recipient, CC recipient, or admin
            $canView = $letter->getPrimaryRecipient() == $currentUserId
                || $letter->getCreatedBy() == $currentUserId
                || $isCcRecipient
                || current_user_can('manage_options');
            if (!$canView) {
                wp_die('شما مجوز مشاهده این نامه را ندارید.');
            }
            
            // Mark as read: primary recipient -> letter status; CC recipient -> CC row
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Tables from prefix.
            if ($letter->getPrimaryRecipient() == $currentUserId && $letter->getStatus() !== 'read') {
                $wpdb->update(
                    $wpdb->prefix . 'persian_oa_correspondence',
                    ['status' => 'read'],
                    ['id' => $id]
                );
                $letter->setStatus('read');
            } elseif ($isCcRecipient) {
                $wpdb->update(
                    $tableCc,
                    ['is_read' => 1, 'read_at' => current_time('mysql')],
                    ['correspondence_id' => $id, 'user_id' => $currentUserId]
                );
            }
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        }

        $attachments = $this->getAttachmentsForLetter($id);

        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/internal/view.php';
    }

    /**
     * Get attachments for a letter
     */
    private function getAttachmentsForLetter($correspondenceId) {
        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_attachments';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$table} WHERE correspondence_id = %d ORDER BY id ASC",
            $correspondenceId
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return is_array($results) ? $results : [];
    }

    public function handleCreate() {
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_wpnonce'])), 'persian_oa_create_internal_letter_nonce')) {
            wp_die('امنیت فرم تایید نشد.');
        }

        $data = [
            'recipient_id' => isset($_POST['recipient_id']) ? sanitize_text_field(wp_unslash($_POST['recipient_id'])) : '',
            'priority' => isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : 'normal',
            'confidentiality' => isset($_POST['confidentiality']) ? sanitize_text_field(wp_unslash($_POST['confidentiality'])) : 'normal',
            'cc_recipients' => isset($_POST['cc_recipients']) && is_array($_POST['cc_recipients']) ? array_map('sanitize_text_field', wp_unslash($_POST['cc_recipients'])) : [],
            'subject' => isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '',
            'content' => isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '',
            'submit_action' => isset($_POST['submit_action']) ? sanitize_text_field(wp_unslash($_POST['submit_action'])) : 'send'
        ];

        // Draft vs Send
        $submit_action = $data['submit_action'];
        $data['status'] = ($submit_action === 'draft') ? 'draft' : 'sent';

        // Generate internal number
        $data['letter_number'] = $this->service->generateNextInternalNumber('INT-');

        // Set date to today
        $data['letter_date'] = JalaliDate::today();

        $dto = InternalLetterDTO::fromRequest($data);
        $dto->attachments = $this->handleFileUploads();

        $result = $this->service->createInternalLetter($dto);

        if ($result['success']) {
            $message = ( $submit_action === 'draft' ) ? 'draft_saved' : 'success';
            wp_safe_redirect( add_query_arg(
                [
                    'page'                  => 'persian-oa-internal',
                    'tab'                   => 'sent',
                    'message'               => $message,
                    'persian_oa_list_nonce' => wp_create_nonce( 'persian_oa_internal_list' ),
                ],
                admin_url( 'admin.php' )
            ) );
            exit;
        } else {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- errors_escaped built via array_map( 'esc_html' ).
            $errors_escaped = array_map( 'esc_html', (array) ( $result['errors'] ?? [] ) );
            wp_die( esc_html( 'خطا: ' . implode( ', ', $errors_escaped ) ) );
        }
    }

    /**
     * Handle file uploads for internal letter attachments. Nonce verified in handleCreate() before this is called.
     *
     * phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verifies nonce.
     */
    private function handleFileUploads() {
        $attachments = [];

        if ( empty( $_FILES['attachments'] ) || ! is_array( $_FILES['attachments']['name'] ?? null ) ) {
            return $attachments;
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';

        $max_size = (int) get_option('persian_oa_max_upload_size', 10) * 1024 * 1024;
        $allowed_types = get_option('persian_oa_allowed_types', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'xls', 'xlsx']);
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Multipart array unslashed; each field individually sanitized below.
        $files = wp_unslash( $_FILES['attachments'] );
        $file_count = count($files['name']);

        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                continue;
            }
            $tmp_name = isset( $files['tmp_name'][$i] ) ? sanitize_text_field( (string) $files['tmp_name'][$i] ) : '';
            if ( empty( $tmp_name ) || ! is_uploaded_file( $tmp_name ) ) {
                continue;
            }
            $file = [
                'name'     => sanitize_file_name( isset( $files['name'][$i] ) ? (string) $files['name'][$i] : '' ),
                'type'     => isset( $files['type'][$i] ) ? sanitize_mime_type( (string) $files['type'][$i] ) : '',
                'tmp_name' => $tmp_name,
                'error'    => (int) $files['error'][$i],
                'size'     => isset( $files['size'][$i] ) ? (int) $files['size'][$i] : 0,
            ];

            if ($file['size'] > $max_size) {
                $max_mb = (int) get_option('persian_oa_max_upload_size', 10);
                wp_die( esc_html( sprintf( 'فایل "%s" بیش از حد مجاز %d مگابایت است.', $file['name'], $max_mb ) ) );
            }

            $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if ( ! in_array( $file_ext, $allowed_types, true ) ) {
                $allowed_list = array_map( 'esc_html', array_map( 'strval', (array) $allowed_types ) );
                wp_die( esc_html( sprintf( 'نوع فایل "%s" مجاز نیست. فرمت‌های مجاز: %s', esc_html( $file['name'] ), implode( ', ', $allowed_list ) ) ) );
            }

            $allowed_mimes = $this->getAllowedMimeTypes($allowed_types);
            $upload = wp_handle_upload($file, ['test_form' => false, 'mimes' => $allowed_mimes]);

            if (isset($upload['error'])) {
                wp_die('خطا در آپلود فایل: ' . esc_html($upload['error']));
            }

            if (!empty($upload['file'])) {
                $attachments[] = [
                    'name' => basename($upload['file']),
                    'path' => $upload['file'],
                    'type' => isset($upload['type']) ? $upload['type'] : '',
                    'size' => $file['size'],
                ];
            }
        }

        return $attachments;
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing

    /**
     * Get allowed MIME types for upload validation
     */
    private function getAllowedMimeTypes($extensions) {
        $mime_map = [
            'pdf'  => 'application/pdf',
            'doc'  => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls'  => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg'  => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'zip'  => 'application/zip',
        ];
        $allowed = [];
        foreach ((array) $extensions as $ext) {
            if (isset($mime_map[$ext])) {
                $allowed[$ext] = $mime_map[$ext];
            }
        }
        return $allowed;
    }
}

<?php
/**
 * Outgoing Letter Controller
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Nonce verified in form handlers; GET used for display only and sanitized.
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Presentation\Controllers;

use PersianOfficeAutomation\Application\DTO\OutgoingLetterDTO;
use PersianOfficeAutomation\Application\Services\CorrespondenceService;
use PersianOfficeAutomation\Infrastructure\Repository\CorrespondenceRepository;

class OutgoingLetterController {
    
    private $service;
    
    public function __construct() {
        $repository = new CorrespondenceRepository();
        $this->service = new CorrespondenceService($repository);
    }
    
    /**
     * Handle form submission
     */
    public function handleSubmit() {
        // Check nonce
        if (!isset($_POST['persian_oa_outgoing_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['persian_oa_outgoing_nonce'])), 'persian_oa_save_outgoing_letter')) {
            wp_die('امنیت فرم تایید نشد.');
        }
        
        // Check permissions (persian_oa_create_letter is assigned by RoleService; manage_options for WP admins)
        if (!current_user_can('persian_oa_create_letter') && !current_user_can('manage_options')) {
            wp_die('شما مجوز ایجاد نامه ندارید.');
        }
        
        // Handle file uploads
        $attachments = $this->handleFileUploads();
        
        // Create DTO
        $data = [
            'id' => isset($_POST['id']) ? sanitize_text_field(wp_unslash($_POST['id'])) : null,
            'letter_number' => isset($_POST['letter_number']) ? sanitize_text_field(wp_unslash($_POST['letter_number'])) : '',
            'letter_date' => isset($_POST['letter_date']) ? sanitize_text_field(wp_unslash($_POST['letter_date'])) : '',
            'letter_date_gregorian' => isset($_POST['letter_date_gregorian']) ? sanitize_text_field(wp_unslash($_POST['letter_date_gregorian'])) : '',
            'recipient' => isset($_POST['recipient']) ? sanitize_text_field(wp_unslash($_POST['recipient'])) : '',
            'signer' => isset($_POST['signer']) ? sanitize_text_field(wp_unslash($_POST['signer'])) : 0,
            'subject' => isset($_POST['subject']) ? sanitize_text_field(wp_unslash($_POST['subject'])) : '',
            'content' => isset($_POST['content']) ? wp_kses_post(wp_unslash($_POST['content'])) : '',
            'priority' => isset($_POST['priority']) ? sanitize_text_field(wp_unslash($_POST['priority'])) : 'normal',
            'notes' => isset($_POST['notes']) ? sanitize_textarea_field(wp_unslash($_POST['notes'])) : '',
            'status' => isset($_POST['status']) ? sanitize_text_field(wp_unslash($_POST['status'])) : 'draft'
        ];
        $dto = OutgoingLetterDTO::fromRequest($data);
        $dto->attachments = $attachments;
        
        // Determine status action
        if (isset($_POST['submit_approval'])) {
            $dto->status = 'pending';
        } elseif (isset($_POST['save_draft'])) {
            $dto->status = 'draft';
        }
        
        // Update or Create
        if (!empty($dto->id)) {
            if (!current_user_can('persian_oa_edit_letter') && !current_user_can('manage_options')) {
                wp_die('شما مجوز ویرایش نامه ندارید.');
            }
            $result = $this->service->updateOutgoingLetter($dto);
        } else {
            $result = $this->service->createOutgoingLetter($dto);
        }
        
        // Redirect with message
        if ($result['success']) {
            $redirect_url = add_query_arg([
                'page'                  => 'persian-oa-outgoing',
                'message'               => 'success',
                'persian_oa_list_nonce' => wp_create_nonce( 'persian_oa_outgoing_list' ),
            ], admin_url('admin.php'));
            
            wp_safe_redirect( $redirect_url );
            exit;
        } else {
            // Store errors in transient
            set_transient('persian_oa_outgoing_errors_' . get_current_user_id(), $result['errors'], 60);
            
            $args = ['page' => 'persian-oa-outgoing', 'error' => '1'];
            if (!empty($dto->id)) {
                $args['action'] = 'edit';
                $args['id'] = $dto->id;
            } else {
                $args['action'] = 'new';
            }
            $redirect_url = add_query_arg($args, admin_url('admin.php'));
            
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }
    
    /**
     * List/filter GET must include a valid nonce when any filter or flash parameter is present.
     */
    private function verify_outgoing_list_get_nonce(): void {
        if ( isset( $_GET['persian_oa_list_nonce'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['persian_oa_list_nonce'] ) ), 'persian_oa_outgoing_list' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
            }
            return;
        }
        if (
            ( isset( $_GET['message'] ) && $_GET['message'] !== '' )
            || ( isset( $_GET['status'] ) && $_GET['status'] !== '' )
            || ( isset( $_GET['s'] ) && (string) $_GET['s'] !== '' )
        ) {
            wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
        }
    }

    /**
     * Render list view (nonce verified here; template has no raw $_GET reads).
     */
    public function renderList(): void {
        if ( ! current_user_can( 'persian_oa_view_letter' ) && ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
        }
        $this->verify_outgoing_list_get_nonce();

        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_correspondence';

        $outgoing_message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
        $outgoing_status = isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '';
        $outgoing_search = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';

        $where = "WHERE type = 'outgoing'";
        if ( $outgoing_status !== '' ) {
            if ( in_array( $outgoing_status, [ 'draft', 'pending', 'approved', 'rejected' ], true ) ) {
                $where .= $wpdb->prepare( ' AND status = %s', $outgoing_status );
            }
        }
        if ( $outgoing_search !== '' ) {
            $search = '%' . $wpdb->esc_like( $outgoing_search ) . '%';
            $where .= $wpdb->prepare( ' AND (subject LIKE %s OR number LIKE %s)', $search, $search );
        }
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $letters = $wpdb->get_results( "SELECT * FROM $table $where ORDER BY created_at DESC LIMIT 50" );
        $total = $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE type = 'outgoing'" );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/outgoing.php';
    }
    
    /**
     * Render Form View (new, edit, or view)
     */
    public function renderForm() {
        $letter_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $get_action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
        $letter = null;
        $attachments = [];
        
        if ($letter_id) {
            if (!current_user_can('persian_oa_view_letter') && !current_user_can('manage_options')) {
                wp_die('شما مجوز دسترسی به این بخش را ندارید.');
            }
            $letter = $this->service->getOutgoingLetter($letter_id);
            if (!$letter) {
                wp_die('نامه مورد نظر یافت نشد.');
            }
            if ( $get_action === 'edit' ) {
                if (!current_user_can('persian_oa_edit_letter') && !current_user_can('manage_options')) {
                    wp_die('شما مجوز ویرایش نامه ندارید.');
                }
            }
            $attachments = $this->getAttachments($letter_id);
        } else {
            if (!current_user_can('persian_oa_create_letter') && !current_user_can('manage_options')) {
                wp_die('شما مجوز ایجاد نامه ندارید.');
            }
        }
        
        // View-only mode
        if ( $get_action === 'view' && $letter ) {
            require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/outgoing-view.php';
            return;
        }
        
        $next_number = $letter_id ? '' : $this->service->generateNextOutgoingNumber('OUT-');
        
        // Get errors from transient
        $errors = get_transient('persian_oa_outgoing_errors_' . get_current_user_id());
        if ($errors) {
            delete_transient('persian_oa_outgoing_errors_' . get_current_user_id());
        }
        if (!is_array($errors)) {
            $errors = [];
        }
        
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/outgoing-form.php';
    }
    
    /**
     * Handle delete
     */
    public function handleDelete() {
        if (!isset($_GET['id']) || !isset($_GET['_wpnonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'persian_oa_delete_outgoing_' . intval($_GET['id']))) {
            wp_die('امنیت عملیات تایید نشد.');
        }
        if (!current_user_can('persian_oa_edit_letter') && !current_user_can('manage_options')) {
            wp_die('شما مجوز حذف نامه را ندارید.');
        }
        $id = intval($_GET['id']);
        $letter = $this->service->getOutgoingLetter($id);
        if (!$letter) {
            wp_die('نامه مورد نظر یافت نشد.');
        }
        $repository = new \PersianOfficeAutomation\Infrastructure\Repository\CorrespondenceRepository();
        $repository->delete($id);
        $redirect_url = add_query_arg([
            'page'                  => 'persian-oa-outgoing',
            'message'               => 'deleted',
            'persian_oa_list_nonce' => wp_create_nonce( 'persian_oa_outgoing_list' ),
        ], admin_url('admin.php'));
        wp_safe_redirect($redirect_url);
        exit;
    }
    
    /**
     * Get attachments for a letter
     */
    private function getAttachments($correspondenceId) {
        global $wpdb;
        $tables = [$wpdb->prefix . 'persian_oa_attachments'];
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix.
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") === $table) {
                $rows = $wpdb->get_results($wpdb->prepare(
                    "SELECT * FROM {$table} WHERE correspondence_id = %d ORDER BY id ASC",
                    $correspondenceId
                ));
                // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                return is_array($rows) ? $rows : [];
            }
        }
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return [];
    }

    /**
     * Handle file uploads. Nonce verified in handleSubmit() before this is called.
     *
     * phpcs:disable WordPress.Security.NonceVerification.Missing -- Caller verifies nonce.
     */
    private function handleFileUploads() {
        $attachments = [];
        if ( empty( $_FILES['attachment'] ) || ! is_array( $_FILES['attachment'] ) || ( isset( $_FILES['attachment']['error'] ) && (int) $_FILES['attachment']['error'] === UPLOAD_ERR_NO_FILE ) ) {
            return $attachments;
        }
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $max_size = (int) get_option( 'persian_oa_max_upload_size', 10 ) * 1024 * 1024;
        $allowed_types = get_option( 'persian_oa_allowed_types', [ 'pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip' ] );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized,WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Upload array unslashed; each field individually sanitized below.
        $raw = wp_unslash( $_FILES['attachment'] );
        $file = [
            'name'     => sanitize_file_name( isset( $raw['name'] ) ? (string) $raw['name'] : '' ),
            'type'     => isset( $raw['type'] ) ? sanitize_mime_type( (string) $raw['type'] ) : '',
            'tmp_name' => isset( $raw['tmp_name'] ) ? sanitize_text_field( (string) $raw['tmp_name'] ) : '',
            'error'    => isset( $raw['error'] ) ? (int) $raw['error'] : UPLOAD_ERR_NO_FILE,
            'size'     => isset( $raw['size'] ) ? (int) $raw['size'] : 0,
        ];

        if ( UPLOAD_ERR_OK !== $file['error'] || empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
            return $attachments;
        }

        // Check file size
        if ( $file['size'] > $max_size ) {
            wp_die( esc_html__( 'حجم فایل بیش از حد مجاز است.', 'persian-office-automation' ) );
        }

        // Check file extension
        $file_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( ! in_array( $file_ext, $allowed_types, true ) ) {
            wp_die( esc_html__( 'فرمت فایل مجاز نیست.', 'persian-office-automation' ) );
        }

        $upload = wp_handle_upload( $file, [ 'test_form' => false ] );
        
        if ( isset( $upload['error'] ) ) {
            wp_die( esc_html( sprintf( /* translators: %s: upload error */ __( 'خطا در آپلود فایل: %s', 'persian-office-automation' ), $upload['error'] ) ) );
        }

        if ( isset( $upload['file'] ) ) {
            $attachments[] = [
                'name' => basename( $upload['file'] ),
                'path' => $upload['file'],
                'type' => isset( $upload['type'] ) ? $upload['type'] : '',
                'size' => $file['size'],
            ];
        }
        
        return $attachments;
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing
}



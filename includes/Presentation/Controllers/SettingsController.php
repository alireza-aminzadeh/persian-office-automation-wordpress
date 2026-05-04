<?php
/**
 * Settings Controller
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Presentation\Controllers;

class SettingsController {

    /**
     * GET flash (?message=) requires a valid screen nonce; optional nonce in URL must verify when present.
     */
    private function verify_settings_screen_get(): void {
        if ( isset( $_GET['persian_oa_settings_nonce'] ) ) {
            if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['persian_oa_settings_nonce'] ) ), 'persian_oa_settings_screen' ) ) {
                wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
            }
            return;
        }
        if ( isset( $_GET['message'] ) && $_GET['message'] !== '' ) {
            wp_die( esc_html__( 'Security check failed.', 'persian-office-automation' ), '', [ 'response' => 403 ] );
        }
    }

    /**
     * Redirect after save with flash message and screen nonce.
     *
     * @param string $tab Tab slug.
     */
    private function redirect_settings_success( string $tab ): void {
        $redirect_url = add_query_arg(
            [
                'page'                      => 'persian-oa-settings',
                'tab'                       => $tab,
                'message'                   => 'success',
                'persian_oa_settings_nonce' => wp_create_nonce( 'persian_oa_settings_screen' ),
            ],
            admin_url( 'admin.php' )
        );
        wp_safe_redirect( $redirect_url );
        exit;
    }
    
    /**
     * Handle general settings submission
     */
    public function handleGeneralSettings() {
        // Check nonce
        if (!isset($_POST['persian_oa_general_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['persian_oa_general_nonce'])), 'persian_oa_general_settings')) {
            wp_die('امنیت فرم تایید نشد.');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('شما مجوز تغییر تنظیمات ندارید.');
        }
        
        // Handle icon removal
        if (isset($_POST['persian_oa_title_icon_remove']) && $_POST['persian_oa_title_icon_remove'] == '1') {
            $old_icon_id = get_option('persian_oa_title_icon_attachment_id', 0);
            if ($old_icon_id) {
                wp_delete_attachment($old_icon_id, true);
            }
            delete_option('persian_oa_title_icon_attachment_id');
        }
        
        // Handle icon upload
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Multipart $_FILES; unslashed below, then is_uploaded_file() and wp_handle_upload().
        if ( ! empty( $_FILES['persian_oa_title_icon']['name'] ) && isset( $_FILES['persian_oa_title_icon']['error'] ) && (int) $_FILES['persian_oa_title_icon']['error'] === UPLOAD_ERR_OK ) {
                // Include WordPress file handling functions
                if (!function_exists('wp_handle_upload')) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                }
                if (!function_exists('wp_generate_attachment_metadata')) {
                    require_once(ABSPATH . 'wp-admin/includes/image.php');
                }

                $file = wp_unslash( $_FILES['persian_oa_title_icon'] );
                // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                $file = is_array( $file ) ? [
                    'name'     => sanitize_file_name( isset( $file['name'] ) ? (string) $file['name'] : '' ),
                    'type'     => isset( $file['type'] ) ? sanitize_mime_type( (string) $file['type'] ) : '',
                    'tmp_name' => isset( $file['tmp_name'] ) ? sanitize_text_field( (string) $file['tmp_name'] ) : '',
                    'error'    => isset( $file['error'] ) ? (int) $file['error'] : UPLOAD_ERR_NO_FILE,
                    'size'     => isset( $file['size'] ) ? (int) $file['size'] : 0,
                ] : [];
                if ( ! empty( $file['tmp_name'] ) && is_uploaded_file( $file['tmp_name'] ) ) {
                
                // Delete old icon if exists
                $old_icon_id = get_option('persian_oa_title_icon_attachment_id', 0);
                if ($old_icon_id) {
                    wp_delete_attachment($old_icon_id, true);
                }
                
                // Upload new icon
                $upload = wp_handle_upload($file, ['test_form' => false]);
                
                if ( ! isset( $upload['error'] ) ) {
                    $original_filename = $file['name'];
                    $attachment_id = wp_insert_attachment([
                        'post_mime_type' => sanitize_mime_type( $upload['type'] ),
                        'post_title' => sanitize_file_name( pathinfo( $original_filename, PATHINFO_FILENAME ) ),
                        'post_content' => '',
                        'post_status' => 'inherit'
                    ], $upload['file']);
                    if ( ! is_wp_error( $attachment_id ) ) {
                        $attach_data = wp_generate_attachment_metadata( $attachment_id, $upload['file'] );
                        wp_update_attachment_metadata( $attachment_id, $attach_data );
                        update_option( 'persian_oa_title_icon_attachment_id', $attachment_id );
                    }
                }
                } // end is_uploaded_file
        } // end $_FILES check
        
        $this->redirect_settings_success( 'general' );
    }
    
    /**
     * Handle upload settings submission
     */
    public function handleUploadSettings() {
        // Check nonce
        if (!isset($_POST['persian_oa_upload_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['persian_oa_upload_nonce'])), 'persian_oa_upload_settings')) {
            wp_die('امنیت فرم تایید نشد.');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('شما مجوز تغییر تنظیمات ندارید.');
        }
        
        // Save max upload size
        $max_size = isset($_POST['persian_oa_max_upload_size']) ? intval(sanitize_text_field(wp_unslash($_POST['persian_oa_max_upload_size']))) : 10;
        if ($max_size < 1) $max_size = 1;
        if ($max_size > 100) $max_size = 100;
        update_option('persian_oa_max_upload_size', $max_size);
        
        // Save allowed file types
        $allowed_types = [];
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array values sanitized in loop below.
        $allowed_types_input = isset( $_POST['persian_oa_allowed_types'] ) ? wp_unslash( $_POST['persian_oa_allowed_types'] ) : [];
        if ( is_array( $allowed_types_input ) ) {
            foreach ( $allowed_types_input as $type ) {
                $type = sanitize_text_field( (string) $type );
                $types = explode( ',', $type );
                foreach ( $types as $t ) {
                    $allowed_types[] = trim( sanitize_file_name( $t ) );
                }
            }
        }
        
        // Ensure at least one type is allowed
        if (empty($allowed_types)) {
            $allowed_types = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'zip', 'xls', 'xlsx'];
        }
        
        update_option('persian_oa_allowed_types', array_unique($allowed_types));
        
        $this->redirect_settings_success( 'upload' );
    }

    /**
     * Handle workflow settings submission
     */
    public function handleWorkflowSettings() {
        // Check nonce
        if (!isset($_POST['persian_oa_workflow_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['persian_oa_workflow_nonce'])), 'persian_oa_workflow_settings')) {
            wp_die('امنیت فرم تایید نشد.');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('شما مجوز تغییر تنظیمات ندارید.');
        }

        // Save General Workflow Settings
        $general_settings = [
            'allow_self_approval' => isset($_POST['persian_oa_wf_allow_self_approval']) ? 1 : 0,
            'holiday_counting' => isset( $_POST['persian_oa_wf_holiday_counting'] ) ? sanitize_text_field( wp_unslash( $_POST['persian_oa_wf_holiday_counting'] ) ) : 'stop',
        ];
        update_option('persian_oa_workflow_general_settings', $general_settings);

        // Process Workflow Definitions
        // We expect a JSON string or array from the frontend builder
        if (isset($_POST['persian_oa_workflow_definitions'])) {
            // If it's a JSON string (from a hidden input updated by JS)
            // Sanitize the JSON string first
            $workflow_json = sanitize_textarea_field(wp_unslash($_POST['persian_oa_workflow_definitions']));
            $definitions = json_decode($workflow_json, true);
            
            if (is_array($definitions)) {
                // Sanitize and validate
                $sanitized_defs = [];
                foreach ($definitions as $def) {
                    if (empty($def['name'])) continue;
                    
                    $sanitized_steps = [];
                    if (isset($def['steps']) && is_array($def['steps'])) {
                        foreach ($def['steps'] as $step) {
                            $sanitized_steps[] = [
                                'step' => intval($step['step']),
                                'title' => sanitize_text_field($step['title']),
                                'type' => sanitize_text_field($step['type']), // role, user, direct_manager
                                'value' => sanitize_text_field($step['value'] ?? '') // role slug or user id
                            ];
                        }
                    }

                    $sanitized_defs[] = [
                        'id' => sanitize_key($def['id'] ?? uniqid('wf_')),
                        'name' => sanitize_text_field($def['name']),
                        'description' => sanitize_textarea_field($def['description'] ?? ''),
                        'is_active' => isset($def['is_active']) ? (bool)$def['is_active'] : true,
                        'sla' => intval($def['sla'] ?? 24),
                        'steps' => $sanitized_steps
                    ];
                }
                update_option('persian_oa_workflow_definitions', $sanitized_defs);
            }
        }

        $this->redirect_settings_success( 'workflow' );
    }
    
    /**
     * Handle category settings submission
     */
    public function handleCategorySettings() {
        // Check nonce
        if (!isset($_POST['persian_oa_category_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['persian_oa_category_nonce'])), 'persian_oa_category_settings')) {
            wp_die('امنیت فرم تایید نشد.');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('شما مجوز تغییر تنظیمات ندارید.');
        }
        
        $categories = [];
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Array values sanitized in loop below.
        $categories_input = isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : [];
        if ( is_array( $categories_input ) ) {
            foreach ( $categories_input as $cat ) {
                if ( ! is_array( $cat ) ) {
                    continue;
                }
                $label = isset( $cat['label'] ) ? sanitize_text_field( (string) $cat['label'] ) : '';
                if ( $label !== '' ) {
                    $key = ! empty( $cat['key'] ) ? sanitize_key( (string) $cat['key'] ) : 'cat_' . uniqid();
                    $categories[ $key ] = $label;
                }
            }
        }
        
        // If empty (user deleted all), maybe restore defaults or allow empty?
        // Let's allow empty but maybe warn or just save empty array.
        // However, it's safer to always have at least "Other".
        if (empty($categories)) {
             $categories = ['other' => 'سایر'];
        }
        
        update_option('persian_oa_incoming_categories', $categories);
        
        $this->redirect_settings_success( 'categories' );
    }

    /**
     * Render settings page
     */
    public function renderSettings() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('شما مجوز دسترسی به تنظیمات ندارید.');
        }

               $this->verify_settings_screen_get();

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only screen routing; verify_settings_screen_get() requires nonce when ?message= is set.
        $allowed_tabs = [ 'general', 'upload', 'numbering', 'categories', 'workflow', 'advanced' ];
        $active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'general';
        if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
            $active_tab = 'general';
        }
        $settings_message = isset( $_GET['message'] ) ? sanitize_text_field( wp_unslash( $_GET['message'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        // Localize script for workflow builder
        $workflows = get_option('persian_oa_workflow_definitions', []);
        global $wp_roles;
        $roles = $wp_roles->roles;
        
        wp_localize_script('persian-oa-admin', 'persianOaWorkflowData', [
            'workflows' => array_values($workflows),
            'roles' => $roles
        ]);
        
        // Load view
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/settings.php';
    }
}





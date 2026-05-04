<?php
/**
 * Admin assets - loaded by plugin bootstrap only.
 * phpcs:ignore PluginCheck.Security.MissingDirectFileAccessProtection -- ABSPATH check follows namespace (PHP requires namespace first).
 * phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- All URLs are local (PERSIAN_OA_ASSETS_URL / plugin_dir_url).
 */
namespace PersianOfficeAutomation\Presentation\Assets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AssetManager {
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
    }

    public function enqueueAssets($hook) {
        // Check if we're on a Persian Office Automation admin page
        if (strpos($hook, 'office-automation') === false && strpos($hook, 'persian-oa-') === false && $hook !== 'toplevel_page_office-automation') {
            return;
        }

        // Vazirmatn Font - Local plugin directory (Plugin Check: no external URLs).
        wp_enqueue_style( 'vazirmatn', plugin_dir_url( PERSIAN_OA_PLUGIN_FILE ) . 'assets/fonts/vazirmatn/style.css', [], '33.003' );
        
        // Fallback fonts inline CSS
        wp_add_inline_style('vazirmatn', '
            @font-face {
                font-family: "Vazirmatn Fallback";
                src: local("Tahoma"), local("Arial");
                font-display: swap;
            }
            body, .persian-oa-wrap, .persian-oa-wrap * {
                font-family: "Vazirmatn", "Tahoma", "Iranian Sans", "Arial", sans-serif !important;
            }
        ');
        
        // Main CSS
        wp_enqueue_style('persian-oa-admin', PERSIAN_OA_ASSETS_URL . 'css/admin.css', ['vazirmatn'], PERSIAN_OA_VERSION);
        
        // Chart.js (Local)
        wp_enqueue_script('chartjs', PERSIAN_OA_ASSETS_URL . 'js/vendor/chart.umd.min.js', [], '4.4.7', true);
        
        // Mermaid.js (Local)
        wp_enqueue_script('mermaid', PERSIAN_OA_ASSETS_URL . 'js/vendor/mermaid.min.js', [], '10.6.1', true);
        
        // Alpine.js (Local)
        wp_enqueue_script('alpinejs', PERSIAN_OA_ASSETS_URL . 'js/vendor/alpine.min.js', [], '3.15.9', true);
        
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Simple Persian Date Picker (Zero Dependencies)
        wp_enqueue_script('simple-persian-datepicker', PERSIAN_OA_ASSETS_URL . 'js/vendor/simple-persian-datepicker.js', [], PERSIAN_OA_VERSION, true);

        // CKEditor 5
        wp_enqueue_script('persian-oa-ckeditor', PERSIAN_OA_ASSETS_URL . 'js/vendor/ckeditor.js', [], '41.2.0', true);
        wp_enqueue_script('persian-oa-ckeditor-fa', PERSIAN_OA_ASSETS_URL . 'js/vendor/ckeditor-fa.js', ['persian-oa-ckeditor'], '41.2.0', true);
        
        // Main JS
        wp_enqueue_script('persian-oa-admin', PERSIAN_OA_ASSETS_URL . 'js/admin.js', ['jquery', 'simple-persian-datepicker', 'persian-oa-ckeditor', 'persian-oa-ckeditor-fa', 'mermaid'], PERSIAN_OA_VERSION, true);
        
        wp_localize_script('persian-oa-admin', 'persianOaData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('persian_oa_nonce'),
            'cartable_nonce' => wp_create_nonce('persian_oa_cartable_nonce'),
        ]);

        // Task Page Specific Assets
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- GET used for asset loading by page hook; admin context.
        if ( isset( $_GET['page'] ) && sanitize_text_field( wp_unslash( $_GET['page'] ) ) === 'persian-oa-tasks' ) {
            wp_enqueue_style('persian-oa-tasks', PERSIAN_OA_ASSETS_URL . 'css/tasks.css', ['persian-oa-admin'], PERSIAN_OA_VERSION);
            wp_enqueue_media();
            wp_enqueue_script('persian-oa-tasks', PERSIAN_OA_ASSETS_URL . 'js/tasks.js', ['jquery'], time(), true);
        }

        // Reports Page Specific Assets (dashicons required for stat card icons)
        if ( strpos( $hook, 'persian-oa-reports' ) !== false ) {
            wp_enqueue_style('dashicons');
            wp_enqueue_style('persian-oa-reports', PERSIAN_OA_ASSETS_URL . 'css/reports.css', ['persian-oa-admin', 'dashicons'], PERSIAN_OA_VERSION);
        }
    }
}


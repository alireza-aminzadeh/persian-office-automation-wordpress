<?php
/**
 * Frontend shortcode and assets - loaded by plugin bootstrap only.
 * phpcs:ignore PluginCheck.Security.MissingDirectFileAccessProtection -- ABSPATH check follows namespace (PHP requires namespace first).
 * phpcs:disable WordPress.Security.NonceVerification.Recommended -- Shortcode output; GET/POST handled in controllers with nonce.
 */
namespace PersianOfficeAutomation\Presentation\Frontend;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PersianOfficeAutomation\Presentation\Controllers\CartableController;
use PersianOfficeAutomation\Presentation\Controllers\IncomingLetterController;
use PersianOfficeAutomation\Presentation\Controllers\OutgoingLetterController;
use PersianOfficeAutomation\Presentation\Controllers\SettingsController;

class ShortcodeManager {
    private $cartableController;
    private $incomingController;
    private $outgoingController;
    private $settingsController;

    public function __construct() {
        $this->cartableController = new CartableController();
        $this->incomingController = new IncomingLetterController();
        $this->outgoingController = new OutgoingLetterController();
        $this->settingsController = new SettingsController();

        add_shortcode( PERSIAN_OA_SHORTCODE_TAG, [ $this, 'renderShortcode' ] );
        add_shortcode( PERSIAN_OA_SHORTCODE_LEGACY, [ $this, 'renderShortcode' ] );
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // Handle custom redirects for frontend forms
        add_filter('wp_redirect', [$this, 'handleRedirect'], 10, 2);
    }

    /**
     * Enqueue assets if shortcode is present
     */
    public function enqueueAssets() {
        global $post;
        
        // Enqueue if the current post contains the registered shortcode (or legacy tag for asset compatibility).
        if ( ! is_a( $post, 'WP_Post' ) ) {
            return;
        }
        $content = (string) $post->post_content;
        if ( ! has_shortcode( $content, PERSIAN_OA_SHORTCODE_TAG ) && ! has_shortcode( $content, PERSIAN_OA_SHORTCODE_LEGACY ) ) {
            return;
        }

        // Vazirmatn Font - Local plugin directory (Plugin Check: no external URLs).
        // phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- Local URL via plugin_dir_url().
        wp_enqueue_style( 'vazirmatn', plugin_dir_url( PERSIAN_OA_PLUGIN_FILE ) . 'assets/fonts/vazirmatn/style.css', [], '33.003' );
        
        // Inline styles for font - Scoped to .persian-oa-wrap
        wp_add_inline_style('vazirmatn', '
            .persian-oa-wrap {
                font-family: "Vazirmatn", "Tahoma", "Iranian Sans", "Arial", sans-serif !important;
            }
            .persian-oa-wrap * {
                font-family: "Vazirmatn", "Tahoma", "Iranian Sans", "Arial", sans-serif;
            }
        ');
        
        // Main CSS
        wp_enqueue_style('persian-oa-admin', PERSIAN_OA_ASSETS_URL . 'css/admin.css', ['vazirmatn'], PERSIAN_OA_VERSION);
        
        // Fix for admin styles on frontend
        wp_add_inline_style('persian-oa-admin', '
            /* Frontend specific fixes */
            .persian-oa-wrap {
                margin: 20px 0;
                background: #f9fafb;
                border-radius: 8px;
                padding: 20px;
            }
        ');
        
        // Chart.js (Local)
        wp_enqueue_script('chartjs', PERSIAN_OA_ASSETS_URL . 'js/vendor/chart.umd.min.js', [], '4.4.0', true);
        
        // Alpine.js (Local)
        wp_enqueue_script('alpinejs', PERSIAN_OA_ASSETS_URL . 'js/vendor/alpine.min.js', [], '3.15.9', true);
        
        // Ensure jQuery is loaded
        wp_enqueue_script('jquery');
        
        // Simple Persian Date Picker
        wp_enqueue_script('simple-persian-datepicker', PERSIAN_OA_ASSETS_URL . 'js/vendor/simple-persian-datepicker.js', [], PERSIAN_OA_VERSION, true);

        // CKEditor 5
        wp_enqueue_script('persian-oa-ckeditor', PERSIAN_OA_ASSETS_URL . 'js/vendor/ckeditor.js', [], '41.2.0', true);
        wp_enqueue_script('persian-oa-ckeditor-fa', PERSIAN_OA_ASSETS_URL . 'js/vendor/ckeditor-fa.js', ['persian-oa-ckeditor'], '41.2.0', true);
        
        // Main JS
        wp_enqueue_script('persian-oa-admin', PERSIAN_OA_ASSETS_URL . 'js/admin.js', ['jquery', 'simple-persian-datepicker', 'persian-oa-ckeditor', 'persian-oa-ckeditor-fa'], PERSIAN_OA_VERSION, true);
        
        wp_localize_script('persian-oa-admin', 'persianOaData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('persian_oa_nonce'),
        ]);
    }

    /**
     * Render the shortcode
     */
    public function renderShortcode($atts) {
        if (!is_user_logged_in()) {
            return '<div class="persian-oa-message persian-oa-error" style="padding: 20px; background: #fee2e2; color: #b91c1c; border-radius: 8px; text-align: center;">لطفا برای دسترسی به اتوماسیون اداری، ابتدا وارد سایت شوید.</div>';
        }

        // Parse attributes
        $atts = shortcode_atts([
            'default_view' => 'dashboard'
        ], $atts);

        // Determine view (sanitized)
        $view = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : $atts['default_view'];
        if ( empty( $view ) ) {
            $view = $atts['default_view'];
        }
        
        // Add hidden field for frontend detection to forms if needed
        // But since we can't easily inject into existing views without JS or editing files,
        // we might rely on the referer check in handleRedirect.

        ob_start();
        
        echo '<div class="persian-oa-wrap persian-oa-frontend-view">';
        
        // Navigation Bar for Frontend
        $this->renderFrontendNav($view);

        switch ($view) {
            case 'persian-oa-incoming':
            case 'persian-oa-incoming-letters':
                // Check permissions logic copied from AdminMenu (persian_oa_view_letter from RoleService)
                if (!current_user_can('persian_oa_view_letter') && !current_user_can('manage_options')) {
                     echo '<div class="persian-oa-alert persian-oa-alert-danger">شما مجوز دسترسی به این بخش را ندارید.</div>';
                } else {
                    $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';
                    $get_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
                    if ( ( $action === 'new' ) || $view === 'persian-oa-incoming' ) {
                        $this->incomingController->renderForm();
                    } elseif ( $action === 'edit' && $get_id ) {
                        $this->incomingController->renderForm();
                    } else {
                        $this->incomingController->renderList();
                    }
                }
                break;

            case 'persian-oa-outgoing':
                if ( ! current_user_can( 'persian_oa_view_letter' ) && ! current_user_can( 'manage_options' ) ) {
                    echo '<div class="persian-oa-alert persian-oa-alert-danger">شما مجوز دسترسی به این بخش را ندارید.</div>';
                } else {
                    $this->outgoingController->renderList();
                }
                break;
                
            case 'persian-oa-cartable-inbox':
                $this->cartableController->renderInbox();
                break;
                
            case 'persian-oa-cartable-sent':
                $this->cartableController->renderSent();
                break;
                
            case 'persian-oa-cartable-pending':
                $this->cartableController->renderPending();
                break;
                
            case 'persian-oa-cartable-starred':
                $this->cartableController->renderStarred();
                break;
                
            case 'persian-oa-cartable-archive':
                $this->cartableController->renderArchive();
                break;
                
            case 'persian-oa-users':
                 if (current_user_can('manage_options')) {
                    require PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/users.php';
                 } else {
                    echo '<div class="persian-oa-alert persian-oa-alert-danger">دسترسی محدود شده است.</div>';
                 }
                break;
                
            case 'persian-oa-settings':
                 if (current_user_can('manage_options')) {
                    $this->settingsController->renderSettings();
                 } else {
                    echo '<div class="persian-oa-alert persian-oa-alert-danger">دسترسی محدود شده است.</div>';
                 }
                break;

            case 'dashboard':
            default:
                require PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/dashboard.php';
                break;
        }

        echo '</div>'; // End persian-oa-wrap

        return ob_get_clean();
    }
    
    /**
     * Render a simple navigation menu for frontend usage
     */
    private function renderFrontendNav($current_view) {
        $menu_items = [
            'dashboard' => ['title' => 'داشبورد', 'icon' => '📊'],
            'persian-oa-incoming-letters' => ['title' => 'نامه‌های وارده', 'icon' => '📥'],
            'persian-oa-outgoing' => ['title' => 'نامه‌های صادره', 'icon' => '📤'],
            'persian-oa-cartable-inbox' => ['title' => 'کارتابل', 'icon' => '💼'],
        ];
        
        if (current_user_can('manage_options')) {
            $menu_items['persian-oa-users'] = ['title' => 'کاربران', 'icon' => '👥'];
            $menu_items['persian-oa-settings'] = ['title' => 'تنظیمات', 'icon' => '⚙️'];
        }
        
        echo '<div class="persian-oa-frontend-nav" style="margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 10px; display: flex; gap: 10px; flex-wrap: wrap;">';
        foreach ( $menu_items as $slug => $item ) {
            $active = ( $slug === $current_view || ( $slug === 'persian-oa-incoming-letters' && $current_view === 'persian-oa-incoming' ) ) ? 'background: #e0e7ff; color: #4338ca;' : 'background: #fff;';
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- slug, active, icon, title escaped via esc_attr/esc_html in printf.
            printf(
                '<a href="?page=%1$s" class="persian-oa-btn persian-oa-btn-outline" style="%2$s text-decoration: none;">%3$s %4$s</a>',
                esc_attr( $slug ),
                esc_attr( $active ),
                esc_html( $item['icon'] ),
                esc_html( $item['title'] )
            );
        }
        echo '</div>';
    }

    /**
     * Handle Redirects from Controllers to stay on frontend
     */
    public function handleRedirect($location, $status) {
        // Check if the redirect is going to admin.php?page=persian-oa-...
        if (strpos($location, 'admin.php?page=persian-oa-') !== false) {
            // Check if referer was from frontend
            $referer = wp_get_referer();
            if ($referer && strpos($referer, 'wp-admin') === false) {
                // We are likely on frontend.
                // Convert admin URL back to current frontend URL with query args
                
                // Parse the target location to get query args (wp_parse_url is WP-recommended).
                // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Using wp_parse_url().
                $parts = wp_parse_url( $location );
                parse_str( isset( $parts['query'] ) ? $parts['query'] : '', $query_args );
                
                // Construct new location: referer + query args
                // Remove 'page' from query args if it's already in referer? 
                // No, we want to force the 'page' arg to match what the controller intended.
                
                $new_location = add_query_arg($query_args, $referer);
                return $new_location;
            }
        }
        return $location;
    }
}

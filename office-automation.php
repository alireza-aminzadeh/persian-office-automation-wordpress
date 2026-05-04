<?php
/**
 * Plugin Name: Persian Office Automation SaaS
 * Plugin URI: https://ariacoder.ir
 * Description: سیستم جامع مدیریت مکاتبات، دبیرخانه و اتوماسیون اداری - SaaS Platform
 * Version: 3.0.14
 * Author: Alireza Aminzadeh
 * Author URI: https://aryait.net
 * Text Domain: persian-office-automation
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * 
 * @package OfficeAutomation
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PERSIAN_OA_VERSION', '3.0.14');
define('PERSIAN_OA_PLUGIN_FILE', __FILE__);
define('PERSIAN_OA_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('PERSIAN_OA_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PERSIAN_OA_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('PERSIAN_OA_INCLUDES_DIR', PERSIAN_OA_PLUGIN_DIR . 'includes/');
define('PERSIAN_OA_ASSETS_URL', PERSIAN_OA_PLUGIN_URL . 'assets/');
/** Shortcode tag registered for the frontend panel (wp.org: must be uniquely prefixed). */
define('PERSIAN_OA_SHORTCODE_TAG', 'persian_oa_office_panel');
/**
 * Legacy shortcode from early releases — still registered so existing post content keeps working.
 * New content should use PERSIAN_OA_SHORTCODE_TAG.
 */
define('PERSIAN_OA_SHORTCODE_LEGACY', 'office_automation');

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = 'PersianOfficeAutomation\\';
    $base_dir = PERSIAN_OA_INCLUDES_DIR;

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

// Initialize the plugin
function persian_office_automation_init() {
    try {
        // Check and update capabilities on every admin page load (only for admins)
        if (is_admin() && current_user_can('manage_options')) {
            persian_office_automation_ensure_capabilities();
        }
        
        $plugin = \PersianOfficeAutomation\Core\Plugin::getInstance();
        $plugin->run();
    } catch (\Exception $e) {
        // Silent catch for production (Plugin Check: no error_log in production).
    }
}
add_action('plugins_loaded', 'persian_office_automation_init');

/**
 * Load plugin text domain for translations.
 * phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Kept for non–WordPress.org installs; wp.org loads by slug automatically.
 */
function persian_office_automation_load_textdomain() {
	// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Kept for non–wp.org installs; wp.org loads by slug.
	load_plugin_textdomain(
		'persian-office-automation',
		false,
		dirname( PERSIAN_OA_PLUGIN_BASENAME ) . '/languages'
	);
}
add_action( 'init', 'persian_office_automation_load_textdomain' );

/**
 * Ensure capabilities are properly set
 * This runs on every admin page load to make sure capabilities exist
 */
function persian_office_automation_ensure_capabilities() {
    // Check if capabilities need to be updated
    $admin = get_role('administrator');
    if ($admin && !$admin->has_cap('persian_oa_create_letter')) {
        require_once PERSIAN_OA_INCLUDES_DIR . 'Application/Services/RoleService.php';
        \PersianOfficeAutomation\Application\Services\RoleService::createDefaultRoles();
    }
}

// Activation hook
register_activation_hook(__FILE__, function() {
    require_once PERSIAN_OA_INCLUDES_DIR . 'Infrastructure/Database/Schema.php';
    \PersianOfficeAutomation\Infrastructure\Database\Schema::createTables();
    
    // Create default roles and capabilities
    require_once PERSIAN_OA_INCLUDES_DIR . 'Application/Services/RoleService.php';
    \PersianOfficeAutomation\Application\Services\RoleService::createDefaultRoles();
    
    // Flush rewrite rules
    flush_rewrite_rules();
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Uninstall hook - Note: Use uninstall.php file instead of closure
// register_uninstall_hook cannot use closures - WordPress will serialize it
// The actual uninstall logic is in uninstall.php


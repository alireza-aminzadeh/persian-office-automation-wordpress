<?php
/**
 * Uninstall Plugin
 *
 * @package OfficeAutomation
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Include Schema to drop tables
require_once plugin_dir_path(__FILE__) . 'includes/Infrastructure/Database/Schema.php';

// Drop all custom tables
\PersianOfficeAutomation\Infrastructure\Database\Schema::dropTables();

// Delete options
delete_option('persian_oa_db_version');

// Delete user meta if any (optional, be careful)
// delete_metadata('user', 0, 'persian_oa_some_meta_key', '', true);

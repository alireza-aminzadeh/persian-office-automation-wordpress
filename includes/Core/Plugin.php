<?php
/**
 * Main Plugin Class - Singleton Pattern
 * 
 * @package PersianOfficeAutomation\Core
 */

namespace PersianOfficeAutomation\Core;

use PersianOfficeAutomation\Presentation\Admin\AdminMenu;
use PersianOfficeAutomation\Presentation\Admin\AdminBarNotifications;
use PersianOfficeAutomation\Presentation\Admin\DashboardWidgets;
use PersianOfficeAutomation\Presentation\Assets\AssetManager;

class Plugin {
    private static $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     * Use getInstance() method instead
     */
    private function __construct() {
        // Constructor intentionally left empty
        // Initialization happens when run() is called
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Run the plugin
     * This method initializes all plugin components
     * 
     * @return void
     */
    public function run() {
        $this->initHooks();
    }

    /**
     * Initialize WordPress hooks and plugin components
     * 
     * @return void
     */
    private function initHooks() {
        new AdminBarNotifications();

        if (is_admin()) {
            new AdminMenu();
            new AssetManager();
            new DashboardWidgets();
        }
        
        // Initialize Shortcode Manager (Frontend)
        new \PersianOfficeAutomation\Presentation\Frontend\ShortcodeManager();
    }

    /**
     * Prevent cloning of the instance
     * 
     * @return void
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing of the instance
     * 
     * @throws \Exception
     * @return void
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}


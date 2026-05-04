<?php

namespace PersianOfficeAutomation\Presentation\Controllers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reports Controller
 * phpcs:ignore PluginCheck.CodeAnalysis.EnqueuedResourceOffloading.OffloadedContent -- Scripts/styles enqueued in AssetManager (local URLs).
 *
 * @package OfficeAutomation
 */

use PersianOfficeAutomation\Application\Services\ReportService;

class ReportController {
    
    private $service;
    
    public function __construct() {
        $this->service = new ReportService();
    }
    
    public function render() {
        // Enqueue Chart.js is handled by AssetManager
        
        // Fetch data
        $summary = $this->service->getDashboardStats();
        $chartData = $this->service->getChartData();
        $taskStats = $this->service->getTaskSummary();
        $meetings = $this->service->getUpcomingMeetings();
        
        // Render View
        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/reports/dashboard.php';
    }
}

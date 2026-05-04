<?php
/**
 * Report Service
 * Uses gmdate() and JalaliDate for timezone-safe dates.
 *
 * @package OfficeAutomation
 * @phpcs:disable WordPress.DateTime.RestrictedFunctions.date_date -- Use gmdate()/JalaliDate.
 */

namespace PersianOfficeAutomation\Application\Services;

use PersianOfficeAutomation\Infrastructure\Repository\ReportRepository;
use PersianOfficeAutomation\Common\JalaliDate;

class ReportService {
    
    private $repository;
    
    public function __construct() {
        $this->repository = new ReportRepository();
    }
    
    /**
     * Get dashboard statistics
     * 
     * @return array
     */
    public function getDashboardStats() {
        $rawStats = $this->repository->getCorrespondenceStats();
        
        // Initialize summary structure
        $summary = [
            'total_incoming' => 0,
            'total_outgoing' => 0,
            'total_internal' => 0,
            'status_breakdown' => []
        ];
        
        foreach ($rawStats as $row) {
            $type = $row['type'];
            $count = (int)$row['count'];
            $status = $row['status'];
            
            if ($type === 'incoming') $summary['total_incoming'] += $count;
            if ($type === 'outgoing') $summary['total_outgoing'] += $count;
            if ($type === 'internal') $summary['total_internal'] += $count;
            
            if (!isset($summary['status_breakdown'][$status])) {
                $summary['status_breakdown'][$status] = 0;
            }
            $summary['status_breakdown'][$status] += $count;
        }
        
        return $summary;
    }
    
    /**
     * Get chart data for correspondence
     * 
     * @return array
     */
    public function getChartData() {
        // Priority Data – Persian labels, fixed order for consistent chart colors
        $priorityStats = $this->repository->getCorrespondenceByPriority();
        $priorityMap = [
            'low'       => 'کم',
            'medium'    => 'متوسط',
            'high'      => 'زیاد',
            'urgent'    => 'فوری',
            'normal'    => 'عادی',
            'immediate' => 'فوری',
            'instant'   => 'آنی'
        ];
        $order = [ 'low', 'medium', 'high', 'urgent', 'normal', 'immediate', 'instant' ];
        $byKey = [];
        foreach ($priorityStats as $stat) {
            $key = $stat['priority'];
            $byKey[$key] = (int) $stat['count'];
        }
        $priorityLabels = [];
        $priorityData = [];
        foreach ($order as $key) {
            if ( isset( $byKey[$key] ) && $byKey[$key] > 0 ) {
                $priorityLabels[] = isset( $priorityMap[$key] ) ? $priorityMap[$key] : $key;
                $priorityData[] = $byKey[$key];
            }
        }
        // Any priority not in $order (e.g. from older data) append at end
        foreach ( $byKey as $key => $count ) {
            if ( in_array( $key, $order, true ) ) {
                continue;
            }
            $priorityLabels[] = isset( $priorityMap[$key] ) ? $priorityMap[$key] : $key;
            $priorityData[] = $count;
        }
        
        // Monthly Trends
        $monthlyStats = $this->repository->getMonthlyStats();
        $months = [];
        $datasets = [
            'incoming' => [],
            'outgoing' => [],
            'internal' => []
        ];
        
        // Pre-fill last 6 months to ensure continuity
        for ($i = 5; $i >= 0; $i--) {
            $date = gmdate( 'Y-m', strtotime( "-$i months" ) );
            
            // Convert to Jalali for label (F Y = Full month name + Year)
            $label = JalaliDate::jdate('F Y', strtotime($date . '-01'));
            
            $months[$date] = $label;
            $datasets['incoming'][$date] = 0;
            $datasets['outgoing'][$date] = 0;
            $datasets['internal'][$date] = 0;
        }
        
        foreach ($monthlyStats as $stat) {
            $month = $stat['month']; // YYYY-MM
            if (isset($datasets[$stat['type']][$month])) {
                $datasets[$stat['type']][$month] = (int)$stat['count'];
            }
        }
        
        return [
            'priority' => [
                'labels' => $priorityLabels,
                'data' => $priorityData
            ],
            'trends' => [
                'labels' => array_values($months),
                'incoming' => array_values($datasets['incoming']),
                'outgoing' => array_values($datasets['outgoing']),
                'internal' => array_values($datasets['internal'])
            ]
        ];
    }
    
    /**
     * Get task summary
     * 
     * @return array
     */
    public function getTaskSummary() {
        $stats = $this->repository->getTaskStats();
        $summary = [
            'todo' => 0,
            'in_progress' => 0,
            'done' => 0,
            'total' => 0
        ];
        
        foreach ($stats as $stat) {
            $count = (int)$stat['count'];
            $status = $stat['status'] ?? '';
            $summary['total'] += $count;

            if ($status === 'todo') $summary['todo'] += $count;
            if ($status === 'in_progress') $summary['in_progress'] += $count;
            if ($status === 'done' || $status === 'completed') $summary['done'] += $count;
        }
        
        return $summary;
    }
    
    /**
     * Get upcoming meetings
     *
     * @param int $limit Max number of meetings to return
     * @return array
     */
    public function getUpcomingMeetings($limit = 5) {
        $meetings = $this->repository->getUpcomingMeetings($limit);
        
        foreach ($meetings as &$meeting) {
            // Add formatted Jalali date
            $ts = strtotime($meeting['meeting_date']);
            $meeting['formatted_date'] = JalaliDate::jdate('l j F Y - H:i', $ts);
        }
        
        return $meetings;
    }
}



<?php
/**
 * Calendar Controller
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Presentation\Controllers;

use PersianOfficeAutomation\Infrastructure\Repository\MeetingRepository;
use PersianOfficeAutomation\Infrastructure\Repository\TaskRepository;
use PersianOfficeAutomation\Infrastructure\Repository\CorrespondenceRepository;
use PersianOfficeAutomation\Common\JalaliDate;

class CalendarController {
    
    private $meetingRepo;
    private $taskRepo;
    private $letterRepo;
    
    public function __construct() {
        $this->meetingRepo = new MeetingRepository();
        $this->taskRepo = new TaskRepository();
        $this->letterRepo = new CorrespondenceRepository();
    }
    
    /**
     * Render Calendar View
     */
    public function render() {
        $current_user_id = get_current_user_id();
        
        // Date Params
        $nowJalali = JalaliDate::now('Y/m/d');
        // Convert to English numbers for calculation
        $nowJalali = JalaliDate::toEnglishNumbers($nowJalali);
        list($currentYear, $currentMonth, $currentDay) = explode('/', $nowJalali);
        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- GET used for calendar navigation; sanitized and bounded.
        $year  = isset( $_GET['y'] ) ? absint( wp_unslash( $_GET['y'] ) ) : (int) $currentYear;
        $month = isset( $_GET['m'] ) ? absint( wp_unslash( $_GET['m'] ) ) : (int) $currentMonth;
        // phpcs:enable WordPress.Security.NonceVerification.Recommended

        // Validation
        if ($month < 1) $month = 1;
        if ($month > 12) $month = 12;
        if ($year < 1300) $year = 1300;
        if ($year > 1500) $year = 1500;
        
        // Grid calculations
        $daysInMonth = JalaliDate::getDaysInJalaliMonth($year, $month);
        $firstDayOfWeek = JalaliDate::getFirstDayOfWeek($year, $month);
        
        // Date Range for Queries (Gregorian)
        $startDateJalali = sprintf('%d/%02d/01', $year, $month);
        $endDateJalali = sprintf('%d/%02d/%02d', $year, $month, $daysInMonth);
        
        $startDateGregorian = JalaliDate::jalaliToGregorianString($startDateJalali);
        $endDateGregorian = JalaliDate::jalaliToGregorianString($endDateJalali);
        
        // Append time for DB queries covering full days
        $dbStart = $startDateGregorian . ' 00:00:00';
        $dbEnd = $endDateGregorian . ' 23:59:59';
        
        // Fetch events
        $meetings = $this->meetingRepo->findBetween($current_user_id, $dbStart, $dbEnd);
        $tasks = $this->taskRepo->findBetween($current_user_id, $dbStart, $dbEnd);
        $letters = $this->letterRepo->findDeadlinesBetween($dbStart, $dbEnd);
        
        // Merge events
        $events = [];
        
        foreach ($meetings as $meeting) {
            $jDate = JalaliDate::toJalali($meeting->getMeetingDate(), 'Y/m/d');
            $jDate = JalaliDate::toEnglishNumbers($jDate);
            list($jy, $jm, $jd) = explode('/', $jDate);
            // Only add if in current view month (should be guaranteed by query but safe to check)
            if ((int)$jm == $month && (int)$jy == $year) {
                $dayIndex = (int)$jd;
                $meetingColor = $meeting->getColor();
                if (empty($meetingColor) || !preg_match('/^#[0-9a-fA-F]{6}$/', $meetingColor)) {
                    $meetingColor = '#3b82f6';
                }
                $events[$dayIndex][] = [
                    'id' => $meeting->getId(),
                    'title' => 'جلسه: ' . $meeting->getTitle(),
                    'time' => JalaliDate::format($meeting->getMeetingDate(), 'H:i'),
                    'type' => 'meeting',
                    'color' => $meetingColor,
                    'url' => admin_url('admin.php?page=persian-oa-meetings&action=view&id=' . $meeting->getId())
                ];
            }
        }
        
        foreach ($tasks as $task) {
            if ($task->getDeadline()) {
                $jDate = JalaliDate::toJalali($task->getDeadline(), 'Y/m/d');
                $jDate = JalaliDate::toEnglishNumbers($jDate);
                list($jy, $jm, $jd) = explode('/', $jDate);
                if ((int)$jm == $month && (int)$jy == $year) {
                    $dayIndex = (int)$jd;
                    $events[$dayIndex][] = [
                        'id' => $task->getId(),
                        'title' => 'وظیفه: ' . $task->getTitle(),
                        'time' => JalaliDate::format($task->getDeadline(), 'H:i'),
                        'type' => 'task',
                        'color' => '#10b981', // Green
                        'url' => admin_url('admin.php?page=persian-oa-tasks&action=edit&id=' . $task->getId())
                    ];
                }
            }
        }

        foreach ($letters as $letter) {
            if ($letter->getDeadline()) {
                $jDate = JalaliDate::toJalali($letter->getDeadline(), 'Y/m/d');
                $jDate = JalaliDate::toEnglishNumbers($jDate);
                list($jy, $jm, $jd) = explode('/', $jDate);
                if ((int)$jm == $month && (int)$jy == $year) {
                    $dayIndex = (int)$jd;
                    $events[$dayIndex][] = [
                        'id' => $letter->getId(),
                        'title' => 'مهلت نامه: ' . $letter->getSubject(),
                        'time' => '23:59', // Usually end of day
                        'type' => 'letter',
                        'color' => '#ef4444', // Red
                        'url' => admin_url('admin.php?page=persian-oa-incoming-letters&action=view&id=' . $letter->getId())
                    ];
                }
            }
        }
        
        // Navigation Links
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        
        $prevMonth = $month - 1;
        $prevYear = $year;
        if ($prevMonth < 1) {
            $prevMonth = 12;
            $prevYear--;
        }
        
        $nextLink = admin_url("admin.php?page=persian-oa-calendar&y=$nextYear&m=$nextMonth");
        $prevLink = admin_url("admin.php?page=persian-oa-calendar&y=$prevYear&m=$prevMonth");
        $todayLink = admin_url("admin.php?page=persian-oa-calendar");

        // phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only view mode; whitelist month|agenda.
        $view_mode_raw = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';
        // phpcs:enable WordPress.Security.NonceVerification.Recommended
        $calendar_view_mode = ( $view_mode_raw === 'agenda' ) ? 'agenda' : 'month';

        require_once PERSIAN_OA_PLUGIN_DIR . 'includes/Presentation/Views/calendar.php';
    }
}

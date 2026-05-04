<?php
/**
 * Global Constants
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Common;

class Constants {
    
    /**
     * Letter Types / Subjects
     * Used for categorizing the nature of the letter
     */
    const LETTER_TYPES = [
        'general' => 'نامه عمومی',
        'leave_request' => 'درخواست مرخصی',
        'mission_request' => 'درخواست ماموریت',
        'purchase_request' => 'درخواست خرید کالا',
        'service_request' => 'درخواست خدمات',
        'employment_certificate' => 'گواهی اشتغال به کار',
        'personnel_order' => 'حکم کارگزینی',
        'loan_request' => 'درخواست وام',
        'resignation' => 'استعفا',
        'checkout' => 'تسویه حساب',
        'timesheet' => 'گزارش کارکرد / تایم‌شیت',
        'invoice' => 'فاکتور / پیش‌فاکتور',
        'payment_voucher' => 'سند پرداخت',
        'circular' => 'بخشنامه',
        'regulation' => 'آیین‌نامه / دستورالعمل',
        'meeting_minutes' => 'صورتجلسه',
        'invitation' => 'دعوت‌نامه',
        'inquiry' => 'استعلام',
        'report' => 'گزارش کار',
    ];

    /**
     * Referral Actions / Paraphs
     * Used when referring a letter to someone
     */
    const REFERRAL_ACTIONS = [
        'for_info' => 'جهت استحضار',
        'for_action' => 'جهت اقدام',
        'for_review' => 'جهت بررسی و اقدام',
        'appropriate_action' => 'جهت اقدام مقتضی',
        'for_comment' => 'جهت بررسی و اعلام نظر',
        'for_approval' => 'جهت تایید',
        'for_signature' => 'جهت امضا',
        'for_archive' => 'جهت بایگانی',
        'follow_up' => 'جهت پیگیری',
        'issue_order' => 'جهت صدور دستور',
        'info_usage' => 'جهت اطلاع و بهره‌برداری',
        'immediate_action' => 'اقدام فوری',
        'please_reply' => 'پاسخ داده شود',
        'attach_history' => 'سابقه ضمیمه شود',
    ];

    /**
     * Letter Priorities
     */
    const PRIORITIES = [
        'low' => 'عادی',
        'medium' => 'متوسط',
        'high' => 'فوری',
        'urgent' => 'آنی'
    ];

    /**
     * Letter Confidentiality Levels
     */
    const CONFIDENTIALITY_LEVELS = [
        'normal' => 'عادی',
        'confidential' => 'محرمانه',
        'highly_confidential' => 'سری'
    ];
}


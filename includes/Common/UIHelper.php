<?php
/**
 * UI Helper Functions
 * 
 * @package PersianOfficeAutomation\Common
 */

namespace PersianOfficeAutomation\Common;

/**
 * UI Helper Class
 */
class UIHelper {
    
    /**
     * Get title icon HTML
     * Returns uploaded icon if available, otherwise returns emoji fallback
     * 
     * @param string $fallback_emoji Default emoji to show if no icon is uploaded
     * @return string HTML for the icon
     */
    public static function getTitleIcon($fallback_emoji = '📋') {
        $icon_attachment_id = get_option('persian_oa_title_icon_attachment_id', 0);
        
        // If icon is uploaded, display it
        if ($icon_attachment_id && wp_attachment_is_image($icon_attachment_id)) {
            $icon_url = wp_get_attachment_image_url($icon_attachment_id, 'full');
            if ($icon_url) {
                return sprintf(
                    '<img src="%s" alt="%s" style="width: 100%%; height: 100%%; object-fit: contain;" />',
                    esc_url($icon_url),
                    esc_attr(get_bloginfo('name'))
                );
            }
        }
        
        // Fallback to emoji
        return esc_html($fallback_emoji);
    }

    /**
     * Get Meeting Status Label
     * 
     * @param string $status
     * @return string
     */
    public static function getMeetingStatusLabel($status) {
        $statuses = [
            'scheduled' => 'برنامه‌ریزی شده',
            'held' => 'برگزار شده',
            'cancelled' => 'لغو شده',
            'minutes_pending' => 'در انتظار صورتجلسه'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }

    /**
     * Get Priority Label
     * 
     * @param string $priority
     * @return string
     */
    public static function getPriorityLabel($priority) {
        $priorities = [
            'normal' => 'عادی',
            'immediate' => 'فوری',
            'instant' => 'آنی'
        ];
        
        return isset($priorities[$priority]) ? $priorities[$priority] : $priority;
    }

    /**
     * Get Letter Status Label
     * 
     * @param string $status
     * @return string
     */
    public static function getStatusLabel($status) {
        $statuses = [
            'draft' => 'پیش‌نویس',
            'pending' => 'در جریان',
            'replied' => 'پاسخ داده شده',
            'archived' => 'بایگانی شده',
            'rejected' => 'رد شده',
            'viewed' => 'مشاهده شده'
        ];
        
        return isset($statuses[$status]) ? $statuses[$status] : $status;
    }
}

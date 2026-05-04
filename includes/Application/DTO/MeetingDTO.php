<?php
/**
 * Meeting DTO
 * 
 * @package PersianOfficeAutomation\Application\DTO
 */

namespace PersianOfficeAutomation\Application\DTO;

use PersianOfficeAutomation\Common\JalaliDate;

class MeetingDTO {
    
    public $id;
    public $title;
    public $description;
    public $meetingDate;
    public $meetingDateGregorian;
    public $endDate; // Calculated or passed
    public $location;
    public $participants = []; // User IDs
    public $status = 'scheduled';
    public $recurrence = 'none';
    public $color = '#3b82f6';
    
    /**
     * Create DTO from request data
     */
    public static function fromRequest($data) {
        $dto = new self();
        
        $whitelist = [
            'id', 'title', 'description', 'meeting_date', 'meeting_date_gregorian', 
            'duration', 'location', 'participants', 'status', 'recurrence', 'color'
        ];
        
        $dto->id = isset($data['id']) ? intval($data['id']) : null;
        $dto->title = isset($data['title']) ? sanitize_text_field($data['title']) : '';
        $dto->description = isset($data['description']) ? wp_kses_post($data['description']) : '';
        
        $dto->meetingDate = isset($data['meeting_date']) ? sanitize_text_field($data['meeting_date']) : '';
        $dto->meetingDateGregorian = isset($data['meeting_date_gregorian']) ? sanitize_text_field($data['meeting_date_gregorian']) : '';
        $endGregorian = isset($data['end_date_gregorian']) ? sanitize_text_field($data['end_date_gregorian']) : '';

        // End date: from explicit end_date_gregorian or from start + duration
        if (!empty($endGregorian)) {
            try {
                $endDt = new \DateTime($endGregorian);
                $dto->endDate = $endDt->format('Y-m-d H:i:s');
            } catch (\Exception $e) {
                $dto->endDate = null;
            }
        }
        if (empty($dto->endDate) && !empty($dto->meetingDateGregorian) && isset($data['duration'])) {
            $durationMinutes = intval($data['duration']);
            if ($durationMinutes > 0) {
                try {
                    $start = new \DateTime($dto->meetingDateGregorian);
                    $start->modify("+{$durationMinutes} minutes");
                    $dto->endDate = $start->format('Y-m-d H:i:s');
                } catch (\Exception $e) {
                    // Invalid date format; endDate remains unset
                }
            }
        }
        
        $dto->location = isset($data['location']) ? sanitize_text_field($data['location']) : '';
        
        $dto->participants = isset($data['participants']) && is_array($data['participants']) 
            ? array_map('intval', $data['participants']) 
            : [];
            
        $dto->status = isset($data['status']) ? sanitize_text_field($data['status']) : 'scheduled';
        $dto->recurrence = isset($data['recurrence']) ? sanitize_text_field($data['recurrence']) : 'none';
        $dto->color = isset($data['color']) ? sanitize_text_field($data['color']) : '#3b82f6';
        
        return $dto;
    }
    
    /**
     * Validate DTO
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->title)) {
            $errors['title'] = 'عنوان جلسه الزامی است.';
        }
        
        if (empty($this->meetingDate) && empty($this->meetingDateGregorian)) {
            $errors['meeting_date'] = 'تاریخ جلسه الزامی است.';
        }
        if (!empty($this->meetingDateGregorian) && !empty($this->endDate)) {
            try {
                $start = new \DateTime($this->meetingDateGregorian);
                $end = new \DateTime($this->endDate);
                if ($end <= $start) {
                    $errors['end_date'] = 'زمان پایان باید بعد از زمان شروع باشد.';
                }
            } catch (\Exception $e) {
                // ignore
            }
        }
        return $errors;
    }
}



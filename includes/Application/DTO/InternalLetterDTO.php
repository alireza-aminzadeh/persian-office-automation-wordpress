<?php
/**
 * Internal Letter DTO
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Application\DTO;

use PersianOfficeAutomation\Common\JalaliDate;

class InternalLetterDTO {
    
    public $id;
    public $number;
    public $letterDate;
    public $letterDateGregorian;
    public $recipientId; // First/main recipient (backward compat)
    /** @var int[] */
    public $recipientIds = []; // All recipient user IDs (multi-select)
    public $subject;
    public $content;
    public $priority = 'normal';
    public $confidentiality = 'normal';
    public $status = 'draft';
    public $attachments = [];
    public $ccRecipients = []; // Array of user IDs
    
    /**
     * Create DTO from request data
     */
    public static function fromRequest($data) {
        $dto = new self();
        
        $dto->id = isset($data['id']) ? intval($data['id']) : null;
        
        $number = isset($data['letter_number']) ? JalaliDate::toEnglishNumbers($data['letter_number']) : '';
        $dto->number = sanitize_text_field($number);
        
        $dto->letterDate = isset($data['letter_date']) ? sanitize_text_field($data['letter_date']) : '';
        $dto->letterDateGregorian = isset($data['letter_date_gregorian']) ? sanitize_text_field($data['letter_date_gregorian']) : '';
        // Multiple recipients: recipient_ids[] takes precedence; fallback to single recipient_id
        if (!empty($data['recipient_ids']) && is_array($data['recipient_ids'])) {
            $dto->recipientIds = array_values(array_filter(array_map('intval', $data['recipient_ids'])));
            $dto->recipientId = !empty($dto->recipientIds) ? $dto->recipientIds[0] : 0;
        } else {
            $dto->recipientId = isset($data['recipient_id']) ? intval($data['recipient_id']) : 0;
            $dto->recipientIds = $dto->recipientId ? [$dto->recipientId] : [];
        }
        $dto->subject = isset($data['subject']) ? sanitize_text_field($data['subject']) : '';
        $dto->content = isset($data['content']) ? wp_kses_post($data['content']) : '';
        $dto->priority = isset($data['priority']) ? sanitize_text_field($data['priority']) : 'normal';
        $dto->confidentiality = isset($data['confidentiality']) ? sanitize_text_field($data['confidentiality']) : 'normal';
        
        if (isset($data['status'])) {
            $dto->status = sanitize_text_field($data['status']);
        }
        
        if (isset($data['cc_recipients']) && is_array($data['cc_recipients'])) {
            $dto->ccRecipients = array_map('intval', $data['cc_recipients']);
        }
        
        return $dto;
    }
    
    /**
     * Validate DTO
     */
    public function validate() {
        $errors = [];
        
        if (empty($this->number)) {
            $errors['letter_number'] = 'شماره نامه الزامی است.';
        }
        
        if (empty($this->letterDate)) {
            $errors['letter_date'] = 'تاریخ نامه الزامی است.';
        }
        
        if (empty($this->recipientIds)) {
            $errors['recipient_ids'] = 'حداقل یک گیرنده انتخاب کنید.';
        }
        
        if (empty($this->subject)) {
            $errors['subject'] = 'موضوع نامه الزامی است.';
        }
        
        if (empty($this->content)) {
            $errors['content'] = 'متن نامه الزامی است.';
        }
        
        return $errors;
    }
}


<?php
/**
 * Outgoing Letter DTO
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Application\DTO;

use PersianOfficeAutomation\Common\JalaliDate;

class OutgoingLetterDTO {
    
    public $id;
    public $number;
    public $letterDate;
    public $letterDateGregorian;
    public $recipient; // External recipient name
    public $signer; // Internal user ID
    public $subject;
    public $content;
    public $priority = 'normal';
    public $status = 'draft';
    public $notes;
    public $attachments = [];
    
    /**
     * Create DTO from request data
     */
    public static function fromRequest($data) {
        $dto = new self();
        
        $whitelist = [
            'id', 'letter_number', 'letter_date', 'letter_date_gregorian', 
            'recipient', 'signer', 'subject', 'content', 'priority', 'notes', 'status'
        ];
        
        $dto->id = isset($data['id']) ? intval($data['id']) : null;
        
        $number = isset($data['letter_number']) ? JalaliDate::toEnglishNumbers($data['letter_number']) : '';
        $dto->number = sanitize_text_field($number);
        
        $dto->letterDate = isset($data['letter_date']) ? sanitize_text_field($data['letter_date']) : '';
        $dto->letterDateGregorian = isset($data['letter_date_gregorian']) ? sanitize_text_field($data['letter_date_gregorian']) : '';
        $dto->recipient = isset($data['recipient']) ? sanitize_text_field($data['recipient']) : '';
        $dto->signer = isset($data['signer']) ? intval($data['signer']) : 0;
        $dto->subject = isset($data['subject']) ? sanitize_text_field($data['subject']) : '';
        $dto->content = isset($data['content']) ? wp_kses_post($data['content']) : '';
        $dto->priority = isset($data['priority']) ? sanitize_text_field($data['priority']) : 'normal';
        $dto->notes = isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '';
        
        // Status logic handled in controller usually, but can be set here if passed
        if (isset($data['status'])) {
            $dto->status = sanitize_text_field($data['status']);
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
        
        if (empty($this->recipient)) {
            $errors['recipient'] = 'گیرنده نامه الزامی است.';
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


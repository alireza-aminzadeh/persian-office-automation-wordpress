<?php
/**
 * Incoming Letter DTO (Data Transfer Object)
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Application\DTO;

use PersianOfficeAutomation\Common\JalaliDate;

class IncomingLetterDTO {
    
    public $id;
    public $number;
    public $referenceNumber;
    public $subject;
    public $description;
    public $content;
    public $sender;
    public $senderDepartment;
    public $senderPhone;
    public $senderEmail;
    public $category;
    public $priority = 'medium';
    public $confidentiality = 'normal';
    public $status = 'draft';
    public $letterDate;
    public $letterDateGregorian;
    public $receivedAt;
    public $receivedAtGregorian;
    public $deadline;
    public $deadlineGregorian;
    public $archiveCode;
    public $physicalLocation;
    public $shelfFolder;
    public $primaryRecipient;
    public $ccRecipients = [];
    public $instruction;
    public $tags;
    public $keywords;
    public $notes;
    public $attachments = [];
    
    /**
     * Create DTO from request data
     */
    public static function fromRequest($data) {
        $dto = new self();
        
        $whitelist = [
            'id', 'number', 'reference_number', 'subject', 'description', 'content', 'sender',
            'sender_department', 'sender_phone', 'sender_email', 'category', 'priority',
            'confidentiality', 'status', 'letter_date', 'letter_date_gregorian', 'received_at',
            'received_at_gregorian', 'deadline', 'deadline_gregorian', 'archive_code', 
            'physical_location', 'shelf_folder', 'primary_recipient', 'cc_recipients', 
            'instruction', 'tags', 'keywords', 'notes'
        ];
        
        $dto->id = isset($data['id']) ? intval($data['id']) : null;
        
        $number = isset($data['number']) ? JalaliDate::toEnglishNumbers($data['number']) : '';
        $dto->number = sanitize_text_field($number);
        
        $referenceNumber = isset($data['reference_number']) ? JalaliDate::toEnglishNumbers($data['reference_number']) : '';
        $dto->referenceNumber = sanitize_text_field($referenceNumber);
        
        $dto->subject = isset($data['subject']) ? sanitize_text_field($data['subject']) : '';
        $dto->description = isset($data['description']) ? sanitize_textarea_field($data['description']) : '';
        $dto->content = isset($data['content']) ? wp_kses_post($data['content']) : '';
        $dto->sender = isset($data['sender']) ? sanitize_text_field($data['sender']) : '';
        $dto->senderDepartment = isset($data['sender_department']) ? sanitize_text_field($data['sender_department']) : '';
        $dto->senderPhone = isset($data['sender_phone']) ? sanitize_text_field($data['sender_phone']) : '';
        $dto->senderEmail = isset($data['sender_email']) ? sanitize_email($data['sender_email']) : '';
        $dto->category = isset($data['category']) ? sanitize_text_field($data['category']) : '';
        $dto->priority = isset($data['priority']) ? sanitize_text_field($data['priority']) : 'medium';
        $dto->confidentiality = isset($data['confidentiality']) ? sanitize_text_field($data['confidentiality']) : 'normal';
        $dto->status = isset($data['status']) ? sanitize_text_field($data['status']) : 'draft';
        $dto->letterDate = isset($data['letter_date']) ? sanitize_text_field($data['letter_date']) : '';
        $dto->letterDateGregorian = isset($data['letter_date_gregorian']) ? sanitize_text_field($data['letter_date_gregorian']) : '';
        $dto->receivedAt = isset($data['received_at']) ? sanitize_text_field($data['received_at']) : '';
        $dto->receivedAtGregorian = isset($data['received_at_gregorian']) ? sanitize_text_field($data['received_at_gregorian']) : '';
        $dto->deadline = isset($data['deadline']) ? sanitize_text_field($data['deadline']) : '';
        $dto->deadlineGregorian = isset($data['deadline_gregorian']) ? sanitize_text_field($data['deadline_gregorian']) : '';
        $dto->archiveCode = isset($data['archive_code']) ? sanitize_text_field($data['archive_code']) : '';
        $dto->physicalLocation = isset($data['physical_location']) ? sanitize_text_field($data['physical_location']) : '';
        $dto->shelfFolder = isset($data['shelf_folder']) ? sanitize_text_field($data['shelf_folder']) : '';
        $dto->primaryRecipient = isset($data['primary_recipient']) ? intval($data['primary_recipient']) : null;
        $dto->ccRecipients = isset($data['cc_recipients']) && is_array($data['cc_recipients']) ? array_map('intval', $data['cc_recipients']) : [];
        $dto->instruction = isset($data['instruction']) ? sanitize_textarea_field($data['instruction']) : '';
        $dto->tags = isset($data['tags']) ? sanitize_text_field($data['tags']) : '';
        $dto->keywords = isset($data['keywords']) ? sanitize_text_field($data['keywords']) : '';
        $dto->notes = isset($data['notes']) ? sanitize_textarea_field($data['notes']) : '';
        
        return $dto;
    }
    
    /**
     * Validate DTO
     */
    public function validate() {
        $errors = [];
        
        // Number is optional for new letters - auto-generated by service
        if (!empty($this->id) && empty(trim($this->number))) {
            $errors['number'] = 'شماره نامه الزامی است.';
        }
        
        if (empty($this->subject)) {
            $errors['subject'] = 'موضوع نامه الزامی است.';
        } elseif (mb_strlen($this->subject) < 5) {
            $errors['subject'] = 'موضوع نامه باید حداقل 5 کاراکتر باشد.';
        }
        
        if (empty($this->sender)) {
            $errors['sender'] = 'نام فرستنده الزامی است.';
        }
        
        if (empty($this->letterDate)) {
            $errors['letter_date'] = 'تاریخ نامه الزامی است.';
        }
        
        if (!in_array($this->priority, ['low', 'medium', 'high', 'urgent'])) {
            $errors['priority'] = 'اولویت نامعتبر است.';
        }
        
        if (!in_array($this->status, ['draft', 'pending', 'approved', 'rejected'])) {
            $errors['status'] = 'وضعیت نامعتبر است.';
        }
        
        if (!empty($this->senderEmail) && !is_email($this->senderEmail)) {
            $errors['sender_email'] = 'ایمیل نامعتبر است.';
        }
        
        return $errors;
    }
}
















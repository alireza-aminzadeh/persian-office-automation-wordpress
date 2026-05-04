<?php
/**
 * Correspondence Service
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Application\Services;

use PersianOfficeAutomation\Domain\Entity\Correspondence;
use PersianOfficeAutomation\Domain\Repository\CorrespondenceRepositoryInterface;
use PersianOfficeAutomation\Application\DTO\IncomingLetterDTO;
use PersianOfficeAutomation\Application\DTO\OutgoingLetterDTO;
use PersianOfficeAutomation\Application\DTO\InternalLetterDTO;
use PersianOfficeAutomation\Common\JalaliDate;

class CorrespondenceService {
    
    private $repository;
    
    public function __construct(CorrespondenceRepositoryInterface $repository) {
        $this->repository = $repository;
    }
    
    /**
     * Create incoming letter
     */
    public function createIncomingLetter(IncomingLetterDTO $dto) {
        // Validate
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Auto-generate unique number: empty or duplicate -> generate until unique
        $number = trim($dto->number);
        if (empty($number)) {
            $number = $this->generateNextIncomingNumber('IN-');
        }
        $maxAttempts = 20;
        while ($this->repository->numberExists($number) && $maxAttempts-- > 0) {
            $number = $this->incrementIncomingNumber($number);
        }
        if ($this->repository->numberExists($number)) {
            return ['success' => false, 'errors' => ['general' => 'خطا در تولید شماره یکتا. لطفاً دوباره تلاش کنید.']];
        }
        
        // Create entity
        $correspondence = new Correspondence();
        $correspondence->setType('incoming');
        $correspondence->setNumber($number);
        $correspondence->setReferenceNumber($dto->referenceNumber);
        $correspondence->setSubject($dto->subject);
        $correspondence->setDescription($dto->description);
        $correspondence->setContent($dto->content);
        $correspondence->setSender($dto->sender);
        $correspondence->setSenderDepartment($dto->senderDepartment);
        $correspondence->setSenderPhone($dto->senderPhone);
        $correspondence->setSenderEmail($dto->senderEmail);
        $correspondence->setCategory($dto->category);
        $correspondence->setPriority($dto->priority);
        $correspondence->setConfidentiality($dto->confidentiality);
        $correspondence->setStatus($dto->status);
        
        // Convert Jalali to Gregorian
        if (!empty($dto->letterDateGregorian)) {
            $correspondence->setLetterDate($dto->letterDateGregorian);
        } elseif (!empty($dto->letterDate)) {
            $correspondence->setLetterDate(JalaliDate::jalaliToGregorianString($dto->letterDate));
        }
        
        if (!empty($dto->receivedAtGregorian)) {
             $correspondence->setReceivedAt($dto->receivedAtGregorian);
        } elseif (!empty($dto->receivedAt)) {
            $correspondence->setReceivedAt($dto->receivedAt); // This might need conversion if it's jalali string but setReceivedAt expects datetime
            // But receivedAt usually has time component? 
            // In form it's a datepicker.
            // Let's assume receivedAt needs conversion if string
             if (!is_numeric($dto->receivedAt) && preg_match('/^1[34]/', $dto->receivedAt)) {
                $correspondence->setReceivedAt(JalaliDate::jalaliToGregorianString($dto->receivedAt));
             }
        } else {
            $correspondence->setReceivedAt(current_time('mysql'));
        }
        
        if (!empty($dto->deadlineGregorian)) {
            $correspondence->setDeadline($dto->deadlineGregorian);
        } elseif (!empty($dto->deadline)) {
            $correspondence->setDeadline(JalaliDate::jalaliToGregorianString($dto->deadline));
        }
        
        $correspondence->setArchiveCode($dto->archiveCode);
        $correspondence->setPhysicalLocation($dto->physicalLocation);
        $correspondence->setShelfFolder($dto->shelfFolder);
        $correspondence->setPrimaryRecipient($dto->primaryRecipient);
        $correspondence->setInstruction($dto->instruction);
        $correspondence->setTags($dto->tags);
        $correspondence->setKeywords($dto->keywords);
        $correspondence->setNotes($dto->notes);
        $correspondence->setCreatedBy(get_current_user_id());
        
        // Save
        $id = $this->repository->save($correspondence);
        
        if ($id) {
            // Save CC recipients
            if (!empty($dto->ccRecipients)) {
                $this->saveCCRecipients($id, $dto->ccRecipients);
            }
            
            // Handle attachments
            if (!empty($dto->attachments)) {
                $this->saveAttachments($id, $dto->attachments);
            }
            
            // Log action
            $this->logAction($id, 'create', null, $correspondence->toArray());
            
            return ['success' => true, 'id' => $id, 'message' => 'نامه وارده با موفقیت ثبت شد.'];
        }
        
        return ['success' => false, 'errors' => ['general' => 'خطا در ذخیره نامه. لطفاً دوباره تلاش کنید.']];
    }
    
    /**
     * Update incoming letter
     */
    public function updateIncomingLetter(IncomingLetterDTO $dto) {
        // Validate
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Check if exists
        $existing = $this->repository->findById($dto->id);
        if (!$existing) {
            return ['success' => false, 'errors' => ['general' => 'نامه مورد نظر یافت نشد.']];
        }
        
        // Check if number exists (excluding current)
        if ($this->repository->numberExists($dto->number, $dto->id)) {
            return ['success' => false, 'errors' => ['number' => 'این شماره نامه قبلاً ثبت شده است.']];
        }
        
        // Store old values for audit
        $oldValues = $existing->toArray();
        
        // Update entity
        $existing->setNumber($dto->number);
        $existing->setReferenceNumber($dto->referenceNumber);
        $existing->setSubject($dto->subject);
        $existing->setDescription($dto->description);
        $existing->setContent($dto->content);
        $existing->setSender($dto->sender);
        $existing->setSenderDepartment($dto->senderDepartment);
        $existing->setSenderPhone($dto->senderPhone);
        $existing->setSenderEmail($dto->senderEmail);
        $existing->setCategory($dto->category);
        $existing->setPriority($dto->priority);
        $existing->setConfidentiality($dto->confidentiality);
        $existing->setStatus($dto->status);
        
        if (!empty($dto->letterDateGregorian)) {
            $existing->setLetterDate($dto->letterDateGregorian);
        } elseif (!empty($dto->letterDate)) {
            $existing->setLetterDate(JalaliDate::jalaliToGregorianString($dto->letterDate));
        }
        
        if (!empty($dto->deadlineGregorian)) {
            $existing->setDeadline($dto->deadlineGregorian);
        } elseif (!empty($dto->deadline)) {
            $existing->setDeadline(JalaliDate::jalaliToGregorianString($dto->deadline));
        }
        
        $existing->setArchiveCode($dto->archiveCode);
        $existing->setPhysicalLocation($dto->physicalLocation);
        $existing->setShelfFolder($dto->shelfFolder);
        $existing->setPrimaryRecipient($dto->primaryRecipient);
        $existing->setInstruction($dto->instruction);
        $existing->setTags($dto->tags);
        $existing->setKeywords($dto->keywords);
        $existing->setNotes($dto->notes);
        $existing->setUpdatedBy(get_current_user_id());
        
        // Update
        $success = $this->repository->update($existing);
        
        if ($success) {
            // Update CC recipients
            $this->deleteCCRecipients($dto->id);
            if (!empty($dto->ccRecipients)) {
                $this->saveCCRecipients($dto->id, $dto->ccRecipients);
            }
            
            // Log action
            $this->logAction($dto->id, 'update', $oldValues, $existing->toArray());
            
            return ['success' => true, 'message' => 'نامه وارده با موفقیت ویرایش شد.'];
        }
        
        return ['success' => false, 'errors' => ['general' => 'خطا در ویرایش نامه. لطفاً دوباره تلاش کنید.']];
    }
    
    /**
     * Get incoming letter by ID
     */
    public function getIncomingLetter($id) {
        $letter = $this->repository->findById($id);
        
        if (!$letter || $letter->getType() !== 'incoming') {
            return null;
        }
        
        return $letter;
    }

    /**
     * Create outgoing letter
     */
    public function createOutgoingLetter(OutgoingLetterDTO $dto) {
        // Auto-generate next number when empty (counter behavior)
        $number = trim($dto->number);
        if (empty($number)) {
            $number = $this->generateNextOutgoingNumber('OUT-');
            $maxAttempts = 20;
            while ($this->repository->numberExists($number) && $maxAttempts-- > 0) {
                $number = $this->incrementOutgoingNumber($number);
            }
            if ($this->repository->numberExists($number)) {
                return ['success' => false, 'errors' => ['letter_number' => 'امکان تخصیص شماره خودکار وجود ندارد. لطفاً شماره را دستی وارد کنید.']];
            }
            $dto->number = $number;
        }

        // Validate
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Check if number exists
        if ($this->repository->numberExists($dto->number)) {
            return ['success' => false, 'errors' => ['letter_number' => 'این شماره نامه قبلاً ثبت شده است.']];
        }

        // Create entity
        $correspondence = new Correspondence();
        $correspondence->setType('outgoing');
        $correspondence->setNumber($dto->number);
        $correspondence->setSubject($dto->subject);
        $correspondence->setContent($dto->content);
        $correspondence->setRecipient($dto->recipient);
        $correspondence->setPrimaryRecipient($dto->signer); // Internal signer
        $correspondence->setPriority($dto->priority);
        $correspondence->setStatus($dto->status);
        $correspondence->setNotes($dto->notes);
        
        // Date handling
        if (!empty($dto->letterDateGregorian)) {
            $correspondence->setLetterDate($dto->letterDateGregorian);
        } elseif (!empty($dto->letterDate)) {
            $correspondence->setLetterDate(JalaliDate::jalaliToGregorianString($dto->letterDate));
        }
        
        $correspondence->setCreatedBy(get_current_user_id());
        $correspondence->setCreatedAt(current_time('mysql'));
        
        // Save
        $id = $this->repository->save($correspondence);
        
        if ($id) {
            // Handle attachments
            if (!empty($dto->attachments)) {
                $this->saveAttachments($id, $dto->attachments);
            }
            
            // Log action
            $this->logAction($id, 'create_outgoing', null, $correspondence->toArray());
            
            return ['success' => true, 'id' => $id, 'message' => 'نامه صادره با موفقیت ثبت شد.'];
        }
        
        return ['success' => false, 'errors' => ['general' => 'خطا در ذخیره نامه. لطفاً دوباره تلاش کنید.']];
    }
    
    /**
     * Get outgoing letter by ID
     */
    public function getOutgoingLetter($id) {
        $letter = $this->repository->findById($id);
        
        if (!$letter || $letter->getType() !== 'outgoing') {
            return null;
        }
        
        return $letter;
    }

    /**
     * Update outgoing letter
     */
    public function updateOutgoingLetter(OutgoingLetterDTO $dto) {
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        $existing = $this->repository->findById($dto->id);
        if (!$existing || $existing->getType() !== 'outgoing') {
            return ['success' => false, 'errors' => ['general' => 'نامه مورد نظر یافت نشد.']];
        }
        if ($this->repository->numberExists($dto->number, $dto->id)) {
            return ['success' => false, 'errors' => ['letter_number' => 'این شماره نامه قبلاً ثبت شده است.']];
        }
        $oldValues = $existing->toArray();
        $existing->setNumber($dto->number);
        $existing->setSubject($dto->subject);
        $existing->setContent($dto->content);
        $existing->setRecipient($dto->recipient);
        $existing->setPrimaryRecipient($dto->signer);
        $existing->setPriority($dto->priority);
        $existing->setStatus($dto->status);
        $existing->setNotes($dto->notes);
        $existing->setUpdatedBy(get_current_user_id());
        if (!empty($dto->letterDateGregorian)) {
            $existing->setLetterDate($dto->letterDateGregorian);
        } elseif (!empty($dto->letterDate)) {
            $existing->setLetterDate(JalaliDate::jalaliToGregorianString($dto->letterDate));
        }
        $success = $this->repository->update($existing);
        if ($success) {
            if (!empty($dto->attachments)) {
                $this->saveAttachments($dto->id, $dto->attachments);
            }
            $this->logAction($dto->id, 'update_outgoing', $oldValues, $existing->toArray());
            return ['success' => true, 'message' => 'نامه صادره با موفقیت ویرایش شد.'];
        }
        return ['success' => false, 'errors' => ['general' => 'خطا در ویرایش نامه. لطفاً دوباره تلاش کنید.']];
    }

    /**
     * Create internal letter
     */
    public function createInternalLetter(InternalLetterDTO $dto) {
        // Validate (except number can be empty for auto-generation)
        $number = trim($dto->number ?? '');
        if (empty($number)) {
            $number = $this->generateNextInternalNumber('INT-');
        }
        $maxAttempts = 20;
        while ($this->repository->numberExists($number) && $maxAttempts-- > 0) {
            $number = $this->incrementInternalNumber($number);
        }
        if ($this->repository->numberExists($number)) {
            return ['success' => false, 'errors' => ['letter_number' => 'امکان تخصیص شماره خودکار وجود ندارد. لطفاً دوباره تلاش کنید.']];
        }
        $dto->number = $number;

        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // First recipient = primary; rest = CC (so all receive the letter)
        $primaryRecipient = !empty($dto->recipientIds) ? $dto->recipientIds[0] : $dto->recipientId;
        $ccRecipients = !empty($dto->recipientIds)
            ? array_slice($dto->recipientIds, 1)
            : $dto->ccRecipients;

        // Create entity
        $correspondence = new Correspondence();
        $correspondence->setType('internal');
        $correspondence->setNumber($dto->number);
        $correspondence->setSubject($dto->subject);
        $correspondence->setContent($dto->content);
        $correspondence->setPrimaryRecipient($primaryRecipient);
        $correspondence->setPriority($dto->priority);
        $correspondence->setConfidentiality($dto->confidentiality);
        $correspondence->setStatus($dto->status);
        
        // Date handling
        if (!empty($dto->letterDateGregorian)) {
            $correspondence->setLetterDate($dto->letterDateGregorian);
        } elseif (!empty($dto->letterDate)) {
            $correspondence->setLetterDate(JalaliDate::jalaliToGregorianString($dto->letterDate));
        }
        
        $correspondence->setCreatedBy(get_current_user_id());
        $correspondence->setCreatedAt(current_time('mysql'));
        
        // Save
        $id = $this->repository->save($correspondence);
        
        if ($id) {
            // Save additional recipients as CC (so they appear in inbox)
            if (!empty($ccRecipients)) {
                $this->saveCCRecipients($id, $ccRecipients);
            }

            // Handle attachments
            if (!empty($dto->attachments)) {
                $this->saveAttachments($id, $dto->attachments);
            }
            
            // Log action
            $this->logAction($id, 'create_internal', null, $correspondence->toArray());
            
            return ['success' => true, 'id' => $id, 'message' => 'نامه داخلی با موفقیت ثبت شد.'];
        }
        
        return ['success' => false, 'errors' => ['general' => 'خطا در ذخیره نامه. لطفاً دوباره تلاش کنید.']];
    }

    /**
     * Get internal letter by ID
     */
    public function getInternalLetter($id) {
        $letter = $this->repository->findById($id);
        
        if (!$letter || $letter->getType() !== 'internal') {
            return null;
        }
        
        return $letter;
    }

    /**
     * Generate next letter number
     */
    public function generateNextIncomingNumber($prefix = '') {
        $year = JalaliDate::now('Y');
        $fullPrefix = $prefix . $year . '/';
        return $this->repository->generateNextNumber('incoming', $fullPrefix);
    }

    /**
     * Increment incoming letter number (for resolving duplicates)
     */
    private function incrementIncomingNumber($number) {
        $year = JalaliDate::now('Y');
        $fullPrefix = 'IN-' . $year . '/';
        if (strpos($number, $fullPrefix) !== 0) {
            return $this->generateNextIncomingNumber('IN-');
        }
        $numericPart = preg_replace('/[^0-9]/', '', substr($number, strlen($fullPrefix)));
        $next = (int) $numericPart + 1;
        return $fullPrefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate next outgoing number
     */
    public function generateNextOutgoingNumber($prefix = '') {
        $year = JalaliDate::now('Y');
        $fullPrefix = $prefix . $year . '/';
        return $this->repository->generateNextNumber('outgoing', $fullPrefix);
    }

    /**
     * Increment outgoing letter number (for resolving duplicates / race)
     */
    private function incrementOutgoingNumber($number) {
        $year = JalaliDate::now('Y');
        $fullPrefix = 'OUT-' . $year . '/';
        if (strpos($number, $fullPrefix) !== 0) {
            return $this->generateNextOutgoingNumber('OUT-');
        }
        $numericPart = preg_replace('/[^0-9]/', '', substr($number, strlen($fullPrefix)));
        $next = (int) $numericPart + 1;
        return $fullPrefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate next internal number
     */
    public function generateNextInternalNumber($prefix = '') {
        $year = JalaliDate::now('Y');
        $fullPrefix = $prefix . $year . '/';
        return $this->repository->generateNextNumber('internal', $fullPrefix);
    }

    /**
     * Increment internal letter number (for resolving duplicates / race)
     */
    private function incrementInternalNumber($number) {
        $year = JalaliDate::now('Y');
        $fullPrefix = 'INT-' . $year . '/';
        if (strpos($number, $fullPrefix) !== 0) {
            return $this->generateNextInternalNumber('INT-');
        }
        $numericPart = preg_replace('/[^0-9]/', '', substr($number, strlen($fullPrefix)));
        $next = (int) $numericPart + 1;
        return $fullPrefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Save CC recipients
     */
    private function saveCCRecipients($correspondenceId, $recipients) {
        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_cc_recipients';
        foreach ($recipients as $userId) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb->prefix.
            $wpdb->insert($table, [
                'correspondence_id' => $correspondenceId,
                'user_id' => $userId,
                'is_read' => 0
            ]);
        }
    }
    
    /**
     * Delete CC recipients
     */
    private function deleteCCRecipients($correspondenceId) {
        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_cc_recipients';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb->prefix.
        $wpdb->delete($table, ['correspondence_id' => $correspondenceId]);
    }
    
    /**
     * Save attachments
     */
    private function saveAttachments($correspondenceId, $attachments) {
        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_attachments';
        foreach ($attachments as $attachment) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb->prefix.
            $wpdb->insert($table, [
                'correspondence_id' => $correspondenceId,
                'file_name' => $attachment['name'],
                'file_path' => $attachment['path'],
                'file_type' => $attachment['type'],
                'file_size' => $attachment['size'],
                'uploaded_by' => get_current_user_id()
            ]);
        }
    }
    
    /**
     * Get circulation history for a letter
     */
    public function getCirculationHistory($correspondenceId) {
        global $wpdb;
        
        $correspondence = $this->repository->findById($correspondenceId);
        if (!$correspondence) {
            return [];
        }

        $history = [];
        
        // 1. Creation event
        $creator = get_userdata($correspondence->getCreatedBy());
        $history[] = [
            'type' => 'created',
            'user_id' => $correspondence->getCreatedBy(),
            'user_name' => $creator ? $creator->display_name : 'کاربر حذف شده',
            'action' => 'ایجاد نامه',
            'timestamp' => $correspondence->getCreatedAt(),
            'details' => 'نامه ثبت شد'
        ];

        // 2. Referrals
        $table_referrals = $wpdb->prefix . 'persian_oa_referrals';
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from prefix; correspondenceId int.
        $referrals = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_referrals WHERE correspondence_id = %d ORDER BY referred_at ASC",
            $correspondenceId
        ));
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter

        foreach ($referrals as $ref) {
            $sender = get_userdata($ref->from_user);
            $receiver = get_userdata($ref->to_user);
            
            $history[] = [
                'type' => 'referral',
                'user_id' => $ref->from_user,
                'user_name' => $sender ? $sender->display_name : 'کاربر حذف شده',
                'target_id' => $ref->to_user,
                'target_name' => $receiver ? $receiver->display_name : 'کاربر حذف شده',
                'action' => 'ارجاع',
                'timestamp' => $ref->referred_at,
                'details' => $ref->comments,
                'status' => $ref->status
            ];
            
            // If responded
            if ($ref->responded_at) {
                 $history[] = [
                    'type' => 'response',
                    'user_id' => $ref->to_user,
                    'user_name' => $receiver ? $receiver->display_name : 'کاربر حذف شده',
                    'target_id' => $ref->from_user,
                    'target_name' => $sender ? $sender->display_name : 'کاربر حذف شده',
                    'action' => 'پاسخ ارجاع',
                    'timestamp' => $ref->responded_at,
                    'details' => $ref->response_text,
                    'status' => 'responded'
                ];
            }
        }

        // Sort by timestamp
        usort($history, function($a, $b) {
            return strtotime($a['timestamp']) - strtotime($b['timestamp']);
        });

        return $history;
    }
    
    /**
     * Log action in audit trail
     */
    private function logAction($correspondenceId, $action, $oldValue, $newValue) {
        global $wpdb;
        $table = $wpdb->prefix . 'persian_oa_audit_log';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table from $wpdb->prefix.
        $wpdb->insert($table, [
            'correspondence_id' => $correspondenceId,
            'user_id' => get_current_user_id(),
            'action' => $action,
            'old_value' => maybe_serialize($oldValue),
            'new_value' => maybe_serialize($newValue),
            'ip_address' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])) : ''
        ]);
    }
}

<?php
/**
 * Correspondence Entity
 * 
 * @package OfficeAutomation
 */

namespace PersianOfficeAutomation\Domain\Entity;

class Correspondence {
    
    private $id;
    private $type;
    private $number;
    private $referenceNumber;
    private $subject;
    private $description;
    private $content;
    private $sender;
    private $recipient;
    private $senderDepartment;
    private $senderPhone;
    private $senderEmail;
    private $category;
    private $priority;
    private $confidentiality;
    private $status;
    private $letterDate;
    private $receivedAt;
    private $deadline;
    private $archiveCode;
    private $physicalLocation;
    private $shelfFolder;
    private $primaryRecipient;
    private $instruction;
    private $replyNumber;
    private $repliedAt;
    private $replyContent;
    private $actionType;
    private $tags;
    private $keywords;
    private $notes;
    private $createdBy;
    private $updatedBy;
    private $createdAt;
    private $updatedAt;
    
    // Getters
    public function getId() { return $this->id; }
    public function getType() { return $this->type; }
    public function getNumber() { return $this->number; }
    public function getReferenceNumber() { return $this->referenceNumber; }
    public function getSubject() { return $this->subject; }
    public function getDescription() { return $this->description; }
    public function getContent() { return $this->content; }
    public function getSender() { return $this->sender; }
    public function getRecipient() { return $this->recipient; }
    public function getSenderDepartment() { return $this->senderDepartment; }
    public function getSenderPhone() { return $this->senderPhone; }
    public function getSenderEmail() { return $this->senderEmail; }
    public function getCategory() { return $this->category; }
    public function getPriority() { return $this->priority; }
    public function getConfidentiality() { return $this->confidentiality; }
    public function getStatus() { return $this->status; }
    public function getLetterDate() { return $this->letterDate; }
    public function getReceivedAt() { return $this->receivedAt; }
    public function getDeadline() { return $this->deadline; }
    public function getArchiveCode() { return $this->archiveCode; }
    public function getPhysicalLocation() { return $this->physicalLocation; }
    public function getShelfFolder() { return $this->shelfFolder; }
    public function getPrimaryRecipient() { return $this->primaryRecipient; }
    public function getInstruction() { return $this->instruction; }
    public function getReplyNumber() { return $this->replyNumber; }
    public function getRepliedAt() { return $this->repliedAt; }
    public function getReplyContent() { return $this->replyContent; }
    public function getActionType() { return $this->actionType; }
    public function getTags() { return $this->tags; }
    public function getKeywords() { return $this->keywords; }
    public function getNotes() { return $this->notes; }
    public function getCreatedBy() { return $this->createdBy; }
    public function getUpdatedBy() { return $this->updatedBy; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setType($type) { $this->type = $type; }
    public function setNumber($number) { $this->number = $number; }
    public function setReferenceNumber($referenceNumber) { $this->referenceNumber = $referenceNumber; }
    public function setSubject($subject) { $this->subject = $subject; }
    public function setDescription($description) { $this->description = $description; }
    public function setContent($content) { $this->content = $content; }
    public function setSender($sender) { $this->sender = $sender; }
    public function setRecipient($recipient) { $this->recipient = $recipient; }
    public function setSenderDepartment($senderDepartment) { $this->senderDepartment = $senderDepartment; }
    public function setSenderPhone($senderPhone) { $this->senderPhone = $senderPhone; }
    public function setSenderEmail($senderEmail) { $this->senderEmail = $senderEmail; }
    public function setCategory($category) { $this->category = $category; }
    public function setPriority($priority) { $this->priority = $priority; }
    public function setConfidentiality($confidentiality) { $this->confidentiality = $confidentiality; }
    public function setStatus($status) { $this->status = $status; }
    public function setLetterDate($letterDate) { $this->letterDate = $letterDate; }
    public function setReceivedAt($receivedAt) { $this->receivedAt = $receivedAt; }
    public function setDeadline($deadline) { $this->deadline = $deadline; }
    public function setArchiveCode($archiveCode) { $this->archiveCode = $archiveCode; }
    public function setPhysicalLocation($physicalLocation) { $this->physicalLocation = $physicalLocation; }
    public function setShelfFolder($shelfFolder) { $this->shelfFolder = $shelfFolder; }
    public function setPrimaryRecipient($primaryRecipient) { $this->primaryRecipient = $primaryRecipient; }
    public function setInstruction($instruction) { $this->instruction = $instruction; }
    public function setReplyNumber($replyNumber) { $this->replyNumber = $replyNumber; }
    public function setRepliedAt($repliedAt) { $this->repliedAt = $repliedAt; }
    public function setReplyContent($replyContent) { $this->replyContent = $replyContent; }
    public function setActionType($actionType) { $this->actionType = $actionType; }
    public function setTags($tags) { $this->tags = $tags; }
    public function setKeywords($keywords) { $this->keywords = $keywords; }
    public function setNotes($notes) { $this->notes = $notes; }
    public function setCreatedBy($createdBy) { $this->createdBy = $createdBy; }
    public function setUpdatedBy($updatedBy) { $this->updatedBy = $updatedBy; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; }
    
    /**
     * Convert entity to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'number' => $this->number,
            'reference_number' => $this->referenceNumber,
            'subject' => $this->subject,
            'description' => $this->description,
            'content' => $this->content,
            'sender' => $this->sender,
            'recipient' => $this->recipient,
            'sender_department' => $this->senderDepartment,
            'sender_phone' => $this->senderPhone,
            'sender_email' => $this->senderEmail,
            'category' => $this->category,
            'priority' => $this->priority,
            'confidentiality' => $this->confidentiality,
            'status' => $this->status,
            'letter_date' => $this->letterDate,
            'received_at' => $this->receivedAt,
            'deadline' => $this->deadline,
            'archive_code' => $this->archiveCode,
            'physical_location' => $this->physicalLocation,
            'shelf_folder' => $this->shelfFolder,
            'primary_recipient' => $this->primaryRecipient,
            'instruction' => $this->instruction,
            'reply_number' => $this->replyNumber,
            'replied_at' => $this->repliedAt,
            'reply_content' => $this->replyContent,
            'action_type' => $this->actionType,
            'tags' => $this->tags,
            'keywords' => $this->keywords,
            'notes' => $this->notes,
            'created_by' => $this->createdBy,
            'updated_by' => $this->updatedBy,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
    
    /**
     * Create entity from array
     */
    public static function fromArray($data) {
        $entity = new self();
        
        if (isset($data['id'])) $entity->setId($data['id']);
        if (isset($data['type'])) $entity->setType($data['type']);
        if (isset($data['number'])) $entity->setNumber($data['number']);
        if (isset($data['reference_number'])) $entity->setReferenceNumber($data['reference_number']);
        if (isset($data['subject'])) $entity->setSubject($data['subject']);
        if (isset($data['description'])) $entity->setDescription($data['description']);
        if (isset($data['content'])) $entity->setContent($data['content']);
        if (isset($data['sender'])) $entity->setSender($data['sender']);
        if (isset($data['recipient'])) $entity->setRecipient($data['recipient']);
        if (isset($data['sender_department'])) $entity->setSenderDepartment($data['sender_department']);
        if (isset($data['sender_phone'])) $entity->setSenderPhone($data['sender_phone']);
        if (isset($data['sender_email'])) $entity->setSenderEmail($data['sender_email']);
        if (isset($data['category'])) $entity->setCategory($data['category']);
        if (isset($data['priority'])) $entity->setPriority($data['priority']);
        if (isset($data['confidentiality'])) $entity->setConfidentiality($data['confidentiality']);
        if (isset($data['status'])) $entity->setStatus($data['status']);
        if (isset($data['letter_date'])) $entity->setLetterDate($data['letter_date']);
        if (isset($data['received_at'])) $entity->setReceivedAt($data['received_at']);
        if (isset($data['deadline'])) $entity->setDeadline($data['deadline']);
        if (isset($data['archive_code'])) $entity->setArchiveCode($data['archive_code']);
        if (isset($data['physical_location'])) $entity->setPhysicalLocation($data['physical_location']);
        if (isset($data['shelf_folder'])) $entity->setShelfFolder($data['shelf_folder']);
        if (isset($data['primary_recipient'])) $entity->setPrimaryRecipient($data['primary_recipient']);
        if (isset($data['instruction'])) $entity->setInstruction($data['instruction']);
        if (isset($data['reply_number'])) $entity->setReplyNumber($data['reply_number']);
        if (isset($data['replied_at'])) $entity->setRepliedAt($data['replied_at']);
        if (isset($data['reply_content'])) $entity->setReplyContent($data['reply_content']);
        if (isset($data['action_type'])) $entity->setActionType($data['action_type']);
        if (isset($data['tags'])) $entity->setTags($data['tags']);
        if (isset($data['keywords'])) $entity->setKeywords($data['keywords']);
        if (isset($data['notes'])) $entity->setNotes($data['notes']);
        if (isset($data['created_by'])) $entity->setCreatedBy($data['created_by']);
        if (isset($data['updated_by'])) $entity->setUpdatedBy($data['updated_by']);
        if (isset($data['created_at'])) $entity->setCreatedAt($data['created_at']);
        if (isset($data['updated_at'])) $entity->setUpdatedAt($data['updated_at']);
        
        return $entity;
    }
}
















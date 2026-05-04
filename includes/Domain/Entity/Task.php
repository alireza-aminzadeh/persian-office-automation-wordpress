<?php
/**
 * Task Entity
 * 
 * @package PersianOfficeAutomation\Domain\Entity
 */

namespace PersianOfficeAutomation\Domain\Entity;

class Task {
    
    private $id;
    private $parentId;
    private $title;
    private $description;
    private $correspondenceId;
    private $assignedTo;
    private $assignedBy;
    private $priority;
    private $status;
    private $startDate;
    private $deadline;
    private $estimatedTime;
    private $spentTime;
    private $category;
    private $isRecurring;
    private $recurrencePattern;
    private $completedAt;
    private $checklist;
    private $progress;
    private $tags;
    private $createdAt;
    private $updatedAt;
    
    // Getters
    public function getId() { return $this->id; }
    public function getParentId() { return $this->parentId; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getCorrespondenceId() { return $this->correspondenceId; }
    public function getAssignedTo() { return $this->assignedTo; }
    public function getAssignedBy() { return $this->assignedBy; }
    public function getPriority() { return $this->priority; }
    public function getStatus() { return $this->status; }
    public function getStartDate() { return $this->startDate; }
    public function getDeadline() { return $this->deadline; }
    public function getEstimatedTime() { return $this->estimatedTime; }
    public function getSpentTime() { return $this->spentTime; }
    public function getCategory() { return $this->category; }
    public function getIsRecurring() { return $this->isRecurring; }
    public function getRecurrencePattern() { return $this->recurrencePattern; }
    public function getCompletedAt() { return $this->completedAt; }
    public function getChecklist() { return $this->checklist; }
    public function getProgress() { return $this->progress; }
    public function getTags() { return $this->tags; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setParentId($parentId) { $this->parentId = $parentId; }
    public function setTitle($title) { $this->title = $title; }
    public function setDescription($description) { $this->description = $description; }
    public function setCorrespondenceId($correspondenceId) { $this->correspondenceId = $correspondenceId; }
    public function setAssignedTo($assignedTo) { $this->assignedTo = $assignedTo; }
    public function setAssignedBy($assignedBy) { $this->assignedBy = $assignedBy; }
    public function setPriority($priority) { $this->priority = $priority; }
    public function setStatus($status) { $this->status = $status; }
    public function setStartDate($startDate) { $this->startDate = $startDate; }
    public function setDeadline($deadline) { $this->deadline = $deadline; }
    public function setEstimatedTime($estimatedTime) { $this->estimatedTime = $estimatedTime; }
    public function setSpentTime($spentTime) { $this->spentTime = $spentTime; }
    public function setCategory($category) { $this->category = $category; }
    public function setIsRecurring($isRecurring) { $this->isRecurring = $isRecurring; }
    public function setRecurrencePattern($recurrencePattern) { $this->recurrencePattern = $recurrencePattern; }
    public function setCompletedAt($completedAt) { $this->completedAt = $completedAt; }
    public function setChecklist($checklist) { $this->checklist = $checklist; }
    public function setProgress($progress) { $this->progress = $progress; }
    public function setTags($tags) { $this->tags = $tags; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'parent_id' => $this->parentId,
            'title' => $this->title,
            'description' => $this->description,
            'correspondence_id' => $this->correspondenceId,
            'assigned_to' => $this->assignedTo,
            'assigned_by' => $this->assignedBy,
            'priority' => $this->priority,
            'status' => $this->status,
            'start_date' => $this->startDate,
            'deadline' => $this->deadline,
            'estimated_time' => $this->estimatedTime,
            'spent_time' => $this->spentTime,
            'category' => $this->category,
            'is_recurring' => $this->isRecurring,
            'recurrence_pattern' => $this->recurrencePattern,
            'completed_at' => $this->completedAt,
            'checklist' => $this->checklist,
            'progress' => $this->progress,
            'tags' => $this->tags,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
    
    /**
     * Create from array
     */
    public static function fromArray($data) {
        $task = new self();
        
        if (isset($data['id'])) $task->setId($data['id']);
        if (isset($data['parent_id'])) $task->setParentId($data['parent_id']);
        if (isset($data['title'])) $task->setTitle($data['title']);
        if (isset($data['description'])) $task->setDescription($data['description']);
        if (isset($data['correspondence_id'])) $task->setCorrespondenceId($data['correspondence_id']);
        if (isset($data['assigned_to'])) $task->setAssignedTo($data['assigned_to']);
        if (isset($data['assigned_by'])) $task->setAssignedBy($data['assigned_by']);
        if (isset($data['priority'])) $task->setPriority($data['priority']);
        if (isset($data['status'])) $task->setStatus($data['status']);
        if (isset($data['start_date'])) $task->setStartDate($data['start_date']);
        if (isset($data['deadline'])) $task->setDeadline($data['deadline']);
        if (isset($data['estimated_time'])) $task->setEstimatedTime($data['estimated_time']);
        if (isset($data['spent_time'])) $task->setSpentTime($data['spent_time']);
        if (isset($data['category'])) $task->setCategory($data['category']);
        if (isset($data['is_recurring'])) $task->setIsRecurring($data['is_recurring']);
        if (isset($data['recurrence_pattern'])) $task->setRecurrencePattern($data['recurrence_pattern']);
        if (isset($data['completed_at'])) $task->setCompletedAt($data['completed_at']);
        if (isset($data['checklist'])) $task->setChecklist($data['checklist']);
        if (isset($data['progress'])) $task->setProgress($data['progress']);
        if (isset($data['tags'])) $task->setTags($data['tags']);
        if (isset($data['created_at'])) $task->setCreatedAt($data['created_at']);
        if (isset($data['updated_at'])) $task->setUpdatedAt($data['updated_at']);
        
        return $task;
    }
}

<?php
/**
 * Meeting Entity
 * 
 * @package PersianOfficeAutomation\Domain\Entity
 */

namespace PersianOfficeAutomation\Domain\Entity;

class Meeting {
    
    private $id;
    private $title;
    private $description;
    private $meetingDate;
    private $location;
    private $organizerId;
    private $minutes;
    private $decisions;
    private $status;
    private $endDate;
    private $recurrence;
    private $color;
    private $createdAt;
    private $updatedAt;
    
    // Getters
    public function getId() { return $this->id; }
    public function getTitle() { return $this->title; }
    public function getDescription() { return $this->description; }
    public function getMeetingDate() { return $this->meetingDate; }
    public function getEndDate() { return $this->endDate; }
    public function getRecurrence() { return $this->recurrence; }
    public function getColor() { return $this->color; }
    public function getLocation() { return $this->location; }
    public function getOrganizerId() { return $this->organizerId; }
    public function getMinutes() { return $this->minutes; }
    public function getDecisions() { return $this->decisions; }
    public function getStatus() { return $this->status; }
    public function getCreatedAt() { return $this->createdAt; }
    public function getUpdatedAt() { return $this->updatedAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setTitle($title) { $this->title = $title; }
    public function setDescription($description) { $this->description = $description; }
    public function setMeetingDate($meetingDate) { $this->meetingDate = $meetingDate; }
    public function setEndDate($endDate) { $this->endDate = $endDate; }
    public function setRecurrence($recurrence) { $this->recurrence = $recurrence; }
    public function setColor($color) { $this->color = $color; }
    public function setLocation($location) { $this->location = $location; }
    public function setOrganizerId($organizerId) { $this->organizerId = $organizerId; }
    public function setMinutes($minutes) { $this->minutes = $minutes; }
    public function setDecisions($decisions) { $this->decisions = $decisions; }
    public function setStatus($status) { $this->status = $status; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
    public function setUpdatedAt($updatedAt) { $this->updatedAt = $updatedAt; }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'meeting_date' => $this->meetingDate,
            'end_date' => $this->endDate,
            'location' => $this->location,
            'organizer_id' => $this->organizerId,
            'minutes' => $this->minutes,
            'decisions' => $this->decisions,
            'status' => $this->status,
            'recurrence' => $this->recurrence,
            'color' => $this->color,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
    
    /**
     * Create from array
     */
    public static function fromArray($data) {
        $meeting = new self();
        
        if (isset($data['id'])) $meeting->setId($data['id']);
        if (isset($data['title'])) $meeting->setTitle($data['title']);
        if (isset($data['description'])) $meeting->setDescription($data['description']);
        if (isset($data['meeting_date'])) $meeting->setMeetingDate($data['meeting_date']);
        if (isset($data['end_date'])) $meeting->setEndDate($data['end_date']);
        if (isset($data['location'])) $meeting->setLocation($data['location']);
        if (isset($data['organizer_id'])) $meeting->setOrganizerId($data['organizer_id']);
        if (isset($data['minutes'])) $meeting->setMinutes($data['minutes']);
        if (isset($data['decisions'])) $meeting->setDecisions($data['decisions']);
        if (isset($data['status'])) $meeting->setStatus($data['status']);
        if (isset($data['recurrence'])) $meeting->setRecurrence($data['recurrence']);
        if (isset($data['color'])) $meeting->setColor($data['color']);
        if (isset($data['created_at'])) $meeting->setCreatedAt($data['created_at']);
        if (isset($data['updated_at'])) $meeting->setUpdatedAt($data['updated_at']);
        
        return $meeting;
    }
}



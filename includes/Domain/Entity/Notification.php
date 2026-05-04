<?php
/**
 * Notification Entity
 * 
 * @package PersianOfficeAutomation\Domain\Entity
 */

namespace PersianOfficeAutomation\Domain\Entity;

class Notification {
    
    private $id;
    private $userId;
    private $type;
    private $title;
    private $message;
    private $link;
    private $isRead;
    private $readAt;
    private $createdAt;
    
    // Getters
    public function getId() { return $this->id; }
    public function getUserId() { return $this->userId; }
    public function getType() { return $this->type; }
    public function getTitle() { return $this->title; }
    public function getMessage() { return $this->message; }
    public function getLink() { return $this->link; }
    public function getIsRead() { return $this->isRead; }
    public function getReadAt() { return $this->readAt; }
    public function getCreatedAt() { return $this->createdAt; }
    
    // Setters
    public function setId($id) { $this->id = $id; }
    public function setUserId($userId) { $this->userId = $userId; }
    public function setType($type) { $this->type = $type; }
    public function setTitle($title) { $this->title = $title; }
    public function setMessage($message) { $this->message = $message; }
    public function setLink($link) { $this->link = $link; }
    public function setIsRead($isRead) { $this->isRead = $isRead; }
    public function setReadAt($readAt) { $this->readAt = $readAt; }
    public function setCreatedAt($createdAt) { $this->createdAt = $createdAt; }
    
    /**
     * Convert to array
     */
    public function toArray() {
        return [
            'id' => $this->id,
            'user_id' => $this->userId,
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'link' => $this->link,
            'is_read' => $this->isRead,
            'read_at' => $this->readAt,
            'created_at' => $this->createdAt
        ];
    }
    
    /**
     * Create from array
     */
    public static function fromArray($data) {
        $notification = new self();
        
        if (isset($data['id'])) $notification->setId($data['id']);
        if (isset($data['user_id'])) $notification->setUserId($data['user_id']);
        if (isset($data['type'])) $notification->setType($data['type']);
        if (isset($data['title'])) $notification->setTitle($data['title']);
        if (isset($data['message'])) $notification->setMessage($data['message']);
        if (isset($data['link'])) $notification->setLink($data['link']);
        if (isset($data['is_read'])) $notification->setIsRead($data['is_read']);
        if (isset($data['read_at'])) $notification->setReadAt($data['read_at']);
        if (isset($data['created_at'])) $notification->setCreatedAt($data['created_at']);
        
        return $notification;
    }
}


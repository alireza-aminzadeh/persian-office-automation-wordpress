<?php
/**
 * Task Repository Interface
 * 
 * @package PersianOfficeAutomation\Domain\Repository
 */

namespace PersianOfficeAutomation\Domain\Repository;

use PersianOfficeAutomation\Domain\Entity\Task;

interface TaskRepositoryInterface {
    public function save(Task $task);
    public function update(Task $task);
    public function findById($id);
    public function findByAssignee($userId, $status = null);
    public function findByCreator($userId, $status = null);
    public function delete($id);
    public function findAll($limit = 50, $offset = 0);
    public function findBetween($userId, $start, $end);
    
    // New methods
    public function findSubtasks($parentId);
    public function addComment($taskId, $userId, $comment, $attachment = null);
    public function getComments($taskId);
    public function addLog($taskId, $userId, $action, $details = null);
    public function getLogs($taskId);
    public function addTimeLog($taskId, $userId, $startTime, $endTime = null, $duration = 0, $description = null);
    public function getTimeLogs($taskId);
    public function addDependency($taskId, $dependencyId, $type = 'finish_to_start');
    public function getDependencies($taskId);
}

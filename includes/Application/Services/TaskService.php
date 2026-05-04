<?php
/**
 * Task Service
 * 
 * @package PersianOfficeAutomation\Application\Services
 */

namespace PersianOfficeAutomation\Application\Services;

use PersianOfficeAutomation\Domain\Entity\Task;
use PersianOfficeAutomation\Domain\Repository\TaskRepositoryInterface;
use PersianOfficeAutomation\Application\DTO\TaskDTO;
use PersianOfficeAutomation\Common\JalaliDate;

class TaskService {
    
    private $repository;
    
    public function __construct(TaskRepositoryInterface $repository) {
        $this->repository = $repository;
    }
    
    /**
     * Create new task
     */
    public function createTask(TaskDTO $dto) {
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $task = new Task();
        $task->setParentId($dto->parentId);
        $task->setTitle($dto->title);
        $task->setDescription($dto->description);
        $task->setCorrespondenceId($dto->correspondenceId);
        $task->setAssignedTo($dto->assignedTo);
        $task->setAssignedBy(get_current_user_id());
        $task->setPriority($dto->priority);
        $task->setStatus($dto->status);
        $task->setEstimatedTime($dto->estimatedTime);
        $task->setCategory($dto->category);
        $task->setIsRecurring($dto->isRecurring ? 1 : 0);
        $task->setRecurrencePattern($dto->recurrencePattern);
        
        // Date handling
        if (!empty($dto->startDateGregorian)) {
            $task->setStartDate($dto->startDateGregorian);
        } elseif (!empty($dto->startDate)) {
            $task->setStartDate(JalaliDate::jalaliToGregorianString($dto->startDate));
        }

        if (!empty($dto->deadlineGregorian)) {
            $task->setDeadline($dto->deadlineGregorian);
        } elseif (!empty($dto->deadline)) {
            $task->setDeadline(JalaliDate::jalaliToGregorianString($dto->deadline));
        }
        
        $task->setChecklist($dto->checklist);
        $task->setProgress($dto->progress);
        $task->setTags($dto->tags);
        
        $id = $this->repository->save($task);
        
        if ($id) {
            // Log creation
            $this->repository->addLog($id, get_current_user_id(), 'create', 'وظیفه ایجاد شد');
            
            // Send notification to assignee (TODO: NotificationService)
            return ['success' => true, 'id' => $id, 'message' => 'وظیفه با موفقیت ایجاد شد.'];
        }
        
        return ['success' => false, 'errors' => ['general' => 'خطا در ایجاد وظیفه.']];
    }
    
    /**
     * Update existing task
     */
    public function updateTask($id, TaskDTO $dto) {
        $task = $this->repository->findById($id);
        if (!$task) {
            return ['success' => false, 'message' => 'وظیفه یافت نشد.'];
        }

        // Validate DTO
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $task->setTitle($dto->title);
        $task->setDescription($dto->description);
        $task->setPriority($dto->priority);
        $task->setAssignedTo($dto->assignedTo);
        $task->setCategory($dto->category);
        $task->setEstimatedTime($dto->estimatedTime);
        
        // Date handling
        if (!empty($dto->deadlineGregorian)) {
            $task->setDeadline($dto->deadlineGregorian);
        } elseif (!empty($dto->deadline)) {
            $task->setDeadline(JalaliDate::jalaliToGregorianString($dto->deadline));
        }

        if (!empty($dto->startDateGregorian)) {
            $task->setStartDate($dto->startDateGregorian);
        } elseif (!empty($dto->startDate)) {
            $task->setStartDate(JalaliDate::jalaliToGregorianString($dto->startDate));
        }
        
        $result = $this->repository->update($task);
        
        if ($result) {
            $this->repository->addLog($id, get_current_user_id(), 'update', 'ویرایش اطلاعات وظیفه');
            return ['success' => true, 'message' => 'وظیفه با موفقیت ویرایش شد.'];
        }
        
        return ['success' => false, 'message' => 'خطا در ویرایش وظیفه.'];
    }

    /**
     * Update checklist
     */
    public function updateChecklist($taskId, $checklist) {
        $task = $this->repository->findById($taskId);
        if (!$task) return ['success' => false];

        $task->setChecklist($checklist);
        $this->repository->update($task);
        return ['success' => true];
    }
    
    /**
     * Update task status
     */
    public function updateStatus($id, $status, $progress = null) {
        $task = $this->repository->findById($id);
        if (!$task) {
            return ['success' => false, 'message' => 'وظیفه یافت نشد.'];
        }
        
        $oldStatus = $task->getStatus();
        if ($oldStatus !== $status) {
            $task->setStatus($status);
            $this->repository->addLog($id, get_current_user_id(), 'status_change', "تغییر وضعیت از $oldStatus به $status");
        }
        
        if ($progress !== null) {
            $task->setProgress($progress);
        }
        
        if ($status === 'completed' && !$task->getCompletedAt()) {
            $task->setCompletedAt(current_time('mysql'));
            $task->setProgress(100);
            $this->repository->addLog($id, get_current_user_id(), 'complete', 'وظیفه تکمیل شد');
        }
        
        $this->repository->update($task);
        
        return ['success' => true, 'message' => 'وضعیت وظیفه بروز شد.'];
    }

    /**
     * Add comment to task
     */
    public function addComment($taskId, $comment, $attachment = null) {
        $result = $this->repository->addComment($taskId, get_current_user_id(), $comment, $attachment);
        if ($result) {
            // Log comment action
            // $this->repository->addLog($taskId, get_current_user_id(), 'comment', 'نظر جدید ثبت شد');
            return ['success' => true, 'message' => 'نظر ثبت شد.'];
        }
        return ['success' => false, 'message' => 'خطا در ثبت نظر.'];
    }

    /**
     * Get task comments
     */
    public function getComments($taskId) {
        return $this->repository->getComments($taskId);
    }

    /**
     * Add time log
     */
    public function addTimeLog($taskId, $startTime, $endTime, $description = null) {
        $start = strtotime($startTime);
        $end = strtotime($endTime);
        $duration = round(abs($end - $start) / 60); // minutes

        $result = $this->repository->addTimeLog($taskId, get_current_user_id(), $startTime, $endTime, $duration, $description);
        
        if ($result) {
            // Update total spent time on task
            $task = $this->repository->findById($taskId);
            $currentSpent = $task->getSpentTime() ?? 0;
            $task->setSpentTime($currentSpent + $duration);
            $this->repository->update($task);

            return ['success' => true, 'message' => 'زمان کار ثبت شد.'];
        }
        return ['success' => false, 'message' => 'خطا در ثبت زمان.'];
    }
    
    /**
     * Get user tasks
     */
    public function getUserTasks($userId) {
        return $this->repository->findByAssignee($userId);
    }
    
    /**
     * Get tasks created by user
     */
    public function getCreatedTasks($userId) {
        return $this->repository->findByCreator($userId);
    }
    
    public function getTask($id) {
        return $this->repository->findById($id);
    }

    public function getTaskLogs($id) {
        return $this->repository->getLogs($id);
    }

    public function getTaskTimeLogs($id) {
        return $this->repository->getTimeLogs($id);
    }

    public function getSubtasks($parentId) {
        return $this->repository->findSubtasks($parentId);
    }

    /**
     * Delete task
     */
    public function deleteTask($id) {
        $result = $this->repository->delete($id);
        if ($result !== false) {
             // We can't log to a deleted task, but we return success
            return ['success' => true, 'message' => 'وظیفه حذف شد.'];
        }
        return ['success' => false, 'message' => 'خطا در حذف وظیفه.'];
    }

    /**
     * Update task description
     */
    public function updateDescription($id, $description) {
        $task = $this->repository->findById($id);
        if (!$task) {
            return ['success' => false, 'message' => 'وظیفه یافت نشد.'];
        }
        
        $task->setDescription($description);
        $result = $this->repository->update($task);
        
        if ($result) {
            $this->repository->addLog($id, get_current_user_id(), 'update', 'ویرایش توضیحات');
            return ['success' => true, 'message' => 'توضیحات به‌روز شد.'];
        }
        
        return ['success' => false, 'message' => 'خطا در ویرایش توضیحات.'];
    }
}

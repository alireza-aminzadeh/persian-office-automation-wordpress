<?php
/**
 * Task Repository Implementation
 * Table name from $wpdb->prefix; dynamic parts via prepare().
 *
 * @package PersianOfficeAutomation\Infrastructure\Repository
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * @phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
 * @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * @phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace PersianOfficeAutomation\Infrastructure\Repository;

use PersianOfficeAutomation\Domain\Entity\Task;
use PersianOfficeAutomation\Domain\Repository\TaskRepositoryInterface;

class TaskRepository implements TaskRepositoryInterface {
    
    private $table;
    private $tableComments;
    private $tableLogs;
    private $tableTimeLogs;
    private $tableDependencies;
    
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'persian_oa_tasks';
        $this->tableComments = $wpdb->prefix . 'persian_oa_task_comments';
        $this->tableLogs = $wpdb->prefix . 'persian_oa_task_logs';
        $this->tableTimeLogs = $wpdb->prefix . 'persian_oa_task_time_logs';
        $this->tableDependencies = $wpdb->prefix . 'persian_oa_task_dependencies';
    }
    
    public function save(Task $task) {
        global $wpdb;
        
        $data = $task->toArray();
        unset($data['id']); // Let DB handle AI
        unset($data['created_at']);
        unset($data['updated_at']);
        
        // Ensure checklist is string
        if (is_array($data['checklist'])) {
            $data['checklist'] = wp_json_encode($data['checklist']);
        }
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->insert($this->table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    public function update(Task $task) {
        global $wpdb;
        
        $data = $task->toArray();
        $id = $data['id'];
        unset($data['id']);
        unset($data['created_at']);
        unset($data['updated_at']);
        
        // Ensure checklist is string
        if (is_array($data['checklist'])) {
            $data['checklist'] = wp_json_encode($data['checklist']);
        }
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->update($this->table, $data, ['id' => $id]);
        
        return $result !== false;
    }
    
    public function findById($id) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}persian_oa_tasks WHERE id = %d", $id), ARRAY_A);
        
        if ($row) {
            return Task::fromArray($row);
        }
        
        return null;
    }
    
    public function findByAssignee($userId, $status = null) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}persian_oa_tasks WHERE assigned_to = %d", $userId);
        
        if ($status) {
            $sql .= $wpdb->prepare(" AND status = %s", $status);
        }
        
        $sql .= " ORDER BY created_at DESC";

        $results = $wpdb->get_results( $sql, ARRAY_A );
        return array_map( [ Task::class, 'fromArray' ], $results );
    }

    public function findByCreator($userId, $status = null) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $sql = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}persian_oa_tasks WHERE assigned_by = %d", $userId);
        
        if ($status) {
            $sql .= $wpdb->prepare(" AND status = %s", $status);
        }
        
        $sql .= " ORDER BY created_at DESC";

        $results = $wpdb->get_results( $sql, ARRAY_A );
        return array_map( [ Task::class, 'fromArray' ], $results );
    }

    public function delete($id) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->delete($this->table, ['id' => $id]);
    }

    public function findAll($limit = 50, $offset = 0) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}persian_oa_tasks ORDER BY created_at DESC LIMIT %d OFFSET %d", $limit, $offset), ARRAY_A);
        return array_map([Task::class, 'fromArray'], $results);
    }

    public function findBetween($userId, $start, $end) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}persian_oa_tasks WHERE assigned_to = %d AND deadline >= %s AND deadline <= %s ORDER BY deadline ASC", 
            $userId, $start, $end
        ), ARRAY_A);
        return array_map([Task::class, 'fromArray'], $results);
    }

    public function findSubtasks($parentId) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}persian_oa_tasks WHERE parent_id = %d ORDER BY created_at ASC", $parentId), ARRAY_A);
        return array_map([Task::class, 'fromArray'], $results);
    }

    public function addComment($taskId, $userId, $comment, $attachment = null) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->insert($this->tableComments, [
            'task_id' => $taskId,
            'user_id' => $userId,
            'comment' => $comment,
            'file_attachment' => $attachment,
            'created_at' => current_time('mysql')
        ]);
    }

    public function getComments($taskId) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}persian_oa_task_comments WHERE task_id = %d ORDER BY created_at ASC", 
            $taskId
        ), ARRAY_A);
    }

    public function addLog($taskId, $userId, $action, $details = null) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->insert($this->tableLogs, [
            'task_id' => $taskId,
            'user_id' => $userId,
            'action' => $action,
            'details' => $details,
            'created_at' => current_time('mysql')
        ]);
    }

    public function getLogs($taskId) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}persian_oa_task_logs WHERE task_id = %d ORDER BY created_at DESC", 
            $taskId
        ), ARRAY_A);
    }

    public function addTimeLog($taskId, $userId, $startTime, $endTime = null, $duration = 0, $description = null) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->insert($this->tableTimeLogs, [
            'task_id' => $taskId,
            'user_id' => $userId,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'duration' => $duration,
            'description' => $description,
            'created_at' => current_time('mysql')
        ]);
    }

    public function getTimeLogs($taskId) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}persian_oa_task_time_logs WHERE task_id = %d ORDER BY start_time DESC", 
            $taskId
        ), ARRAY_A);
    }

    public function addDependency($taskId, $dependencyId, $type = 'finish_to_start') {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->insert($this->tableDependencies, [
            'task_id' => $taskId,
            'dependency_id' => $dependencyId,
            'type' => $type,
            'created_at' => current_time('mysql')
        ]);
    }

    public function getDependencies($taskId) {
        global $wpdb;
        // Tables from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results($wpdb->prepare(
            "SELECT d.*, t.title as dependency_title, t.status as dependency_status 
            FROM {$wpdb->prefix}persian_oa_task_dependencies d 
            JOIN {$wpdb->prefix}persian_oa_tasks t ON d.dependency_id = t.id 
            WHERE d.task_id = %d", 
            $taskId
        ), ARRAY_A);
    }
}

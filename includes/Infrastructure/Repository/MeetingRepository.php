<?php
/**
 * Meeting Repository Implementation
 * Table names from $wpdb->prefix; all values via prepare().
 *
 * @package PersianOfficeAutomation\Infrastructure\Repository
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * @phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * @phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * @phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 */

namespace PersianOfficeAutomation\Infrastructure\Repository;

use PersianOfficeAutomation\Domain\Entity\Meeting;
use PersianOfficeAutomation\Domain\Repository\MeetingRepositoryInterface;

class MeetingRepository implements MeetingRepositoryInterface {
    
    private $table;
    private $participantsTable;
    
    public function __construct() {
        global $wpdb;
        $this->table = $wpdb->prefix . 'persian_oa_meetings';
        $this->participantsTable = $wpdb->prefix . 'persian_oa_meeting_participants';
    }
    
    public function save(Meeting $meeting) {
        global $wpdb;
        
        $data = $meeting->toArray();
        unset($data['id']);
        unset($data['created_at']);
        unset($data['updated_at']);
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->insert($this->table, $data);
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return ['error' => $wpdb->last_error ?: 'خطا در ذخیره جلسه در پایگاه داده.'];
    }
    
    public function update(Meeting $meeting) {
        global $wpdb;
        
        $data = $meeting->toArray();
        $id = $data['id'];
        unset($data['id']);
        unset($data['created_at']);
        unset($data['updated_at']);
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->update($this->table, $data, ['id' => $id]);
        
        return $result !== false;
    }
    
    public function findById($id) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $row = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}persian_oa_meetings WHERE id = %d", $id), ARRAY_A);
        
        if ($row) {
            return Meeting::fromArray($row);
        }
        
        return null;
    }
    
    public function findByOrganizer($userId) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}persian_oa_meetings WHERE organizer_id = %d ORDER BY meeting_date DESC",
            $userId
        ), ARRAY_A);

        return array_map([Meeting::class, 'fromArray'], $results);
    }

    public function findByOrganizerPaginated($userId, $limit, $offset) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}persian_oa_meetings WHERE organizer_id = %d ORDER BY meeting_date DESC LIMIT %d OFFSET %d",
            $userId,
            (int) $limit,
            (int) $offset
        ), ARRAY_A);

        return array_map([Meeting::class, 'fromArray'], $results);
    }

    public function countByOrganizer($userId) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}persian_oa_meetings WHERE organizer_id = %d",
            $userId
        ));
    }

    public function findUpcoming($userId) {
        // Find meetings where user is organizer OR participant
        global $wpdb;
        
        // TODO: Complex query to join participants
        // For now just organizer
        return $this->findByOrganizer($userId);
    }
    
    public function addParticipant($meetingId, $userId) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->insert($this->participantsTable, [
            'meeting_id' => $meetingId,
            'user_id' => $userId,
            'attendance' => 'pending'
        ]);
    }
    
    public function getParticipants($meetingId) {
        global $wpdb;
        // Table names from prefix/wpdb. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->get_results($wpdb->prepare(
            "SELECT p.*, u.display_name FROM {$wpdb->prefix}persian_oa_meeting_participants p 
             LEFT JOIN {$wpdb->users} u ON p.user_id = u.ID 
             WHERE meeting_id = %d", 
            $meetingId
        ));
    }

    public function findBetween($userId, $start, $end) {
        global $wpdb;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}persian_oa_meetings WHERE organizer_id = %d AND meeting_date >= %s AND meeting_date <= %s ORDER BY meeting_date ASC", 
            $userId, $start, $end
        ), ARRAY_A);
        return array_map([Meeting::class, 'fromArray'], $results);
    }

    /**
     * Remove all participants for a meeting (used before update or delete)
     */
    public function removeParticipants($meetingId) {
        global $wpdb;
        $table = $this->participantsTable;
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $wpdb->delete($table, ['meeting_id' => $meetingId], ['%d']);
    }

    /**
     * Delete a meeting and its participants
     */
    public function delete($id) {
        global $wpdb;
        $this->removeParticipants($id);
        // Table from prefix. phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
        return $wpdb->delete($this->table, ['id' => $id], ['%d']) !== false;
    }
}



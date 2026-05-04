<?php
/**
 * Meeting Repository Interface
 * 
 * @package PersianOfficeAutomation\Domain\Repository
 */

namespace PersianOfficeAutomation\Domain\Repository;

use PersianOfficeAutomation\Domain\Entity\Meeting;

interface MeetingRepositoryInterface {
    public function save(Meeting $meeting);
    public function update(Meeting $meeting);
    public function delete($id);
    public function findById($id);
    public function findByOrganizer($userId);

    /**
     * Get paginated meetings for organizer (ordered by meeting_date DESC).
     *
     * @param int $userId Organizer ID
     * @param int $limit  Number of records per page
     * @param int $offset Offset for pagination
     * @return array Array of Meeting entities
     */
    public function findByOrganizerPaginated($userId, $limit, $offset);

    /**
     * Count total meetings for organizer.
     *
     * @param int $userId Organizer ID
     * @return int Total count
     */
    public function countByOrganizer($userId);

    public function findUpcoming($userId);
    public function addParticipant($meetingId, $userId);
    public function removeParticipants($meetingId);
    public function getParticipants($meetingId);
}



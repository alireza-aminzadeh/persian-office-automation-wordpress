<?php
/**
 * Meeting Service
 * 
 * @package PersianOfficeAutomation\Application\Services
 */

namespace PersianOfficeAutomation\Application\Services;

use PersianOfficeAutomation\Domain\Entity\Meeting;
use PersianOfficeAutomation\Domain\Repository\MeetingRepositoryInterface;
use PersianOfficeAutomation\Application\DTO\MeetingDTO;
use PersianOfficeAutomation\Common\JalaliDate;

class MeetingService {
    
    private $repository;
    
    public function __construct(MeetingRepositoryInterface $repository) {
        $this->repository = $repository;
    }
    
    /**
     * Create new meeting
     */
    public function createMeeting(MeetingDTO $dto) {
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        $meeting = new Meeting();
        $meeting->setTitle($dto->title);
        $meeting->setDescription($dto->description);
        
        // Date handling: ensure MySQL datetime format (Y-m-d H:i:s)
        $dateForDb = null;
        if (!empty($dto->meetingDateGregorian)) {
            $dateForDb = self::normalizeDateTime($dto->meetingDateGregorian);
        } elseif (!empty($dto->meetingDate)) {
            $dateOnly = JalaliDate::jalaliToGregorianString($dto->meetingDate);
            $dateForDb = self::normalizeDateTime($dateOnly);
        }
        if (!$dateForDb) {
            return ['success' => false, 'errors' => ['meeting_date' => 'فرمت تاریخ یا زمان نامعتبر است. لطفاً تاریخ و ساعت را دوباره انتخاب کنید.']];
        }
        $meeting->setMeetingDate($dateForDb);
        
        $meeting->setLocation($dto->location);
        $meeting->setOrganizerId(get_current_user_id());
        $meeting->setStatus($dto->status);
        $meeting->setRecurrence($dto->recurrence);
        $meeting->setColor($dto->color);
        
        if (!empty($dto->endDate)) {
            $meeting->setEndDate($dto->endDate);
        }
        
        $saveResult = $this->repository->save($meeting);
        
        if (is_array($saveResult) && isset($saveResult['error'])) {
            return ['success' => false, 'errors' => ['general' => $saveResult['error']]];
        }
        
        $id = $saveResult;
        if ($id) {
            // Add participants
            if (!empty($dto->participants)) {
                foreach ($dto->participants as $userId) {
                    $this->repository->addParticipant($id, $userId);
                }
            }
            
            return ['success' => true, 'id' => $id, 'message' => 'جلسه با موفقیت ثبت شد.'];
        }
        
        return ['success' => false, 'errors' => ['general' => 'خطا در ثبت جلسه.']];
    }
    
    public function getUserMeetings($userId) {
        return $this->repository->findByOrganizer($userId);
    }

    /**
     * Get paginated meetings for organizer.
     *
     * @param int $userId  Organizer ID
     * @param int $page    Current page (1-based)
     * @param int $perPage Records per page
     * @return array{meetings: array, total: int, total_pages: int, current_page: int, per_page: int}
     */
    public function getUserMeetingsPaginated($userId, $page = 1, $perPage = 15) {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));

        $total = $this->repository->countByOrganizer($userId);
        $totalPages = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;

        $meetings = $this->repository->findByOrganizerPaginated($userId, $perPage, $offset);

        return [
            'meetings'      => $meetings,
            'total'         => $total,
            'total_pages'   => $totalPages,
            'current_page'  => $page,
            'per_page'      => $perPage,
        ];
    }

    public function getMeetingById($id, $organizerId = null) {
        $meeting = $this->repository->findById($id);
        if (!$meeting) {
            return null;
        }
        if ($organizerId !== null && (int) $meeting->getOrganizerId() !== (int) $organizerId) {
            return null;
        }
        return $meeting;
    }

    /**
     * Update existing meeting
     */
    public function updateMeeting(MeetingDTO $dto) {
        if (empty($dto->id)) {
            return ['success' => false, 'errors' => ['general' => 'شناسه جلسه نامعتبر است.']];
        }
        $errors = $dto->validate();
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $meeting = $this->repository->findById($dto->id);
        if (!$meeting || (int) $meeting->getOrganizerId() !== get_current_user_id()) {
            return ['success' => false, 'errors' => ['general' => 'جلسه یافت نشد یا شما مجوز ویرایش آن را ندارید.']];
        }

        $meeting->setTitle($dto->title);
        $meeting->setDescription($dto->description);

        $dateForDb = null;
        if (!empty($dto->meetingDateGregorian)) {
            $dateForDb = self::normalizeDateTime($dto->meetingDateGregorian);
        } elseif (!empty($dto->meetingDate)) {
            $dateOnly = JalaliDate::jalaliToGregorianString($dto->meetingDate);
            $dateForDb = self::normalizeDateTime($dateOnly);
        }
        if (!$dateForDb) {
            return ['success' => false, 'errors' => ['meeting_date' => 'فرمت تاریخ یا زمان نامعتبر است.']];
        }
        $meeting->setMeetingDate($dateForDb);
        $meeting->setLocation($dto->location);
        $meeting->setStatus($dto->status);
        $meeting->setRecurrence($dto->recurrence);
        $meeting->setColor($dto->color);
        if (!empty($dto->endDate)) {
            $meeting->setEndDate($dto->endDate);
        }

        $updated = $this->repository->update($meeting);
        if (!$updated) {
            return ['success' => false, 'errors' => ['general' => 'خطا در به‌روزرسانی جلسه.']];
        }

        $this->repository->removeParticipants($meeting->getId());
        if (!empty($dto->participants)) {
            foreach ($dto->participants as $userId) {
                $this->repository->addParticipant($meeting->getId(), $userId);
            }
        }

        return ['success' => true, 'id' => $meeting->getId(), 'message' => 'جلسه با موفقیت به‌روزرسانی شد.'];
    }

    /**
     * Delete meeting (only organizer)
     */
    public function deleteMeeting($id, $userId) {
        $meeting = $this->repository->findById($id);
        if (!$meeting || (int) $meeting->getOrganizerId() !== (int) $userId) {
            return false;
        }
        return $this->repository->delete($id);
    }

    /**
     * Normalize date string to MySQL datetime format (Y-m-d H:i:s)
     */
    private static function normalizeDateTime($input) {
        if (empty($input)) {
            return null;
        }
        try {
            $dt = new \DateTime($input);
            return $dt->format('Y-m-d H:i:s');
        } catch (\Exception $e) {
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($input))) {
                return trim($input) . ' 00:00:00';
            }
            return null;
        }
    }
}



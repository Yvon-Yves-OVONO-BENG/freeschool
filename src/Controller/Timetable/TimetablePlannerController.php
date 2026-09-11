<?php

namespace App\Controller\Timetable;

use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use App\Entity\TimeTable;
use App\Entity\TimeTableSlotTemplate;
use App\Repository\ClassroomRepository;
use App\Repository\DayRepository;
use App\Repository\LessonRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Repository\TimeTableRepository;
use App\Repository\TimeTableSlotTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/timetable/planner')]
class TimetablePlannerController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClassroomRepository $classroomRepository,
        private readonly LessonRepository $lessonRepository,
        private readonly DayRepository $dayRepository,
        private readonly TimeTableRepository $timeTableRepository,
        private readonly TimeTableSlotTemplateRepository $slotTemplateRepository,
        private readonly SchoolYearRepository $schoolYearRepository,
        private readonly SubSystemRepository $subSystemRepository,
    ) {
    }

    #[Route('/save-entry', name: 'timetable_planner_save_entry', methods: ['POST'])]
    public function saveEntry(Request $request): JsonResponse
    {
        [$schoolYear, $subSystem] = $this->resolveContext($request);

        if (!$schoolYear || !$subSystem) {
            return $this->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $payload = $this->getPayload($request);

        $classroomId = (int) ($payload['classroomId'] ?? 0);
        $lessonId = (int) ($payload['lessonId'] ?? 0);
        $dayId = (int) ($payload['dayId'] ?? 0);
        $startTime = $this->normalizeTime((string) ($payload['startTime'] ?? ''));
        $duration = max(1, min(2, (int) ($payload['duration'] ?? 1)));
        $entryId = isset($payload['entryId']) && $payload['entryId'] ? (int) $payload['entryId'] : null;

        $classroom = $this->classroomRepository->find($classroomId);
        $day = $this->dayRepository->find($dayId);
        $lesson = $lessonId > 0 ? $this->lessonRepository->find($lessonId) : null;

        $timeTable = $entryId ? $this->timeTableRepository->find($entryId) : new TimeTable();

        if ($entryId && !$timeTable) {
            return $this->json(['success' => false, 'message' => 'Scheduled lesson not found.'], 404);
        }

        /*
         * When moving an already scheduled card, the lessonId can be missing if the DOM was created from an old entry.
         * In that case, we keep the existing subject/teacher from TimeTable and only move the day/time.
         */
        if (!$lesson && $timeTable instanceof TimeTable && $timeTable->getSubject() && $timeTable->getTeacher()) {
            $lesson = $this->lessonRepository->findOneBy([
                'classroom' => $timeTable->getClassroom(),
                'subject' => $timeTable->getSubject(),
                'teacher' => $timeTable->getTeacher(),
            ]);
        }

        if (!$classroom || !$day || !$startTime) {
            return $this->json(['success' => false, 'message' => 'Invalid timetable payload.'], 422);
        }

        if (!$lesson && !$entryId) {
            return $this->json(['success' => false, 'message' => 'Please drag a valid lesson from the left panel.'], 422);
        }

        if ($lesson && $lesson->getClassroom()?->getId() !== $classroom->getId()) {
            return $this->json(['success' => false, 'message' => 'This lesson does not belong to the selected classroom.'], 422);
        }

        $slots = $this->loadOrCreateSlotConfiguration($schoolYear, $subSystem);
        $endTime = $this->resolveEndTime($slots, $startTime, $duration);

        if (!$endTime) {
            return $this->json(['success' => false, 'message' => 'Impossible to determine the ending time for this slot.'], 422);
        }

        $teacher = $lesson ? $lesson->getTeacher() : $timeTable->getTeacher();
        $subject = $lesson ? $lesson->getSubject() : $timeTable->getSubject();

        if (!$teacher || !$subject) {
            return $this->json(['success' => false, 'message' => 'This lesson is missing a teacher or a subject.'], 422);
        }

        if ($this->hasClassroomConflict($classroomId, $dayId, $startTime, $endTime, $entryId)) {
            return $this->json(['success' => false, 'message' => 'This classroom already has a course in the selected time range.'], 409);
        }

        if ($this->hasTeacherConflict($teacher->getId(), $dayId, $startTime, $endTime, $entryId, $schoolYear->getId(), $subSystem->getId())) {
            return $this->json(['success' => false, 'message' => 'This teacher is already busy in the selected time range.'], 409);
        }

        $timeTable
            ->setClassroom($classroom)
            ->setTeacher($teacher)
            ->setSubject($subject)
            ->setDay($day)
            ->setSchoolYear($schoolYear)
            ->setSubSystem($subSystem)
            ->setStartTime($startTime)
            ->setEndTime($endTime);

        if (!$timeTable->getSlug()) {
            $timeTable->setSlug($this->generateSlug());
        }

        $this->em->persist($timeTable);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Timetable updated successfully.',
            'entry' => [
                'id' => $timeTable->getId(),
                'lessonId' => $lesson?->getId(),
                'subject' => $subject->getSubject(),
                'teacher' => $teacher->getFullName(),
                'startTime' => substr((string) $timeTable->getStartTime(), 0, 5),
                'endTime' => substr((string) $timeTable->getEndTime(), 0, 5),
                'duration' => $duration,
                'deleteUrl' => $this->generateUrl('timetable_planner_delete_entry', ['id' => $timeTable->getId()]),
            ],
        ]);
    }

    #[Route('/delete-entry/{id}', name: 'timetable_planner_delete_entry', methods: ['POST', 'DELETE'])]
    public function deleteEntry(Request $request, int $id): JsonResponse
    {
        [$schoolYear, $subSystem] = $this->resolveContext($request);

        if (!$schoolYear || !$subSystem) {
            return $this->json([
                'success' => false,
                'message' => 'Session expired.',
            ], 401);
        }

        $entry = $this->timeTableRepository->find($id);

        if (!$entry) {
            return $this->json([
                'success' => false,
                'message' => 'Scheduled lesson not found.',
            ], 404);
        }

        if (
            $entry->getSchoolYear()?->getId() !== $schoolYear->getId()
            || $entry->getSubSystem()?->getId() !== $subSystem->getId()
        ) {
            return $this->json([
                'success' => false,
                'message' => 'You are not allowed to delete this scheduled lesson.',
            ], 403);
        }

        $deletedId = $entry->getId();

        $this->em->remove($entry);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Scheduled lesson deleted successfully.',
            'id' => $deletedId,
        ]);
    }

    #[Route('/save-slots', name: 'timetable_planner_save_slots', methods: ['POST'])]
    public function saveSlots(Request $request): JsonResponse
    {
        [$schoolYear, $subSystem] = $this->resolveContext($request);

        if (!$schoolYear || !$subSystem) {
            return $this->json(['success' => false, 'message' => 'Session expired.'], 401);
        }

        $payload = $this->getPayload($request);
        $rows = $payload['rows'] ?? [];

        if (!is_array($rows)) {
            return $this->json(['success' => false, 'message' => 'Invalid slot configuration.'], 422);
        }

        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $type = ($row['type'] ?? 'course') === 'break' ? 'break' : 'course';
            $start = $this->normalizeTime((string) ($row['start'] ?? ''));
            $end = $this->normalizeTime((string) ($row['end'] ?? ''));
            $label = trim((string) ($row['label'] ?? ''));

            if (!$start || !$end || strcmp($start, $end) >= 0) {
                continue;
            }

            $normalized[] = [
                'type' => $type,
                'start' => $start,
                'end' => $end,
                'label' => $label,
            ];
        }

        if (count($normalized) === 0) {
            return $this->json(['success' => false, 'message' => 'Add at least one valid slot.'], 422);
        }

        usort($normalized, static function (array $left, array $right): int {
            return strcmp($left['start'], $right['start']);
        });

        $this->slotTemplateRepository->deleteForContext($schoolYear, $subSystem);

        $rowOrder = 1;
        foreach ($normalized as $row) {
            $slot = (new TimeTableSlotTemplate())
                ->setSchoolYear($schoolYear)
                ->setSubSystem($subSystem)
                ->setRowOrder($rowOrder++)
                ->setType($row['type'])
                ->setStartTime($row['start'])
                ->setEndTime($row['end'])
                ->setLabel($row['label'] ?: null)
                ->setActive(true)
                ->setSlug($this->generateSlug());

            $this->em->persist($slot);
        }

        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Schedule configuration saved permanently in database.',
        ]);
    }

    private function hasClassroomConflict(int $classroomId, int $dayId, string $startTime, string $endTime, ?int $currentEntryId): bool
    {
        $entries = $this->timeTableRepository->findBy([
            'classroom' => $classroomId,
            'day' => $dayId,
        ]);

        foreach ($entries as $entry) {
            if ($currentEntryId && $entry->getId() === $currentEntryId) {
                continue;
            }

            if ($this->isOverlapping($startTime, $endTime, $entry->getStartTime(), $entry->getEndTime())) {
                return true;
            }
        }

        return false;
    }

    private function hasTeacherConflict(int $teacherId, int $dayId, string $startTime, string $endTime, ?int $currentEntryId, int $schoolYearId, int $subSystemId): bool
    {
        $entries = $this->timeTableRepository->findBy([
            'teacher' => $teacherId,
            'day' => $dayId,
            'schoolYear' => $schoolYearId,
            'subSystem' => $subSystemId,
        ]);

        foreach ($entries as $entry) {
            if ($currentEntryId && $entry->getId() === $currentEntryId) {
                continue;
            }

            if ($this->isOverlapping($startTime, $endTime, $entry->getStartTime(), $entry->getEndTime())) {
                return true;
            }
        }

        return false;
    }

    private function isOverlapping(string $startA, string $endA, ?string $startB, ?string $endB): bool
    {
        if (!$startB || !$endB) {
            return false;
        }

        return strcmp($startA, $endB) < 0 && strcmp($startB, $endA) < 0;
    }

    /**
     * @return array{0:?SchoolYear, 1:?SubSystem}
     */
    private function resolveContext(Request $request): array
    {
        $session = $request->getSession();
        $schoolYearSession = $session->get('schoolYear');
        $subSystemSession = $session->get('subSystem');

        if (!$schoolYearSession || !$subSystemSession) {
            return [null, null];
        }

        return [
            $this->schoolYearRepository->find($schoolYearSession->getId()),
            $this->subSystemRepository->find($subSystemSession->getId()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPayload(Request $request): array
    {
        $content = trim((string) $request->getContent());

        if ($content !== '') {
            $decoded = json_decode($content, true);

            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $request->request->all();
    }

    /**
     * @return array<int, array{type:string,start:string,end:string,label:string}>
     */
    private function loadOrCreateSlotConfiguration(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        $templates = $this->slotTemplateRepository->findActiveForContext($schoolYear, $subSystem);

        if (count($templates) === 0) {
            $rowOrder = 1;

            foreach ($this->getDefaultSlotConfiguration() as $row) {
                $slot = (new TimeTableSlotTemplate())
                    ->setSchoolYear($schoolYear)
                    ->setSubSystem($subSystem)
                    ->setRowOrder($rowOrder++)
                    ->setType($row['type'])
                    ->setStartTime($row['start'])
                    ->setEndTime($row['end'])
                    ->setLabel($row['label'])
                    ->setActive(true)
                    ->setSlug($this->generateSlug());

                $this->em->persist($slot);
            }

            $this->em->flush();
            $templates = $this->slotTemplateRepository->findActiveForContext($schoolYear, $subSystem);
        }

        $rows = [];

        foreach ($templates as $template) {
            $rows[] = [
                'type' => $template->getType() === TimeTableSlotTemplate::TYPE_BREAK ? 'break' : 'course',
                'start' => (string) $template->getStartTime(),
                'end' => (string) $template->getEndTime(),
                'label' => (string) $template->getLabel(),
            ];
        }

        usort($rows, static function (array $left, array $right): int {
            return strcmp($left['start'], $right['start']);
        });

        return $rows;
    }

    /**
     * @param array<int, array{type:string,start:string,end:string,label:string}> $rows
     */
    private function resolveEndTime(array $rows, string $startTime, int $duration): ?string
    {
        $courseRows = array_values(array_filter($rows, static fn (array $row): bool => ($row['type'] ?? 'course') === 'course'));

        foreach ($courseRows as $index => $row) {
            if ($row['start'] !== $startTime) {
                continue;
            }

            $targetIndex = $index + $duration - 1;

            if (!isset($courseRows[$targetIndex])) {
                return null;
            }

            return $courseRows[$targetIndex]['end'];
        }

        return null;
    }

    private function normalizeTime(string $time): ?string
    {
        $time = trim($time);

        if ($time === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $time) === 1) {
            return $time . ':00';
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $time) === 1) {
            return $time;
        }

        return null;
    }

    /**
     * @return array<int, array{type:string,start:string,end:string,label:string}>
     */
    private function getDefaultSlotConfiguration(): array
    {
        return [
            ['type' => 'course', 'start' => '07:30:00', 'end' => '08:25:00', 'label' => ''],
            ['type' => 'course', 'start' => '08:25:00', 'end' => '09:20:00', 'label' => ''],
            ['type' => 'course', 'start' => '09:20:00', 'end' => '10:15:00', 'label' => ''],
            ['type' => 'break', 'start' => '10:15:00', 'end' => '10:30:00', 'label' => 'PETITE PAUSE'],
            ['type' => 'course', 'start' => '10:30:00', 'end' => '11:25:00', 'label' => ''],
            ['type' => 'course', 'start' => '11:25:00', 'end' => '12:20:00', 'label' => ''],
            ['type' => 'course', 'start' => '12:20:00', 'end' => '13:15:00', 'label' => ''],
            ['type' => 'break', 'start' => '13:15:00', 'end' => '13:40:00', 'label' => 'GRANDE PAUSE'],
            ['type' => 'course', 'start' => '13:40:00', 'end' => '14:35:00', 'label' => ''],
            ['type' => 'course', 'start' => '14:35:00', 'end' => '15:30:00', 'label' => ''],
            ['type' => 'course', 'start' => '15:30:00', 'end' => '16:25:00', 'label' => ''],
            ['type' => 'course', 'start' => '16:25:00', 'end' => '17:20:00', 'label' => ''],
        ];
    }

    private function generateSlug(): string
    {
        return substr(bin2hex(random_bytes(12)), 0, 24);
    }
}

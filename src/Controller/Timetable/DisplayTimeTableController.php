<?php

namespace App\Controller\Timetable;

use App\Entity\Classroom;
use App\Entity\Day;
use App\Entity\Lesson;
use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use App\Entity\TimeTable;
use App\Entity\TimeTableSlotTemplate;
use App\Repository\ClassroomRepository;
use App\Repository\DayRepository;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Repository\TimeTableRepository;
use App\Repository\TimeTableSlotTemplateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/timetable')]
class DisplayTimeTableController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SchoolRepository $schoolRepository,
        private readonly SchoolYearRepository $schoolYearRepository,
        private readonly SubSystemRepository $subSystemRepository,
        private readonly ClassroomRepository $classroomRepository,
        private readonly LessonRepository $lessonRepository,
        private readonly TimeTableRepository $timeTableRepository,
        private readonly TimeTableSlotTemplateRepository $slotTemplateRepository,
        private readonly DayRepository $dayRepository,
    ) {
    }

    #[Route('/display-time-table/{slug}', name: 'display_time_table', defaults: ['slug' => null])]
    public function displayTimeTable(Request $request, ?string $slug = null): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout', null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if (!$mySession || !$mySession->get('schoolYear') || !$mySession->get('subSystem')) {
            return $this->redirectToRoute('app_logout');
        }

        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
        $subSystem = $this->subSystemRepository->find($mySession->get('subSystem')->getId());

        if (!$schoolYear || !$subSystem) {
            return $this->redirectToRoute('app_logout');
        }

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        $selectedClassroom = null;

        if ($slug) {
            $selectedClassroom = $this->classroomRepository->findOneBy([
                'slug' => $slug,
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ]);
        }

        if (!$selectedClassroom && $request->isMethod('POST')) {
            $classroomId = (int) $request->request->get('classroom');

            if ($classroomId > 0) {
                $selectedClassroom = $this->classroomRepository->findOneBy([
                    'id' => $classroomId,
                    'schoolYear' => $schoolYear,
                    'subSystem' => $subSystem,
                ]);
            }
        }

        if (!$selectedClassroom && $request->query->getInt('classroom') > 0) {
            $selectedClassroom = $this->classroomRepository->findOneBy([
                'id' => $request->query->getInt('classroom'),
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ]);
        }

        $days = $this->getOrderedDays();
        $slotConfiguration = $this->loadOrCreateSlotConfiguration($schoolYear, $subSystem);
        $lessons = [];
        $lessonMap = [];
        $timeTables = [];
        $boardRows = [];

        if ($selectedClassroom instanceof Classroom) {
            $lessons = $this->lessonRepository->findAllToDisplay($selectedClassroom, $subSystem);

            foreach ($lessons as $lesson) {
                if (!$lesson instanceof Lesson || !$lesson->getTeacher() || !$lesson->getSubject()) {
                    continue;
                }

                $lessonMap[$lesson->getTeacher()->getId() . '_' . $lesson->getSubject()->getId()] = $lesson->getId();
            }

            $timeTables = $this->timeTableRepository->findBy([
                'classroom' => $selectedClassroom,
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ]);

            $slotConfiguration = $this->completeSlotConfigurationWithLegacyEntries($slotConfiguration, $timeTables);
            $boardRows = $this->buildBoardRows($slotConfiguration, $days, $timeTables);
        }

        return $this->render('timetable/displayTimeTable.html.twig', [
            'school' => $school,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'lessons' => $lessons,
            'lessonMap' => $lessonMap,
            'timeTables' => $timeTables,
            'days' => $days,
            'boardRows' => $boardRows,
            'slotConfiguration' => $slotConfiguration,
            'courseSlotCount' => count(array_filter($slotConfiguration, static fn (array $row): bool => $row['type'] === 'course')),
            'selectedClassroomSlug' => $selectedClassroom?->getSlug(),
        ]);
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
                    ->setSlug($this->generateSlotSlug());

                $this->em->persist($slot);
            }

            $this->em->flush();
            $templates = $this->slotTemplateRepository->findActiveForContext($schoolYear, $subSystem);
        }

        return $this->templatesToRows($templates);
    }

    /**
     * @param array<int, TimeTableSlotTemplate> $templates
     * @return array<int, array{type:string,start:string,end:string,label:string}>
     */
    private function templatesToRows(array $templates): array
    {
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
     * Adds legacy slots from already saved TimeTable rows that are not yet present in the persisted template.
     * This avoids hiding old timetable entries after installing the new module.
     *
     * @param array<int, array{type:string,start:string,end:string,label:string}> $rows
     * @param array<int, TimeTable> $timeTables
     * @return array<int, array{type:string,start:string,end:string,label:string}>
     */
    private function completeSlotConfigurationWithLegacyEntries(array $rows, array $timeTables): array
    {
        foreach ($timeTables as $timeTable) {
            if (!$timeTable instanceof TimeTable || !$timeTable->getStartTime() || !$timeTable->getEndTime()) {
                continue;
            }

            $exists = false;
            foreach ($rows as $row) {
                if ($row['type'] === 'course' && $row['start'] === $timeTable->getStartTime()) {
                    $exists = true;
                    break;
                }
            }

            if (!$exists) {
                $rows[] = [
                    'type' => 'course',
                    'start' => $timeTable->getStartTime(),
                    'end' => $timeTable->getEndTime(),
                    'label' => '',
                ];
            }
        }

        usort($rows, static function (array $left, array $right): int {
            return strcmp($left['start'], $right['start']);
        });

        return $rows;
    }

    /**
     * @return array<int, Day>
     */
    private function getOrderedDays(): array
    {
        $days = $this->dayRepository->findAll();

        usort($days, static function (Day $left, Day $right): int {
            $order = [
                'Lundi/Monday' => 1,
                'Mardi/Tuesday' => 2,
                'Mercredi/Wednesday' => 3,
                'Jeudi/Thursday' => 4,
                'Vendredi/Friday' => 5,
            ];

            return ($order[$left->getDay()] ?? 99) <=> ($order[$right->getDay()] ?? 99);
        });

        return array_values(array_filter($days, static function (Day $day): bool {
            return in_array($day->getDay(), ['Lundi/Monday', 'Mardi/Tuesday', 'Mercredi/Wednesday', 'Jeudi/Thursday', 'Vendredi/Friday'], true);
        }));
    }

    /**
     * @param array<int, array{type:string,start:string,end:string,label:string}> $slotRows
     * @param array<int, Day> $days
     * @param array<int, TimeTable> $timeTables
     * @return array<int, array<string, mixed>>
     */
    private function buildBoardRows(array $slotRows, array $days, array $timeTables): array
    {
        $indexedEntries = [];

        foreach ($timeTables as $entry) {
            if (!$entry instanceof TimeTable || !$entry->getDay()) {
                continue;
            }

            $indexedEntries[$entry->getDay()->getId()][$entry->getStartTime()] = $entry;
        }

        $occupied = [];
        $rows = [];

        foreach ($slotRows as $index => $slot) {
            $row = $slot;
            $row['timeLabel'] = substr($slot['start'], 0, 5) . '-' . substr($slot['end'], 0, 5);
            $row['rowIndex'] = $index;
            $row['cells'] = [];

            if ($slot['type'] === 'break') {
                $rows[] = $row;
                continue;
            }

            foreach ($days as $day) {
                $dayId = $day->getId();

                if (($occupied[$dayId][$slot['start']] ?? false) === true) {
                    $row['cells'][$dayId] = ['skip' => true];
                    continue;
                }

                $entry = $indexedEntries[$dayId][$slot['start']] ?? null;

                if ($entry instanceof TimeTable) {
                    $span = $this->calculateRowSpan($slotRows, $index, $entry->getEndTime());
                    $this->markCoveredRows($slotRows, $occupied, $dayId, $index, $span);

                    $row['cells'][$dayId] = [
                        'skip' => false,
                        'entry' => $entry,
                        'rowspan' => $span,
                        'duration' => max(1, $span),
                    ];
                } else {
                    $row['cells'][$dayId] = [
                        'skip' => false,
                        'entry' => null,
                        'rowspan' => 1,
                        'duration' => 1,
                    ];
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param array<int, array{type:string,start:string,end:string,label:string}> $slotRows
     */
    private function calculateRowSpan(array $slotRows, int $startIndex, ?string $targetEndTime): int
    {
        if (!$targetEndTime) {
            return 1;
        }

        $span = 1;

        for ($i = $startIndex; $i < count($slotRows); $i++) {
            if (($slotRows[$i]['type'] ?? 'course') === 'break') {
                continue;
            }

            if ($i === $startIndex) {
                if ($slotRows[$i]['end'] === $targetEndTime) {
                    return 1;
                }

                continue;
            }

            $span++;

            if ($slotRows[$i]['end'] === $targetEndTime) {
                return $span;
            }
        }

        return 1;
    }

    /**
     * @param array<int, array{type:string,start:string,end:string,label:string}> $slotRows
     * @param array<int, array<string, bool>> $occupied
     */
    private function markCoveredRows(array $slotRows, array &$occupied, int $dayId, int $startIndex, int $span): void
    {
        if ($span <= 1) {
            return;
        }

        $covered = 0;

        for ($i = $startIndex + 1; $i < count($slotRows); $i++) {
            if (($slotRows[$i]['type'] ?? 'course') === 'break') {
                continue;
            }

            $occupied[$dayId][$slotRows[$i]['start']] = true;
            $covered++;

            if ($covered >= ($span - 1)) {
                break;
            }
        }
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

    private function generateSlotSlug(): string
    {
        return substr(bin2hex(random_bytes(12)), 0, 24);
    }
}

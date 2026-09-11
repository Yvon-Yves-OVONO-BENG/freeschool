<?php

namespace App\Controller\Admin;

use App\Entity\Classroom;
use App\Entity\Depense;
use App\Entity\Evaluation;
use App\Entity\Lesson;
use App\Entity\Registration;
use App\Entity\SchoolYear;
use App\Entity\Sequence;
use App\Entity\Student;
use App\Entity\Subject;
use App\Entity\SubSystem;
use App\Entity\Teacher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/statistiques-admin')]
class StatistiquesAdminController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        protected TranslatorInterface $translator, 
    ) {
    }

    #[Route('', name: 'statistiques_admin_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->denyUnlessAdminDashboardAllowed();

        $session = $request->getSession();
        $schoolYear = $session->get('schoolYear');
        $subSystem = $session->get('subSystem');

        if (!$schoolYear instanceof SchoolYear) {
            $schoolYear = $this->em->getRepository(SchoolYear::class)->findOneBy([], ['id' => 'DESC']);
        }

        if (!$subSystem instanceof SubSystem) {
            $subSystem = null;
        }

        $overview = $this->buildOverview($schoolYear, $subSystem);
        $studentStats = $this->buildStudentStats($schoolYear, $subSystem);
        $staffStats = $this->buildStaffStats($schoolYear, $subSystem);
        $evaluationStats = $this->buildEvaluationStats($schoolYear, $subSystem);
        $performanceStats = $this->buildPerformanceStats($schoolYear, $subSystem);
        $financeStats = $this->buildFinanceStats($schoolYear, $subSystem);
        $lessonCoverageStats = $this->buildLessonCoverageStats($schoolYear, $subSystem);

        return $this->render('admin/statistiques/index.html.twig', [
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'cards' => $overview['cards'],
            'charts' => [
                'studentsByClassroom' => $studentStats['studentsByClassroom'],
                'studentsBySex' => $studentStats['studentsBySex'],
                'staffByDuty' => $staffStats['staffByDuty'],
                'staffBySex' => $staffStats['staffBySex'],
                'marksBySequence' => $evaluationStats['marksBySequence'],
                'completionBySequence' => $evaluationStats['completionBySequence'],
                'completionByClassroom' => $evaluationStats['completionByClassroom'],
                'successBySequence' => $performanceStats['successBySequence'],
                'successByClassroom' => $performanceStats['successByClassroom'],
                'subjectAverages' => $performanceStats['subjectAverages'],
                'financeMonthly' => $financeStats['financeMonthly'],
                'feesBreakdown' => $financeStats['feesBreakdown'],
                'lessonCoverage' => $lessonCoverageStats['lessonCoverage'],
            ],
            'tables' => [
                'evaluationProgressRows' => $evaluationStats['evaluationProgressRows'],
                'classroomCompletionRows' => $evaluationStats['classroomCompletionRows'],
                'subjectAverageRows' => $performanceStats['subjectAverageRows'],
                'lessonCoverageRows' => $lessonCoverageStats['lessonCoverageRows'],
            ],
        ]);
    }

    private function denyUnlessAdminDashboardAllowed(): void
    {
        $allowed = $this->isGranted('ROLE_ADMIN')
            || $this->isGranted('ROLE_SUPER_ADMIN')
            || $this->isGranted('ROLE_PROVISEUR')
            || $this->isGranted('ROLE_CENSEUR')
            || $this->isGranted('ROLE_SECRETAIRE');

        if (!$allowed) {
            throw $this->createAccessDeniedException('Accès réservé aux statistiques administratives.');
        }
    }

    private function buildOverview(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $studentCount = $this->countStudents($schoolYear, $subSystem);
        $teacherCount = $this->countTeachers($schoolYear, $subSystem);
        $classroomCount = $this->countClassrooms($schoolYear, $subSystem);
        $subjectCount = $this->countSubjects($schoolYear, $subSystem);
        $lessonCount = $this->countLessons($schoolYear, $subSystem);
        $evaluationStats = $this->getGlobalEvaluationCompletion($schoolYear, $subSystem);
        $financeTotals = $this->getFinanceTotals($schoolYear, $subSystem);

        return [
            'cards' => [
                [
                    'label' => $this->translator->trans('Students'),
                    'value' => $studentCount,
                    'icon' => 'fe fe-users',
                    'variant' => 'primary',
                    'helper' => $this->translator->trans('Total filtered count'),
                ],
                [
                    'label' => $this->translator->trans('Staff'),
                    'value' => $teacherCount,
                    'icon' => 'fe fe-user-check',
                    'variant' => 'success',
                    'helper' => $this->translator->trans('Active staff in the system'),
                ],
                [
                    'label' => $this->translator->trans('Classes'),
                    'value' => $classroomCount,
                    'icon' => 'fe fe-grid',
                    'variant' => 'warning',
                    'helper' => $this->translator->trans('Open classes'),
                ],
                [
                    'label' => $this->translator->trans('Subjects'),
                    'value' => $subjectCount,
                    'icon' => 'fe fe-book-open',
                    'variant' => 'info',
                    'helper' => $this->translator->trans('Registered subjects'),
                ],
                [
                    'label' => $this->translator->trans('Assigned lessons'),
                    'value' => $lessonCount,
                    'icon' => 'fe fe-layers',
                    'variant' => 'secondary',
                    'helper' => $this->translator->trans('Class / subject / teacher assignments'),
                ],
                [
                    'label' => $this->translator->trans('Submission rate'),
                    'value' => $evaluationStats['completionRate'] . '%',
                    'icon' => 'fe fe-check-circle',
                    'variant' => 'success',
                    'helper' =>
                        $evaluationStats['submitted'] .
                        $this->translator->trans(' submitted / ') .
                        $evaluationStats['expected'] .
                        $this->translator->trans(' expected'),
                ],
                [
                    'label' => $this->translator->trans('Pending grades'),
                    'value' => $evaluationStats['pending'],
                    'icon' => 'fe fe-alert-circle',
                    'variant' => 'danger',
                    'helper' => $this->translator->trans('Grades not yet submitted'),
                ],
                [
                    'label' => $this->translator->trans('Revenue'),
                    'value' => $this->money($financeTotals['revenue']),
                    'icon' => 'fe fe-credit-card',
                    'variant' => 'primary',
                    'helper' => $this->translator->trans('Total income collected'),
                ],
                [
                    'label' => $this->translator->trans('Expenses'),
                    'value' => $this->money($financeTotals['expenses']),
                    'icon' => 'fe fe-shopping-bag',
                    'variant' => 'warning',
                    'helper' => $this->translator->trans('Total expenses'),
                ],
                [
                    'label' => $this->translator->trans('Balance'),
                    'value' => $this->money($financeTotals['balance']),
                    'icon' => 'fe fe-trending-up',
                    'variant' => $financeTotals['balance'] >= 0 ? 'success' : 'danger',
                    'helper' => $this->translator->trans('Revenue minus expenses'),
                ],
            ],
        ];
    }

    private function buildStudentStats(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $byClassroom = $this->studentsByClassroom($schoolYear, $subSystem);
        $bySex = $this->studentsBySex($schoolYear, $subSystem);

        return [
            'studentsByClassroom' => [
                'labels' => array_column($byClassroom, 'label'),
                'datasets' => [[
                    'label' => 'Élèves',
                    'data' => array_map('intval', array_column($byClassroom, 'value')),
                ]],
            ],
            'studentsBySex' => [
                'labels' => array_column($bySex, 'label'),
                'datasets' => [[
                    'label' => 'Élèves',
                    'data' => array_map('intval', array_column($bySex, 'value')),
                ]],
            ],
        ];
    }

    private function buildStaffStats(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $byDuty = $this->teachersByDuty($schoolYear, $subSystem);
        $bySex = $this->teachersBySex($schoolYear, $subSystem);

        return [
            'staffByDuty' => [
                'labels' => array_column($byDuty, 'label'),
                'datasets' => [[
                    'label' => 'Personnels',
                    'data' => array_map('intval', array_column($byDuty, 'value')),
                ]],
            ],
            'staffBySex' => [
                'labels' => array_column($bySex, 'label'),
                'datasets' => [[
                    'label' => 'Personnels',
                    'data' => array_map('intval', array_column($bySex, 'value')),
                ]],
            ],
        ];
    }

    private function buildEvaluationStats(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $sequences = $this->em->getRepository(Sequence::class)->createQueryBuilder('seq')
            ->leftJoin('seq.term', 't')
            ->addSelect('t')
            ->orderBy('t.id', 'ASC')
            ->addOrderBy('seq.sequence', 'ASC')
            ->getQuery()
            ->getResult();

        $lessons = $this->scopedLessonsQuery($schoolYear, $subSystem)
            ->addSelect('c')
            ->addSelect('s')
            ->getQuery()
            ->getResult();

        $studentCountByClassroom = $this->studentCountByClassroomMap($schoolYear, $subSystem);
        $submittedByLessonSequence = $this->submittedCountByLessonSequence($schoolYear, $subSystem);
        $submittedByClassroomSequence = $this->submittedCountByClassroomSequence($schoolYear, $subSystem);

        $sequenceRows = [];
        $classRows = [];

        foreach ($sequences as $sequence) {
            $sequenceId = $sequence->getId();
            $label = 'Éval. ' . $sequence->getSequence();

            $expected = 0;
            $submitted = 0;

            foreach ($lessons as $lesson) {
                $classroom = $lesson->getClassroom();
                if (!$classroom) {
                    continue;
                }

                $classroomId = $classroom->getId();
                $expectedForLesson = $studentCountByClassroom[$classroomId] ?? 0;
                $expected += $expectedForLesson;
                $submitted += $submittedByLessonSequence[$lesson->getId() . '_' . $sequenceId] ?? 0;
            }

            $pending = max(0, $expected - $submitted);
            $sequenceRows[] = [
                'id' => $sequenceId,
                'label' => $label,
                'expected' => $expected,
                'submitted' => $submitted,
                'pending' => $pending,
                'completionRate' => $this->rate($submitted, $expected),
            ];
        }

        $classrooms = $this->scopedClassroomsQuery($schoolYear, $subSystem)
            ->orderBy('c.classroom', 'ASC')
            ->getQuery()
            ->getResult();

        foreach ($classrooms as $classroom) {
            $expected = 0;
            $submitted = 0;
            $classroomId = $classroom->getId();
            $studentCount = $studentCountByClassroom[$classroomId] ?? 0;

            foreach ($sequences as $sequence) {
                foreach ($lessons as $lesson) {
                    if (!$lesson->getClassroom() || $lesson->getClassroom()->getId() !== $classroomId) {
                        continue;
                    }

                    $expected += $studentCount;
                }

                $submitted += $submittedByClassroomSequence[$classroomId . '_' . $sequence->getId()] ?? 0;
            }

            $classRows[] = [
                'id' => $classroomId,
                'label' => $classroom->getClassroom(),
                'expected' => $expected,
                'submitted' => $submitted,
                'pending' => max(0, $expected - $submitted),
                'completionRate' => $this->rate($submitted, $expected),
            ];
        }

        usort($classRows, static fn (array $a, array $b): int => $b['completionRate'] <=> $a['completionRate']);

        return [
            'marksBySequence' => [
                'labels' => array_column($sequenceRows, 'label'),
                'datasets' => [
                    ['label' => 'Notes attendues', 'data' => array_column($sequenceRows, 'expected')],
                    ['label' => 'Notes saisies', 'data' => array_column($sequenceRows, 'submitted')],
                    ['label' => 'Notes restantes', 'data' => array_column($sequenceRows, 'pending')],
                ],
            ],
            'completionBySequence' => [
                'labels' => array_column($sequenceRows, 'label'),
                'datasets' => [[
                    'label' => '% saisie',
                    'data' => array_column($sequenceRows, 'completionRate'),
                ]],
            ],
            'completionByClassroom' => [
                'labels' => array_column($classRows, 'label'),
                'datasets' => [[
                    'label' => '% saisie',
                    'data' => array_column($classRows, 'completionRate'),
                ]],
            ],
            'evaluationProgressRows' => $sequenceRows,
            'classroomCompletionRows' => $classRows,
        ];
    }

    private function buildPerformanceStats(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $successBySequence = $this->successBySequence($schoolYear, $subSystem);
        $successByClassroom = $this->successByClassroom($schoolYear, $subSystem);
        $subjectAverageRows = $this->subjectAverages($schoolYear, $subSystem);

        return [
            'successBySequence' => [
                'labels' => array_column($successBySequence, 'label'),
                'datasets' => [[
                    'label' => 'Taux de réussite >= 10/20',
                    'data' => array_column($successBySequence, 'successRate'),
                ]],
            ],
            'successByClassroom' => [
                'labels' => array_column($successByClassroom, 'label'),
                'datasets' => [[
                    'label' => 'Taux de réussite >= 10/20',
                    'data' => array_column($successByClassroom, 'successRate'),
                ]],
            ],
            'subjectAverages' => [
                'labels' => array_column($subjectAverageRows, 'label'),
                'datasets' => [[
                    'label' => 'Moyenne /20',
                    'data' => array_column($subjectAverageRows, 'average'),
                ]],
            ],
            'subjectAverageRows' => $subjectAverageRows,
        ];
    }

    private function buildFinanceStats(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $monthly = $this->financeByMonth($schoolYear, $subSystem);
        $feesBreakdown = $this->feesBreakdown($schoolYear, $subSystem);

        return [
            'financeMonthly' => [
                'labels' => array_column($monthly, 'label'),
                'datasets' => [
                    ['label' => 'Recettes', 'data' => array_column($monthly, 'revenue')],
                    ['label' => 'Dépenses', 'data' => array_column($monthly, 'expenses')],
                ],
            ],
            'feesBreakdown' => [
                'labels' => array_column($feesBreakdown, 'label'),
                'datasets' => [[
                    'label' => 'Montant',
                    'data' => array_column($feesBreakdown, 'value'),
                ]],
            ],
        ];
    }

    private function buildLessonCoverageStats(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $lessons = $this->scopedLessonsQuery($schoolYear, $subSystem)->getQuery()->getResult();
        $rows = [];

        for ($i = 1; $i <= 6; $i++) {
            $planned = 0;
            $done = 0;
            $doneWithResource = 0;

            foreach ($lessons as $lesson) {
                $planned += $this->callInt($lesson, 'getNbreLessonTheoriquePrevueSeq' . $i)
                    + $this->callInt($lesson, 'getNbreLessonPratiquePrevueSeq' . $i);
                $done += $this->callInt($lesson, 'getNbreLessonTheoriqueFaiteSeq' . $i)
                    + $this->callInt($lesson, 'getNbreLessonPratiqueFaiteSeq' . $i);
                $doneWithResource += $this->callInt($lesson, 'getNbreLessonTheoriqueFaiteAvecRessourceSeq' . $i)
                    + $this->callInt($lesson, 'getNbreLessonPratiqueFaiteAvecRessourceSeq' . $i);
            }

            $rows[] = [
                'label' => 'Éval. ' . $i,
                'planned' => $planned,
                'done' => $done,
                'doneWithResource' => $doneWithResource,
                'coverageRate' => $this->rate($done, $planned),
                'resourceRate' => $this->rate($doneWithResource, $done),
            ];
        }

        return [
            'lessonCoverage' => [
                'labels' => array_column($rows, 'label'),
                'datasets' => [
                    ['label' => 'Heures prévues', 'data' => array_column($rows, 'planned')],
                    ['label' => 'Heures faites', 'data' => array_column($rows, 'done')],
                    ['label' => '% couverture', 'data' => array_column($rows, 'coverageRate'), 'type' => 'line', 'yAxisID' => 'y1'],
                ],
            ],
            'lessonCoverageRows' => $rows,
        ];
    }

    private function countStudents(?SchoolYear $schoolYear, ?SubSystem $subSystem): int
    {
        $qb = $this->em->getRepository(Student::class)->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->leftJoin('s.classroom', 'c');

        $this->applyStudentScope($qb, $schoolYear, $subSystem);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countTeachers(?SchoolYear $schoolYear, ?SubSystem $subSystem): int
    {
        $qb = $this->em->getRepository(Teacher::class)->createQueryBuilder('t')
            ->select('COUNT(t.id)');

        if ($schoolYear) {
            $qb->andWhere('t.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $qb->andWhere('t.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countClassrooms(?SchoolYear $schoolYear, ?SubSystem $subSystem): int
    {
        $qb = $this->scopedClassroomsQuery($schoolYear, $subSystem)
            ->select('COUNT(c.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countSubjects(?SchoolYear $schoolYear, ?SubSystem $subSystem): int
    {
        $qb = $this->em->getRepository(Subject::class)->createQueryBuilder('s')
            ->select('COUNT(s.id)');

        if ($schoolYear) {
            $qb->andWhere('s.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $qb->andWhere('s.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countLessons(?SchoolYear $schoolYear, ?SubSystem $subSystem): int
    {
        $qb = $this->scopedLessonsQuery($schoolYear, $subSystem)
            ->select('COUNT(l.id)');

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function getGlobalEvaluationCompletion(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $stats = $this->buildEvaluationStats($schoolYear, $subSystem)['evaluationProgressRows'];
        $expected = array_sum(array_column($stats, 'expected'));
        $submitted = array_sum(array_column($stats, 'submitted'));

        return [
            'expected' => $expected,
            'submitted' => $submitted,
            'pending' => max(0, $expected - $submitted),
            'completionRate' => $this->rate($submitted, $expected),
        ];
    }

    private function getFinanceTotals(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $revenue = array_sum(array_column($this->feesBreakdown($schoolYear, $subSystem), 'value'));
        $expenses = (int) $this->scopedExpensesQuery($schoolYear)
            ->select('COALESCE(SUM(d.montant), 0)')
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'revenue' => $revenue,
            'expenses' => $expenses,
            'balance' => $revenue - $expenses,
        ];
    }

    private function studentsByClassroom(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Student::class)->createQueryBuilder('s')
            ->select('COALESCE(c.classroom, :unknown) AS label')
            ->addSelect('COUNT(s.id) AS value')
            ->leftJoin('s.classroom', 'c')
            ->setParameter('unknown', 'Sans classe')
            ->groupBy('c.id')
            ->addGroupBy('c.classroom')
            ->orderBy('c.classroom', 'ASC');

        $this->applyStudentScope($qb, $schoolYear, $subSystem);

        return $this->normalizeRows($qb->getQuery()->getArrayResult());
    }

    private function studentsBySex(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Student::class)->createQueryBuilder('s')
            ->select('COALESCE(sex.sex, :unknown) AS label')
            ->addSelect('COUNT(s.id) AS value')
            ->leftJoin('s.sex', 'sex')
            ->leftJoin('s.classroom', 'c')
            ->setParameter('unknown', 'Non renseigné')
            ->groupBy('sex.id')
            ->addGroupBy('sex.sex')
            ->orderBy('value', 'DESC');

        $this->applyStudentScope($qb, $schoolYear, $subSystem);

        return $this->normalizeRows($qb->getQuery()->getArrayResult());
    }

    private function teachersByDuty(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Teacher::class)->createQueryBuilder('t')
            ->select('COALESCE(d.duty, :unknown) AS label')
            ->addSelect('COUNT(t.id) AS value')
            ->leftJoin('t.duty', 'd')
            ->setParameter('unknown', 'Non renseigné')
            ->groupBy('d.id')
            ->addGroupBy('d.duty')
            ->orderBy('value', 'DESC');

        if ($schoolYear) {
            $qb->andWhere('t.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $qb->andWhere('t.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return $this->normalizeRows($qb->getQuery()->getArrayResult());
    }

    private function teachersBySex(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Teacher::class)->createQueryBuilder('t')
            ->select('COALESCE(sex.sex, :unknown) AS label')
            ->addSelect('COUNT(t.id) AS value')
            ->leftJoin('t.sex', 'sex')
            ->setParameter('unknown', 'Non renseigné')
            ->groupBy('sex.id')
            ->addGroupBy('sex.sex')
            ->orderBy('value', 'DESC');

        if ($schoolYear) {
            $qb->andWhere('t.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $qb->andWhere('t.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return $this->normalizeRows($qb->getQuery()->getArrayResult());
    }

    private function submittedCountByLessonSequence(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('l.id AS lessonId')
            ->addSelect('seq.id AS sequenceId')
            ->addSelect('COUNT(e.id) AS submittedCount')
            ->innerJoin('e.lesson', 'l')
            ->innerJoin('e.sequence', 'seq')
            ->innerJoin('l.classroom', 'c')
            ->andWhere('e.mark IS NOT NULL')
            ->groupBy('l.id')
            ->addGroupBy('seq.id');

        $this->applyClassroomScope($qb, $schoolYear, $subSystem, 'c');

        $map = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $map[(int) $row['lessonId'] . '_' . (int) $row['sequenceId']] = (int) $row['submittedCount'];
        }

        return $map;
    }

    private function submittedCountByClassroomSequence(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('c.id AS classroomId')
            ->addSelect('seq.id AS sequenceId')
            ->addSelect('COUNT(e.id) AS submittedCount')
            ->innerJoin('e.lesson', 'l')
            ->innerJoin('e.sequence', 'seq')
            ->innerJoin('l.classroom', 'c')
            ->andWhere('e.mark IS NOT NULL')
            ->groupBy('c.id')
            ->addGroupBy('seq.id');

        $this->applyClassroomScope($qb, $schoolYear, $subSystem, 'c');

        $map = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $map[(int) $row['classroomId'] . '_' . (int) $row['sequenceId']] = (int) $row['submittedCount'];
        }

        return $map;
    }

    private function studentCountByClassroomMap(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $rows = $this->em->getRepository(Student::class)->createQueryBuilder('s')
            ->select('c.id AS classroomId')
            ->addSelect('COUNT(s.id) AS studentCount')
            ->innerJoin('s.classroom', 'c');

        $qb = $rows;
        $this->applyStudentScope($qb, $schoolYear, $subSystem);
        $qb->groupBy('c.id');

        $map = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            if ($row['classroomId'] !== null) {
                $map[(int) $row['classroomId']] = (int) $row['studentCount'];
            }
        }

        return $map;
    }

    private function successBySequence(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select("CONCAT('Éval. ', seq.sequence) AS label")
            ->addSelect('COUNT(e.id) AS total')
            ->addSelect('SUM(CASE WHEN e.mark >= 10 THEN 1 ELSE 0 END) AS successCount')
            ->innerJoin('e.sequence', 'seq')
            ->innerJoin('e.lesson', 'l')
            ->innerJoin('l.classroom', 'c')
            ->andWhere('e.mark IS NOT NULL')
            ->groupBy('seq.id')
            ->addGroupBy('seq.sequence')
            ->orderBy('seq.sequence', 'ASC');

        $this->applyClassroomScope($qb, $schoolYear, $subSystem, 'c');

        $rows = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $total = (int) $row['total'];
            $success = (int) $row['successCount'];
            $rows[] = [
                'label' => $row['label'],
                'total' => $total,
                'successCount' => $success,
                'successRate' => $this->rate($success, $total),
            ];
        }

        return $rows;
    }

    private function successByClassroom(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('c.classroom AS label')
            ->addSelect('COUNT(e.id) AS total')
            ->addSelect('SUM(CASE WHEN e.mark >= 10 THEN 1 ELSE 0 END) AS successCount')
            ->innerJoin('e.lesson', 'l')
            ->innerJoin('l.classroom', 'c')
            ->andWhere('e.mark IS NOT NULL')
            ->groupBy('c.id')
            ->addGroupBy('c.classroom')
            ->orderBy('c.classroom', 'ASC');

        $this->applyClassroomScope($qb, $schoolYear, $subSystem, 'c');

        $rows = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $total = (int) $row['total'];
            $success = (int) $row['successCount'];
            $rows[] = [
                'label' => $row['label'],
                'total' => $total,
                'successCount' => $success,
                'successRate' => $this->rate($success, $total),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => $b['successRate'] <=> $a['successRate']);

        return $rows;
    }

    private function subjectAverages(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('sub.subject AS label')
            ->addSelect('AVG(e.mark) AS average')
            ->addSelect('COUNT(e.id) AS markCount')
            ->innerJoin('e.lesson', 'l')
            ->innerJoin('l.subject', 'sub')
            ->innerJoin('l.classroom', 'c')
            ->andWhere('e.mark IS NOT NULL')
            ->groupBy('sub.id')
            ->addGroupBy('sub.subject')
            ->orderBy('average', 'DESC')
            ->setMaxResults(15);

        $this->applyClassroomScope($qb, $schoolYear, $subSystem, 'c');

        $rows = [];
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $rows[] = [
                'label' => $row['label'],
                'average' => round((float) $row['average'], 2),
                'markCount' => (int) $row['markCount'],
            ];
        }

        return $rows;
    }

    private function feesBreakdown(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $qb = $this->em->getRepository(Registration::class)->createQueryBuilder('r')
            ->select('COALESCE(SUM(r.schoolFees), 0) AS schoolFees')
            ->addSelect('COALESCE(SUM(r.apeeFees), 0) AS apeeFees')
            ->addSelect('COALESCE(SUM(r.computerFees), 0) AS computerFees')
            ->addSelect('COALESCE(SUM(r.medicalBookletFees), 0) AS medicalBookletFees')
            ->addSelect('COALESCE(SUM(r.cleanSchoolFees), 0) AS cleanSchoolFees')
            ->addSelect('COALESCE(SUM(r.photoFees), 0) AS photoFees')
            ->addSelect('COALESCE(SUM(r.stampFees), 0) AS stampFees')
            ->addSelect('COALESCE(SUM(r.examFees), 0) AS examFees')
            ->leftJoin('r.student', 's')
            ->leftJoin('s.classroom', 'c');

        if ($schoolYear) {
            $qb->andWhere('r.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $qb->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        $row = $qb->getQuery()->getSingleResult();

        return [
            ['label' => 'Scolarité', 'value' => (int) $row['schoolFees']],
            ['label' => 'APEE', 'value' => (int) $row['apeeFees']],
            ['label' => 'Informatique', 'value' => (int) $row['computerFees']],
            ['label' => 'Carnet médical', 'value' => (int) $row['medicalBookletFees']],
            ['label' => 'Clean school', 'value' => (int) $row['cleanSchoolFees']],
            ['label' => 'Photos', 'value' => (int) $row['photoFees']],
            ['label' => 'Timbres', 'value' => (int) $row['stampFees']],
            ['label' => 'Examens', 'value' => (int) $row['examFees']],
        ];
    }

    private function financeByMonth(?SchoolYear $schoolYear, ?SubSystem $subSystem): array
    {
        $revenueRows = $this->em->getRepository(Registration::class)->createQueryBuilder('r')
            ->select('r.createdAt AS createdAt')
            ->addSelect('r.schoolFees AS schoolFees')
            ->addSelect('r.apeeFees AS apeeFees')
            ->addSelect('r.computerFees AS computerFees')
            ->addSelect('r.medicalBookletFees AS medicalBookletFees')
            ->addSelect('r.cleanSchoolFees AS cleanSchoolFees')
            ->addSelect('r.photoFees AS photoFees')
            ->addSelect('r.stampFees AS stampFees')
            ->addSelect('r.examFees AS examFees')
            ->leftJoin('r.student', 's')
            ->leftJoin('s.classroom', 'c');

        if ($schoolYear) {
            $revenueRows->andWhere('r.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $revenueRows->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        $months = [];
        foreach ($revenueRows->getQuery()->getArrayResult() as $row) {
            $label = $this->monthLabel($row['createdAt'] ?? null);
            $months[$label] ??= ['label' => $label, 'revenue' => 0, 'expenses' => 0];
            $months[$label]['revenue'] += (int) ($row['schoolFees'] ?? 0)
                + (int) ($row['apeeFees'] ?? 0)
                + (int) ($row['computerFees'] ?? 0)
                + (int) ($row['medicalBookletFees'] ?? 0)
                + (int) ($row['cleanSchoolFees'] ?? 0)
                + (int) ($row['photoFees'] ?? 0)
                + (int) ($row['stampFees'] ?? 0)
                + (int) ($row['examFees'] ?? 0);
        }

        $expenseRows = $this->scopedExpensesQuery($schoolYear)
            ->select('d.createdAt AS createdAt')
            ->addSelect('d.montant AS montant')
            ->getQuery()
            ->getArrayResult();

        foreach ($expenseRows as $row) {
            $label = $this->monthLabel($row['createdAt'] ?? null);
            $months[$label] ??= ['label' => $label, 'revenue' => 0, 'expenses' => 0];
            $months[$label]['expenses'] += (int) ($row['montant'] ?? 0);
        }

        ksort($months);

        return array_values($months);
    }

    private function scopedLessonsQuery(?SchoolYear $schoolYear, ?SubSystem $subSystem)
    {
        $qb = $this->em->getRepository(Lesson::class)->createQueryBuilder('l')
            ->innerJoin('l.classroom', 'c')
            ->leftJoin('l.subject', 's')
            ->leftJoin('l.teacher', 't');

        $this->applyClassroomScope($qb, $schoolYear, $subSystem, 'c');

        return $qb;
    }

    private function scopedClassroomsQuery(?SchoolYear $schoolYear, ?SubSystem $subSystem)
    {
        $qb = $this->em->getRepository(Classroom::class)->createQueryBuilder('c');
        $this->applyClassroomScope($qb, $schoolYear, $subSystem, 'c');

        return $qb;
    }

    private function scopedExpensesQuery(?SchoolYear $schoolYear)
    {
        $qb = $this->em->getRepository(Depense::class)->createQueryBuilder('d');

        if ($schoolYear) {
            $qb->andWhere('d.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        return $qb;
    }

    private function applyStudentScope($qb, ?SchoolYear $schoolYear, ?SubSystem $subSystem): void
    {
        if ($schoolYear) {
            $qb->andWhere('s.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $qb->andWhere('(s.subSystem = :subSystem OR c.subSystem = :subSystem)')->setParameter('subSystem', $subSystem);
        }
    }

    private function applyClassroomScope($qb, ?SchoolYear $schoolYear, ?SubSystem $subSystem, string $alias = 'c'): void
    {
        if ($schoolYear) {
            $qb->andWhere($alias . '.schoolYear = :schoolYear')->setParameter('schoolYear', $schoolYear);
        }

        if ($subSystem) {
            $qb->andWhere($alias . '.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }
    }

    private function normalizeRows(array $rows): array
    {
        return array_map(static fn (array $row): array => [
            'label' => (string) ($row['label'] ?? 'Non renseigné'),
            'value' => (int) ($row['value'] ?? 0),
        ], $rows);
    }

    private function callInt(object $object, string $method): int
    {
        if (!method_exists($object, $method)) {
            return 0;
        }

        return (int) ($object->$method() ?? 0);
    }

    private function rate(int|float $value, int|float $total): float
    {
        if ($total <= 0) {
            return 0;
        }

        return round(min(100, ($value / $total) * 100), 1);
    }

    private function money(int|float $amount): string
    {
        return number_format((float) $amount, 0, ',', ' ') . ' FCFA';
    }

    private function monthLabel(mixed $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $date->format('Y-m');
        }

        return 'Non daté';
    }
}

<?php

namespace App\Controller\Home;

use App\Entity\Classroom;
use App\Entity\Depense;
use App\Entity\Evaluation;
use App\Entity\Lesson;
use App\Entity\Registration;
use App\Entity\Student;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Repository\ClassroomRepository;
use App\Repository\DepenseRepository;
use App\Repository\EvaluationRepository;
use App\Repository\LessonRepository;
use App\Repository\RegistrationRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\StudentRepository;
use App\Repository\SubjectRepository;
use App\Repository\TeacherRepository;
use App\Repository\TermRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/home')]
class DashboardController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SchoolRepository $schoolRepository,
        private StudentRepository $studentRepository,
        private TeacherRepository $teacherRepository,
        private ClassroomRepository $classroomRepository,
        private SubjectRepository $subjectRepository,
        private LessonRepository $lessonRepository,
        private EvaluationRepository $evaluationRepository,
        private RegistrationRepository $registrationRepository,
        private DepenseRepository $depenseRepository,
    ) {
    }

    #[Route('/dashboard/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}', name: 'home_dashboard')]
    public function dashboard(
        Request $request,
        LessonRepository $lessonRepository,
        TermRepository $termRepository,
        SequenceRepository $sequenceRepository,
        EvaluationRepository $evaluationRepository,
        int $a = 0,
        int $m = 0,
        int $s = 0
    ): Response {
        $session = $request->getSession();
        $this->syncNotificationFlags($session, $a, $m, $s);

        $schoolYear = $session->get('schoolYear');
        $subSystem = $session->get('subSystem');

        $school = $schoolYear
            ? $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear])
            : null;

        $user = $this->getUser();
        $teacher = $user && method_exists($user, 'getTeacher') ? $user->getTeacher() : null;

        $adminRoles = [
            'ROLE_ADMIN',
            'ROLE_SUPER_ADMIN',
            'ROLE_PROVISEUR',
            'ROLE_HEADMASTER',
            'ROLE_DIRECTOR',
        ];

        $dashboardType = ($user && array_intersect($adminRoles, $user->getRoles())) || !$teacher
            ? 'admin'
            : 'teacher';

        $adminStats = $this->buildAdminStats($schoolYear, $subSystem);

        $teacherStats = null;

        if ($dashboardType === 'teacher' && $teacher) {
            $teacherStats = $this->buildTeacherStats($teacher, $schoolYear);

            $evaluationDashboard = $this->buildTeacherEvaluationDashboard(
                $teacher,
                $lessonRepository,
                $termRepository,
                $sequenceRepository,
                $evaluationRepository
            );

            /*
            * Très important :
            * On écrase les anciennes valeurs evaluationCount / pendingMarks
            * pour que les cartes du haut soient exactement égales
            * à la somme des 3 trimestres.
            */
            $teacherStats['evaluationSubmittedTotal'] = $evaluationDashboard['evaluationSubmittedTotal'];
            $teacherStats['evaluationPendingTotal'] = $evaluationDashboard['evaluationPendingTotal'];
            $teacherStats['evaluationTerms'] = $evaluationDashboard['evaluationTerms'];

            $teacherStats['evaluationCount'] = $evaluationDashboard['evaluationSubmittedTotal'];
            $teacherStats['completedMarks'] = $evaluationDashboard['evaluationSubmittedTotal'];
            $teacherStats['pendingMarks'] = $evaluationDashboard['evaluationPendingTotal'];
        }

        return $this->render('home/dashboard.html.twig', [
            'teacherStats' => $teacherStats,
            'school' => $school,
            'dashboardType' => $dashboardType,
            'adminStats' => $adminStats,
            'emailReminderNeeded' => $teacher && method_exists($user, 'getEmail') && !$user->getEmail(),
        ]);
    }

    private function buildTeacherEvaluationDashboard(
        Teacher $teacher,
        LessonRepository $lessonRepository,
        TermRepository $termRepository,
        SequenceRepository $sequenceRepository,
        EvaluationRepository $evaluationRepository
    ): array {
        /*
        * Une leçon = une classe + une matière pour cet enseignant.
        * Exemple : 4e All / Math, 3e All / Math, Tle C / Math.
        */
        $teacherLessons = $lessonRepository->findBy([
            'teacher' => $teacher,
        ]);

        /*
        * Cette map contient les couples déjà saisis :
        * lessonId_sequenceId
        *
        * Exemple :
        * 12_1 = notes saisies pour la leçon 12, évaluation 1
        */
        $submittedRows = $evaluationRepository->findSubmittedLessonSequenceKeysByTeacher($teacher);

        $submittedMap = [];

        foreach ($submittedRows as $row) {
            $lessonId = (int) $row['lessonId'];
            $sequenceId = (int) $row['sequenceId'];

            $submittedMap[$lessonId . '_' . $sequenceId] = true;
        }

        /*
        * On prend les 3 trimestres.
        */
        $terms = $termRepository->findBy([], ['id' => 'ASC']);
        $terms = array_slice($terms, 0, 3);

        $evaluationTerms = [];

        foreach ($terms as $term) {
            $sequences = $sequenceRepository->findBy(
                ['term' => $term],
                ['id' => 'ASC']
            );

            $termData = [
                'id' => $term->getId(),
                'name' => $term->getTerm(),
                'doneCount' => 0,
                'pendingCount' => 0,
                'totalCount' => 0,
                'done' => [],
                'pending' => [],
            ];

            foreach ($sequences as $sequence) {
                foreach ($teacherLessons as $lesson) {
                    $key = $lesson->getId() . '_' . $sequence->getId();

                    $classroom = $lesson->getClassroom();
                    $subject = $lesson->getSubject();

                    $item = [
                        'sequence' => $sequence->getSequence(),
                        'classroom' => $classroom ? $classroom->getClassroom() : '-',
                        'subject' => $subject ? $subject->getSubject() : '-',
                    ];

                    $termData['totalCount']++;

                    if (isset($submittedMap[$key])) {
                        $termData['doneCount']++;
                        $termData['done'][] = $item;
                    } else {
                        $termData['pendingCount']++;
                        $termData['pending'][] = $item;
                    }
                }
            }

            $evaluationTerms[] = $termData;
        }

        /*
        * Calcul final depuis evaluationTerms.
        * Comme ça, les cartes du haut sont forcément égales
        * à la somme des 3 cartes trimestre.
        */
        $evaluationSubmittedTotal = array_sum(array_map(static function (array $term): int {
            return (int) $term['doneCount'];
        }, $evaluationTerms));

        $evaluationPendingTotal = array_sum(array_map(static function (array $term): int {
            return (int) $term['pendingCount'];
        }, $evaluationTerms));

        return [
            'evaluationSubmittedTotal' => $evaluationSubmittedTotal,
            'evaluationPendingTotal' => $evaluationPendingTotal,
            'evaluationTerms' => $evaluationTerms,
        ];
    }

    private function syncNotificationFlags($session, int $a, int $m, int $s): void
    {
        $session->set('ajout', $a === 1 ? 1 : null);
        $session->set('miseAjour', $m === 1 ? 1 : null);
        $session->set('suppression', $s === 1 ? 1 : null);

        if ($a !== 1 && $m !== 1 && $s !== 1) {
            $session->set('ajout', null);
            $session->set('miseAjour', null);
            $session->set('suppression', null);
        }

        $session->set('saisiNotes', null);
    }

    private function buildAdminStats($schoolYear, $subSystem): array
    {
        $studentCount = $this->countStudents($schoolYear, $subSystem);
        $teacherCount = $this->countTeachers($schoolYear, $subSystem);
        $classroomCount = $this->classroomRepository->count([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
        ]);
        $subjectCount = $this->subjectRepository->count([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
        ]);
        $lessonCount = $this->countLessons($schoolYear, $subSystem);
        $evaluationCount = $this->countEvaluations($schoolYear, $subSystem);
        $registrationCount = $this->countRegistrations($schoolYear, $subSystem);
        $pendingMarksCount = $this->countPendingMarks($schoolYear, $subSystem);
        $totalRevenue = $this->sumRegistrationRevenue($schoolYear, $subSystem);
        $totalExpense = $this->sumExpenses($schoolYear);

        return [
            'cards' => [
                ['label' => 'Élèves', 'value' => $studentCount, 'icon' => 'fe fe-users', 'variant' => 'primary'],
                ['label' => 'Personnels', 'value' => $teacherCount, 'icon' => 'fe fe-user-check', 'variant' => 'success'],
                ['label' => 'Classes', 'value' => $classroomCount, 'icon' => 'fe fe-grid', 'variant' => 'warning'],
                ['label' => 'Matières', 'value' => $subjectCount, 'icon' => 'fe fe-book-open', 'variant' => 'info'],
                ['label' => 'Leçons affectées', 'value' => $lessonCount, 'icon' => 'fe fe-layers', 'variant' => 'secondary'],
                ['label' => 'Évaluations', 'value' => $evaluationCount, 'icon' => 'fe fe-edit-3', 'variant' => 'danger'],
            ],
            'finance' => [
                'registrations' => $registrationCount,
                'revenue' => $totalRevenue,
                'expense' => $totalExpense,
                'balance' => $totalRevenue - $totalExpense,
            ],
            'pedagogy' => [
                'pendingMarks' => $pendingMarksCount,
                'completedMarks' => max(0, $evaluationCount - $pendingMarksCount),
            ],
            'recentTeachers' => $this->teacherRepository->findBy([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ], ['id' => 'DESC'], 6),
            'recentClassrooms' => $this->classroomRepository->findBy([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ], ['id' => 'DESC'], 6),
            'recentStudents' => $this->studentRepository->findBy([
                'schoolYear' => $schoolYear,
            ], ['id' => 'DESC'], 6),
        ];
    }

    private function buildTeacherStats(Teacher $teacher, $schoolYear): array
    {
        $lessons = $teacher->getLessons()->toArray();
        $classrooms = [];
        foreach ($lessons as $lesson) {
            if ($lesson->getClassroom()) {
                $classrooms[$lesson->getClassroom()->getId()] = $lesson->getClassroom();
            }
        }

        $evaluationCount = $this->countEvaluationsForTeacher($teacher, $schoolYear);
        $pendingMarks = $this->countPendingMarksForTeacher($teacher, $schoolYear);
        $recentEvaluations = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->join('e.lesson', 'l')
            ->leftJoin('e.sequence', 'seq')
            ->leftJoin('l.classroom', 'c')
            ->where('l.teacher = :teacher')
            ->andWhere('c.schoolYear = :schoolYear')
            ->setParameter('teacher', $teacher)
            ->setParameter('schoolYear', $schoolYear)
            ->orderBy('e.id', 'DESC')
            ->setMaxResults(8)
            ->getQuery()
            ->getResult();

        return [
            'teacher' => $teacher,
            'assignedLessons' => count($lessons),
            'classrooms' => array_values($classrooms),
            'classroomCount' => count($classrooms),
            'evaluationCount' => $evaluationCount,
            'pendingMarks' => $pendingMarks,
            'completedMarks' => max(0, $evaluationCount - $pendingMarks),
            'recentLessons' => array_slice($lessons, 0, 8),
            'recentEvaluations' => $recentEvaluations,
        ];
    }

    private function countStudents($schoolYear, $subSystem): int
    {
        $qb = $this->em->getRepository(Student::class)->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->leftJoin('s.classroom', 'c')
            ->where('s.schoolYear = :schoolYear')
            ->setParameter('schoolYear', $schoolYear);

        if ($subSystem) {
            $qb->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countTeachers($schoolYear, $subSystem): int
    {
        return $this->teacherRepository->count([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
        ]);
    }

    private function countLessons($schoolYear, $subSystem): int
    {
        $qb = $this->em->getRepository(Lesson::class)->createQueryBuilder('l')
            ->select('COUNT(l.id)')
            ->leftJoin('l.classroom', 'c')
            ->where('c.schoolYear = :schoolYear')
            ->setParameter('schoolYear', $schoolYear);

        if ($subSystem) {
            $qb->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countEvaluations($schoolYear, $subSystem): int
    {
        $qb = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->leftJoin('e.lesson', 'l')
            ->leftJoin('l.classroom', 'c')
            ->where('c.schoolYear = :schoolYear')
            ->setParameter('schoolYear', $schoolYear);

        if ($subSystem) {
            $qb->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countRegistrations($schoolYear, $subSystem): int
    {
        $qb = $this->em->getRepository(Registration::class)->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->leftJoin('r.student', 's')
            ->leftJoin('s.classroom', 'c')
            ->where('r.schoolYear = :schoolYear')
            ->setParameter('schoolYear', $schoolYear);

        if ($subSystem) {
            $qb->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function countPendingMarks($schoolYear, $subSystem): int
    {
        $qb = $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->leftJoin('e.lesson', 'l')
            ->leftJoin('l.classroom', 'c')
            ->where('c.schoolYear = :schoolYear')
            ->andWhere('e.mark IS NULL')
            ->setParameter('schoolYear', $schoolYear);

        if ($subSystem) {
            $qb->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function sumRegistrationRevenue($schoolYear, $subSystem): int
    {
        $parts = [
            'COALESCE(r.schoolFees, 0)',
            'COALESCE(r.apeeFees, 0)',
            'COALESCE(r.computerFees, 0)',
            'COALESCE(r.medicalBookletFees, 0)',
            'COALESCE(r.cleanSchoolFees, 0)',
            'COALESCE(r.photoFees, 0)',
            'COALESCE(r.stampFees, 0)',
            'COALESCE(r.examFees, 0)',
        ];

        $qb = $this->em->getRepository(Registration::class)->createQueryBuilder('r')
            ->select('COALESCE(SUM(' . implode(' + ', $parts) . '), 0)')
            ->leftJoin('r.student', 's')
            ->leftJoin('s.classroom', 'c')
            ->where('r.schoolYear = :schoolYear')
            ->setParameter('schoolYear', $schoolYear);

        if ($subSystem) {
            $qb->andWhere('c.subSystem = :subSystem')->setParameter('subSystem', $subSystem);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function sumExpenses($schoolYear): int
    {
        return (int) $this->em->getRepository(Depense::class)->createQueryBuilder('d')
            ->select('COALESCE(SUM(d.montant), 0)')
            ->where('d.schoolYear = :schoolYear')
            ->setParameter('schoolYear', $schoolYear)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countEvaluationsForTeacher(Teacher $teacher, $schoolYear): int
    {
        return (int) $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->leftJoin('e.lesson', 'l')
            ->leftJoin('l.classroom', 'c')
            ->where('l.teacher = :teacher')
            ->andWhere('c.schoolYear = :schoolYear')
            ->setParameter('teacher', $teacher)
            ->setParameter('schoolYear', $schoolYear)
            ->getQuery()
            ->getSingleScalarResult();
    }

    private function countPendingMarksForTeacher(Teacher $teacher, $schoolYear): int
    {
        return (int) $this->em->getRepository(Evaluation::class)->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->leftJoin('e.lesson', 'l')
            ->leftJoin('l.classroom', 'c')
            ->where('l.teacher = :teacher')
            ->andWhere('c.schoolYear = :schoolYear')
            ->andWhere('e.mark IS NULL')
            ->setParameter('teacher', $teacher)
            ->setParameter('schoolYear', $schoolYear)
            ->getQuery()
            ->getSingleScalarResult();
    }
}

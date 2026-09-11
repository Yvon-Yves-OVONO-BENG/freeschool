<?php

namespace App\Controller\Teacher;

use App\Entity\Teacher;
use App\Repository\EvaluationRepository;
use App\Repository\LessonRepository;
use App\Repository\SequenceRepository;
use App\Repository\TermRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeacherDashboardChartsController extends AbstractController
{
    #[Route('/teacher/dashboard/charts', name: 'teacher_dashboard_charts')]
    public function index(
        LessonRepository $lessonRepository,
        TermRepository $termRepository,
        SequenceRepository $sequenceRepository,
        EvaluationRepository $evaluationRepository
    ): Response {
        $user = $this->getUser();

        if (!$user || !method_exists($user, 'getTeacher') || !$user->getTeacher()) {
            throw $this->createAccessDeniedException('Aucun enseignant connecté.');
        }

        /** @var Teacher $teacher */
        $teacher = $user->getTeacher();

        $lessons = $lessonRepository->findBy(
            ['teacher' => $teacher],
            ['id' => 'ASC']
        );

        $terms = $termRepository->findBy([], ['id' => 'ASC']);
        $terms = array_slice($terms, 0, 3);

        $termStats = [];
        $classMap = [];

        foreach ($terms as $term) {
            $termKey = 'term_' . $term->getId();

            $termStats[$termKey] = [
                'id' => $term->getId(),
                'name' => method_exists($term, 'getTerm') ? $term->getTerm() : 'Trimestre',
                'totalMarks' => 0,
                'evaluations' => [],
            ];

            $sequences = $sequenceRepository->findBy(
                ['term' => $term],
                ['id' => 'ASC']
            );

            foreach ($sequences as $sequence) {
                $sequenceKey = 'sequence_' . $sequence->getId();

                $termStats[$termKey]['evaluations'][$sequenceKey] = [
                    'id' => $sequence->getId(),
                    'name' => method_exists($sequence, 'getSequence') ? $sequence->getSequence() : 'Évaluation',
                    'chartId' => $term->getId() . '_' . $sequence->getId(),

                    'totalMarks' => 0,

                    'girlsAverageCount' => 0,
                    'boysAverageCount' => 0,
                    'classAverageCount' => 0,

                    'girlsTotalCount' => 0,
                    'boysTotalCount' => 0,

                    'girlsSuccessRate' => 0,
                    'boysSuccessRate' => 0,
                    'classSuccessRate' => 0,

                    'girlsMaxMark' => null,
                    'boysMaxMark' => null,

                    'girlsMinMark' => null,
                    'boysMinMark' => null,

                    'classMaxMark' => null,
                    'classMinMark' => null,

                    'classes' => [],
                ];

                foreach ($lessons as $lesson) {
                    $classroom = $lesson->getClassroom();

                    if (!$classroom) {
                        continue;
                    }

                    $classKey = 'class_' . $classroom->getId();
                    $classMap[$classKey] = true;

                    if (!isset($termStats[$termKey]['evaluations'][$sequenceKey]['classes'][$classKey])) {
                        $termStats[$termKey]['evaluations'][$sequenceKey]['classes'][$classKey] = [
                            'id' => $classroom->getId(),
                            'label' => $classroom->getClassroom(),

                            'totalMarks' => 0,

                            'girlsAverageCount' => 0,
                            'boysAverageCount' => 0,
                            'classAverageCount' => 0,

                            'girlsTotalCount' => 0,
                            'boysTotalCount' => 0,

                            'girlsSuccessRate' => 0,
                            'boysSuccessRate' => 0,
                            'classSuccessRate' => 0,

                            'girlsMaxMark' => null,
                            'boysMaxMark' => null,

                            'girlsMinMark' => null,
                            'boysMinMark' => null,

                            'classMaxMark' => null,
                            'classMinMark' => null,
                        ];
                    }
                }
            }
        }

        $evaluations = $evaluationRepository->findMarksForTeacherCharts($teacher);

        foreach ($evaluations as $evaluation) {
            $mark = $this->normalizeMark($evaluation->getMark());

            if ($mark === null) {
                continue;
            }

            $lesson = $evaluation->getLesson();
            $sequence = $evaluation->getSequence();

            if (!$lesson || !$sequence || !method_exists($sequence, 'getTerm') || !$sequence->getTerm()) {
                continue;
            }

            $term = $sequence->getTerm();
            $classroom = $lesson->getClassroom();

            if (!$classroom) {
                continue;
            }

            $termKey = 'term_' . $term->getId();
            $sequenceKey = 'sequence_' . $sequence->getId();
            $classKey = 'class_' . $classroom->getId();

            if (
                !isset($termStats[$termKey]) ||
                !isset($termStats[$termKey]['evaluations'][$sequenceKey]) ||
                !isset($termStats[$termKey]['evaluations'][$sequenceKey]['classes'][$classKey])
            ) {
                continue;
            }

            $evaluationData =& $termStats[$termKey]['evaluations'][$sequenceKey];
            $classData =& $termStats[$termKey]['evaluations'][$sequenceKey]['classes'][$classKey];

            $termStats[$termKey]['totalMarks']++;
            $evaluationData['totalMarks']++;
            $classData['totalMarks']++;

            if ($mark >= 10) {
                $evaluationData['classAverageCount']++;
                $classData['classAverageCount']++;
            }

            $this->updateMaxMin($evaluationData, 'classMaxMark', 'classMinMark', $mark);
            $this->updateMaxMin($classData, 'classMaxMark', 'classMinMark', $mark);

            $student = method_exists($evaluation, 'getStudent') ? $evaluation->getStudent() : null;
            $sexLabel = $this->getStudentSexLabel($student);

            if ($this->isGirl($sexLabel)) {
                $evaluationData['girlsTotalCount']++;
                $classData['girlsTotalCount']++;

                if ($mark >= 10) {
                    $evaluationData['girlsAverageCount']++;
                    $classData['girlsAverageCount']++;
                }

                $this->updateMaxMin($evaluationData, 'girlsMaxMark', 'girlsMinMark', $mark);
                $this->updateMaxMin($classData, 'girlsMaxMark', 'girlsMinMark', $mark);
            }

            if ($this->isBoy($sexLabel)) {
                $evaluationData['boysTotalCount']++;
                $classData['boysTotalCount']++;

                if ($mark >= 10) {
                    $evaluationData['boysAverageCount']++;
                    $classData['boysAverageCount']++;
                }

                $this->updateMaxMin($evaluationData, 'boysMaxMark', 'boysMinMark', $mark);
                $this->updateMaxMin($classData, 'boysMaxMark', 'boysMinMark', $mark);
            }

            unset($evaluationData, $classData);
        }

        $globalTotalMarks = 0;
        $evaluationCount = 0;

        foreach ($termStats as &$termData) {
            $globalTotalMarks += $termData['totalMarks'];

            foreach ($termData['evaluations'] as &$evaluationData) {
                $evaluationData['girlsSuccessRate'] = $this->calculateRate(
                    $evaluationData['girlsAverageCount'],
                    $evaluationData['girlsTotalCount']
                );

                $evaluationData['boysSuccessRate'] = $this->calculateRate(
                    $evaluationData['boysAverageCount'],
                    $evaluationData['boysTotalCount']
                );

                $evaluationData['classSuccessRate'] = $this->calculateRate(
                    $evaluationData['classAverageCount'],
                    $evaluationData['totalMarks']
                );

                foreach ($evaluationData['classes'] as &$classData) {
                    $classData['girlsSuccessRate'] = $this->calculateRate(
                        $classData['girlsAverageCount'],
                        $classData['girlsTotalCount']
                    );

                    $classData['boysSuccessRate'] = $this->calculateRate(
                        $classData['boysAverageCount'],
                        $classData['boysTotalCount']
                    );

                    $classData['classSuccessRate'] = $this->calculateRate(
                        $classData['classAverageCount'],
                        $classData['totalMarks']
                    );
                }

                unset($classData);

                $evaluationData['classes'] = array_values($evaluationData['classes']);
                $evaluationCount++;
            }

            unset($evaluationData);

            $termData['evaluations'] = array_values($termData['evaluations']);
        }

        unset($termData);

        $termStats = array_values($termStats);

        return $this->render('teacher/dashboard_charts.html.twig', [
            'teacher' => $teacher,
            'termStats' => $termStats,
            'globalStats' => [
                'totalMarks' => $globalTotalMarks,
                'termCount' => count($termStats),
                'evaluationCount' => $evaluationCount,
                'classCount' => count($classMap),
            ],
        ]);
    }

    private function normalizeMark(mixed $mark): ?float
    {
        if ($mark === null || $mark === '') {
            return null;
        }

        return (float) str_replace(',', '.', (string) $mark);
    }

    private function calculateRate(int|float $successCount, int|float $totalCount): float
    {
        if ($totalCount <= 0) {
            return 0;
        }

        return round(($successCount / $totalCount) * 100, 1);
    }

    private function updateMaxMin(array &$target, string $maxKey, string $minKey, float $mark): void
    {
        $target[$maxKey] = $target[$maxKey] === null
            ? $mark
            : max((float) $target[$maxKey], $mark);

        $target[$minKey] = $target[$minKey] === null
            ? $mark
            : min((float) $target[$minKey], $mark);
    }

    private function getStudentSexLabel(mixed $student): string
    {
        if (!$student || !method_exists($student, 'getSex') || !$student->getSex()) {
            return '';
        }

        $sex = $student->getSex();

        if (is_object($sex)) {
            foreach (['getSex', 'getName', 'getGender', 'getLabel', 'getLibelle'] as $method) {
                if (method_exists($sex, $method)) {
                    return $this->normalizeText((string) $sex->$method());
                }
            }
        }

        return $this->normalizeText((string) $sex);
    }

    private function normalizeText(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'î', 'ï', 'ô', 'ö', 'ù', 'û', 'ç'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'i', 'i', 'o', 'o', 'u', 'u', 'c'],
            $value
        );
    }

    private function isGirl(string $sexLabel): bool
    {
        return $sexLabel === 'f'
            || $sexLabel === 'female'
            || $sexLabel === 'girl'
            || str_contains($sexLabel, 'fille')
            || str_contains($sexLabel, 'feminin');
    }

    private function isBoy(string $sexLabel): bool
    {
        return $sexLabel === 'm'
            || $sexLabel === 'male'
            || $sexLabel === 'boy'
            || str_contains($sexLabel, 'garcon')
            || str_contains($sexLabel, 'masculin');
    }
}
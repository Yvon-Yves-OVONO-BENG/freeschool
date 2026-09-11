<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Entity\Report;
use App\Entity\Sequence;
use App\Entity\Term;
use App\Repository\EvaluationRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\TermRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Maintient les moyennes et les rangs de l'entité Report synchronisés avec
 * les notes. Les bulletins, tableaux d'honneur et états statistiques peuvent
 * ainsi être ouverts directement après une saisie ou une correction.
 */
class ReportRefreshService
{
    public function __construct(
        private EntityManagerInterface $em,
        private GeneralService $generalService,
        private ReportService $reportService,
        private TermRepository $termRepository,
        private SchoolRepository $schoolRepository,
        private ReportRepository $reportRepository,
        private SequenceRepository $sequenceRepository,
        private EvaluationRepository $evaluationRepository,
    ) {
    }

    /**
     * Recalcule le trimestre de la séquence modifiée puis le résultat annuel.
     */
    public function refreshAfterSequence(?Sequence $sequence, ?Classroom $classroom): void
    {
        if ($sequence === null || $classroom === null || $sequence->getTerm() === null) {
            return;
        }

        $this->refreshClassroomTerm($classroom, $sequence->getTerm());

        $annualTerm = $this->termRepository->findOneBy([
            'term' => ConstantsClass::ANNUEL_TERM,
        ]);

        if ($annualTerm !== null && $annualTerm->getId() !== $sequence->getTerm()->getId()) {
            $this->refreshClassroomTerm($classroom, $annualTerm);
        }
    }

    /**
     * Prépare tous les rappels trimestriels avant un bulletin annuel.
     */
    public function refreshForReport(Classroom $classroom, Term $term): void
    {
        if ($term->getTerm() === ConstantsClass::ANNUEL_TERM) {
            $this->refreshAllTerms($classroom);

            return;
        }

        $this->refreshClassroomTerm($classroom, $term);

        $annualTerm = $this->termRepository->findOneBy([
            'term' => ConstantsClass::ANNUEL_TERM,
        ]);

        if ($annualTerm !== null) {
            $this->refreshClassroomTerm($classroom, $annualTerm);
        }
    }

    public function refreshAllTerms(Classroom $classroom): void
    {
        foreach ([1, 2, 3] as $termNumber) {
            $term = $this->termRepository->findOneBy(['term' => $termNumber]);

            if ($term !== null) {
                $this->refreshClassroomTerm($classroom, $term);
            }
        }

        $annualTerm = $this->termRepository->findOneBy([
            'term' => ConstantsClass::ANNUEL_TERM,
        ]);

        if ($annualTerm !== null) {
            $this->refreshClassroomTerm($classroom, $annualTerm);
        }
    }

    /**
     * Recalcule et enregistre en une seule transaction la moyenne et le rang
     * de chaque élève de la classe pour la période demandée.
     */
    public function refreshClassroomTerm(Classroom $classroom, Term $term): void
    {
        $schoolYear = $classroom->getSchoolYear();

        if ($schoolYear === null) {
            return;
        }

        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        if ($school === null) {
            return;
        }

        $studentMarks = $this->buildPeriodMarks($classroom, $term);
        $allRankedStudents = $this->reportService->getRankedStudents(
            $studentMarks,
            $classroom,
            $term,
            $school
        );

        foreach ($allRankedStudents['rankedTerm'] ?? [] as $rankedStudent) {
            $student = $rankedStudent['student'] ?? null;

            if ($student === null) {
                continue;
            }

            $report = $this->reportRepository->findOneBy([
                'student' => $student,
                'term' => $term,
            ]) ?? (new Report())
                ->setStudent($student)
                ->setTerm($term);

            $report
                ->setMoyenne((float) $rankedStudent['moyenne'])
                ->setRang((int) $rankedStudent['rang']);

            $this->em->persist($report);
        }

        $this->em->flush();
    }

    private function buildPeriodMarks(Classroom $classroom, Term $term): array
    {
        if ($term->getTerm() !== ConstantsClass::ANNUEL_TERM) {
            $sequences = $this->sequenceRepository->findBy(
                ['term' => $term],
                ['sequence' => 'ASC']
            );

            if (count($sequences) < 2) {
                return [];
            }

            return $this->generalService->getStudentMarkTerm(
                $this->evaluationRepository->findEvaluationForReport($sequences[0], $classroom),
                $this->evaluationRepository->findEvaluationForReport($sequences[1], $classroom)
            );
        }

        $sequences = [];

        for ($number = 1; $number <= 6; $number++) {
            $sequence = $this->sequenceRepository->findOneBySequence($number);

            if ($sequence === null) {
                return [];
            }

            $sequences[$number] = $sequence;
        }

        $term1 = $this->generalService->getStudentMarkTerm(
            $this->evaluationRepository->findEvaluationForReport($sequences[1], $classroom),
            $this->evaluationRepository->findEvaluationForReport($sequences[2], $classroom)
        );
        $term2 = $this->generalService->getStudentMarkTerm(
            $this->evaluationRepository->findEvaluationForReport($sequences[3], $classroom),
            $this->evaluationRepository->findEvaluationForReport($sequences[4], $classroom)
        );
        $term3 = $this->generalService->getStudentMarkTerm(
            $this->evaluationRepository->findEvaluationForReport($sequences[5], $classroom),
            $this->evaluationRepository->findEvaluationForReport($sequences[6], $classroom)
        );

        return $this->generalService->getAnnualMarks($term1, $term2, $term3);
    }
}

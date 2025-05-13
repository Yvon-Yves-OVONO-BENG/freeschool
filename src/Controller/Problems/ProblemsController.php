<?php

namespace App\Controller\Problems;

use App\Entity\ConstantsClass;
use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SequenceRepository;
use App\Repository\VerrouReportRepository;
use App\Repository\VerrouSequenceRepository;
use App\Service\ClassroomService;
use App\Service\SequenceService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/problems")]
class ProblemsController extends AbstractController
{
    public function __construct( 
        protected TermRepository $termRepository,
        protected SequenceService $sequenceService,
        protected ClassroomService $classroomService,
        protected SchoolRepository $schoolRepository, 
        protected SequenceRepository $sequenceRepository,
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository,
        protected SchoolYearRepository $schoolYearRepository,
        protected VerrouReportRepository $verrouReportRepository,
        protected VerrouSequenceRepository $verrouSequenceRepository,
        )
    {}

    #[Route("/display-problems", name:"display_problems")]
    public function displayProblems(Request $request): Response
    {
        $mySession = $request->getSession();
        
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        
        $display = false;

        $numberOfEvaluationsPerStudent = [];

        $term = [];
        $period = [];
        $classroom = [];
        $sequence = [];
        $firstPeriodLetter = "";
        // Tableau pour stocker les données par élève
        $tableauResultats = [];
        
        if ($request->request->has('period') && $request->request->has('classroom')) 
        {
            $period = $request->request->get('period');

            $firstPeriodLetter = substr($period, 0, 1);
            
            $idP = substr($period, 1);
            
            $display = true;
            $classroom = $this->classroomRepository->find($request->request->get('classroom'));

            if ($firstPeriodLetter == 's') 
            {
                $sequence = $this->sequenceRepository->find($idP);

                $numberOfEvaluationsPerStudent = $this->evaluationRepository->getEvaluationsBySequenceAndClasse($mySession->get('schoolYear'),$sequence, $classroom->getId());
                
            } 
            elseif($firstPeriodLetter == 't')
            {
                $term = $this->termRepository->find($idP);
                
                $numberOfEvaluationsPerStudent = $this->evaluationRepository->getEvaluationsByTrimestreAndClasse($mySession->get('schoolYear'), $term->getId(), $classroom->getId());
            }
            
            foreach ($numberOfEvaluationsPerStudent as $evaluation) 
            {
                $eleveId = $evaluation['fullName'];
                $sequenceId = $evaluation['sequenceId'];
                $nbEvaluations = $evaluation['nbEvaluations'];
                $slugStudent = $evaluation['slugStudent'];
                $classeId = $evaluation['classeId'];
                $slugClassroom = $evaluation['slugClassroom'];

                // Si l'élève n'est pas encore dans le tableau, on l'ajoute
                if (!isset($tableauResultats[$eleveId])) {
                    $tableauResultats[$eleveId] = [
                        'nomEleve' => $evaluation['fullName'], // Méthode pour récupérer le nom de l'élève
                        'sequences' => [],
                        'slugStudent' => $slugStudent,
                        'classeId' => $classeId,
                        'slugClassroom' => $slugClassroom,
                    ];
                }

                // Ajout des évaluations par séquence
                $tableauResultats[$eleveId]['sequences'][$sequenceId] = $nbEvaluations;
            }

        }

        $classroomss = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
        
        $classrooms = $this->classroomService->splitClassrooms($classroomss);

        // on recupère les trimestres
        $term1 = $this->termRepository->findOneByTerm(1);
        $term2 = $this->termRepository->findOneByTerm(2);
        $term3 = $this->termRepository->findOneByTerm(3);
        $term0 = $this->termRepository->findOneByTerm(0);

        // on recupère les verrouReport pour utiliser leurs états dans la requete des terms
        $term1IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term1
        ])->isVerrouReport();

        $term2IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term2
        ])->isVerrouReport();

        $term3IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term3
        ])->isVerrouReport();

        $term0IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term0
        ])->isVerrouReport();

        // on recupère les six sequences pour recupérer les verrouReport liés aux trimestres
        $sequence1 = $this->sequenceRepository->findOneBySequence(1);
        $sequence2 = $this->sequenceRepository->findOneBySequence(2);
        $sequence3 = $this->sequenceRepository->findOneBySequence(3);
        $sequence4 = $this->sequenceRepository->findOneBySequence(4);
        $sequence5 = $this->sequenceRepository->findOneBySequence(5);
        $sequence6 = $this->sequenceRepository->findOneBySequence(6);

        $sequence1IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence1
        ])->isVerrouSequence();

        $sequence2IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence2
        ])->isVerrouSequence();

        $sequence3IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence3
        ])->isVerrouSequence();

        $sequence4IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence4
        ])->isVerrouSequence();

        $sequence5IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence5
        ])->isVerrouSequence();

        $sequence6IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence6
        ])->isVerrouSequence();

        // on recupère les séquences à afficher
        $sequences = $this->sequenceRepository->findForMark($term1IsLocked, $term2IsLocked, $term3IsLocked, $sequence1IsLocked, $sequence2IsLocked, $sequence3IsLocked, $sequence4IsLocked, $sequence5IsLocked, $sequence6IsLocked);

        $terms = $this->termRepository->findTermForReport($term1IsLocked, $term2IsLocked, $term3IsLocked, $term0IsLocked);
        
        return $this->render('problems/problems.html.twig', [
            'term' => $term,
            'terms' => $terms,
            'school' => $school,
            'display' => $display,
            'period' => $period,
            'sequence' => $sequence,
            'sequences' => $sequences,
            'classroom' => $classroom,
            'classrooms' => $classrooms,
            'firstPeriodLetter' => $firstPeriodLetter,
            'tableauResultats' => $tableauResultats,
            'selectedClassroom' => $classroomss[0],
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
        ]);
    }

}

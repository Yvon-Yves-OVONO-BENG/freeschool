<?php

namespace App\Controller\Statistic;

use App\Service\ReportService;
use App\Service\GeneralService;
use App\Service\StatisticService;
use App\Repository\TermRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class PrintFicheSyntheseDePerformanceDesElevesParClasseController extends AbstractController
{
    public function __construct(
        protected ReportService $reportService, 
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected SchoolRepository $schoolRepository,
        protected StatisticService $statisticService,  
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
         )
    {}

    #[Route("/printFicheSyntheseDePerformanceDesElevesParClasse", name:"statistic_printFicheSyntheseDePerformanceDesElevesParClasse")]
    public function printFicheSyntheseDePerformanceDesElevesParClasse(Request $request): Response
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

        $period = $request->request->get('period');
	    $firstPeriodLetter = substr($period, 0, 1);
        $idP = substr($period, 1);

        // on recupère toutes les classes
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        $rankedStudentsPerClass = [];

        foreach($classrooms as $selectedClassroom)
        {
        	$idC = $selectedClassroom->getId();
	        $numberOfStudents = count($selectedClassroom->getStudents());
            
	        if($numberOfStudents)
	        {
                if($firstPeriodLetter === 't') 
                {
                    // si la période c'est le trimestre

                    // on recupère le trimestre en question
                    $selectedTerm = $this->termRepository->find($idP);

                        // les sequences du trimestre
                    $sequences = $this->sequenceRepository->findBy(['term' => $selectedTerm ], ['sequence' => 'ASC']);
                    $sequence1 = $sequences[0];
                    $sequence2 = $sequences[1];

                    // les notes des élèves sequence 1 & 2 dans toutes les matières
                    $studentMarkSequence1 = $this->evaluationRepository->findEvaluationForReport($sequence1, $selectedClassroom);
                    $studentMarkSequence2 = $this->evaluationRepository->findEvaluationForReport($sequence2, $selectedClassroom);
                    
                    // Notes trimestrielles des élèves
                    $studentMarkTerm = $this->generalService->getStudentMarkTerm($studentMarkSequence1, $studentMarkSequence2);

                }elseif($firstPeriodLetter === 's')  
                {
                    // si la période c'est une évaluation

                    // on recupère la séquence concernée
                    $selectedSequence = $this->sequenceRepository->find($idP);
                    
                    $selectedTerm = $selectedSequence->getTerm();

                    // Notes de l'evaluation!
                    $studentMarkTerm = $this->evaluationRepository->findEvaluationForReport($selectedSequence, $selectedClassroom);
                }else
                {
                    // trimestre choisi
                    $selectedTerm = $this->termRepository->find($idP);

                    // On recupère les séquences de l'année
                    $sequence1 = $this->sequenceRepository->findOneBySequence(1);
                    $sequence2 = $this->sequenceRepository->findOneBySequence(2);
                    $sequence3 = $this->sequenceRepository->findOneBySequence(3);
                    $sequence4 = $this->sequenceRepository->findOneBySequence(4);
                    $sequence5 = $this->sequenceRepository->findOneBySequence(5);
                    $sequence6 = $this->sequenceRepository->findOneBySequence(6);

                    // Notes des 6 évaluations de l'année
                    $studentMarkSequence1 = $this->evaluationRepository->findEvaluationForReport($sequence1, $selectedClassroom);
                    $studentMarkSequence2 = $this->evaluationRepository->findEvaluationForReport($sequence2, $selectedClassroom);
                    $studentMarkSequence3 = $this->evaluationRepository->findEvaluationForReport($sequence3, $selectedClassroom);
                    $studentMarkSequence4 = $this->evaluationRepository->findEvaluationForReport($sequence4, $selectedClassroom);
                    $studentMarkSequence5 = $this->evaluationRepository->findEvaluationForReport($sequence5, $selectedClassroom);
                    $studentMarkSequence6 = $this->evaluationRepository->findEvaluationForReport($sequence6, $selectedClassroom);

                    // Notes trimestrielles des élèves
                    $studentMarkTerm1 = $this->generalService->getStudentMarkTerm($studentMarkSequence1, $studentMarkSequence2);
                    $studentMarkTerm2 = $this->generalService->getStudentMarkTerm($studentMarkSequence3, $studentMarkSequence4);
                    $studentMarkTerm3 = $this->generalService->getStudentMarkTerm($studentMarkSequence5, $studentMarkSequence6);

                    // Notes annuelles des élèves
                    $studentMarkTerm = $this->generalService->getAnnualMarks( $studentMarkTerm1, $studentMarkTerm2, $studentMarkTerm3);
                }

                // dump($studentMarkTerm);
                 // Moyennes trimestrielles des élèves classés et moyennes par groupe et classement par order de mérite trimestriel
                 $allRankedStudents = $this->reportService->getRankedStudents($studentMarkTerm, $selectedClassroom, $selectedTerm);

                 // On reupère le classement trimetriel
                 $rankedStudents = $allRankedStudents['rankedTerm'];

	            $rankedStudentsPerClass[] =  $rankedStudents;	
	        }

        }
        
        $rateOfSuccessPerClass = $this->statisticService->getRateOfSuccessPerClass($rankedStudentsPerClass);
        
        $pdf = $this->statisticService->printFicheSyntheseDePerformanceParClasse($rateOfSuccessPerClass, $schoolYear, $firstPeriodLetter, $idP, $school, $subSystem);

        switch ($firstPeriodLetter) 
        {
            case 's':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Summary of student performance by class ".$idP ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("Fiche Synthèse de performance des élèves par classe ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;

            case 't':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Summary of student performance by classof term ".$idP ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("Fiche Synthèse de performance des élèves par classe du trimestre ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
            
            default:
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Annual Fiche Synthèse de performance des élèves par classe" ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("Fiche Synthèse de performance des élèves par classe annuelle"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
        }
        
    }
}
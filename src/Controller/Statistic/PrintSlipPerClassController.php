<?php

namespace App\Controller\Statistic;

use App\Entity\ConstantsClass;
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
class PrintSlipPerClassController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected GeneralService $generalService, 
        protected StatisticService $statisticService, 
        protected SchoolRepository $schoolRepository,
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

   #[Route("/printSlipPerClass", name:"statistic_printSlipPerClass")]
    public function printSlipPerClass(Request $request): Response
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

        $rankPerLessons = [];
        $classrooms = [];
        
		$period = $request->request->get('period');
	    $firstPeriodLetter = substr($period, 0, 1);
        $idP = substr($period, 1);

        if($request->request->get('classroom') == 0)
        {
            // Si l'option Toutes les classes est choisie, on recupere toutes les classes
            if($this->isGranted(ConstantsClass::ROLE_CENSOR))
            {
                /**
                 * @var User
                 */
                $user = $this->getUser();
                $classrooms = $this->classroomRepository->findCensorClassrooms($user->getTeacher(), $schoolYear, $subSystem);
            }else 
            {
                $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
                
            }
        }else 
        {
            // Sinon on recupère la classe choisie
            $classrooms[] = $this->classroomRepository->find($request->request->get('classroom'));
        }

        foreach ($classrooms as $selectedClassroom) 
        {
            $numberOfStudents = count($selectedClassroom->getStudents());

           if($numberOfStudents)
           {
                if($firstPeriodLetter === 't') 
                {
                    // Si c'est le trimestre qui est choisi

                    // on recupère le trimestre sélectionné
                    $selectedTerm = $this->termRepository->find($idP);

                    // les sequences du trimestre
                    $sequences = $this->sequenceRepository->findBy([
                        'term' => $selectedTerm 
                    ], [
                        'sequence' => 'ASC'
                        ]);
                    $sequence1 = $sequences[0];
                    $sequence2 = $sequences[1];

                    // les notes des élèves sequence 1 & 2 dans toutes les matières
                    $studentMarkSequence1 = $this->evaluationRepository->findEvaluationForReport($sequence1, $selectedClassroom);
                    $studentMarkSequence2 = $this->evaluationRepository->findEvaluationForReport($sequence2, $selectedClassroom);
            
                    // Notes trimestrielles des élèves
                    $studentMarkTerm = $this->generalService->getStudentMarkTerm($studentMarkSequence1, $studentMarkSequence2);

                }elseif($firstPeriodLetter === 's')
                {
                    // Si c'est la séquence qui est choisie
                    
                    // Notes de l'évaluation
                    $studentMarkTerm = $this->evaluationRepository->findEvaluationForReport($this->sequenceRepository->find($idP), $selectedClassroom);
                   
                }else
                {
                    // Si c'est l'annuel qui est choisi

                    // On recupère les 6 séquences de l'année  
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

                //  Notes des élèves par Lesson classées par order de mérite
                $rankPerLessons[] = $this->statisticService->getRankPerLesson($studentMarkTerm, $selectedClassroom);
           }
        }

        // On construit la fiche des taux de réussite par classe
        $statisticSlipPerClass = $this->statisticService->getStatisticSlipPerClass($rankPerLessons);

        $pdf = $this->statisticService->printStatisticSlipPerClass($statisticSlipPerClass, $firstPeriodLetter, $idP, $school, $schoolYear);

        switch ($firstPeriodLetter) 
        {
            case 's':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Slip per class of evaluation ".$idP ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("Statistiques par classe de l'évaluation ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;

            case 't':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Slip per class of term ".$idP ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("Statistiques par classe du trimestre ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
            
            default:
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Annual slip per class "), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("Statistiques annuel par classe"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
        }
        
    }

}

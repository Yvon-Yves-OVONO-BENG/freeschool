<?php

namespace App\Controller\Statistic;

use App\Service\GeneralService;
use App\Service\StatisticService;
use App\Repository\TermRepository;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\SubjectRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class PrintFicheDeCollecteDesTauxDeCouvertureDesProgrammesController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected GeneralService $generalService, 
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository,
        protected StatisticService $statisticService, 
        protected SubjectRepository $subjectRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    #[Route("/printFicheDeCollecteDesTauxDeCouvertureDesProgrammes", name:"print_printFicheDeCollecteDesTauxDeCouvertureDesProgrammes")]
    public function printFicheDeCollecteDesTauxDeCouvertureDesProgrammes(Request $request): Response
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

        // $rankPerLessons = [];
        $subjects = [];
        
		$period = $request->request->get('period');
        
	    $firstPeriodLetter = substr($period, 0, 1);
        $idP = substr($period, 1);

        if($request->request->get('subject') == 0)
        {
            // Si l'option Toutes les matières est choisie, on recupere toutes les matières
            $subjects = $this->subjectRepository->findBy([], ['subject' => 'ASC']);
        }else 
        {
            // Sinon on recupère la matière choisie
            $subjects[] = $this->subjectRepository->find($request->request->get('subject'));
        }

        $studentMarkTermsSubjects = [];
        
        foreach($subjects as $subject)
        {
            if($firstPeriodLetter === 't') // si la période c'est le trimestre
            {
                // on recupère le trimestre en question
                $selectedTerm = $this->termRepository->find($idP);

                // les sequences du trimestre
                $sequences = $this->sequenceRepository->findBy([
                    'term' => $this->termRepository->find($idP) 
                ], [
                    'sequence' => 'ASC'
                    ]);
                $sequence1 = $sequences[0];
                $sequence2 = $sequences[1];

                    // on recupère les classes
                $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            
                $studentMarkTerms = [];

                foreach($classrooms as $classroom)
                {
                    if(count($classroom->getStudents()))
                    {
                        // Notes en la matière 1ere et 2eme evaluation du trimestre
                        $studentMarkSequence1 = $this->evaluationRepository->findSubjectEvaluation($sequence1, $classroom, $subject);
                        $studentMarkSequence2 = $this->evaluationRepository->findSubjectEvaluation($sequence2, $classroom, $subject);

                        // Notes trimestrielles des élèves
                        $studentMarkTerm = $this->generalService->getStudentMarkTerm($studentMarkSequence1, $studentMarkSequence2);
                    
                        if(!empty($studentMarkTerm))
                            $studentMarkTerms[] = $studentMarkTerm;
                    }
                }

            }elseif($firstPeriodLetter === 's')  // si la période c'est une évaluation
            {
                // on recupère la séquence concernée
                $selectedSequence = $this->sequenceRepository->find($idP);

                // on recupère les classes
                $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            
                $studentMarkTerms = [];

                foreach($classrooms as $classroom)
                {

                    if(count($classroom->getStudents()))
                    {
                        // Notes en la matière de la sequence choisie
                        $studentMarkTerm = $this->evaluationRepository->findSubjectEvaluation($selectedSequence, $classroom, $subject);
                    
                        if(!empty($studentMarkTerm))
                        $studentMarkTerms[] = $studentMarkTerm;
                    }
                }

            }else  // La période choisie est annuelle
            {
                // On recupère les séquences de l'année
                $sequence1 = $this->sequenceRepository->findOneBySequence(1);
                $sequence2 = $this->sequenceRepository->findOneBySequence(2);
                $sequence3 = $this->sequenceRepository->findOneBySequence(3);
                $sequence4 = $this->sequenceRepository->findOneBySequence(4);
                $sequence5 = $this->sequenceRepository->findOneBySequence(5);
                $sequence6 = $this->sequenceRepository->findOneBySequence(6);

                // on recupère les classes
                $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
                
                $studentMarkTerms = [];

                foreach($classrooms as $classroom)
                {
                    if(count($classroom->getStudents()))
                    {
                        // Notes des 6 évaluations de l'année
                        $studentMarkSequence1 = $this->evaluationRepository->findSubjectEvaluation($sequence1, $classroom, $subject);
                        $studentMarkSequence2 = $this->evaluationRepository->findSubjectEvaluation($sequence2, $classroom, $subject);
                        $studentMarkSequence3 = $this->evaluationRepository->findSubjectEvaluation($sequence3, $classroom, $subject);
                        $studentMarkSequence4 = $this->evaluationRepository->findSubjectEvaluation($sequence4, $classroom, $subject);
                        $studentMarkSequence5 = $this->evaluationRepository->findSubjectEvaluation($sequence5, $classroom, $subject);
                        $studentMarkSequence6 = $this->evaluationRepository->findSubjectEvaluation($sequence6, $classroom, $subject);

                        // Notes des trois trimestres des élèves
                        $studentMarkTerm1 = $this->generalService->getStudentMarkTerm($studentMarkSequence1, $studentMarkSequence2);
                        $studentMarkTerm2 = $this->generalService->getStudentMarkTerm($studentMarkSequence3, $studentMarkSequence4);
                        $studentMarkTerm3 = $this->generalService->getStudentMarkTerm($studentMarkSequence5, $studentMarkSequence6);
                        
                        // Notes annuelles des élèves
                        $studentMarkTerm = $this->generalService->getAnnualMarks( $studentMarkTerm1, $studentMarkTerm2, $studentMarkTerm3);
                        
                        if(!empty($studentMarkTerm))
                        $studentMarkTerms[] = $studentMarkTerm;
                    }
                    
                }
                
            }

            if(!empty($studentMarkTerms))
                $studentMarkTermsSubjects[] = $studentMarkTerms;
        }

        ///on récupère les enseignant des classes
        $lessons = $this->lessonRepository->findAllLessonsOfSchoolYear($schoolYear, $subSystem);
      
        // On construit la fiche des taux de réussite par classe 
        $classroomStatisticSlipPerSubjects = $this->statisticService->getStatisticSlipPerSubject($studentMarkTermsSubjects);
        
        $pdf = $this->statisticService->ficheDeCollecteDesTauxDeCouvertureDesProgrammesEtHeuresEnseignements($classroomStatisticSlipPerSubjects, $firstPeriodLetter, $idP, $school, $schoolYear, $lessons, $subSystem, $this->termRepository->find($idP)->getTerm());

        switch ($firstPeriodLetter) 
        {
            case 's':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Data collection form for program coverage rates and teaching hours ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } 
                else 
                {
                    return new Response($pdf->Output(utf8_decode("FICHE DE COLLECTE DES TAUX DE COUVERTURE DES PROGRAMMES ET DES HEURES D’ENSEIGNEMENTS ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;

            case 't':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Data collection form for program coverage rates and teaching hours of term ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } 
                else 
                {
                    return new Response($pdf->Output(utf8_decode("FICHE DE COLLECTE DES TAUX DE COUVERTURE DES PROGRAMMES ET DES HEURES D’ENSEIGNEMENTS DU TRIMESTRE ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
            
            default:
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("Annual data collection form for program coverage rates and teaching hours"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } 
                else 
                {
                    return new Response($pdf->Output(utf8_decode("FICHE DE COLLECTE DES TAUX DE COUVERTURE DES PROGRAMMES ET DES HEURES D’ENSEIGNEMENTS ANNUELLE"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
        }
    }

}

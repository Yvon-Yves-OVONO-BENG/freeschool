<?php

namespace App\Controller\Statistic;

use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\SubjectRepository;
use App\Repository\TermRepository;
use App\Service\GeneralService;
use App\Service\StatisticService;
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
class PrintFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesParMatiereController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected GeneralService $generalService, 
        protected StatisticService $statisticService, 
        protected SchoolRepository $schoolRepository,
        protected SubjectRepository $subjectRepository, 
        protected LessonRepository $lessonRepository,
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    #[Route("/printFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesParMatiere/{resume<[0-1]{1}>}", name:"statistic_printFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesParMatiere")]
    public function printFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesParMatiere(Request $request, int $resume = 0): Response
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
        
        
        // dd($school);
        // $rankPerLessons = [];
        $subjects = [];
        
		$period = $request->request->get('period');
	    $firstPeriodLetter = substr($period, 0, 1);
        $idP = substr($period, 1);

        $term = (int)substr($period, 1);
        
        if($request->request->get('subject') == 0)
        {
            // Si l'option Toutes les matières est choisie, on recupere toutes les matières
            $subjects = $this->subjectRepository->findBy([
                'schoolYear' => $schoolYear
            ], ['subject' => 'ASC']);
        }
        else 
        {
            // Sinon on recupère la matière choisie
            $subjects[] = $this->subjectRepository->find($request->request->get('subject'));
        }

        $studentMarkTermsSubjects = [];
        $lessonData = [];
        
        foreach($subjects as $subject)
        {
            if($firstPeriodLetter === 't') // si la période c'est le trimestre
            {
                // les sequences du trimestre
                $sequences = $this->sequenceRepository->findBy([
                    'term' => $this->termRepository->find($idP) 
                ], 
                [
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

            ########################

            ##je récupère les lessons de chaque matière
            $lessons = $this->lessonRepository->findBy(['subject' => $subject ]);

            $lessonsByTrimester = [
                'trimestre1' => [
                    'nbreLessonTheoriquePrevue' => 0,
                    'nbreLessonPratiquePrevue' => 0,
                    'nbreLessonTheoriqueFaite' => 0,
                    'nbreLessonPratiqueFaite' => 0,
                    'nbreLessonTheoriqueFaiteAvecRessource' => 0,
                    'nbreLessonPratiqueFaiteAvecRessource' => 0,
                    'nbreHeureParSemaine' => 0,
                ],
                'trimestre2' => [
                    'nbreLessonTheoriquePrevue' => 0,
                    'nbreLessonPratiquePrevue' => 0,
                    'nbreLessonTheoriqueFaite' => 0,
                    'nbreLessonPratiqueFaite' => 0,
                    'nbreLessonTheoriqueFaiteAvecRessource' => 0,
                    'nbreLessonPratiqueFaiteAvecRessource' => 0,
                    'nbreHeureParSemaine' => 0,
                ],
                'trimestre3' => [
                    'nbreLessonTheoriquePrevue' => 0,
                    'nbreLessonPratiquePrevue' => 0,
                    'nbreLessonTheoriqueFaite' => 0,
                    'nbreLessonPratiqueFaite' => 0,
                    'nbreLessonTheoriqueFaiteAvecRessource' => 0,
                    'nbreLessonPratiqueFaiteAvecRessource' => 0,
                    'nbreHeureParSemaine' => 0,
                ],
                'annuel' => [
                    'nbreLessonTheoriquePrevue' => 0,
                    'nbreLessonPratiquePrevue' => 0,
                    'nbreLessonTheoriqueFaite' => 0,
                    'nbreLessonPratiqueFaite' => 0,
                    'nbreLessonTheoriqueFaiteAvecRessource' => 0,
                    'nbreLessonPratiqueFaiteAvecRessource' => 0,
                    'nbreHeureParSemaine' => 0,
                ],
            ];

            ######POUR CHAQUE LESSON JE CALCUL LES LE NOMBRE DE LESSON PAR TRIMESTRE
            foreach ($lessons as $lesson) 
            {
                # Trimestre 1 (Seq 1 + Seq2)
                $lessonsByTrimester['trimestre1']['nbreLessonTheoriquePrevue'] += $lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2();      
                $lessonsByTrimester['trimestre1']['nbreLessonPratiquePrevue'] += $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2();      
                $lessonsByTrimester['trimestre1']['nbreLessonTheoriqueFaite'] += $lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2();      
                $lessonsByTrimester['trimestre1']['nbreLessonPratiqueFaite'] += $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2();      
                $lessonsByTrimester['trimestre1']['nbreHeureParSemaine'] += $lesson->getWeekHours();
                
                # Trimestre 2 (Seq 3 + Seq4)
                $lessonsByTrimester['trimestre2']['nbreLessonTheoriquePrevue'] += $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4();      
                $lessonsByTrimester['trimestre2']['nbreLessonPratiquePrevue'] += $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4();      
                $lessonsByTrimester['trimestre2']['nbreLessonTheoriqueFaite'] += $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4();      
                $lessonsByTrimester['trimestre2']['nbreLessonPratiqueFaite'] += $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4();      
                $lessonsByTrimester['trimestre2']['nbreHeureParSemaine'] += $lesson->getWeekHours();
                
                # Trimestre 3 (Seq 5 + Seq6)
                $lessonsByTrimester['trimestre3']['nbreLessonTheoriquePrevue'] += $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6();      
                $lessonsByTrimester['trimestre3']['nbreLessonPratiquePrevue'] += $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6();      
                $lessonsByTrimester['trimestre3']['nbreLessonTheoriqueFaite'] += $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6();      
                $lessonsByTrimester['trimestre3']['nbreLessonPratiqueFaite'] += $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6();      
                $lessonsByTrimester['trimestre3']['nbreHeureParSemaine'] += $lesson->getWeekHours();
                
                # Annuel (Seq 1 + Seq2 + Seq 3 + Seq4 + Seq 5 + Seq6)
                $lessonsByTrimester['annuel']['nbreLessonTheoriquePrevue'] += $lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6();      
                $lessonsByTrimester['annuel']['nbreLessonPratiquePrevue'] += $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6();      
                
                $lessonsByTrimester['annuel']['nbreLessonTheoriqueFaite'] += $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteSeq2() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteSeq3() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteSeq4() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteSeq5() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteSeq6() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                                                                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6(); 

                $lessonsByTrimester['annuel']['nbreLessonPratiqueFaite'] += $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteSeq2() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteSeq3() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteSeq4() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteSeq5() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteSeq6() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                                                                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6();      
                $lessonsByTrimester['annuel']['nbreHeureParSemaine'] += $lesson->getWeekHours();
                
            }

            $lessonData[] = [
                'matiere' => $subject,
                'lessonsByTrimester' => $lessonsByTrimester,
            ];
            
            
        }

        // On construit la fiche des taux de réussite par discipline 
        $classroomStatisticSlipPerSubjects = $this->statisticService->getStatisticSlipPerSubject($studentMarkTermsSubjects);
        
        $pdf = $this->statisticService->ficheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($classroomStatisticSlipPerSubjects, $firstPeriodLetter, $idP, $school, $schoolYear, $lessonData, $term, $resume);

        switch ($firstPeriodLetter) 
        {
            case 's':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("TEACHING HOURS AND PROGRAMME COVERAGE SYNTHESIS FORM PER SUBJECT OF EVALUATION ".$idP ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("FICHE  SYNTHESE DE LA COUVERTURE DES HEURES ET PROGRAMMES D’ENSEIGNEMENT PAR MATIERE DE LA SEQUENCE ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;

            case 't':
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("TEACHING HOURS AND PROGRAMME COVERAGE SYNTHESIS FORM PER SUBJECT OF TERM".$idP ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("FICHE SYNTHESE DE LA COUVERTURE DES HEURES ET PROGRAMMES D’ENSEIGNEMENT PAR MATIERE DU TRIMESTRE ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
            
            default:
                if ($subSystem->getId() == 1 ) 
                {
                    return new Response($pdf->Output(utf8_decode("TEACHING HOURS AND PROGRAMME COVERAGE SYNTHESIS FORM PER SUBJECT "), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                } else {
                    return new Response($pdf->Output(utf8_decode("FICHE SYNTHESE DE LA COUVERTURE DES HEURES ET PROGRAMMES D’ENSEIGNEMENT PAR MATIERE"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
                }
                break;
        }
    }

}

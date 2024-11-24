<?php

namespace App\Controller\Report;

use App\Entity\Report;
use App\Service\ReportService;
use App\Service\GeneralService;
use App\Service\TeacherService;
use App\Service\StatisticService;
use App\Repository\TermRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ReportElements\StudentReport;
use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class PrintReportController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected ReportService $reportService, 
        protected TermRepository $termRepository, 
        protected GeneralService $generalService, 
        protected TeacherService $teacherService, 
        protected SchoolRepository $schoolRepository, 
        protected StatisticService $statisticService, 
        protected ReportRepository $reportRepository, 
        protected StudentRepository $studentRepository,
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
        {}
        
    #[Route("/printReport/{council<[0-1]{1}>}/{slug}/{slugTerm}/{slugStudent}", name:"report_printReport")]
    public function printReport(Request $request, int $council = 0, string $slug = "", string $slugTerm = "", string $slugStudent = ""): Response
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
       
        if ($slug != null && $slugTerm != null) 
        {
            $idC = $this->classroomRepository->findOneBySlug(['slug' => $slug ])->getId();
            $idT = $this->termRepository->findOneBySlug(['slug' => $slugTerm ])->getId();
        }
        
        // dd($idT);
        $unrecordedEvaluations = $this->teacherService->getUnrecordedMark($idT, 0, $idC);
        
        if(!empty($unrecordedEvaluations))
        {
            $pdf = $this->reportService->printUnrecordedMark($unrecordedEvaluations);

            if ($subSystem->getId() == 1 ) 
            {
              return new Response($pdf->Output("Unrecorded evaluation"), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Evaluations non saisies")), 200, ['Content-Type' => 'application/pdf']) ;
            }
            
        }

        ///
        $school = $this->schoolRepository->findBy(['schoolYear' => $schoolYear])[0];

        // trimestre sélectionné
        $selectedTerm = $this->termRepository->find($idT);

        // classe sélectionnée
        $selectedClassroom = $this->classroomRepository->find($idC);
        //Effectif de la classe
        $numberOfStudents = $this->generalService->getNumberOfStudents($selectedClassroom);
        // Effectif garçons
        $numberOfBoys = $this->generalService->getNumberOfBoys($selectedClassroom);
        // Effectif filles
        $numberOfGirls = $this->generalService->getNumberOfGirls($selectedClassroom);
        // nombre de lessons
        $numberOfLessons = $this->generalService->getNumberOfLessons($selectedClassroom);
        
        if($selectedTerm->getTerm() != 0)  // Bulletins trimestriels
        {
            // les sequences du trimestre
            $sequences = $this->sequenceRepository->findBy(['term' => $selectedTerm ], ['sequence' => 'ASC']);
            $sequence1 = $sequences[0];
            $sequence2 = $sequences[1];
            
            // les notes des élèves sequence 1 & 2 dans toutes les matières
            $studentMarkSequence1 = $this->evaluationRepository->findEvaluationForReport($sequence1, $selectedClassroom);
            $studentMarkSequence2 = $this->evaluationRepository->findEvaluationForReport($sequence2, $selectedClassroom);
            
            // Notes trimestrielles des élèves
            $studentMarkTerm = $this->generalService->getStudentMarkTerm($studentMarkSequence1, $studentMarkSequence2);
            
        }else // Bulletins annuels
        {
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
        
        // Moyennes trimestrielles des élèves classés et moyennes par groupe et classement par order de mérite trimestriel
        $allRankedStudents = $this->reportService->getRankedStudents($studentMarkTerm, $selectedClassroom, $selectedTerm);

        // On reupère le classement trimetriel
        $rankedStudents = $allRankedStudents['rankedTerm'];

        // on recupère les classements par category
        $rankedStudentsCategory1 = $allRankedStudents['rankedCategory1'];
        $rankedStudentsCategory2 = $allRankedStudents['rankedCategory2'];
        $rankedStudentsCategory3 = $allRankedStudents['rankedCategory3'];
        
        //  Notes des élèves par Lesson classées par order de mérite
        $rankPerLesson = $this->reportService->getRankPerLesson($studentMarkTerm, $selectedClassroom, $selectedTerm);

        if ($council) 
        {
            //  Notes des élèves par Lesson classées par order de mérite
            $rankPerLessons[] = $this->statisticService->getRankPerLesson($studentMarkTerm, $selectedClassroom);

            // On construit la fiche des taux de réussite par classe
            $statisticSlipPerClass = $this->statisticService->getStatisticSlipPerClass($rankPerLessons);
        }

        // On construit les valeurs communes à tous les élèves
        
        // Profile de la classe
        $classroomProfile = $this->reportService->getClassroomProfile($rankedStudents);

        // On contruit les bulletins des élèves de la classe
        $allStudentReports = [];
        
        if(!empty($studentMarkTerm))
        {
            // On construit le bulletin de chaque élève
            for($index = 0; $index < $numberOfStudents; $index++)
            {
                if($slugStudent) // Si on veut imprimer un seul bulletin
                {
                    $idS = $this->studentRepository->findOneBySlug(['slug' => $slugStudent ])->getId();
                    $studentIndex = $this->reportService->getStudentPositionForOneReport($rankedStudents, $idS);
                    
                    $index = $studentIndex['index'];
                    $student = $studentIndex['student'];
        
                }else
                {
                    $student = $rankedStudents[$index]['student'];
                }
                
                // On set le header, le body et le footer du bulletin
                $studentReport = new StudentReport();
                $studentReport->setReportHeader($this->reportService->getStudentReportHeader($school, $selectedClassroom, $student, $selectedTerm, $subSystem))
                    ->setReportFooter($this->reportService->getStudentReportFooter($rankedStudents[$index], $classroomProfile, $selectedTerm));

                if($selectedTerm->getTerm() != 0)
                {
                    $studentReport->setReportBody($this->reportService->getStudentReportBody($studentMarkSequence1, $studentMarkSequence2, $studentMarkTerm, $rankedStudents,  $index, $numberOfLessons,  $numberOfStudents, $rankedStudentsCategory1, $rankedStudentsCategory2, $rankedStudentsCategory3, [], $rankPerLesson, $subSystem, $selectedTerm, $schoolYear));

                }else
                {
                    $studentReport->setReportBody($this->reportService->getStudentReportBody($studentMarkTerm1, $studentMarkTerm2, $studentMarkTerm, $rankedStudents,  $index, $numberOfLessons,  $numberOfStudents, $rankedStudentsCategory1, $rankedStudentsCategory2, $rankedStudentsCategory3, $studentMarkTerm3, $rankPerLesson, $subSystem, $selectedTerm, $schoolYear));
                }

                // On sauvegarde le Report pour rappels
                $report = $this->reportRepository->findOneBy(['student' => $student, 'term' => $selectedTerm]);
                
                if($report !== null)
                {
                    $report->setMoyenne($rankedStudents[$index]['moyenne'])
                        // ->setRang(1)
                        ->setRang($rankedStudents[$index]['rang'])
                        ;
                    
                    $this->em->persist($report);
                    $this->em->flush();
                }else
                {
                    $report = new Report();
                    $report->setStudent($student)
                        ->setTerm($selectedTerm)
                        ->setMoyenne($rankedStudents[$index]['moyenne'])
                        // ->setRang(1)
                        ->setRang($rankedStudents[$index]['rang'])
                        ;
                    
                    $this->em->persist($report);
                    $this->em->flush();
                }
    
                $allStudentReports[] = $studentReport;
            
                if($slugStudent)
                {
                    break;
                }

            }

        }
        
        if($council)
        {
            // on imprime la fiche du conseil de classe
            $pdf = $this->statisticService->printClassCouncil($allStudentReports, $statisticSlipPerClass, $numberOfLessons, $schoolYear, $selectedClassroom, $numberOfStudents, $numberOfBoys, $numberOfGirls, $selectedTerm, $school, $subSystem);
            
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output('Council class of '.$selectedClassroom->getClassroom()." - term ".$selectedTerm->getTerm(), 'I'), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output('Conseil de classe de la '.$selectedClassroom->getClassroom()." - trimestre ".$selectedTerm->getTerm(), 'I'), 200, ['Content-Type' => 'application/pdf']) ;
            
            }
            
        
        }else
        {
            // On imprime les bulletins
            $pdf = $this->reportService->printReport($school, $allStudentReports, $numberOfLessons, $selectedTerm, $schoolYear, $numberOfStudents, $numberOfBoys, $numberOfGirls, $subSystem);
            
            if ($subSystem->getId() == 1) 
            {
                if ($selectedTerm->getTerm() == 0) 
                {
                    $trimestre = "Annual";
                } 
                else 
                {
                    $trimestre = "Term : ".$selectedTerm->getTerm();
                }
            } 
            else 
            {
                if ($selectedTerm->getTerm() == 0) 
                {
                    $trimestre = "Annuel";
                } 
                else 
                {
                    $trimestre = "Trimestre - ".$selectedTerm->getTerm();
                }
            }
             
        }
        
        if($subSystem->getId() == 1)
        {
            if ($slugStudent) 
            {
                return new Response($pdf->Output($student->getFullName().' report for '.$selectedClassroom->getClassroom()." - ".$trimestre ,'I'), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output('Report of classroom of '.$selectedClassroom->getClassroom()." - ".$trimestre ,'I'), 200, ['Content-Type' => 'application/pdf']) ;
            }
            
        }
        else
        {
            if ($slugStudent) 
            {
                return new Response($pdf->Output('Bulletin de notes de '.$student->getFullName().' - '.$selectedClassroom->getClassroom()." - ".$trimestre ,'I'), 200, ['Content-Type' => 'application/pdf']) ;
            } else 
            {
                return new Response($pdf->Output('Bulletin de la classe de '.$selectedClassroom->getClassroom()." - ".$trimestre ,'I'), 200, ['Content-Type' => 'application/pdf']) ;
            }
            
        }
               
    }

}

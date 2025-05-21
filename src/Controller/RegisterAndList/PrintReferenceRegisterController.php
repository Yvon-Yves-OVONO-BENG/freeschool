<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Service\ReportService;
use App\Service\GeneralService;
use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Service\RegisterAndListService;
use App\Repository\EvaluationRepository;
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

#[Route("/register_and_list")]
class PrintReferenceRegisterController extends AbstractController
{
    public function __construct(
        protected GeneralService $generalService,  
        protected TermRepository $termRepository, 
        protected ReportService $reportService, 
        protected SchoolRepository $schoolRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected StudentRepository $studentRepository,
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected RegisterAndListService $registerAndListService, 
        )
    {}

    
    #[Route("/printReferenceRegister/{pv<[0-1]>}", name:"register_and_list_printReferenceRegister")]
    public function printReferenceRegister(Request $request, int $pv = 0): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $classrooms = [];
        $classroomReportsForRegister = [];
        
		$period = $request->request->get('period');
	    $firstPeriodLetter = substr($period, 0, 1);
        $idP = substr($period, 1);

        $idC = $request->request->get('classroom');

        if($idC != 0)
        {
            // On recupère la classe choisie
            $choosedClassroom = $this->classroomRepository->find($idC);

            $students = $this->studentRepository->findBy([
                'classroom' => $choosedClassroom,
            ]);
            
            
            if(count($students))
            {
                $classrooms[] = $choosedClassroom;
            }
  
        }else
        {
            // Si l'option Toutes les classes est choisie, on recupere toutes les classes
            if($this->isGranted(ConstantsClass::ROLE_CENSOR))
            {
                /**
                 * @var User
                 */
                $user = $this->getUser();

                $allClassrooms = $this->classroomRepository->findCensorClassrooms($user->getTeacher(), $schoolYear, $subSystem);
            }else 
            {
                $allClassrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
                
            }


            foreach ($allClassrooms as $oneClassroom) 
            {
                $studentsInOneClassroom = [];

                foreach ($oneClassroom->getStudents() as $studentInOneClassroom) 
                {
                    $studentsInOneClassroom = $studentInOneClassroom;
                }

                if(count($studentsInOneClassroom))
                {
                    $classrooms[] = $oneClassroom;
                }
            }
        }

        foreach ($classrooms as $selectedClassroom) 
        {
            $students = $this->studentRepository->findBy([
                'classroom' => $choosedClassroom,
            ]);

            $numberOfStudents = count($students);
            $numberOfLessons = count($selectedClassroom->getLessons());
        
            if($firstPeriodLetter === 't') // si la période est trimestrielle
            {
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

            }elseif($firstPeriodLetter === 's') // si la période est séquentielle
            {
                // on recupère le trimestre concerné
                $selectedTerm = $this->sequenceRepository->find($idP)->getTerm();
                        
                // Notes de l'évaluation
                $studentMarkTerm = $this->evaluationRepository->findEvaluationForReport($this->sequenceRepository->find($idP), $selectedClassroom);

            }else  // si la période est annuelle
            {
                // on recupère le trimestre concerné
                $selectedTerm = $this->termRepository->find($idP);

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
                $studentMarkTerm = $this->generalService->getAnnualMarks($studentMarkTerm1, $studentMarkTerm2, $studentMarkTerm3);
                
            }

            // Moyennes trimestrielles des élèves classés et moyennes par groupe et classement par order de mérite trimestriel
            $allRankedStudents = $this->reportService->getRankedStudents($studentMarkTerm, $selectedClassroom, $selectedTerm, $pv);

            // On reupère le classement trimetriel
            $rankedStudents = $allRankedStudents['rankedTerm'];

            // on recupère les classements par category
            $rankedStudentsCategory1 = $allRankedStudents['rankedCategory1'];
            $rankedStudentsCategory2 = $allRankedStudents['rankedCategory2'];
            $rankedStudentsCategory3 = $allRankedStudents['rankedCategory3'];
            
            //  Notes des élèves par Lesson classées par order de mérite
            // $rankPerLesson = $this->reportService->getRankPerLesson($studentMarkTerm, $selectedClassroom, $selectedTerm);

            // Profile de la classe
            $classroomProfile = $this->reportService->getClassroomProfile($rankedStudents);


            // On contruit les bulletins des élèves de la classe
            $allStudentReports = [];
            if(!empty($studentMarkTerm) && !empty($rankedStudents))
            {
                // On construit le bulletin de chaque élève
                for($index = 0; $index < $numberOfStudents; $index++)
                {
                    $student = $rankedStudents[$index]['student'];
                        
                    // On set le header, le body et le footer du bulletin
                    $studentReport = new StudentReport();
                    
                    $studentReport->setReportHeader($this->reportService->getStudentReportHeader($school, $selectedClassroom, $student, $selectedTerm, $subSystem))
                        ->setReportFooter($this->reportService->getStudentReportFooter($rankedStudents[$index], $classroomProfile, $selectedTerm));
                        
                        if($firstPeriodLetter === 't')
                        {
                            //    bulletins trimestriels
                            $studentReport->setReportBody($this->reportService->getStudentReportBody($studentMarkSequence1, 
                                                                                                    $studentMarkSequence2, 
                                                                                                    $studentMarkTerm, 
                                                                                                    $rankedStudents, 
                                                                                                    $index, 
                                                                                                    $numberOfLessons, 
                                                                                                    $numberOfStudents, 
                                                                                                    $rankedStudentsCategory1, 
                                                                                                    $rankedStudentsCategory2, 
                                                                                                    $rankedStudentsCategory3,   
                                                                                                    $subSystem, 
                                                                                                    $selectedTerm,
                                                                                                    $rankPerLesson = [],
                                                                                                    $studentMarkSequence3 = []));

                        }elseif($firstPeriodLetter === 's')
                        {
                            //    bulletins sequentiels
                            $studentReport->setReportBody($this->reportService->getStudentReportBody(
                                                                                                    $studentMarkTerm, 
                                                                                                    $studentMarkTerm, 
                                                                                                    $studentMarkTerm, 
                                                                                                    $rankedStudents,  
                                                                                                    $index, 
                                                                                                    $numberOfLessons, 
                                                                                                    $numberOfStudents, 
                                                                                                    $rankedStudentsCategory1, 
                                                                                                    $rankedStudentsCategory2, 
                                                                                                    $rankedStudentsCategory3,  
                                                                                                    $subSystem, 
                                                                                                    $selectedTerm,
                                                                                                    $rankPerLesson = [], 
                                                                                                    $studentMarkSequence3 = []));

                        }else
                        {
                            //    bulletins annuels
                            $studentReport->setReportBody($this->reportService->getStudentReportBody($studentMarkTerm1, 
                                                                                                    $studentMarkTerm2, 
                                                                                                    $studentMarkTerm, 
                                                                                                    $rankedStudents, 
                                                                                                    $index, 
                                                                                                    $numberOfLessons, 
                                                                                                    $numberOfStudents, 
                                                                                                    $rankedStudentsCategory1, 
                                                                                                    $rankedStudentsCategory2, 
                                                                                                    $rankedStudentsCategory3, 
                                                                                                    $subSystem, 
                                                                                                    $selectedTerm,
                                                                                                    $rankPerLesson = [], 
                                                                                                    $studentMarkSequence3 = [],
                                                                                                    $studentMarkTerm3,  
                                                                                                
                                                                                                ));
                        }

                        $studentReport->setNumberOfLessons($numberOfLessons);
                        $allStudentReports[] = $studentReport;

                }

            }

            if(!empty($allStudentReports))
            {
                $classroomReportsForRegister[] =  $allStudentReports;
            }
        }

        // on imprime les registre de référence
        $pdf = $this->registerAndListService->printReferenceRegister($classroomReportsForRegister, $school, $schoolYear,   $firstPeriodLetter, $idP, $numberOfLessons = 0,$pv);
        
        switch ($firstPeriodLetter) 
        {
            case 's':
                if($idC != 0)
                {
                    if ($subSystem->getId() == 1 ) 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Reference register of ".$choosedClassroom->getClassroom().utf8_decode(" - Evaluation ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);
                        } 
                        else 
                        {
                            return new Response($pdf->Output(utf8_decode("Minute printing of ".$choosedClassroom->getClassroom().utf8_decode(" - Evaluation ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        
                    } 
                    else 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Registre de référence de la ".$choosedClassroom->getClassroom().utf8_decode(" - Evaluation ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Procès verbal de la ".$choosedClassroom->getClassroom().utf8_decode(" - Evaluation ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    }
                }
                else
                {
                    if ($subSystem->getId() == 1 ) 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Reference register").utf8_decode(" - Evaluation ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Minute printing").utf8_decode(" - Evaluation ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    } 
                    else 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Registre de référence").utf8_decode(" - Evaluation ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Procès verbal").utf8_decode(" - Evaluation ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    }
                }
                break;
            
            case 't':
                if($idC != 0)
                {
                    if ($subSystem->getId() == 1 ) 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Reference register of ".$choosedClassroom->getClassroom().utf8_decode(" - Term ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);
                        } 
                        else 
                        {
                            return new Response($pdf->Output(utf8_decode("Minute printing of ".$choosedClassroom->getClassroom().utf8_decode(" - Term ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        
                    } 
                    else 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Registre de référence de la ".$choosedClassroom->getClassroom().utf8_decode(" - Trimestre ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Procès verbal de la ".$choosedClassroom->getClassroom().utf8_decode(" - Trimestre ".$idP)), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    }
                }
                else
                {
                    if ($subSystem->getId() == 1 ) 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Reference register").utf8_decode(" - Term ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Minute printing").utf8_decode(" - Term ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    } 
                    else 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Registre de référence").utf8_decode(" - Trimestre ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Procès verbal").utf8_decode(" - Trimestre ".$idP), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    }
                }
                break;

                default:
                if($idC != 0)
                {
                    if ($subSystem->getId() == 1 ) 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Reference register of ".$choosedClassroom->getClassroom().utf8_decode(" - Annual ")), "I"), 200, ['Content-Type' => 'application/pdf']);
                        } 
                        else 
                        {
                            return new Response($pdf->Output(utf8_decode("Minute printing of ".$choosedClassroom->getClassroom().utf8_decode(" - Annual ")), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        
                    } 
                    else 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Registre de référence de la ".$choosedClassroom->getClassroom().utf8_decode(" - Annuel ")), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Procès verbal de la ".$choosedClassroom->getClassroom().utf8_decode(" - Annuel ")), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    }
                }
                else
                {
                    if ($subSystem->getId() == 1 ) 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Reference register").utf8_decode(" - Annual "), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Minute printing").utf8_decode(" - Annual "), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    } 
                    else 
                    {
                        if ($pv != 1) 
                        {
                            return new Response($pdf->Output(utf8_decode("Registre de référence").utf8_decode(" - Annuel "), "I"), 200, ['Content-Type' => 'application/pdf']);
                        }
                        else
                        {
                            return new Response($pdf->Output(utf8_decode("Procès verbal").utf8_decode(" - Annuel "), "I"), 200, ['Content-Type' => 'application/pdf']);

                        }
                    }
                }
                break;
        }
        
        
    }

}

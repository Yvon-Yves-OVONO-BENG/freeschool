<?php

namespace App\Controller\RegisterAndList;

use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\TermRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PrintTopGirlsStudentsByScienceClassroomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintTopGirlsStudentsScienceClassroomController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintTopGirlsStudentsByScienceClassroomService $printTopGirlsStudentsByScienceClassroomService
        )
    {}

    #[Route("/PrintTopGirlsStudentsByScienceClassroom", name:"top_girls_students_by_science_classroom")]
    public function printTopGirlsStudentsByScienceClassroom(Request $request): Response
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

        $classroom = $this->classroomRepository->find($request->request->get('classroom'));
        $term = $this->termRepository->find(5);
        
        $students = $this->reportRepository->findTopGirlsStudentsByScienceClassroom($schoolYear, $subSystem, $classroom, $term);
        if ($request->request->has('printBestStudentsPerClass')) 
        {
            $pdf = $this->printTopGirlsStudentsByScienceClassroomService->printTopGirlsStudentsByScienceClassroomService($students, $schoolYear, $school, $subSystem, $term, $classroom);
            
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Top five students of ".$classroom->getClassroom() ), "I"), 200, ['Content-Type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Les cinqs premiers élèves de la ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']);
            }

        } else 
        {
            // dd($students);
            usort($students, function($a, $b) 
            {
                return $b->getMoyenne() <=> $a->getMoyenne();
            });

            $classroom = $this->classroomRepository->find($request->request->get('classroom'));

            return $this->render('register_and_list/AffichagestResultsOfYear.html.twig', [
                'students' => $students,
                'school' => $school,
                'level' => 0,
                'classroom' => $classroom,
            ]);
        }

    }
}

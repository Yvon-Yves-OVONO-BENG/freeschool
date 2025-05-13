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
use App\Service\PrintTopFiveStudentsByClassroomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintTopFiveStudentsByClassroomController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintTopFiveStudentsByClassroomService $printTopFiveStudentsByClassroomService
        )
    {}

    #[Route("/PrintTopFiveStudentsByClassroom", name:"top_five_students_by_classroom")]
    public function printTopFiveStudentsByClassroom(Request $request): Response
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
        $term = $this->termRepository->find($request->request->get('term'));
        
        $topFiveStudents = $this->reportRepository->findTopFiveStudentsByClassroom($schoolYear, $subSystem, $classroom, $term);
        
        $pdf = $this->printTopFiveStudentsByClassroomService->printTopFiveStudentsByClassroomService($topFiveStudents, $schoolYear, $school, $subSystem, $term, $classroom);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Top five students of ".$classroom->getClassroom() ), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les cinqs premiers élèves de la ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

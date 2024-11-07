<?php

namespace App\Controller\RegisterAndList;

use App\Repository\ClassroomRepository;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Service\RegisterAndListService;
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
class PrintMarkReportClassroomController extends AbstractController
{
    public function __construct(
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository,    
        protected TeacherRepository $teacherRepository, 
        protected RegisterAndListService $registerAndListService, 
        protected ClassroomRepository $classroomRepository,
        )
    {}

    #[Route("/printMarkReportClassroom", name:"print_mark_report_classroom")]
    public function printMarkReport(Request $request): Response
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

        if ($request->request->get('idClassroom') != 0) 
        {
            $classroom = $this->classroomRepository->find($request->request->get('idClassroom'));
            // On contruit les relevés de notes
            $markReports = $this->lessonRepository->reportClassroom($classroom);

            $pdf =  $this->registerAndListService->printMarkReportsClassroom($markReports, $schoolYear, $school);
        } 
        else 
        {
            // On contruit les relevés de notes
            $markReports = $this->lessonRepository->reportClassroom();

            $pdf =  $this->registerAndListService->printMarkReportsAllClassroom($markReports, $schoolYear, $school);
        }
        
        // $markReports = $this->registerAndListService->getMarkReports($markReports);

        // On imprime les relevés de notes
        
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Mark report of ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
           return new Response($pdf->Output(utf8_decode("Relevé de notes de la classe de ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

        
        
    }

}

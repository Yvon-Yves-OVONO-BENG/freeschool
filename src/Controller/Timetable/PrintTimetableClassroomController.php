<?php

namespace App\Controller\Timetable;

use App\Repository\ClassroomRepository;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\TimeTableRepository;
use App\Service\PrintTimetableClassroomService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/student")]
class PrintTimetableClassroomController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected TimeTableRepository $timeTableRepository, 
        protected ClassroomRepository $classroomRepository,
        protected PrintTimetableClassroomService $printTimetableClassroomService, 
        )
    {}

    #[Route("/print-timetable-classroom/{slug}", name:"print_timetable_classroom")]
    public function printTimetableClassroom(string $slug, Request $request): Response
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
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $classroom = $this->classroomRepository->findOneBySlug(['slug' => $slug]);
        
        $timeTables = $this->timeTableRepository->findBy([
                'classroom' => $classroom,
                'schoolYear' => $schoolYear,
            ]);
        
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printTimetableClassroomService->print($timeTables, $school, $schoolYear, $classroom);
        
        if($subSystem->getId() == 1)
        {
            return new Response($pdf->Output("Time table of - ".$classroom->getClassroom(),'I'), 200, ['Content-Type' => 'application/pdf']);
        }
        else
        {  
            return new Response($pdf->Output("Emploi du temps de - ".$classroom->getClassroom(),'I'), 200, ['Content-Type' => 'application/pdf']);
        }
        
    }

}

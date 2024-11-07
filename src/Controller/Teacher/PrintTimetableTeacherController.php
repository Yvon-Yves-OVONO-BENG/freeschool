<?php

namespace App\Controller\Teacher;

use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Repository\TimeTableRepository;
use App\Service\PrintTimetableTeacherService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/teacher")]
class PrintTimetableTeacherController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected TeacherRepository $teacherRepository,
        protected SchoolYearService $schoolYearService, 
        protected TimeTableRepository $timeTableRepository, 
        protected PrintTimetableTeacherService $printTimetableTeacherService, 
        )
    {}

    #[Route("/print-timetable-teacher/{slug}", name:"print_timetable_teacher")]
    public function printTimetableTeacher(string $slug, Request $request): Response
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

        $teacher = $this->teacherRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        $timeTables = $this->timeTableRepository->findBy([
                'teacher' => $teacher,
                'schoolYear' => $schoolYear,
            ]);
        
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printTimetableTeacherService->print($timeTables, $school, $schoolYear, $teacher);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Time table of ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Emploi du temps de ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
    }

}

<?php

namespace App\Controller\RegisterAndList;

use App\Entity\Classroom;
use App\Service\ClassroomService;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class MarkReportController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        protected ClassroomService $classroomService,
        protected ClassroomRepository $classroomRepository
        )
    {}

    #[Route("/markReport", name:"register_and_list_markReport")]
    public function markReport(Request $request): Response
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

        $teachers = $this->teacherRepository->findAllToDisplay($schoolYear, $subSystem);

        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        $selectedClassroom = new Classroom();

        return $this->render('register_and_list/markReport.html.twig', [
            'teachers' => $teachers,
            'classrooms' => $classrooms,
            'school' => $school,
            'selectedClassroom' => $selectedClassroom,
        ]);
    }

}

<?php

namespace App\Controller\Progress;

use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Service\ClassroomService;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
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

#[Route("/progress")]
class LessonDoneController extends AbstractController
{
    public function __construct(
        protected ClassroomRepository $classroomRepository, 
        protected ClassroomService $classroomService, 
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository, )
    { }

    #[Route("/lesson-donne", name:"lesson_done")]
    public function lessonDone(Request $request): Response
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

        
        $selectedClassroom = new Classroom();
        $classrooms = [];
        $lessons = [];

        $methodIsPost = false;

        if($idc = $request->request->get('classroom'))
        {
            $methodIsPost = true;
            $selectedClassroom = $this->classroomRepository->find($idc);
            
            $lessons = $this->lessonRepository->findTeachersPerClassroom($schoolYear, $selectedClassroom);
           
        }

        if($this->isGranted(ConstantsClass::ROLE_CENSOR))
        {
            /**
             * @var User
             */
            $user = $this->getUser();
            $classrooms = $this->classroomRepository->findCensorClassrooms($user->getTeacher(), $schoolYear, $subSystem);
        }else 
        {
            $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            
        }

        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        
        return $this->render('progress/lessonDone.html.twig', [
            'lessons' => $lessons,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'methodIsPost' => $methodIsPost,
            'school' => $school,
        ]);
    }

}

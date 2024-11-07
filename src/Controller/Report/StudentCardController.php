<?php

namespace App\Controller\Report;

use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Service\ClassroomService;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
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

#[Route("/report")]
class StudentCardController extends AbstractController
{
    public function __construct(
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/studentCard", name:"report_studentCard")]
    public function studentCard(Request $request): Response
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
        $students = [];

        $methodIsPost = false;

        if($idC = $request->request->get('classroom'))
        {
            $methodIsPost = true;
            // On recupère la classe sélectionnée
            $selectedClassroom = $this->classroomRepository->find($idC);
            
            // On recupère les élèves de la classe sélectinnée
            $students = $this->studentRepository->findBy([
                'classroom' => $selectedClassroom
            ], [
                'fullName' => 'ASC'
            ]);
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

        return $this->render('report/studentCard.html.twig', [
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'students' => $students,
            'methodIsPost' => $methodIsPost,
            'school' => $school,
        ]);
    }


}

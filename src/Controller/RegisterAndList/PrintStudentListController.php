<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
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
class PrintStudentListController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,  
        protected ClassroomRepository $classroomRepository, 
        protected RegisterAndListService $registerAndListService, 
        )
    {}

    #[Route("/printStudentList", name:"register_and_list_printStudentList")]
    public function printStudentList(Request $request): Response
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
        $idC = $request->request->get('classroom');

        if($idC != 0)
        {
            // Si on veut la liste d'une seule classe
            $selectedClassroom = $this->classroomRepository->find($idC);

            if(count($selectedClassroom->getStudents()))
            {
                $classrooms[] = $selectedClassroom;
            }

        }else
        {
            // Si toutes les classes on recupère toutes les classes
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
       
            foreach($allClassrooms as $classroom)
            {
                if(count($classroom->getStudents()))
                {
                    $classrooms[] = $classroom;
                }
            }
        }

        $studentList = $this->registerAndListService->getStudentList($classrooms, $schoolYear);

        $pdf = $this->registerAndListService->printStudentList($studentList, $school, $schoolYear, $subSystem);
        
        if($idC != 0)
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Student list of ".$selectedClassroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Liste des élèves de la ".$selectedClassroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }
        else
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Student list"), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Liste des élèves"), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }
        
    }

}

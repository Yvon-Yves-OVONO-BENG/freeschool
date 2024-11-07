<?php

namespace App\Controller\Registration;

use App\Repository\ClassroomRepository;
use App\Repository\RegistrationHistoryRepository;
use App\Repository\RegistrationRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\InsolvableService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class PrintStudentInsolvableController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected InsolvableService $insolvableService, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected RegistrationRepository $registrationRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository, 
        )
    {}

    #[Route("/print-student-insolvable/{slugClassroom}", name:"print_student_insolvable")]
    public function printStudentInsolvable(Request $request, string $slugClassroom = null): Response
    {
        $mySession = $request->getSession();
        #mes variables témoin pour afficher les sweetAlert
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
    
        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $schoolYear = $mySession->get('schoolYear');
        $subSystem = $mySession->get('subSystem');
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        // $classrooms = [];

        if($slugClassroom != null)
        {
            $classroom = $this->classroomRepository->findOneBySlug(['slug' => $slugClassroom]);
        }else 
        {
            $classrooms = $this->classroomRepository->findForSelect($schoolYear,$subSystem);
        }

        // $registrationHistories = $this->studentRepository->getSumFeesPerRubrique($classroom);
        // $registrationHistories = $this->registrationHistoryRepository->getSumFeesPerRubrique($classroom);
        $registrations = $this->registrationRepository->getSumFeesPerRubrique($classroom);

        $pdf = $this->insolvableService->printStudentInsolvable($classroom, $school, $schoolYear, $registrations);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Student insolvable of ".$classroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Elèves insolvables de la ".$classroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }

}
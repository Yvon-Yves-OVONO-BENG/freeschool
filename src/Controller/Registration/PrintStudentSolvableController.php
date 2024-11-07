<?php

namespace App\Controller\Registration;

use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\SolvableService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class PrintStudentSolvableController extends AbstractController
{
    public function __construct(
        protected SolvableService $solvableService, 
        protected SchoolRepository $schoolRepository, 
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/print-student-solvable/{slugClassroom}", name:"print_student_solvable")]
    public function printStudentSolvable(Request $request, string $slugClassroom): Response
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
            $classroom = $this->classroomRepository->findOneBySlug([
                'slug' => $slugClassroom
            ]);
        }else 
        {
            $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
        }

        $pdf = $this->solvableService->printStudentSolvable($classroom, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("All Students solvables of ".$classroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Tous les élèves solvables de la ".$classroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }

}

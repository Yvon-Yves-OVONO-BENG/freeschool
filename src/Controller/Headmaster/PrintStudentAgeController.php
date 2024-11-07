<?php

namespace App\Controller\Headmaster;

use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Service\HeadmasterReportService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/headmaster")]
class PrintStudentAgeController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected ClassroomRepository $classroomRepository,
        protected HeadmasterReportService $headmasterReportService, 
        )
    {}

    #[Route("/printStudentAge", name:"headmaster_printStudentAge")]
    public function printStudentAge(Request $request): Response
    {
        $mySession = $request->getSession();

        #mes variables témoin pour afficher les sweetAlert
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

        $students = [];

        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
        
        $studentsAges = $this->headmasterReportService->getStudentsAge($classrooms, $schoolYear);

        $pdf = $this->headmasterReportService->printStudentsAge($studentsAges, $school, $schoolYear, $subSystem);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output("Student Age", "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Tranche d'âge des élèves"), "I"), 200, ['content-type' => 'application/pdf']);
        }
        
        

    }
}

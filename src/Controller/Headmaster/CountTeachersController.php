<?php

namespace App\Controller\Headmaster;

use App\Repository\DepartmentRepository;
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
class CountTeachersController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected DepartmentRepository $departmentRepository, 
        protected HeadmasterReportService $headmasterReportService, 
        )
    {}

    #[Route("/countTeachers", name:"headmaster_countTeachers")]
    public function countTeachers(Request $request): Response
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

        $departments = $this->departmentRepository->findBy(['schoolYear' => $schoolYear], ['department' => 'ASC']);

        $pdf = $this->headmasterReportService->printCountTeachers($departments, $school, $schoolYear, $subSystem);
        
        if ($subSystem->getid() == 1 ) 
        {
            return new Response($pdf->Output("Effective teachers per department", "I"), 200, ['content-type', 'applivation/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Effectif du personnel par département"), "I"), 200, ['content-type', 'applivation/pdf']);
        }
        
    }

}

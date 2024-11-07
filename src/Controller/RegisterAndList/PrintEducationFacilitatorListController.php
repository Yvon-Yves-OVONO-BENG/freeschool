<?php

namespace App\Controller\RegisterAndList;

use App\Repository\SchoolRepository;
use App\Service\RegisterAndListService;
use App\Repository\DepartmentRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintEducationFacilitatorListController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,     
        protected DepartmentRepository $departmentRepository, 
        protected RegisterAndListService $registerAndListService, 
        )
    {}

    #[Route("/educationalFacilitatorList", name:"register_and_list_educationalFacilitatorList")]
    public function printEducationFacilitatorList(Request $request): Response
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

        $departments = $this->departmentRepository->findDepartmentsForFacilitator($schoolYear, $subSystem);

        $pdf = $this->registerAndListService->printEducationalFacilitatorList($departments, $school, $schoolYear, $subSystem);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Educational facilitator list"), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Liste des animateurs pédagogiques"), "I"), 200, ['Content-Type' => 'application/pdf']);
        }
    }

}

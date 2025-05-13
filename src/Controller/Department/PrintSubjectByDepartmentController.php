<?php

namespace App\Controller\Department;

use App\Service\SchoolYearService;
use App\Repository\DepartmentRepository;
use App\Repository\SchoolRepository;
use App\Service\PrintSubjectByDepartmentService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/department")]
class PrintSubjectByDepartmentController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected DepartmentRepository $departmentRepository,  
        protected PrintSubjectByDepartmentService $printSubjectByDepartmentService, 
        )
    {}

    #[Route("/print-subject-by-department/{slug}", name:"print_subject_by_department")]
    public function printSubjectByDepartment(string $slug, Request $request): Response
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

        $department = $this->departmentRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printSubjectByDepartmentService->print($department, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("The department's subjects ".$department->getDepartment()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les matières du département ".$department->getDepartment()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
    }

}

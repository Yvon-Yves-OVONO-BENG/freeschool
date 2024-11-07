<?php

namespace App\Controller\RegisterAndList;

use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use App\Service\RegisterAndListService;
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
class PrintStudentResponsabilityListController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected ClassroomRepository $classroomRepository, 
        protected RegisterAndListService $registerAndListService, 
        )
    {}

    #[Route("/studentResponsabilityList", name:"register_and_list_studentResponsabilityList")]
    public function printStudentResponsabilityList(Request $request): Response
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

        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        $responsableStudents = $this->registerAndListService->getResponsableStudents($classrooms, $schoolYear);

        $pdf = $this->registerAndListService->printResponsableStudents($responsableStudents, $school, $schoolYear, $subSystem);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Student responsability list"), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Liste des élèves responsables des classes"), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }

}

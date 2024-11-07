<?php

namespace App\Controller\Registration;

use App\Service\RegistrationService;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Service\PrintSchoolFeesStatementService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class PrintSchoolFeesStatementController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected PrintSchoolFeesStatementService $printSchoolFeesStatementService, 
        )
    {}

    #[Route("/printSchoolFeesStatement/{slug}", name:"registration_printSchoolFeesStatement")]
    public function printSchoolFeesStatement(Request $request, string $slug = ""): Response
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

        $classrooms = [];

        if($slug != null)
        {
            $classrooms[] = $this->classroomRepository->findOneBySlug([
                'slug' => $slug
            ]);
        }else 
        {
            $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
        }

        $pdf = $this->printSchoolFeesStatementService->printSchoolFeesStatement($classrooms, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("School Fees Statement"), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Frais de scolarité de l'établissement"), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }
}
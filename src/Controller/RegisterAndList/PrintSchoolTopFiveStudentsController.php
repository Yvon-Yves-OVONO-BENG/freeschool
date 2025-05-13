<?php

namespace App\Controller\RegisterAndList;

use App\Repository\TermRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
use App\Service\PrintSchoolTopFiveStudentsService;
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
class PrintSchoolTopFiveStudentsController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected SchoolRepository $schoolRepository, 
        protected ReportRepository $reportRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintSchoolTopFiveStudentsService $printSchoolTopFiveStudentsService
        )
    {}

    #[Route("/printSchoolTopFiveStudentsController", name:"print_school_top_five_students")]
    public function printSchoolTopFiveStudentsController(Request $request): Response
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

        $term = $this->termRepository->findOneBy((['term' => 0 ]));

        // On recupère les premiers du trimestre choisi
        $topFiveStudents = $this->reportRepository->findSchoolTopFiveStudents($schoolYear, $subSystem, $term);
        
       
        $pdf = $this->printSchoolTopFiveStudentsService->printSchoolTopFiveStudentsService($topFiveStudents, $schoolYear, $school, $subSystem, $term);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("School top five students"), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les cinqs premiers élèves de l'établissement"), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

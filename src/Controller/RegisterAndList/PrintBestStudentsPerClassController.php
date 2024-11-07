<?php

namespace App\Controller\RegisterAndList;

use App\Repository\TermRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
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
class PrintBestStudentsPerClassController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected SchoolRepository $schoolRepository, 
        protected ReportRepository $reportRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        )
    {}

    #[Route("/printBestStudentsPerClass", name:"register_and_list_printBestStudentsPerClass")]
    public function printBestStudentsPerClass(Request $request): Response
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
        // $classroms = $this->classroomRepository->findBy(['schoolYear' => $schoolYear], []);
        // $students = $this->studentRepository->findBy(['schoolYear' => $schoolYear], []);

        // on recupère le trimestre choisi
        $term = $this->termRepository->find($request->request->get('term'));

        // On recupère les premiers du trimestre choisi
        $bestReports = $this->reportRepository->findBy(
        [
        'rang' => 1, 
        ], 
        ['moyenne' => 'DESC']);
       
        $pdf = $this->firstPerClassService->printBestStudentPerClass($bestReports, $term, $schoolYear, $school, $subSystem);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Best student per classroom"), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Meilleurs élèves par classe"), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

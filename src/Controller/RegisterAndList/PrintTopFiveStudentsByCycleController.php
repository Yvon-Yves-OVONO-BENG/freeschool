<?php

namespace App\Controller\RegisterAndList;

use App\Repository\TermRepository;
use App\Repository\CycleRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PrintTopFiveStudentsByCycleService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintTopFiveStudentsByCycleController extends AbstractController
{
    public function __construct(
        protected CycleRepository $cycleRepository, 
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository,
        protected EvaluationRepository $evaluationRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintTopFiveStudentsByCycleService $printTopFiveStudentsByCycleService
        )
    {}

    #[Route("/printTopFiveStudentsByCycle", name:"top_five_students_by_cycle")]
    public function printTopFiveStudentsByCycle(Request $request): Response
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

        $cycle = $this->cycleRepository->find($request->request->get('cycle'));

        $term = $this->termRepository->find($request->request->get('term'));
        
        $topFiveStudents = $this->reportRepository->findTopFiveStudentsByCycle($schoolYear, $subSystem, $cycle, $term);
        
        $pdf = $this->printTopFiveStudentsByCycleService->printTopFiveStudentsByCycleService($topFiveStudents, $schoolYear, $school, $subSystem, $cycle, $term);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Top five students of cycle ".$cycle->getCycle() ), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les cinqs premiers élèves du cycle ".$cycle->getCycle()), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

<?php

namespace App\Controller\RegisterAndList;

use App\Repository\LevelRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\TermRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PrintTopFiveStudentsByLevelService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintTopFiveStudentsByLevelController extends AbstractController
{
    public function __construct(
        protected LevelRepository $levelRepository, 
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintTopFiveStudentsByLevelService $printTopFiveStudentsByLevelService
        )
    {}

    #[Route("/printTopFiveStudentsByLevel", name:"top_five_students_by_level")]
    public function printTopFiveStudentsByLevel(Request $request): Response
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

        $level = $this->levelRepository->find($request->request->get('level'));

        $term = $this->termRepository->find($request->request->get('term'));
        
        $topFiveStudents = $this->reportRepository->findTopFiveStudentsByLevel($schoolYear, $subSystem, $level, $term);
        
        $pdf = $this->printTopFiveStudentsByLevelService->printTopFiveStudentsByLevelService($topFiveStudents, $schoolYear, $school, $subSystem, $term, $level);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Top five students of level ".$level->getLevel() ), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les cinqs premiers élèves du niveau ".$level->getLevel()), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

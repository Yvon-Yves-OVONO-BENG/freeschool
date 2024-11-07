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
use App\Service\PrintTopStudentsByLevelService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintBestStudentsByLevelController extends AbstractController
{
    public function __construct(
        protected LevelRepository $levelRepository, 
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintTopStudentsByLevelService $printTopStudentsByLevelService
        )
    {}

    #[Route("/printBestStudentsByLevel", name:"best_students_by_level")]
    public function printBestStudentsByLevel(Request $request): Response
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
        $term = $this->termRepository->find(5);
        
        $students = $this->reportRepository->findBestStudentsByLevel($schoolYear, $subSystem, $level, $term);

        if ($request->request->has('printBestStudentsPerClass')) 
        {
            $pdf = $this->printTopStudentsByLevelService->printTopStudentsByLevelService($students, $schoolYear, $school, $subSystem, $term, $level);
        
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Best students of level ".$level->getLevel() ), "I"), 200, ['Content-Type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Les meilleurs élèves du niveau ".$level->getLevel()), "I"), 200, ['Content-Type' => 'application/pdf']);
            }
        } else 
        {
            // dd($students);
            usort($students, function($a, $b) 
            {
                return $b->getMoyenne() <=> $a->getMoyenne();
            });

            $level = $this->levelRepository->find($request->request->get('level'));
            return $this->render('register_and_list/AffichagestResultsOfYear.html.twig', [
                'students' => $students,
                'school' => $school,
                'level' => $level,
                'classroom' => 0,
            ]);
        }

    }
}

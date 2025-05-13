<?php

namespace App\Controller\RegisterAndList;

use App\Repository\CycleRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\SubjectRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Repository\TermRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PrintTopFiveStudentsByCycleAndSubjectService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintTopFiveStudentsByCycleAndSubjectController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository,
        protected CycleRepository $cycleRepository, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository,
        protected SubjectRepository $subjectRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintTopFiveStudentsByCycleAndSubjectService $printTopFiveStudentsByCycleAndSubjectService
        )
    {}

    #[Route("/printTopFiveStudentsByCycleAndSubject", name:"top_five_students_by_cycle_and_subject")]
    public function printTopFiveStudentsByCycleAndSubject(Request $request): Response
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
        $subject = $this->subjectRepository->find($request->request->get('subject'));
        $term = $this->termRepository->find($request->request->get('term'));
        
        $topFiveStudents = $this->evaluationRepository->findTopStudentsByCycleAndSubjectAndTrimester($schoolYear, $subSystem, $cycle->getId(), $subject->getId(), $term->getTerm());
        
        $pdf = $this->printTopFiveStudentsByCycleAndSubjectService->printTopFiveStudentsByCycleAndSubjectService($topFiveStudents, $schoolYear, $school, $subSystem, $cycle, $subject, $term);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Top five students of cycle ".$cycle->getCycle()." in ".$subject->getSubject() ), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les cinqs premiers élèves du cycle ".$cycle->getCycle()." en ".$subject->getSubject()), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

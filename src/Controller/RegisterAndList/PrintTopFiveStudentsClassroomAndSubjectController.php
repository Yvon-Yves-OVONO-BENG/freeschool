<?php

namespace App\Controller\RegisterAndList;

use App\Repository\SubjectRepository;
use App\Repository\EvaluationRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
use App\Repository\TermRepository;
use App\Service\PrintTopFiveStudentsClassroomAndSubjectService;
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
class PrintTopFiveStudentsClassroomAndSubjectController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository,
        protected SubjectRepository $subjectRepository, 
        protected SchoolRepository $schoolRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintTopFiveStudentsClassroomAndSubjectService $printTopFiveStudentsClassroomAndSubjectService
        )
    {}

    #[Route("/PrintTopFiveStudentsClassroomAndSubject", name:"print_school_top_five_students_classroom_and_subject")]
    public function PrintTopFiveStudentsClassroomAndSubject(Request $request): Response
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

        $subject = $this->subjectRepository->find($request->request->get('subject') );
        $classroom = $this->classroomRepository->find($request->request->get('classroom') );
        $term = $this->termRepository->find($request->request->get('term') );
        
        // On recupère les premiers du trimestre choisi
        $topFiveStudents = $this->evaluationRepository->findTop5StudentsBySubjectClassAndTrimester($schoolYear, $subSystem, $subject->getId(), $classroom->getId(), $term->getTerm());
        
        $pdf = $this->printTopFiveStudentsClassroomAndSubjectService->printTopFiveStudentsClassroomAndSubjectService($topFiveStudents, $schoolYear, $school, $subSystem, $subject, $classroom, $term);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Top five students of ".$subject->getSubject()." of ".$classroom->getClassroom() ), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les cinqs premiers en ".$subject->getSubject()." de la ".$classroom->getClassroom() ), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

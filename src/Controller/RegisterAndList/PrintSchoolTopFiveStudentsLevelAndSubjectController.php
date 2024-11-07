<?php

namespace App\Controller\RegisterAndList;

use App\Repository\LevelRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\SubjectRepository;
use App\Service\FirstPerClassService;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use App\Service\PrintSchoolTopFiveStudentsLevelAndSubjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintSchoolTopFiveStudentsLevelAndSubjectController extends AbstractController
{
    public function __construct(
        protected SubjectRepository $subjectRepository, 
        protected LevelRepository $levelRepository, 
        protected SchoolRepository $schoolRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected FirstPerClassService $firstPerClassService,
        protected PrintSchoolTopFiveStudentsLevelAndSubjectService $printSchoolTopFiveStudentsLevelAndSubjectService
        )
    {}

    #[Route("/printSchoolTopFiveStudentsLevelAndSubject", name:"top_five_students_level_and_subject")]
    public function printSchoolTopFiveStudentsLevelAndSubject(Request $request): Response
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
        $level = $this->levelRepository->find($request->request->get('level') );
        
        // On recupère les premiers du trimestre choisi
        $topFiveStudents = $this->evaluationRepository->findTopFiveStudentsLevelAndSubject($schoolYear, $subSystem, $level, $subject);
        
        
        $pdf = $this->printSchoolTopFiveStudentsLevelAndSubjectService->printSchoolTopFiveStudentsLevelAndSubjectService($topFiveStudents, $schoolYear, $school, $subSystem, $level, $subject);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Top five students of ".$subject->getSubject()." niveau ".$level->getLevel()), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Les cinqs premiers en ".$subject->getSubject()." niveau ".$level->getLevel()), "I"), 200, ['Content-Type' => 'application/pdf']);
        }

    }
}

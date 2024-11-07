<?php

namespace App\Controller\Report;

use App\Entity\Term;
use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Service\ClassroomService;
use App\Repository\TermRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class RollOfHonorController extends AbstractController
{
    public function __construct(
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/rollOfHonor", name:"report_rollOfHonor")]
    public function rollOfHonor(Request $request): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        
        $selectedClassroom = new Classroom();
        $classrooms = [];
        $reports = [];
        $numberOfStudents = 0;
        $numberOfRollOfHonors = 0;

        $selectedTerm = new Term();
        $terms = [];

        $methodIsPost = false;

        if($idc = $request->request->get('classroom'))
        {
            $methodIsPost = true;
            
            $selectedClassroom = $this->classroomRepository->find($idc);
            $selectedTerm = $this->termRepository->find($request->request->get('term'));
            
            $reports = $this->reportRepository->findStudentToDisplayRollOfHonor($selectedClassroom, $selectedTerm);

            $numberOfStudents = $this->generalService->getNumberOfStudents($selectedClassroom);

            $numberOfRollOfHonors = count($this->reportRepository->findStudentToPrintRollOfHonor($selectedClassroom, $selectedTerm));

        }
        
        $terms = $this->termRepository->findBy([], ['term' => 'ASC']);

        if($this->isGranted(ConstantsClass::ROLE_CENSOR))
        {
            /**
             * @var User
             */
            $user = $this->getUser();
            $classrooms = $this->classroomRepository->findCensorClassrooms($user->getTeacher(), $schoolYear, $subSystem);
        }else 
        {
            $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            
        }

        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        
        return $this->render('report/rollOfHonor.html.twig', [
            'reports' => $reports,
            'numberOfStudents' => $numberOfStudents,
            'numberOfRollOfHonors' => $numberOfRollOfHonors,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'terms' => $terms,
            'selectedTerm' => $selectedTerm,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
            'methodIsPost' => $methodIsPost,
            'school' => $school,
        ]);
    }
}
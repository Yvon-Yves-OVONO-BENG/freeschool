<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Repository\DutyRepository;
use App\Repository\SchoolRepository;
use App\Repository\AbsenceRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use App\Service\RegisterAndListService;
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
class PrintAbsenceReportController extends AbstractController
{
    public function __construct(
        protected DutyRepository $dutyRepository, 
        protected SchoolRepository $schoolRepository,
        protected AbsenceRepository $absenceRepository, 
        protected TeacherRepository $teacherRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected RegisterAndListService $registerAndListService, 
        )
    {}

    #[Route("/printAbsenceReport", name:"register_and_list_printAbsenceReport")]
    public function printAbsenceReport(Request $request): Response
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

        $teachers = [];

        // Si c'est le relevé d'un seul surveillant qui est demandé
        $teacherId = $request->request->get('teacher');
		if($teacherId != 0)
		{
            $teachers[] = $this->teacherRepository->find($teacherId);

		}else // si tous les relevés sont demandés
		{
            $duty = $this->dutyRepository->findOneBy([
                'duty' => ConstantsClass::SUPERVISOR_DUTY
            ]);
            $teachers = $this->teacherRepository->findBy([
                'duty' => $duty,
                'schoolYear' => $schoolYear
            ]);
        }
        
        $allClassrooms = [];
        $allAbsences = [];
        
		// Pour chaque surveillant on recupère ses classes
        foreach($teachers as $teacher)
        {
        	$allClassrooms = $this->classroomRepository->findSupervisorClassrooms($teacher, $schoolYear, $subSystem);
            
        	// pour chaque classe non vide on recupere la liste des eleves et leurs absences
        	foreach($allClassrooms as $classroom)
            {
                if(count($classroom->getStudents()))
                {
                    $absencesOfClassroom = [];
                    $absencesOfClassroom['classroom'] = $classroom;
                   
                    $absencesOfClassroom['absences'] = $this->absenceRepository->findClassroomAbsences($classroom->getId());

                    $allAbsences[] =  $absencesOfClassroom;

                }
            }
        }
        // dd($allAbsences);
        $absencesReports = $this->registerAndListService->getAbsenceReports($allAbsences);
        
        $pdf =  $this->registerAndListService->printAbsenceReports($absencesReports, $schoolYear, $school);

        if($teacherId != 0)
		{
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Absence report of ".$teachers[0]->getFullName()) , "I"), 200, ['Content-Type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Report d'Absence de ".$teachers[0]->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']);
            }
        }
        else
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output("Absence report of all supervisors", "I"), 200, ['Content-Type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output("Report d'Absence de tous les surveillants", "I"), 200, ['Content-Type' => 'application/pdf']);
            }
        }
        
    }

}

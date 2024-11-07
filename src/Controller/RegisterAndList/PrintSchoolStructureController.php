<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Repository\DutyRepository;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Service\RegisterAndListService;
use App\Repository\DepartmentRepository;
use App\Repository\GradeRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */ 
#[Route("/register_and_list")]
class PrintSchoolStructureController extends AbstractController
{
    public function __construct(
        protected DutyRepository $dutyRepository, 
        protected GradeRepository $gradeRepository,
        protected SchoolRepository $schoolRepository, 
        protected TeacherRepository $teacherRepository, 
        protected DepartmentRepository $departmentRepository, 
        protected RegisterAndListService $registerAndListService, 
        )
    {}

    #[Route("/printSchoolStructure/{staff<[0-1]{1}>}", name:"register_and_list_printSchoolStructure")]
    public function printSchoolStructure(Request $request, int $staff = 0): Response
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

        $censors = $this->teacherRepository->findBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findOneByDuty(ConstantsClass::CENSOR_DUTY)
        ]);

        $supervisors = $this->teacherRepository->findBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findOneByDuty(ConstantsClass::SUPERVISOR_DUTY)
        ], [
            'fullName' => 'ASC'
        ]);

        $counsellors = $this->teacherRepository->findBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findOneByDuty(ConstantsClass::COUNSELLOR_DUTY)
        ], [
            'fullName' =>'ASC'
        ]);

        $chiefOrientation = $this->teacherRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findByDuty(ConstantsClass::CHIEF_ORIENTATION_DUTY)
        ], [
            'fullName' =>'ASC'
        ]);

        $sportService = $this->teacherRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findByDuty(ConstantsClass::SPORT_SERVICE_DUTY)
        ], [
            'fullName' =>'ASC'
        ]);

        $chiefOfwork = $this->teacherRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findByDuty(ConstantsClass::CHIEF_WORK_DUTY)
        ], [
            'fullName' =>'ASC'
        ]);

        $apps = $this->teacherRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findByDuty(ConstantsClass::APPS_DUTY)
        ], [
            'fullName' =>'ASC'
        ]);

        $treasurer = $this->teacherRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findByDuty(ConstantsClass::TREASURER_DUTY)
        ], [
            'fullName' =>'ASC'
        ]);

        $econome = $this->teacherRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
            'duty' => $this->dutyRepository->findByDuty(ConstantsClass::ECONOME_DUTY)
        ], [
            'fullName' =>'ASC'
        ]);

        $headmaster = $school->getHeadmaster();

        if($staff == 1)
        {
            $ap = $this->teacherRepository->findBy([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'duty' => $this->dutyRepository->findByDuty(ConstantsClass::AP_DUTY)
            ], [
                'fullName' =>'ASC'
            ]);

            $teachers = $this->teacherRepository->findTeachersForStaff($schoolYear, $subSystem);
            
            $pdf = $this->registerAndListService->printStaffList($headmaster, $censors, $supervisors, $counsellors, $chiefOrientation, $sportService, $chiefOfwork, $apps, $treasurer, $econome, $ap, $teachers, $school, $schoolYear, $subSystem);

            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output("Sheet of staff", "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Fichier du personnel"), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }

        
        $departments = $this->departmentRepository->findDepartments($schoolYear, $subSystem);
        // $othersdepartments = $this->departmentRepository->findDepartments(true);
        $teacherDuty = $this->dutyRepository->findOneByDuty(ConstantsClass::TEACHER_DUTY);

        $teachersByDepartments = $this->registerAndListService->getTeachersByDepartment($departments, $schoolYear, $teacherDuty, $subSystem);

        $otherTeachers = $this->teacherRepository->findByGrade($this->gradeRepository->findOneByGrade(ConstantsClass::VAC_GRADE));

        $pdf = $this->registerAndListService->printSchoolStructure($headmaster, $censors, $supervisors, $counsellors, $chiefOrientation, $sportService, $chiefOfwork, $apps, $treasurer, $econome, $teachersByDepartments, $otherTeachers, $school, $schoolYear, $subSystem);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("School structure"), "I"), 200, ['content-type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Structure de l'Etablissement"), "I"), 200, ['content-type' => 'application/pdf']);
        }
    }

}

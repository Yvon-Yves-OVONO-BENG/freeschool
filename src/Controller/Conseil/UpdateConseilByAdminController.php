<?php

namespace App\Controller\Conseil;

use App\Entity\ConstantsClass;
use App\Service\AbsenceManagerService;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Repository\AbsenceRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Service\SchoolYearService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/absence")]
class UpdateConseilByAdminController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected DutyRepository $dutyRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        protected AbsenceRepository $absenceRepository, 
        protected StudentRepository $studentRepository, 
        protected SchoolYearService $schoolYearService, 
        protected ClassroomRepository $classroomRepository, 
        protected AbsenceManagerService $absenceManagerService, 
        )
    {}

    #[Route("/updateAbsenceByAdmin", name:"absence_updateAbsenceByAdmin")]
    public function updateAbsenceByAdmin(Request $request)
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
        
        if($request->request->has('updateAbsenceExtra'))
        {
            return $this->redirectToRoute('absence_absenceRecorder', [
                'slug' => $this->teacherRepository->find($request->request->get('teacher'))->getSlug(),
            ]);
        }

        $duty = $this->dutyRepository->findOneByDuty(ConstantsClass::SUPERVISOR_DUTY);

        $teachers = $this->teacherRepository->findBy([
            'schoolYear' => $schoolYear,
            'duty' => $duty,
            'subSystem' => $subSystem
        ], [
            'fullName' => 'ASC'
        ]);

        return $this->render('absence/updateAbsenceByAdmin.html.twig', [
            'teachers' => $teachers,
            'school' => $school,
        ]);
    }

}
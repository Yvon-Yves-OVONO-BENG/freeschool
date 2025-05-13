<?php

namespace App\Controller\Absence;

use App\Entity\ConstantsClass;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Service\SchoolYearService;
use App\Repository\AbsenceRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Service\AbsenceManagerService;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use App\Repository\SubSystemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/absence")]
class AbsenceRecorderController extends AbstractController
{
    public function __construct(
        protected Security $security, 
        protected EntityManagerInterface $em, 
        protected DutyRepository $dutyRepository, 
        protected TermRepository $termRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected TeacherRepository $teacherRepository, 
        protected AbsenceRepository $absenceRepository, 
        protected StudentRepository $studentRepository, 
        protected SchoolYearService $schoolYearService, 
        protected ClassroomRepository $classroomRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected AbsenceManagerService $absenceManagerService, 
        )
    {}

    #[Route("/absenceRecorder/{slug}", name:"absence_absenceRecorder")]
    public function absenceRecorder(Request $request, string $slug = null): Response
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

        $terms = $this->termRepository->findBy([], ['term' => 'ASC']);
        
        $teacher = $this->teacherRepository->findOneBySlug(['slug' => $slug ]);
        
        $classrooms = $this->classroomRepository->findSupervisorClassrooms($teacher, $schoolYear, $subSystem);

        $absenceToUpdate = null;

        if($request->request->has('absenceToUpdate'))
        {
            $absenceToUpdate = $this->absenceRepository->find($request->request->get('absence'));
        }

        if($request->request->has('term')) 
        {
            $termId = $request->request->get('term');
            $classroomId = $request->request->get('classroom');

            $selectedTerm = $this->termRepository->find($termId);
            $selectedClassroom = $this->classroomRepository->find($classroomId);
            
            
            if ($request->request->has('saveAbsence')) 
            {  
                $students = $this->studentRepository->findBy([
                    'classroom' => $selectedClassroom
                ]);

                $this->absenceManagerService->saveAbsences($selectedTerm, $request);

                $this->addFlash('info', $this->translator->trans('Hour absences saved with success !'));

                $mySession->set('ajout', 1);
            }
            elseif($request->request->has('updateAbsence'))
            { 
                $this->absenceManagerService->updateAbsence($request->request->get('absenceToUpdateId'), $request->request->get('updatedAbsence'), $request);
                $this->addFlash('info', $this->translator->trans('Hour absense updated with success !'));
                $mySession->set('miseAjour', 1);
            }
            elseif ($request->request->has('removeAllAbsences')) 
            {
                $this->absenceManagerService->removeAbsences($request->request->get('term'), $request->request->get('classroom'), $request);
                $this->addFlash('info', $this->translator->trans('Hour absences deleted with success !'));
                $mySession->set('suppression', 1);
            }

            $absences = $this->absenceRepository->findAbsences($selectedTerm, $selectedClassroom);
            
            $students = $this->studentRepository->findBy([
                'classroom' => $selectedClassroom,
                'schoolYear' => $schoolYear,
                ], [
                'fullName' => 'ASC'
            ]);

            return $this->render('absence/absenceRecorder.html.twig', [
                'terms' => $terms,
                'school' => $school,
                'classrooms' => $classrooms,
                'teacher' => $teacher,
                'selectedTerm' => $selectedTerm,
                'selectedClassroom' => $selectedClassroom,
                'absences' => $absences,
                'students' => $students,
                'absenceToUpdate' => $absenceToUpdate,
                'annualTerm' => ConstantsClass::ANNUEL_TERM,
            ]);
        }
        
        
        // $notification = true;
        return $this->render('absence/absenceRecorder.html.twig', [
            'terms' => $terms,
            'school' => $school,
            'classrooms' => $classrooms,
            'teacher' => $teacher,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
        ]);
    }
 
}
<?php

namespace App\Controller\AbsenceTeacher;

use App\Entity\ConstantsClass;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Service\AbsenceManagerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\AbsenceTeacherRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

 #[Route("/absenceTeacher")]
class AbsenceTeacherRecorderController extends AbstractController
{
    public function __construct(
        protected Security $security, 
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected DutyRepository $dutyRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        protected TeacherRepository $teacherRepository, 
        protected AbsenceManagerService $absenceManagerService, 
        protected AbsenceTeacherRepository $absenceTeacherRepository, 
        )
    {}

    #[Route("/absenceTeacherRecorder/{slug}", name:"absence_absenceTeacherRecorder")]
    public function absenceTeacherRecorder(Request $request, string $slug): Response
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

        $teacher = $this->teacherRepository->findOneBySlug(['slug' => $slug ]);

        $terms = $this->termRepository->findBy([], ['term' => 'ASC']);

        $teachers = $this->teacherRepository->findBy([
            'schoolYear' => $schoolYear, 
            'subSystem' => $subSystem 
            ],
            [
            'fullName' => 'ASC'
            ]);


        $absenceToUpdate = null;

        if($request->request->has('absenceToUpdate'))
        {
            $absenceToUpdate = $this->absenceTeacherRepository->find($request->request->get('absence'));
        }

        if($request->request->has('term')) 
        {
            $termId = $request->request->get('term');
            
            $selectedTerm = $this->termRepository->find($termId);
            
            if ($request->request->has('saveAbsence')) 
            {  
                $this->absenceManagerService->saveAbsencesTeacher($selectedTerm, $request);
                $this->addFlash('info', $this->translator->trans('Hour absenses saved successfully'));
                $mySession->set('ajout', 1);
                
            }
            elseif($request->request->has('updateAbsence'))
            { 
                $this->absenceManagerService->updateAbsenceTeacher($request->request->get('absenceToUpdateId'), $request->request->get('updatedAbsence'), $request);
                $this->addFlash('info', $this->translator->trans('Hour absense updated successfully'));
                $mySession->set('miseAjour', 1);
            }
            elseif ($request->request->has('removeAllAbsences')) 
            {
                $this->absenceManagerService->removeAbsencesTeacher($request->request->get('term'), $request);
                $this->addFlash('info', $this->translator->trans('Hour absenses deleted successfully'));
                $mySession->set('suppression', 1);
            }

            $absences = $this->absenceTeacherRepository->findAbsencesTeacher($selectedTerm);
            
            return $this->render('absenceTeacher/absenceTeacherRecorder.html.twig', [
                'terms' => $terms,
                'school' => $school,
                'teachers' => $teachers,
                'teacher' => $teacher,
                'selectedTerm' => $selectedTerm,
                'absences' => $absences,
                'absenceToUpdate' => $absenceToUpdate,
                'annualTerm' => ConstantsClass::ANNUEL_TERM,
            ]);
        }
        
        
        return $this->render('absenceTeacher/absenceTeacherRecorder.html.twig', [
            'terms' => $terms,
            'school' => $school,
            'teachers' => $teachers,
            'teacher' => $teacher,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
        ]);
    }
 
}
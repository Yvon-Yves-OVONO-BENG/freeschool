<?php

namespace App\Controller\Conseil;

use App\Entity\ConstantsClass;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Service\SchoolYearService;
use App\Repository\ConseilRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Service\ConseilManagerService;
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

#[Route("/conseil")]
class ConseilRecorderController extends AbstractController
{
    public function __construct(
        protected Security $security, 
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected DutyRepository $dutyRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected TeacherRepository $teacherRepository, 
        protected ConseilRepository $conseilRepository, 
        protected StudentRepository $studentRepository, 
        protected SchoolYearService $schoolYearService, 
        protected ClassroomRepository $classroomRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected ConseilManagerService $conseilManagerService, 
        )
    {
    }

    #[Route("/conseilRecorder/{slug}/{notification}", name:"conseil_conseilRecorder")]
    public function conseilRecorder(Request $request, string $slug, int $notification = 0): Response
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
        
        $teacher = $this->teacherRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $classrooms = $this->classroomRepository->findSupervisorClassrooms($teacher, $schoolYear, $subSystem);

        $conseilToUpdate = null;

        if($request->request->has('conseilToUpdate'))
        {
            $conseilToUpdate = $this->conseilRepository->find($request->request->get('conseil'));
        }

        $notification = false;
        if($request->request->has('term')) 
        {
            $termId = $request->request->get('term');
            $classroomId = $request->request->get('classroom');

            $selectedTerm = $this->termRepository->find($termId);
            $selectedClassroom = $this->classroomRepository->find($classroomId);
            
            
            if ($request->request->has('saveConseil')) 
            {  
                $students = $this->studentRepository->findBy([
                    'classroom' => $selectedClassroom
                ]);

                $this->conseilManagerService->saveConseils($selectedTerm, $request);
                $this->addFlash('info', $this->translator->trans('Decisions council saved with success !'));

                $notification = true;
            }elseif($request->request->has('updateConseil'))
            { 
               $this->conseilManagerService->updateConseil($request->request->get('conseilToUpdateId'), $request->request->get('updatedDecision'), $request->request->get('updatedMotif'), $request);
               
               $this->addFlash('info', $this->translator->trans('Decision council updated with success !'));

               $notification = true;

            }elseif ($request->request->has('removeAllConseils')) 
            {
               $this->conseilManagerService->removeConseils($request->request->get('term'), $request->request->get('classroom'), $request);
               $this->addFlash('info', $this->translator->trans('Decision council deleted with success !'));

               $notification = true;
            }

            $conseils = $this->conseilRepository->findConseils($selectedTerm, $selectedClassroom);
            
            $students = $this->studentRepository->findBy([
                'classroom' => $selectedClassroom,
                'schoolYear' => $schoolYear
                ], [
                'fullName' => 'ASC'
                ]);
            
            

            return $this->render('conseil/conseilRecorder.html.twig', [
                'terms' => $terms,
                'school' => $school,
                'classrooms' => $classrooms,
                'teacher' => $teacher,
                'selectedTerm' => $selectedTerm,
                'selectedClassroom' => $selectedClassroom,
                'conseils' => $conseils,
                'students' => $students,
                'conseilToUpdate' => $conseilToUpdate,
                'annualTerm' => ConstantsClass::ANNUEL_TERM,
                'notification' => $notification,
            ]);
        }
        
        
        // $notification = true;
        return $this->render('conseil/conseilRecorder.html.twig', [
            'terms' => $terms,
            'school' => $school,
            'classrooms' => $classrooms,
            'teacher' => $teacher,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
            'notification' => $notification,
        ]);
    }
 
}
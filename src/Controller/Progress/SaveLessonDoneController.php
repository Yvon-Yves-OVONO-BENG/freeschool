<?php

namespace App\Controller\Progress;

use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Form\ProgressLessonFaiteType;
use App\Repository\SubjectRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use Doctrine\ORM\EntityManagerInterface;
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

#[Route("/progress")]
class SaveLessonDoneController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected LessonRepository $lessonRepository, 
        protected TeacherRepository $teacherRepository, 
        protected SubjectRepository $subjectReposirory, 
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/save-lesson-done/{slugTeacher}/{slugSubject}/{slugClassroom}", name:"save_lesson_done")]
    public function addLessonDone(Request $request, string $slugTeacher = "", string $slugSubject = "", string $slugClassroom = ""): Response
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
        

        //////Je récupère l'enseignant, la matière et la classe
        $teacher = $this->teacherRepository->findOneBySlug(['slug' => $slugTeacher]);
        $subject = $this->subjectReposirory->findOneBySlug(['slug' => $slugSubject]);
        $classroom = $this->classroomRepository->findOneBySlug(['slug' => $slugClassroom]);
        // dump($teacher);
        // dump($subject);
        // dd($classroom);
        ////je récupère la lecçon prévue en fonction de l'enseigant, 
        ////la matière et la classe d'une année scolaire
        $lessonPlain = $this->lessonRepository->findBy([
            'teacher' => $teacher,
            'subject' => $subject,
            'classroom' => $classroom,
            // 'schoolYear' => $schoolYear,
            ]);
        
        $lessonPlain = $lessonPlain[0];
        
        $form = $this->createForm(ProgressLessonFaiteType::class, $lessonPlain);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $this->em->flush();
            $this->addFlash('info', $this->translator->trans('Lesson done saved successfully !'));
            $mySession->set('ajout', 1);

            return $this->redirectToRoute('lesson_done');
        }

        return $this->render('progress/addLessonDone.html.twig', [
            'formLessonDone' => $form->createView(),
            'teacher' => $teacher,
            'subject' => $subject,
            'classroom' => $classroom,
            'school' => $school,
        ]);
    }
}

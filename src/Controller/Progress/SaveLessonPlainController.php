<?php

namespace App\Controller\Progress;

use App\Entity\Lesson;
use App\Repository\SchoolRepository;
use App\Form\ProgressLessonPrevuType;
use App\Repository\LessonRepository;
use App\Repository\SchoolYearRepository;
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
class SaveLessonPlainController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolYearRepository $schoolYearRepository, 
        protected SchoolRepository $schoolRepository,
        protected LessonRepository $lessonRepository
        )
    {}

    #[Route("/save-lesson-plain/{slugTeacher}", name:"save_lesson_plain")]
    public function saveLessonPlain(Request $request, string $slugTeacher = ""): Response
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
        

        /**
         * @var User
         */
        $user = $this->getUser();
        $teacher = $user->getTeacher();
        
        $progress = new Lesson;
        
        $form = $this->createForm(ProgressLessonPrevuType::class, $progress);
        $form->handleRequest($request);

        // $progress->setSchoolYear($schoolYear)
        //         ->setTeacher($teacher);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $subject = $form->getData()->getSubject();
            $classroom = $form->getData()->getClassroom();

            $progress = $this->lessonRepository->findBy([
                'subject' => $subject,
                'classroom' => $classroom
            ]);

            $this->em->persist($progress[0]);
            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Number of planned lessons saved with success !'));
            $mySession->set('ajout', 1);

            $progress = new Lesson;
            $form = $this->createForm(ProgressLessonPrevuType::class, $progress);
            
        }

        return $this->render('progress/addLessonPlain.html.twig', [
            'school' => $school,
            'formProgressLessonPrevue' => $form->createView(),
        ]);
    }

}

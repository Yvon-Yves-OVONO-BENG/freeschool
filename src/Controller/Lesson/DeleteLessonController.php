<?php

namespace App\Controller\Lesson;

use App\Service\SchoolYearService;
use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

 #[Route("/lesson")]
class DeleteLessonController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected LessonRepository $lessonRepository, 
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route("/deleteLesson/{slug}", name:"lesson_deleteLesson")]
    public function deleteLesson(string $slug, Request $request): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $lesson = $this->lessonRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        $slugClassroom = $lesson->getClassroom()->getSlug();

        if(count($lesson->getEvaluations()))
        {
            $this->addFlash('info', $this->translator->trans('Deleting denied. This lesson has recorded marks'));
            $mySession->set('suppression', 1);

            return $this->redirectToRoute('lesson_displayLesson', [
                'slug' => $slugClassroom,
                's' => 1
            ]);
        }else 
        {
            $this->em->remove($lesson);
            $this->em->flush();
            
            $this->addFlash('info', $this->translator->trans('Lesson deleted successfully'));
            $mySession->set('suppression', 1);
            
            return $this->redirectToRoute('lesson_displayLesson', [
                'slug' => $slugClassroom,
                's' => 1
            ]);
        }

        
    }
}

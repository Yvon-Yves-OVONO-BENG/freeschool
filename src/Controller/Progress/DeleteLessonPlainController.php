<?php

namespace App\Controller\Progress;

use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route("/progress")]
class DeleteLessonPlainController extends AbstractController
{
    public function __construct(protected EntityManagerInterface $em, protected TranslatorInterface $translator, protected LessonRepository $lessonRepository)
    {}

    #[Route("/delete-plain-lesson/{slug}", name:"delete_plain_Lesson")]
    public function deletePlainLesson(string $slug, Request $request): Response
    {
        $mySession = $request->getSession();
        
        #mes variables témoin pour afficher les sweetAlert
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        
        $plainLesson = $this->lessonRepository->findOneBy(['slug' => $slug]);
        
        if (!$plainLesson) 
        {
            return $this->redirectToRoute('page_error');
        }

        $this->em->remove($plainLesson);
        $this->em->flush();
            
        $this->addFlash('info', $this->translator->trans('Lesson plain deleted with success !'));
        $mySession->set('suppression', 1);
        return $this->redirectToRoute('display_lesson_plain', ['s' => 1 ]);
    }

}

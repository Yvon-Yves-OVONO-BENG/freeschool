<?php

namespace App\Controller\Lesson;

use App\Form\LessonType;
use App\Service\SchoolYearService;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\SubSystemRepository;
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

#[Route("/lesson")]
class EditLessonController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected LessonRepository $lessonRepository,  
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository,
        )
    {}

    #[Route("/editLesson/{slug}", name:"lesson_editLesson")]
    public function saveLesson(Request $request, string $slug): Response
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
        
        $subSystem = $this->subSystemRepository->find($subSystem->getId());
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $lesson = $this->lessonRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(LessonType::class, $lesson);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {   
            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait la derniere matiere de la table
            $dernierLesson = $this->lessonRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierLesson) 
            {
                $id = $dernierLesson[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $lesson->setSubSystem($subSystem)
            ->setSlug($slug.$id);

            $this->em->persist($lesson);
            $this->em->flush(); // On modifie

            $this->addFlash('info', $this->translator->trans('Lesson updated with success !'));
            
            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des Courss
            return $this->redirectToRoute('lesson_displayLesson', [
                    'slug' => $lesson->getClassroom()->getSlug(),
                    'm' => 1
                ]);

        }

        return $this->render('lesson/saveLesson.html.twig', [
            'formLesson' => $form->createView(),
            'slugClassroom' => $slug,
            'school' => $school,
            ]);
    }

}

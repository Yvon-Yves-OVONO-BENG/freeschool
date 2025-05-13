<?php

namespace App\Controller\Lesson;

use App\Entity\Lesson;
use App\Form\LessonType;
use App\Service\SchoolYearService;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolYearRepository;
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
class SaveLessonController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected ClassroomRepository $classroomRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/saveLesson", name:"lesson_saveLesson")]
    public function saveLesson(Request $request): Response
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

        $lesson = new Lesson();       
        
        $form = $this->createForm(LessonType::class, $lesson);

        $form->handleRequest($request);
        $slug = 0;
        $slugClassroom = 0;
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

            $selectedClassroom = $lesson->getClassroom();

            // on verifie si la lesson n'existe pas encore
            $existingLesson = $this->lessonRepository->findOneBy([
                'subject' => $lesson->getSubject(),
                'classroom' => $lesson->getClassroom()
            ]);
            
            if($existingLesson === null)
            {
                // On ajoute dans la BD
                $lesson->setClassroom($selectedClassroom)
                        ->setSlug($slug.$id)
                        ->setSubSystem($subSystem);
                ;
                $this->em->persist($lesson);
                $this->em->flush(); 

                $this->addFlash('info', $this->translator->trans('Lesson saved with success !'));
                $mySession->set('ajout',1);
            }
            else
            {
                $this->addFlash('info', $this->translator->trans('This subject is already exist'));
                $mySession->set('saisiNotes', 1);
            }

            // on vide le formulaire
            $lesson = new Lesson();
            $lesson->setClassroom($selectedClassroom)
            ->setSubSystem($subSystem);

            $slugClassroom = $lesson->getClassroom()->getSlug();

            $form = $this->createForm(LessonType::class, $lesson);
            
        }

        $slug = 0;

        return $this->render('lesson/saveLesson.html.twig', [
            'slug' => $slug,
            'school' => $school,
            'slugClassroom' => $slugClassroom,
            'formLesson' => $form->createView(),
            ]);
    }
}

<?php

namespace App\Controller\AjoutSlug;

use App\Repository\LessonRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugLessonController extends AbstractController
{
    #[Route('/ajout-slug-lesson', name: 'ajout_slug_lesson')]
    public function index(EntityManagerInterface $em, LessonRepository $lessonRepository): Response
    {
        $lessons = $lessonRepository->findAll();

        foreach ($lessons as $lesson) 
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
            $dernierLesson = $lessonRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierLesson) 
            {
                $id = $dernierLesson[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $lesson->setSlug($slug.$id);
            $em->persist($lesson);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

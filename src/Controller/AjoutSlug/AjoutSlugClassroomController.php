<?php

namespace App\Controller\AjoutSlug;

use App\Repository\ClassroomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugClassroomController extends AbstractController
{
    #[Route('/ajout-slug-classroom', name: 'ajout_slug_classroom')]
    public function index(EntityManagerInterface $em, ClassroomRepository $classroomRepository): Response
    {
        $classrooms = $classroomRepository->findAll();

        foreach ($classrooms as $classroom) 
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
            $dernierClassroom = $classroomRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierClassroom) 
            {
                $id = $dernierClassroom[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $classroom->setSlug($slug.$id);
            $em->persist($classroom);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

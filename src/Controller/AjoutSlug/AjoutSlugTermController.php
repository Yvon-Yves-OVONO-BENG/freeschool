<?php

namespace App\Controller\AjoutSlug;

use App\Repository\TermRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugTermController extends AbstractController
{
    #[Route('/ajout-slug-term', name: 'ajout_slug_term')]
    public function index(EntityManagerInterface $em, TermRepository $termRepository): Response
    {
        $terms = $termRepository->findAll();

        foreach ($terms as $term) 
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
            $dernierTerm = $termRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierTerm) 
            {
                $id = $dernierTerm[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $term->setSlug($slug.$id);
            $em->persist($term);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

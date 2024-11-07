<?php

namespace App\Controller\AjoutSlug;

use App\Repository\DiplomaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugDiplomaController extends AbstractController
{
    #[Route('/ajout-slug-diploma', name: 'ajout_slug_diploma')]
    public function index(EntityManagerInterface $em, DiplomaRepository $diplomaRepository): Response
    {
        $diplomas = $diplomaRepository->findAll();

        foreach ($diplomas as $diploma) 
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
            $dernierDiploma = $diplomaRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDiploma) 
            {
                $id = $dernierDiploma[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $diploma->setSlug($slug.$id);
            $em->persist($diploma);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

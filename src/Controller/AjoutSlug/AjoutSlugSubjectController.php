<?php

namespace App\Controller\AjoutSlug;

use App\Repository\SubjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugSubjectController extends AbstractController
{
    #[Route('/ajout-slug-subject', name: 'ajout_slug_subject')]
    public function index(EntityManagerInterface $em, SubjectRepository $subjectRepository): Response
    {
        $subjects = $subjectRepository->findAll();

        foreach ($subjects as $subject) 
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
            $dernierSubject = $subjectRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierSubject) 
            {
                $id = $dernierSubject[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $subject->setSlug($slug.$id);
            $em->persist($subject);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

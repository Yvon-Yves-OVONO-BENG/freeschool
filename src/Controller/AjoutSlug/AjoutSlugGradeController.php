<?php

namespace App\Controller\AjoutSlug;

use App\Repository\GradeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugGradeController extends AbstractController
{
    #[Route('/ajout-slug-grade', name: 'ajout_slug_grade')]
    public function index(EntityManagerInterface $em, GradeRepository $gradeRepository): Response
    {
        $grades = $gradeRepository->findAll();

        foreach ($grades as $grade) 
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
            $dernierGrade = $gradeRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierGrade) 
            {
                $id = $dernierGrade[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $grade->setSlug($slug.$id);
            $em->persist($grade);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

<?php

namespace App\Controller\AjoutSlug;

use App\Repository\AbsenceTeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugAbsenceTeacherController extends AbstractController
{
    #[Route('/ajout-slug-absence-teacher', name: 'ajout_slug_absenceTeacher')]
    public function index(EntityManagerInterface $em, AbsenceTeacherRepository $absenceTeacherRepository): Response
    {
        $absenceTeachers = $absenceTeacherRepository->findAll();

        foreach ($absenceTeachers as $absenceTeacher) 
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
            $dernierAbsenceTeacher = $absenceTeacherRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierAbsenceTeacher) 
            {
                $id = $dernierAbsenceTeacher[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $absenceTeacher->setSlug($slug.$id);
            $em->persist($absenceTeacher);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

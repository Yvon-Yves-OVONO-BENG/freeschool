<?php

namespace App\Controller\AjoutSlug;

use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugTeacherController extends AbstractController
{
    #[Route('/ajout-slug-teacher', name: 'ajout_slug_teacher')]
    public function index(EntityManagerInterface $em, TeacherRepository $teacherRepository): Response
    {
        $teachers = $teacherRepository->findAll();

        foreach ($teachers as $teacher) 
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
            $dernierTeacher = $teacherRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierTeacher) 
            {
                $id = $dernierTeacher[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $teacher->setSlug($slug.$id);
            $em->persist($teacher);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

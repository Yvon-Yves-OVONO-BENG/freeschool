<?php

namespace App\Controller\AjoutSlug;

use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugDepartmentController extends AbstractController
{
    #[Route('/ajout-slug-Department', name: 'ajout_slug_Department')]
    public function index(EntityManagerInterface $em, DepartmentRepository $departmentRepository): Response
    {
        $departments = $departmentRepository->findAll();

        foreach ($departments as $department) 
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
            $dernierDepartment = $departmentRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDepartment) 
            {
                $id = $dernierDepartment[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $department->setSlug($slug.$id);
            $em->persist($department);
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

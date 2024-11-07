<?php

namespace App\Controller\AjoutSlug;

use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\StudentRepository;
use App\Service\QrcodeService;
use App\Service\StrService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ajoutSlug')]
class AjoutSlugStudentController extends AbstractController
{  
    #[Route('/ajout-slug-student', name: 'ajout_slug_student')]
    public function index(StrService $strService, QrcodeService $qrcodeService, SchoolRepository $schoolRepository, SchoolYearRepository $schoolYearRepository, Request $request, EntityManagerInterface $em, StudentRepository $studentRepository): Response
    {
        $mySession = $request->getSession();

        $students = $studentRepository->findAll();

        $schoolYear = $schoolYearRepository->find($mySession->get('schoolYear')->getId());
        
        $school = $schoolRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        
        foreach ($students as $student) 
        {
            if(!$student->getSlug())
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
                $dernierStudent = $studentRepository->findBy([],['id' => 'DESC'],1,0);

                /////je récupère l'id du sernier utilisateur
                
                if ($dernierStudent) 
                {
                    $id = $dernierStudent[0]->getId();
                } 
                else 
                {
                    $id = 1;
                }



                $student->setSlug($slug.$id);
                $em->persist($student);
            }
            
            
        }

        $em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

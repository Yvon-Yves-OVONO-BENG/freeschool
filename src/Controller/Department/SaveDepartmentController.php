<?php

namespace App\Controller\Department;

use App\Entity\Department;
use App\Form\DepartmentType;
use App\Repository\DepartmentRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Service\SchoolYearService;
use App\Service\StrService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/department")]
class SaveDepartmentController extends AbstractController
{
    public function __construct(protected DepartmentRepository $departmentRepository, protected SchoolYearRepository $schoolYearRepository, protected StrService $strService, protected EntityManagerInterface $em, protected SchoolYearService $schoolYearService, protected TranslatorInterface $translator, protected SchoolRepository $schoolRepository, protected SubSystemRepository $subSystemRepository)
    {
    }

    #[Route("/saveDepartment", name:"department_saveDepartment")]
    public function saveDepartment(Request $request): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $subSyste = $this->subSystemRepository->find($subSystem->getId());
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        // on ecupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
        
        $department = new Department();       
        
        $form = $this->createForm(DepartmentType::class, $department);

        $form->handleRequest($request);
        $slug = 0;
        if ($form->isSubmitted() && $form->isValid()) 
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
            $dernierDepartment =  $this->departmentRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDepartment) 
            {
                $id = $dernierDepartment[0]->getId();
            } 
            else 
            {
                $id = 1;
            }
            
            // on met le department en majuscule
            $department->setDepartment($this->strService->strToUpper($department->getDepartment()))
                        ->setSchoolYear($schoolYear)
                        ->setSubSystem($subSyste)
                        ->setSlug($slug.$id)
            ;

            // On ajoute dans la BD
            $this->em->persist($department);
            $this->em->flush(); 

            $this->addFlash('info',  $this->translator->trans('Department saved with success !'));

            $mySession->set('ajout', 1);

            $department = new Department();
            $form = $this->createForm(DepartmentType::class, $department);
            
        }

        $departments = $this->departmentRepository->findToDisplay($schoolYear, $subSystem);
        $slug = 0;
        return $this->render('department/saveDepartment.html.twig', [
            'formDepartment' => $form->createView(),
            'departments' => $departments,
            'slug' => $slug,
            'school' => $school,
            ]);
    }
}

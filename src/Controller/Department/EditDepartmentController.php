<?php

namespace App\Controller\Department;

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
class EditDepartmentController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository,
        protected DepartmentRepository $departmentRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/editDepartment/{slug}", name:"department_editDepartment")]
    public function editDepartment(Request $request, string $slug): Response
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
        
        $subSyste = $this->subSystemRepository->find($subSystem->getId());
       
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        // on ecupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
        
        $department = $this->departmentRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(DepartmentType::class, $department);

        // on set le schoolYear pour qu'il soit pris en compte dans la validation du formulaire
        $department->setSchoolYear($schoolYear); 

        $form->handleRequest($request);

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
            ->setSubSystem($subSyste)
            ->setSlug($slug.$id)
            ;

            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Department updated successfully'));

            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des matières
            return $this->redirectToRoute('department_displayDepartment',
            [ 'm' => 1]);

            
        }
        
        $departments = $this->departmentRepository->findToDisplay($schoolYear, $subSystem);

        return $this->render('department/saveDepartment.html.twig', [
            'formDepartment' => $form->createView(),
            'slug' => $slug,
            'school' => $school,
            'departments' => $departments
            ]);
    }

}

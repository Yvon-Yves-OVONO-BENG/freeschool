<?php

namespace App\Controller\Department;

use App\Repository\DepartmentRepository;
use App\Service\SchoolYearService;
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
class DeleteDepartmentController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService, 
        protected DepartmentRepository $departmentRepository, 
        )
    {}

    #[Route("/deleteDepartment/{slug}", name:"department_deleteDepartment")]
    public function deleteDepartment(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        

        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $department = $this->departmentRepository->findOneBySlug([
            'slug' => $slug
        ]);

        if(count($department->getSubjects()))
        {
            $this->addFlash('info', $this->translator->trans('Impossible to delete a department with subjects'));
            return $this->redirectToRoute('department_displayDepartment',
            [ 's' => 1]);
        }else 
        {
            $this->em->remove($department);
            $this->em->flush();
            
            $this->addFlash('info',  $this->translator->trans('Department deleted with success !'));
            
            $mySession->set('suppression', 1);
            
            return $this->redirectToRoute('department_displayDepartment',
            [ 's' => 1]);
        }

    }
}

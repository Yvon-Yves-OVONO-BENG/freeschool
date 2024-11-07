<?php

namespace App\Controller\SuperAdmin;

use App\Form\UserType;
use App\Service\StrService;
use App\Repository\UserRepository;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/super-admin")]
class SuperAdminEditUserController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em, 
        protected UserRepository $userRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        )
    {}

    #[Route("/edit-user/{slug}", name:"super_admin_editUser")]
    public function superAdminEditUser(Request $request, string $slug = ""): Response
    {   
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $user = $this->userRepository->findOneBySlug(['slug' => $slug]);

        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) 
        {
            $user->setRoles([$request->request->get('role')]);
            $user->setFullName($this->strService->strToUpper($user->getFullName()));

            $this->em->flush(); // On modifie
            $this->addFlash('info',  $this->translator->trans('User updated successfully'));

            $mySession->set('miseAjour', 1);
            // On se redirige sur la page d'affichage des classes
            return $this->redirectToRoute('super_admin_displayUser');

        }

        return $this->render('super_admin/saveUser.html.twig', [
            'formUser' => $form->createView(),
            'slug' => $slug,
            'school' => $school,
            ]);
    }
}
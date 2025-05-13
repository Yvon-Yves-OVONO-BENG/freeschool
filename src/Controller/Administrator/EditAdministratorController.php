<?php

namespace App\Controller\Administrator;

use App\Form\AdministratorType;
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
#[Route('/administrators')]
class EditAdministratorController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected TranslatorInterface $translator,
        protected SchoolRepository $schoolRepository, 
    )
    {}
    
    #[Route('/edit-administrator/{slug}', name: 'edit_administrator')]
    public function aditAdministrator(Request $request, string $slug): Response
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

        $administrator = $this->userRepository->findOneBy(['slug' => $slug ]);

        $form = $this->createForm(AdministratorType::class, $administrator);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $administrator->setUsername($administrator->getFullName());

            $this->em->persist($administrator);
            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Administrator updated with success !'));
                
            $mySession->set('miseAjour', 1);

            return $this->redirectToRoute('list_administrators', ['m' => 1 ]);
            
        }

        return $this->render('administrator/addAdministrator.html.twig', [
            'id' => 0,
            'school' => $school,
            'formAdmin' => $form->createView(),
        ]);
    }
}

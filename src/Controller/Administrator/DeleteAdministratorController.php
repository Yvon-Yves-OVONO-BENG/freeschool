<?php

namespace App\Controller\Administrator;

use App\Entity\ConstantsClass;
use App\Repository\UserRepository;
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
class DeleteAdministratorController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected TranslatorInterface $translator,
    )
    {}

    #[Route('/delete-administrator/{slug}', name: 'delete_administrator')]
    public function DeleteAdministrator(Request $request, string $slug): Response
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

        $administrator = $this->userRepository->findOneBy(['slug' => $slug ]);
        
        $administrator->setSupprime(1);

        $this->em->persist($administrator);
        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Administrator deleted with success !'));
            
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('list_administrators', ['s' => 1 ]);
            
        
    }
}

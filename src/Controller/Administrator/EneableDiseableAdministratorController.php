<?php

namespace App\Controller\Administrator;

use App\Entity\ConstantsClass;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/administrators')]
class EneableDiseableAdministratorController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
    )
    {}

    #[Route('/eneable-diseable-administrator', name: 'eneable_diseable_administrator')]
    public function eneableDiseableAdministrator(Request $request): JsonResponse
    {
        # je récupère ma session
        $maSession = $request->getSession();
        
        #mes variables témoin pour afficher les sweetAlert
        $maSession->set('ajout', null);
        $maSession->set('suppression', null);
        
        $utilisateurId = (int)$request->request->get('utilisateur_id');
        
        $utilisateur = $this->userRepository->find($utilisateurId);
        
        if ($utilisateur->isBloque() == 1) 
        {
            $utilisateur->setBloque(0);
        } 
        else 
        {
            $utilisateur->setBloque(1);
        }
        
        #je prépare ma requête à la suppression
        $this->em->persist($utilisateur);

        #j'exécute ma requête
        $this->em->flush();

        #je retourne à la liste des catégories
        return new JsonResponse(['success' => true, 'etat' => $utilisateur->getRoles()[0] ]);
    }
}

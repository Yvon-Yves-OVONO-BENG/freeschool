<?php

namespace App\Controller\Profil;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


#[Route('profil')]
class ReinitialiserMotDePasseController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected TranslatorInterface $translator,
        private ParameterBagInterface $parametres,
        protected UserPasswordHasherInterface $userPasswordHasher,
    )
    {}

    #[Route('/reinitialiser-mot-de-passe/{idUser}', name: 'reinitialiser_mot_de_passe')]
    public function reinitialiserMotDePasseOublie(Request $request, $idUser): Response
    {
        # je récupère ma session
        $mySession = $request->getSession();
        
        #mes variables témoin pour afficher les sweetAlert
        $mySession->set('ajout', null);
        $mySession->set('suppression', null);

        $user = $this->userRepository->find($idUser);

        if ($request->request->has('envoyer') && $request->request->has('motDePasse')) 
        {
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $request->request->get('motDePasse')
                )
            );

            $this->em->persist($user);
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans("success "));

            return $this->redirectToRoute('home_mainMenu');
            
        }


        return $this->render('profil/reinitialiser_mot_de_passe.html.twig', [
            'licence' => 1,
            'motDePasse' => 0,
            'idUser' => $idUser,
        ]);
    }

}

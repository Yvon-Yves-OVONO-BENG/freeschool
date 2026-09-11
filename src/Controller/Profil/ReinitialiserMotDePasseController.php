<?php

namespace App\Controller\Profil;

use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('profil')]
class ReinitialiserMotDePasseController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private TranslatorInterface $translator,
        private UserPasswordHasherInterface $userPasswordHasher,
    ) {
    }

    #[Route('/reinitialiser-mot-de-passe/{idUser}', name: 'reinitialiser_mot_de_passe')]
    public function reinitialiserMotDePasseOublie(Request $request, int $idUser): Response
    {
        $session = $request->getSession();
        $reset = $session->get('password_reset');

        if (!$reset || empty($reset['verified']) || (int) ($reset['user_id'] ?? 0) !== $idUser) {
            $this->addFlash('warning', $this->translator->trans('Accès refusé. Veuillez reprendre la procédure de réinitialisation.'));
            return $this->redirectToRoute('mot_de_passe_oublie');
        }

        $user = $this->userRepository->find($idUser);
        if (!$user) {
            $session->remove('password_reset');
            $this->addFlash('warning', $this->translator->trans('Utilisateur introuvable.'));
            return $this->redirectToRoute('mot_de_passe_oublie');
        }

        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($user->getPassword() !== $user->getConfirmPassword()) {
                $this->addFlash('warning', $this->translator->trans('Les mots de passe ne correspondent pas.'));
            } else {
                $user->setPassword(
                    $this->userPasswordHasher->hashPassword($user, $user->getPassword())
                );
                $user->setConfirmPassword(null);

                $this->em->flush();
                $session->remove('password_reset');

                $this->addFlash('info', $this->translator->trans('Votre mot de passe a été réinitialisé avec succès. Vous pouvez maintenant vous connecter.'));
                return $this->redirectToRoute('login');
            }
        }

        return $this->render('profil/reinitialiser_mot_de_passe.html.twig', [
            'userForm' => $form->createView(),
            'fullName' => $user->getFullName(),
        ]);
    }
}

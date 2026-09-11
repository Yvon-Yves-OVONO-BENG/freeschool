<?php

namespace App\Controller\Profil;

use App\Form\RecoveryEmailType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
class EditRecoveryEmailController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/my-recovery-email', name: 'edit_my_recovery_email')]
    public function __invoke(Request $request): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            return $this->redirectToRoute('login');
        }

        $user = $this->userRepository->find($currentUser->getId());
        $form = $this->createForm(RecoveryEmailType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Recovery email saved successfully.'));

            return $this->redirectToRoute('home_dashboard', ['m' => 1]);
        }

        return $this->render('profil/edit_recovery_email.html.twig', [
            'userForm' => $form->createView(),
        ]);
    }
}

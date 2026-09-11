<?php

namespace App\Controller\Profil;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('profil')]
class VerificationCodeReinitialisationController extends AbstractController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }

    #[Route('/verification-code-reinitialisation', name: 'verification_code_reinitialisation')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $reset = $session->get('password_reset');

        if (!$reset || !isset($reset['user_id'], $reset['code'], $reset['expires_at'])) {
            return $this->redirectToRoute('mot_de_passe_oublie');
        }

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code'));

            if (time() > (int) $reset['expires_at']) {
                $session->remove('password_reset');
                $this->addFlash('warning', $this->translator->trans('Le code a expiré. Veuillez recommencer la procédure.'));
                return $this->redirectToRoute('mot_de_passe_oublie');
            }

            if ($code !== (string) $reset['code']) {
                $this->addFlash('warning', $this->translator->trans('Code invalide. Veuillez réessayer.'));
                return $this->redirectToRoute('verification_code_reinitialisation');
            }

            $reset['verified'] = true;
            $session->set('password_reset', $reset);

            return $this->redirectToRoute('reinitialiser_mot_de_passe', ['idUser' => $reset['user_id']]);
        }

        return $this->render('profil/verification_code_reinitialisation.html.twig', [
            'maskedEmail' => $reset['masked_email'] ?? '',
            'expiresAt' => $reset['expires_at'] ?? null,
        ]);
    }
}

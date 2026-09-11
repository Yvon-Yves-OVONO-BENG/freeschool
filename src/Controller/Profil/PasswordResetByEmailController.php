<?php

namespace App\Controller\Profil;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class PasswordResetByEmailController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private UserPasswordHasherInterface $passwordHasher,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/password-reset/request', name: 'password_reset_request')]
    public function request(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $email = trim((string) $request->request->get('email', ''));
            $user = $email !== '' ? $this->userRepository->findOneBy(['email' => $email]) : null;

            if (!$user) {
                $this->addFlash('danger', $this->translator->trans('No account is linked to this email address.'));
            } else {
                $code = (string) random_int(10000, 99999);
                $user
                    ->setResetPasswordCode($code)
                    ->setResetPasswordCodeExpiresAt(new \DateTimeImmutable('+15 minutes'))
                ;

                $this->em->flush();

                $message = (new Email())
                    ->from($_ENV['MAILER_FROM'] ?? 'no-reply@freeschool.local')
                    ->to($user->getEmail())
                    ->subject($this->translator->trans('Your Freeschool password reset code'))
                    ->html(sprintf(
                        '<div style="font-family:Arial,sans-serif;line-height:1.7;color:#0f172a"><h2 style="margin-bottom:10px;">Freeschool</h2><p>Bonjour %s,</p><p>Voici votre code de réinitialisation :</p><p style="font-size:30px;font-weight:700;letter-spacing:6px;color:#2563eb;">%s</p><p>Ce code expire dans 15 minutes.</p><p>Si vous n\'êtes pas à l\'origine de cette demande, ignorez simplement cet e-mail.</p></div>',
                        htmlspecialchars((string) $user->getFullName(), ENT_QUOTES),
                        $code
                    ));

                $this->mailer->send($message);

                $request->getSession()->set('password_reset_user_id', $user->getId());
                $request->getSession()->set('password_reset_verified', false);

                return $this->redirectToRoute('password_reset_verify_code');
            }
        }

        return $this->render('profil/password_reset_request.html.twig');
    }

    #[Route('/password-reset/verify', name: 'password_reset_verify_code')]
    public function verifyCode(Request $request): Response
    {
        $user = $this->getResetUser($request);

        if (!$user) {
            return $this->redirectToRoute('password_reset_request');
        }

        if ($request->isMethod('POST')) {
            $code = trim((string) $request->request->get('code', ''));
            $expiresAt = $user->getResetPasswordCodeExpiresAt();

            if ($user->getResetPasswordCode() !== $code) {
                $this->addFlash('danger', $this->translator->trans('Invalid code.'));
            } elseif (!$expiresAt || $expiresAt < new \DateTimeImmutable()) {
                $this->addFlash('danger', $this->translator->trans('This code has expired. Please request a new one.'));
            } else {
                $request->getSession()->set('password_reset_verified', true);

                return $this->redirectToRoute('password_reset_new_password');
            }
        }

        return $this->render('profil/password_reset_verify_code.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }

    #[Route('/password-reset/new-password', name: 'password_reset_new_password')]
    public function newPassword(Request $request): Response
    {
        $user = $this->getResetUser($request);
        $verified = (bool) $request->getSession()->get('password_reset_verified', false);

        if (!$user || !$verified) {
            return $this->redirectToRoute('password_reset_request');
        }

        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password', '');
            $confirmPassword = (string) $request->request->get('confirm_password', '');

            if (mb_strlen($password) < 6) {
                $this->addFlash('danger', $this->translator->trans('The password must contain at least 6 characters.'));
            } elseif ($password !== $confirmPassword) {
                $this->addFlash('danger', $this->translator->trans('The passwords do not match.'));
            } else {
                $user->setPassword($this->passwordHasher->hashPassword($user, $password));
                $user->clearResetPasswordRequest();
                $this->em->flush();

                $request->getSession()->remove('password_reset_user_id');
                $request->getSession()->remove('password_reset_verified');

                $this->addFlash('info', $this->translator->trans('Password reset successfully. You can now log in.'));

                return $this->redirectToRoute('login');
            }
        }

        return $this->render('profil/password_reset_new_password.html.twig', [
            'email' => $user->getEmail(),
        ]);
    }

    private function getResetUser(Request $request)
    {
        $userId = $request->getSession()->get('password_reset_user_id');

        if (!$userId) {
            return null;
        }

        return $this->userRepository->find($userId);
    }
}

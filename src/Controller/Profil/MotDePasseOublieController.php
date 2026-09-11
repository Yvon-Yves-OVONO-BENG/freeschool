<?php

namespace App\Controller\Profil;

use App\Repository\UserRepository;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('profil')]
class MotDePasseOublieController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private MailerInterface $mailer,
        private TranslatorInterface $translator,
    ) {
    }

    #[Route('/mot-de-passe-oublie', name: 'mot_de_passe_oublie')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $session->remove('password_reset');

        if ($request->isMethod('POST')) {
            $userId = (int) $request->request->get('userId');
            $user = $this->userRepository->find($userId);

            if (!$user) {
                $this->addFlash('warning', $this->translator->trans('Utilisateur introuvable.'));
                return $this->redirectToRoute('mot_de_passe_oublie');
            }

            if (!$user->getEmail()) {
                $this->addFlash('warning', $this->translator->trans('Cet utilisateur ne possède pas d’email renseigné. Veuillez contacter l’administration.'));
                return $this->redirectToRoute('mot_de_passe_oublie');
            }

            $code = (string) random_int(10000, 99999);
            $session->set('password_reset', [
                'user_id' => $user->getId(),
                'code' => $code,
                'expires_at' => (new \DateTimeImmutable('+15 minutes'))->getTimestamp(),
                'masked_email' => $this->maskEmail($user->getEmail()),
            ]);

            $email = (new TemplatedEmail())
                ->to($user->getEmail())
                ->subject('Code de réinitialisation FreeSchool')
                ->htmlTemplate('emails/password_reset_code.html.twig')
                ->context([
                    'fullName' => $user->getFullName(),
                    'code' => $code,
                    'expiration' => 15,
                ]);

            $this->mailer->send($email);
            $this->addFlash('info', $this->translator->trans('Un code de réinitialisation a été envoyé à votre adresse email.'));

            return $this->redirectToRoute('verification_code_reinitialisation');
        }

        return $this->render('profil/motDePasseOublie.html.twig', [
            'users' => $this->userRepository->findBy([], ['fullName' => 'ASC']),
        ]);
    }

    private function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) {
            return $email;
        }

        [$name, $domain] = $parts;
        $visible = mb_substr($name, 0, 2);
        $masked = $visible . str_repeat('*', max(2, mb_strlen($name) - 2));

        return $masked . '@' . $domain;
    }
}

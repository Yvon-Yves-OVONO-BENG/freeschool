<?php

namespace App\Security;

use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Repository\VerrouRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppCustomAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'login';

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private SchoolYearRepository $schoolYearRepository,
        private SubSystemRepository $subSystemRepository,
        private SchoolRepository $schoolRepository,
        private VerrouRepository $verrouRepository,
    )
    {
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && self::LOGIN_ROUTE === $request->attributes->get('_route');
    }

    public function authenticate(Request $request): Passport
    {
        $username = trim((string) $request->request->get('username', ''));
        $schoolYearId = $request->request->get('schoolYear');
        $subSystemId = $request->request->get('subSystem');

        $request->getSession()->set(Security::LAST_USERNAME, $username);

        if (!$schoolYearId || !$subSystemId) {
            throw new CustomUserMessageAuthenticationException('Veuillez choisir le sous-système et l’année scolaire.');
        }

        $schoolYear = $this->schoolYearRepository->find($schoolYearId);
        $subSystem = $this->subSystemRepository->find($subSystemId);

        if (!$schoolYear || !$subSystem) {
            throw new CustomUserMessageAuthenticationException('Année scolaire ou sous-système invalide.');
        }

        $session = $request->getSession();
        $session->set('schoolYear', $schoolYear);
        $session->set('subSystem', $subSystem);
        $session->set('school', $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]));
        $session->set('verrou', $this->verrouRepository->findOneBy(['schoolYear' => $schoolYear]));
        $session->set('ajout', null);
        $session->set('suppression', null);
        $session->set('miseAjour', null);
        $session->set('saisiNotes', null);

        return new Passport(
            new UserBadge($username),
            new PasswordCredentials($request->request->get('password', '')),
            [
                new CsrfTokenBadge('authenticate', $request->request->get('_csrf_token')),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        return new RedirectResponse($this->urlGenerator->generate('home_dashboard'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}

<?php

namespace App\Controller;

use App\Repository\NextYearRepository;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route(path: 'security')]
class SecurityController extends AbstractController
{
    public function __construct(
        protected UserRepository $userRepository,
        protected SchoolRepository $schoolRepository,
        protected NextYearRepository $nextYearRepository,
        protected SchoolYearRepository $schoolYearRepository,
        protected SubSystemRepository $subSystemRepository,
    )
    {}

    #[Route(path: '/login/{duty}', name: 'login', defaults: ['duty' => ''])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, string $duty = ''): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('home_dashboard');
        }

        $nextYears = $this->nextYearRepository->findAll();
        $nextYear = $nextYears[0] ?? null;
        $schoolYears = $nextYear ? $this->schoolYearRepository->findSchoolYears($nextYear) : $this->schoolYearRepository->findBy([], ['schoolYear' => 'DESC']);
        $subSystems = $this->subSystemRepository->findAll();

        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('home/chooseSchoolYear.html.twig', [
            'schoolYears' => $schoolYears,
            'subSystems' => $subSystems,
            'users' => $this->userRepository->findAllForLoginSelector(),
            'last_username' => $lastUsername,
            'error' => $error,
            'home' => true,
            'motDePasse' => 0,
            'chooseSchoolYear' => 'choose',
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

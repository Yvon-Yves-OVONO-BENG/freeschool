<?php

namespace App\Controller;

use App\Repository\SchoolRepository;
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
        protected SchoolRepository $schoolRepository
        )
    {}

    #[Route(path: '/login/{duty}', name: 'login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, string $duty = ''): Response
    {
        #si l'utilisateur est connecté
        if ($this->getUser()) 
        {
            return $this->redirectToRoute('home_dashboard');
        }

        $mySession = $request->getSession();
        
        if($mySession)
        {   

            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

            $school = $this->schoolRepository->findBy([
                "schoolYear" => $schoolYear
            ]);
        }else 
        {
            return $this->redirectToRoute('app_logout');
        }

        if ($this->getUser()) 
        {
            return $this->redirectToRoute('app_logout');
        }

        if (!$schoolYear) 
        {
            return $this->redirectToRoute('app_logout');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();
        
        $users = $this->userRepository->findUserByUserType($duty, $schoolYear, $subSystem);

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername, 
            'error' => $error,
            'users' => $users,
            'school' => $school,
            'home' => true,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}

<?php

namespace App\Controller\Home;

use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use App\Repository\VerrouRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class MainMenuController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,  
        protected VerrouRepository $verrouRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route('/home-mainMenu', name: 'home_mainMenu')]
    public function mainMenu(Request $request): Response
    {
        // L'ancien menu de choix du type utilisateur est supprimé.
        // Toute connexion passe désormais par l'écran unique : sous-système, année, personnel, mot de passe.
        if ($this->getUser()) {
            return $this->redirectToRoute('home_dashboard');
        }

        return $this->redirectToRoute('home_chooseSchoolYear');
    }

}

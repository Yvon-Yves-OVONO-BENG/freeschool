<?php

namespace App\Controller\Home;

use App\Repository\NextYearRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class ChooseSchoolYearController extends AbstractController
{
    public function __construct(
        protected NextYearRepository $nextYearRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route('/', name: 'home_chooseSchoolYear')]
    public function chooseSchoolYear(Request $request): Response
    {
        #je nettoie ma session
        $request->getSession()->clear();

        #je récupère mes années suivantes
        $nextYears = $this->nextYearRepository->findAll();

        #je récupère la 1ère année
        $nextYear = $nextYears[0];

        #je récupère les années inférieures àl'année suivante
        $schoolYears = $this->schoolYearRepository->findSchoolYears($nextYear);

        #je récupère les sous sysyèmes
        $subSystems = $this->subSystemRepository->findAll();

        #je récupère ma session
        $mySession = $request->getSession();

        #j'initialise mes variables de session
        $mySession->set('school', null);
        $mySession->set('schoolYear',null);
        $mySession->set('subSystem', null);
        $mySession->set('verrou', null);

        #mon rendu
        return $this->render('home/chooseSchoolYear.html.twig', [
            'schoolYears' => $schoolYears,
            'subSystems' => $subSystems,
            'home' => true,
            'motDePasse' => 0,
            'chooseSchoolYear' => 'choose'
        ]);
    }

}

<?php

namespace App\Controller\Importation;

use App\Repository\ClassroomRepository;
use App\Service\ExcelImporter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
class AfficherImportationController extends AbstractController
{
    public function __construct(
        protected ClassroomRepository $classroomRepository)
    {}
    
    #[Route('/importation-afficher', name: 'importation_afficher')]
    public function importationAfficher(Request $request): Response
    {
        $mySession = $request->getSession();

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');
        }
        else 
        {
            return $this->redirectToRoute("app_logout");
        }
    
        // On recupère les classe
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        return $this->render('importation/importationEleve.html.twig', [
            'classrooms' => $classrooms,
        ]);
    }
}

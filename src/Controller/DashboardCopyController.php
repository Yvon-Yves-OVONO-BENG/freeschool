<?php

namespace App\Controller;

use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DashboardCopyController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository
        )
    {}

    #[Route('/home-dashboard-copy', name: 'home_dashboard_copy')]
    public function dashboard(Request $request): Response
    {
        # je récupère ma session
        $mySession = $request->getSession();

        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);  

        return $this->render('home/dashboard.html.twig', [
            'school' => $school
        ]);
    }
}

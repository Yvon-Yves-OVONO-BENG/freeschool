<?php

namespace App\Controller\SuperAdmin;

use App\Repository\SchoolRepository;
use App\Repository\NextYearRepository;
use App\Repository\SchoolYearRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/super-admin")]
class SuperAdminChooseSchoolYearController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected NextYearRepository $nextYearRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/choose-schoolYear", name:"super_admin_chooseSchoolYear")]
    public function superAdminChooseSchoolYear(Request $request): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $nextYears = $this->nextYearRepository->findAll();
        $nextYear = $nextYears[0];

        $schoolYears = $this->schoolYearRepository->findSchoolYears($nextYear);

        return $this->render('super_admin/chooseSchoolYear.html.twig', [
            'schoolYears' => $schoolYears,
            'school' => $school,
        ]);
    }

}
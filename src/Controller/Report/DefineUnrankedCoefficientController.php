<?php

namespace App\Controller\Report;

use App\Service\ReportService;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class DefineUnrankedCoefficientController extends AbstractController
{
    public function __construct(
        protected ReportService $reportService, 
        protected SchoolRepository $schoolRepository,)
    {}

    #[Route("/defineUnrankedCoefficient", name:"report_defineUnrankedCoefficient")]
    public function defineUnrankedCoefficient(Request $request)
    {
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

        // $unrankedCoefficientCycle2 = $this->unrankedCoefficientRepository->findForClassroomCycle2($schoolYear);

        $unrankedCoefficientCycle = $this->reportService->getUnrankedCoefficientCycle($schoolYear);
        $levelsName = $this->reportService->getLevelsName();

        return $this->render('report/defineUnrankedCoefficient.html.twig', [
            'school' => $school,
            'levelsName' => $levelsName,
            'unrankedCoefficientCycle' => $unrankedCoefficientCycle,
        ]);
    }

}

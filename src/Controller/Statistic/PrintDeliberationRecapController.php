<?php

namespace App\Controller\Statistic;

use App\Service\StatisticService;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class PrintDeliberationRecapController extends AbstractController
{
    public function __construct(
        protected StatisticService $statisticService,  
        protected SchoolRepository $schoolRepository,
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/printDeliberationRecap", name:"statistic_printDeliberationRecap")]
    public function printDeliberationRecap(Request $request): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        $pdf = $this->statisticService->printDeliberationRecapList($classrooms, $school, $schoolYear, $subSystem);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Deliberation recapitulation"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Récapitulatif des délibérations"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
       
    }   

}

<?php

namespace App\Controller\Statistic;

use App\Service\ReportService;
use App\Service\GeneralService;
use App\Service\StatisticService;
use App\Repository\TermRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class PrintRateOfPresencePerClassController extends AbstractController
{
    public function __construct(
        protected ReportService $reportService, 
        protected TermRepository $termRepository, 
        protected GeneralService $generalService, 
        protected SchoolRepository $schoolRepository,
        protected StatisticService $statisticService, 
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    #[Route("/printRateOfPresencePerClass", name:"statistic_printRateOfPresencePerClass")]
    public function printRateOfPresencePerClass(Request $request): Response
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

        // dd($francophone);
        // on recupère toutes les classes
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        // on recupère le trimestre choisi
        $term = $this->termRepository->find($request->request->get('term'));

        $pdf = $this->statisticService->printRateOfPresencePerClass($term, $classrooms, $schoolYear, $school, $subSystem);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Rate of presence per classe of term ".$term->getTerm() ), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } else {
            return new Response($pdf->Output(utf8_decode("Fiche de statistiques par classe du trimestre ".$term->getTerm()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
    }

}
<?php

namespace App\Controller\Verrou;

use App\Repository\TermRepository;
use App\Repository\VerrouReportRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */ 

#[Route("/verrou")]
class LockReportPrintingController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected TranslatorInterface $translator, 
        protected VerrouReportRepository $verrouReportRepository, 
        )
    {}
    
    #[Route("/lockReportPrinting/{idT<[0-9]+>}/{lock<[0-1]{1}>}", name:"verrou_lockReportPrinting")]
    public function lockReportPrinting(Request $request, int $idT, int $lock): Response
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
        
        $selectedTerm = $this->termRepository->find($idT);

        $verrouReport = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $selectedTerm
        ]);

        if($lock == 1)
        {
            $verrouReport->setVerrouReport(true);
    
            $this->addFlash('info', $this->translator->trans('All printing of report of the selected term  is now locked'));
            $mySession->set('miseAjour', 1);
        }else
        {
            $verrouReport->setVerrouReport(false);
    
            $this->addFlash('info', $this->translator->trans('All printing of report of the selected term is now possible'));
            $mySession->set('miseAjour', 1);
        }
        $this->em->flush();

        return $this->redirectToRoute('verrou_displayLockOption', ['m' => 1]);
    }
}
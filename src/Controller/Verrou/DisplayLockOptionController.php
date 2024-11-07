<?php

namespace App\Controller\Verrou;

use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use App\Repository\VerrouRepository;
use App\Repository\SequenceRepository;
use App\Repository\VerrouReportRepository;
use App\Repository\VerrouSequenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */ 

#[Route("/verrou")]
class DisplayLockOptionController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected VerrouRepository $verrouRepository, 
        protected SchoolRepository $schoolRepository,
        protected SequenceRepository $sequenceRepository, 
        protected VerrouReportRepository $verrouReportRepository, 
        protected VerrouSequenceRepository $verrouSequenceRepository, 
        )
    {}
    
    #[Route("/displayLockOption/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}", name:"verrou_displayLockOption")]
    public function displayLockOption(Request $request, int $a = 0, int $m = 0, int $s = 0): Response
    {
        $mySession = $request->getSession();
        
        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);

        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
            $mySession->set('saisiNotes', null);
            
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
            
        }
        
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $schoolYear = $mySession->get('schoolYear');

        // on recupère les trois trimestre pour recupérer les verrouReport liés aux trimestres
        $term1 = $this->termRepository->findOneByTerm(1);
        $term2 = $this->termRepository->findOneByTerm(2);
        $term3 = $this->termRepository->findOneByTerm(3);
        $term0 = $this->termRepository->findOneByTerm(0);

        $verrouReportTerm1 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term1
        ]);
        $verrouReportTerm2 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term2
        ]);
        $verrouReportTerm3 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term3
        ]);
        $verrouReportTerm0 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term0
        ]);

        // On recupère le verrou lié à l'année
        $verrou = $this->verrouRepository->findOneBySchoolYear($schoolYear);

        // on recupère les six sequences pour recupérer les verrouReport liés aux trimestres
        $sequence1 = $this->sequenceRepository->findOneBySequence(1);
        $sequence2 = $this->sequenceRepository->findOneBySequence(2);
        $sequence3 = $this->sequenceRepository->findOneBySequence(3);
        $sequence4 = $this->sequenceRepository->findOneBySequence(4);
        $sequence5 = $this->sequenceRepository->findOneBySequence(5);
        $sequence6 = $this->sequenceRepository->findOneBySequence(6);


        $verrouSequence1 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence1
        ]);
        $verrouSequence2 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence2
        ]);
        $verrouSequence3 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence3
        ]);
        $verrouSequence4 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence4
        ]);
        $verrouSequence5 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence5
        ]);
        $verrouSequence6 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence6
        ]);


        return $this->render('verrou/displayLockOption.html.twig', [
            'verrou' => $verrou,
            'verrouReportTerm1' => $verrouReportTerm1,
            'verrouReportTerm2' => $verrouReportTerm2,
            'verrouReportTerm3' => $verrouReportTerm3,
            'verrouReportTerm0' => $verrouReportTerm0,
            'term1' => $term1,
            'term2' => $term2,
            'term3' => $term3,
            'term0' => $term0,
            'verrouSequence1' => $verrouSequence1,
            'verrouSequence2' => $verrouSequence2,
            'verrouSequence3' => $verrouSequence3,
            'verrouSequence4' => $verrouSequence4,
            'verrouSequence5' => $verrouSequence5,
            'verrouSequence6' => $verrouSequence6,
            'sequence1' => $sequence1,
            'sequence2' => $sequence2,
            'sequence3' => $sequence3,
            'sequence4' => $sequence4,
            'sequence5' => $sequence5,
            'sequence6' => $sequence6,
            'school' => $school,
        ]);
    }
}

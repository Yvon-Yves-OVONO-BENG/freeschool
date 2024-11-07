<?php

namespace App\Controller\Statistic;

use App\Entity\ConstantsClass;
use App\Service\SequenceService;
use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use App\Repository\SubjectRepository;
use App\Repository\SequenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class PedagogicalSummarySheetController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected SequenceService $sequenceService, 
        protected SchoolRepository $schoolRepository,
        protected SubjectRepository $subjectRepository,  
        protected SequenceRepository $sequenceRepository, 
        )
    {}


    #[Route("/pedagogicalSummarySheet", name:"pedagogical_summary_sheet")]
    public function pedagogicalSummarySheet(Request $request): Response
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

        // on recupèere toutes les classes
        $subjects = $this->subjectRepository->findBy(['schoolYear' => $schoolYear], ['subject' => 'ASC']);
        $terms = $this->termRepository->findBy([], ['term' => 'ASC']);

        $sequences = $this->sequenceRepository->findBy([], ['sequence' => 'ASC']);

        $sequences = $this->sequenceService->removeSequence6($sequences, $schoolYear);
       
        return $this->render('statistic/pedagogicalSummarySheet.html.twig', [
            'subjects' => $subjects,
            'terms' => $terms,
            'sequences' => $sequences,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
            'school' => $school,
        ]);
    }
    

}

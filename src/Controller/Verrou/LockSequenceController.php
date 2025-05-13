<?php

namespace App\Controller\Verrou;

use App\Repository\SequenceRepository;
use App\Repository\VerrouSequenceRepository;
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
class LockSequenceController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SequenceRepository $sequenceRepository,
        protected VerrouSequenceRepository $verrouSequenceRepository, 
        )
    {}
    
    #[Route("/lockSequence/{idS<[0-9]+>}/{lock<[0-1]{1}>}", name:"verrou_lockSequence")]
    public function lockSequence(Request $request, int $idS, int $lock): Response
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
        
        
        $selectedSequence = $this->sequenceRepository->find($idS);
        
        $verrouSequence = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $selectedSequence
        ]);
        
        if($lock == 1)
        {
            $verrouSequence->setVerrouSequence(true);
    
            $this->addFlash('info', $this->translator->trans('Sequence lock with success !'));
            $mySession->set('miseAjour', 1);

        }else
        {
            $verrouSequence->setVerrouSequence(false);
    
            $this->addFlash('info', $this->translator->trans('Sequence unlock with success !'));
            $mySession->set('miseAjour',1);
        }

        $this->em->flush();

        return $this->redirectToRoute('verrou_displayLockOption', ['m' => 1 ]);
    }
}
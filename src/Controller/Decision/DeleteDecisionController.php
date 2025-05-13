<?php

namespace App\Controller\Decision;

use App\Service\SchoolYearService;
use App\Repository\DecisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/decision")]
class DeleteDecisionController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator,
        protected SchoolYearService $schoolYearService, 
        protected DecisionRepository $decisionRepository, 
        )
    {}

    #[Route("/deletedecision/{slug}", name:"decision_deleteDecision")]
    public function deleteDecision(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        

        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $decision = $this->decisionRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $this->em->remove($decision);
        $this->em->flush();
            
        $this->addFlash('info', $this->translator->trans('Decision deleted with success !'));
        
        #j'affecte 1 à ma variable pour afficher le message
        $mySession->set('suppression', 1);

        return $this->redirectToRoute('decision_displayDecision', [ 's' => 1]);
        
    }
}

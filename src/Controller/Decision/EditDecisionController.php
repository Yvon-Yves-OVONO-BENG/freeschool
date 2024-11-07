<?php

namespace App\Controller\Decision;

use App\Form\DecisionType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DecisionRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\This;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/decision")]
class EditDecisionController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected DecisionRepository $decisionRepository, 
        protected SchoolYearService $schoolYearService, 
        )
    {}

    #[Route("/editDecision/{slug}", name:"decision_editDecision")]
    public function saveDecision(Request $request, string $slug): Response
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

        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $decision = $this->decisionRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(DecisionType::class, $decision);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $decision->setDecision(strtoupper($decision->getDecision()));

            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Decision updated successfully'));

            $mySession->set('miseAjour', 1);

            // On se redirige sur la page d'affichage des diplomes
            return $this->redirectToRoute('decision_displayDecision', [ 'm' => 1]);
            
        }

        $decisions = $this->decisionRepository->findBy([], ['decision' => 'ASC']);

        return $this->render('decision/saveDecision.html.twig', [
            'formDecision' => $form->createView(),
            'slug' => $slug,
            'decisions' => $decisions,
            'school' => $school,
            ]);
    }

}

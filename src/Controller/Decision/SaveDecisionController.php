<?php

namespace App\Controller\Decision;

use App\Entity\Decision;
use App\Form\DecisionType;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\DecisionRepository;
use Doctrine\ORM\EntityManagerInterface;
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
class SaveDecisionController extends AbstractController
{
    public function __construct(protected DecisionRepository $decisionRepository, protected EntityManagerInterface $em, protected SchoolYearService $schoolYearService, protected TranslatorInterface $translator, protected SchoolRepository $schoolRepository)
    {
    }

    #[Route("/saveDecision", name:"decision_saveDecision")]
    public function saveDecision(Request $request): Response
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

        $slug = 0;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $decision = new Decision();       
        
        $form = $this->createForm(DecisionType::class, $decision);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait la derniere matiere de la table
            $dernierDecision =  $this->decisionRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDecision) 
            {
                $id = $dernierDecision[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $decision->setDecision(strtoupper($decision->getDecision()))
            ->setSlug($slug.$id)
            ;

            $this->em->persist($decision);
            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Decision saved successfully'));
            
            #j'affecte 1 à ma variable pour afficher le message
            $mySession->set('ajout', 1);

            $decision = new Decision();
            $form = $this->createForm(DecisionType::class, $decision);
            
        }

        $decisions = $this->decisionRepository->findBy([], ['decision' => 'ASC']);

        return $this->render('decision/saveDecision.html.twig', [
            'slug' => $slug,
            'decisions' => $decisions,
            'formDecision' => $form->createView(),
            'school' => $school,
            ]);
    }

}

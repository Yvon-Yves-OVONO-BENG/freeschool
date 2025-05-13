<?php

namespace App\Controller\Profil;

use App\Entity\ReponseQuestion;
use App\Form\ReponseQuestionType;
use App\Repository\ReponseQuestionRepository;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('profil')]
class ReponseQuestionController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected TranslatorInterface $translator,
        private ParameterBagInterface $parametres,
        protected SchoolRepository $schoolRepository,
        protected ReponseQuestionRepository $reponseQuestionRepository
    )
    {}

    #[Route('/reponse-question', name: 'reponse_question')]
    public function reponseQuestion(Request $request): Response
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


        $reponseQuestion = $this->reponseQuestionRepository->findOneBy([
            'user' => $this->getUser()
        ]);

        if (!$reponseQuestion) 
        {
            #je déclare une nouvelle instace d'un Produit
            $reponseQuestion = new ReponseQuestion;
        }

        #je crée mon formulaire et je le lie à mon instance
        $form = $this->createForm(ReponseQuestionType::class, $reponseQuestion);

        #je demande à mon formulaire de récupérer les donnéesqui sont dans le POST avec la $request
        $form->handleRequest($request);
        
        #je teste si mon formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) 
        {
            #je met le nom du produit en CAPITAL LETTER
            $reponseQuestion->setUser($this->getUser());
            
            # je prépare ma requête avec entityManager
            $this->em->persist($reponseQuestion);

            #j'exécutebma requête
            $this->em->flush();

            #j'affiche le message de confirmation d'ajout
            $this->addFlash('info', $this->translator->trans('Secret question save with success !'));

            #j'affecte 1 à ma variable pour afficher le message
            $mySession->set('ajout', 1);
            
            return $this->redirectToRoute('home_dashboard', ['m' => 1]);
        }

        return $this->render('profil/questionReponse.html.twig', [
            'school' => $school,
            'motDePasse' => 1,
            'subSystem' => $subSystem,
            'formQuestionSecrete' => $form->createView(),
        ]);
    }

}

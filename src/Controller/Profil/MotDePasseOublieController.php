<?php

namespace App\Controller\Profil;

use App\Repository\QuestionSecreteRepository;
use App\Repository\ReponseQuestionRepository;
use App\Repository\TeacherRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;


#[Route('profil')]
class MotDePasseOublieController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected TranslatorInterface $translator,
        private ParameterBagInterface $parametres,
        protected TeacherRepository $teacherRepository,
        protected ReponseQuestionRepository $reponseQuestionRepository,
        protected QuestionSecreteRepository $questionSecreteRepository,
    )
    {}

    #[Route('/mot-de-passe-oublie', name: 'mot_de_passe_oublie')]
    public function motDePasseOublie(Request $request): Response
    {
        # je récupère ma session
        $mySession = $request->getSession();
        
        #mes variables témoin pour afficher les sweetAlert
        $mySession->set('ajout', null);
        $mySession->set('suppression', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }
        
        #je r"cupère les questions secrètes
        $questionSecretes = $this->questionSecreteRepository->findAll();
        
        if ($request->request->has('envoyer') && $request->request->has('questionSecreteId') && 
            $request->request->has('userId') && $request->request->has('reponse')) 
        {
            $questionReponse = $this->reponseQuestionRepository->findOneBy([
                'reponse' => $request->request->get('reponse'),
                'user' => $this->userRepository->find($request->request->get('userId')),
                'questionSecrete' => $this->questionSecreteRepository->find($request->request->get('questionSecreteId')),
            ]);

            if($questionReponse)
            {
                #j'affiche le message de confirmation d'ajout
                $this->addFlash('info', $this->translator->trans("Good answer ! Reset your password ! "));
            
                return $this->redirectToRoute('reinitialiser_mot_de_passe', ['idUser' => $this->userRepository->find($request->request->get('userId'))->getId()  ]);
                
            }
            else
            {
                #j'affiche le message de confirmation d'ajout
                $this->addFlash('info', $this->translator->trans("One of the three pieces of information is incorrect ! "));
            
                return $this->redirectToRoute('mot_de_passe_oublie');
            }
        }
        else
        {
            #je récupère les utilisateurs
            $users = $this->userRepository->findBy([], 
            ['fullName' => 'ASC']);
        }

        return $this->render('profil/motDePasseOublie.html.twig', [
            'licence' => 1,
            'motDePasse' => 0,
            'users' => $users,
            'schoolYear' => $schoolYear,
            'questionSecretes' => $questionSecretes,
        ]);
    }

}

<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Form\ModifierMotDePasseType;
use App\Repository\SchoolRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
class ModifierMotDePasseController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected UserRepository $userRepository,
        protected TranslatorInterface $translator,
        protected SchoolRepository $schoolRepository,
        protected UserPasswordHasherInterface $userPasswordHasher,
    )
    {}

    #[Route("/edit-my-password", name:"edit_my_password")]
    public function modifierMotDePasse(Request $request): Response
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

        #je récupère l'utilisateur connecté
        /**
         *@var User
         */

        $user = $this->getUser();

        $user = $this->userRepository->find($user->getId());

        $form = $this->createForm(ModifierMotDePasseType::class, $user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $user->setPassword(
                $this->userPasswordHasher->hashPassword(
                    $user,
                    $user->getPassword()
                )
            );

            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Password updated with success ! !'));
            
            $mySession->set('miseAjour', 1);

            return $this->redirectToRoute('home_dashboard', ['m' => 1 ]);
        }

        return $this->render('profil/modifier_mot_de_passe.html.twig', [
            'licence' => 1,
            'motDePasse' => 1,
            'school' => $school,
            'subSystem' => $subSystem,
            'userForm' => $form->createView(),
        ]);
    }
}

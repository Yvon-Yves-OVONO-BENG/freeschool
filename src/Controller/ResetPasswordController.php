<?php

namespace App\Controller;

use App\Repository\UserRepository;
use App\Repository\TeacherRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */ 
class ResetPasswordController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected UserRepository $userRepository, 
        protected TranslatorInterface $translator, 
        protected TeacherRepository $teacherRepository, 
        protected UserPasswordHasherInterface $encoder,
        )
    {}

    #[Route("/reset-password/{id}", name:"reset_password")]
    public function reinitialiserMotDePasseUser(Request $request, int $id = 0): Response
    {
        $mySession = $request->getSession();
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        

        $teacher = $this->teacherRepository->findBy([ 
            'id' => $id,
            'schoolYear' => $schoolYear,
        ], []);

        $user = $this->userRepository->findOneBy([
            'teacher' => $teacher
         ],[]);

        $characts    = 'abcdefghijklmnopqrstuvwxyz';
        $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';	
        $characts   .= '1234567890'; 
        $code_aleatoire      = ''; 
 
        for($i=0;$i < 5;$i++) 
        { 
            $code_aleatoire .= substr($characts,rand()%(strlen($characts)),1); 
        }

        $hash = $this->encoder->hashPassword($user, $code_aleatoire);

        $user->setPassword($hash);

        $this->em->persist($user);
        $this->em->flush();
        
        $this->addFlash('info', $this->translator->trans('The new password is : ').$code_aleatoire);

        $mySession->set('resetPwd', 1);

        return $this->redirectToRoute('teacher_displayTeacher',
            ['displayLaters' => 0, 'rPwd' => 1]
        );

    }
}

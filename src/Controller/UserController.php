<?php

namespace App\Controller;

use App\Form\ChangePasswordType;
use App\Repository\SchoolRepository;
use App\Repository\UserRepository;
use App\Repository\TeacherRepository;
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

#[Route("/super/admin")]
class UserController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected UserRepository $userRepository, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        protected UserPasswordHasherInterface $encoder, 
        )
    {}


    /**
     * @Route("/displayUser", name="user_displayUser")
     */
    public function displayUser(): Response
    {
        return $this->render('user/displayUser.html.twig', [

        ]);
    }

    /**
     * @Route("/saveUser", name="user_saveUser")
     */
    public function saveUser(): Response
    {
        return $this->render('user/saveUser.html.twig', [

        ]);
    }

    /**
     * @Route("/editUser", name="user_editUser")
     */
    public function editUser(): Response
    {
        return $this->render('user/editUser.html.twig', [

        ]);
    }

    /**
     * @Route("/changePassword/{slug}", name="user_changePassword")
     */
    public function changePassword(Request $request, string $slug): Response 
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        
        // if(!$request->request->has('changePassword'))
        // {
        //     // $mySession['previous'] = $request->headers->get('referer');
        //     $request->getSession()->set('mySession', $mySession);
        // }


        $fullName = "";

        $teacher = $this->teacherRepository->findBy([ 
                'slug' => $slug,
                'schoolYear' => $schoolYear,
            ], []);

        $users = $this->userRepository->findBy([
                'teacher' => $teacher
            ],[]);

        $user = $users[0];
        $fullName = $user->getFullName();
        
            
        // $user = $this->userRepository->find($idU);
        
        $form = $this->createForm(ChangePasswordType::class, $user);
        $form->handleRequest($request);
        
        if($form->isSubmitted() && $form->isValid())
        {
            $user->setPassword($this->encoder->hashPassword($user, $user->getPassword()));

            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('Password updated successfully'));
            
            return $this->redirectToRoute('teacher_displayTeacher', ['displayLaters' => 0, 'm' => 1]);
        }
        
        return $this->render('user/changePassword.html.twig', [
            'userForm' => $form->createView(),
            'fullName' => $fullName,
            'id' => $slug,
            'school' => $school,
        ]);
    }
}

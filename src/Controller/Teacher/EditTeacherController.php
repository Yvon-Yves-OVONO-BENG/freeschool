<?php

namespace App\Controller\Teacher;

use App\Entity\User;
use App\Form\TeacherType;
use App\Service\StrService;
use App\Entity\ConstantsClass;
use App\Repository\UserRepository;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
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

#[Route("/teacher")]
class EditTeacherController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected UserRepository $userRepository,  
        protected SchoolRepository $schoolRepository, 
        protected TeacherRepository $teacherRepository, 
        protected UserPasswordHasherInterface $encoder, 
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/editTeacher/{slug}/{pe<[0-1]{1}>}", name:"teacher_editTeacher")]
    public function editTeacher(Request $request, string $slug, int $pe = 0): Response
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
        
        $subSyste = $this->subSystemRepository->find($subSystem->getId());
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $mySession->set('pe', $pe);
        
        $mySession = $request->getSession();
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }
        // on ecupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());

        $teacher = $this->teacherRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $user = $this->userRepository->findOneBy(['teacher' => $teacher]);

        $form = $this->createForm(TeacherType::class, $teacher);

        $teacher->setSchoolYear($schoolYear); 

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            // on met le nom en majuscule
            $teacher
                ->setFullName($this->strService->strToUpper($teacher->getFullName()))
                ->setSubSystem($subSyste)
            ;
            
            // on set les champs qui se sont pas pris en compte par le formulaire
            $teacher->setUpdatedBy($this->getUser());
            if($user)
            {
                $user->setUsername($teacher->getAdministrativeNumber().$teacher->getId())
                    ->setFullName($teacher->getFullName());

                $teacherDuty = $teacher->getDuty()->getDuty();
                if ($teacherDuty == ConstantsClass::HEADMASTER_DUTY || $teacherDuty == ConstantsClass::DIRECTOR_DUTY) 
                {
                    $user->setRoles([ConstantsClass::ROLE_HEADMASTER]);
                    
                }else
                {
                    $role = implode('_',['ROLE', strtoupper(str_replace(' ', '_',$teacher->getDuty()->getDuty()))]);
                
                    $user->setRoles([$role]);

                }

            }else
            {
                $user = new User();
                $user->setUsername($teacher->getAdministrativeNumber().$teacher->getId())
                ->setFullName($teacher->getFullName())
                ->setTeacher($teacher);

                $hash = $this->encoder->hashPassword($user, ConstantsClass::DEFAULT_TEACHER_PASSWORD);

                $user->setPassword($hash);

                $teacherDuty = $teacher->getDuty()->getDuty();
                if ($teacherDuty == ConstantsClass::HEADMASTER_DUTY || $teacherDuty == ConstantsClass::DIRECTOR_DUTY) 
                {
                    $user->setRoles([ConstantsClass::ROLE_HEADMASTER]);
                    
                }else
                {
                    $role = implode('_',['ROLE', strtoupper(str_replace(' ', '_',$teacher->getDuty()->getDuty()))]);
                
                    $user->setRoles([$role]);

                }

                $this->em->persist($user);

            }
            
            $this->em->flush(); // On modifie
            $this->addFlash('info', $this->translator->trans('Staff updated successfully'));

            $mySession->set('miseAjour', 1);
            
            if($pe == 1)
            {
                // Si c'est le personnel qui a mis à jour ses informations, reste sur la même page
                $this->redirectToRoute('teacher_editTeacher', [
                    'slug' => $slug,
                    'pe' => $pe,
                    'm' => 1,
                    
                ]);
            }else
            {
                // On se redirige sur la page d'affichage des Personnels
                return $this->redirectToRoute('teacher_displayTeacher',
                [   'displayLaters' => 0,
                    'm' => 1
                ]
            );

            } 
        }

        $effectif = count($this->teacherRepository->findAllToDisplay($schoolYear, $subSystem));
        
        return $this->render('teacher/saveTeacher.html.twig', [
            'formTeacher' => $form->createView(),
            'slug' => $slug,
            'pe' => $pe,
            'effectif' => $effectif,
            'school' => $school,
            ]);
    }
  
}
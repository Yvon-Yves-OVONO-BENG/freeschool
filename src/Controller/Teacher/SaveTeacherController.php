<?php

namespace App\Controller\Teacher;

use App\Entity\User;
use App\Entity\Teacher;
use App\Form\TeacherType;
use App\Service\StrService;
use App\Entity\ConstantsClass;
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
class SaveTeacherController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected TeacherRepository $teacherRepository, 
        protected UserPasswordHasherInterface $encoder, 
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/saveTeacher/{pe<[0-1]{1}>}", name:"teacher_saveTeacher")]
    public function saveTeacher(Request $request, int $pe = 0): Response
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
        $slug = 0;
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }
        // on ecupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());

        $teacher = new Teacher();
        $user = new User();  
        
        $form = $this->createForm(TeacherType::class, $teacher);

        $teacher->setSchoolYear($schoolYear); 
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
            $dernierTeacher = $this->teacherRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierTeacher) 
            {
                $id = $dernierTeacher[0]->getId();
            } 
            else 
            {
                $id = 1;
            }
            // on met le nom en majuscule
            $teacher->setFullName($this->strService->strToUpper($teacher->getFullName()))
                    ->setCreatedBy($this->getUser())
                    ->setUpdatedBy($this->getUser())
                    ->setSlug($slug.$id)
                    ->setSupprime(0)
                    ->setSubSystem($subSyste);
            

            // On ajoute dans la BD
            $this->em->persist($teacher);
            $this->em->flush();

            $user->setUsername($teacher->getAdministrativeNumber().$teacher->getId())
                ->setFullName($teacher->getFullName())
                ->setTeacher($teacher);

            $hash = $this->encoder->hashPassword($user, ConstantsClass::DEFAULT_TEACHER_PASSWORD);

            $user->setPassword($hash);

            $teacherDuty = $teacher->getDuty()->getDuty();

            if ($teacherDuty == ConstantsClass::HEADMASTER_DUTY ) 
            {
                $user->setRoles([ConstantsClass::ROLE_HEADMASTER]);
                
            }elseif ($teacherDuty == ConstantsClass::DIRECTOR_DUTY) 
            {
                $user->setRoles([ConstantsClass::ROLE_DIRECTOR]);
                
            }
            else
            {
                $role = implode('_',['ROLE', strtoupper(str_replace(' ', '_',$teacher->getDuty()->getDuty()))]);
                
                $user->setRoles([$role]);

                // $user->setRoles(['ROLE_'.strtoupper($teacher->getDuty()->getDuty())]);
            }

            $this->em->persist($user);
            $this->em->flush(); 

            $this->addFlash('info',  $this->translator->trans('Staff saved with success !'));
            
            $mySession->set('ajout', 1);
            
            $teacher = new Teacher();
            $form = $this->createForm(TeacherType::class, $teacher);
            
        }

        $effectif = count($this->teacherRepository->findAllToDisplay($schoolYear, $subSystem));
        $slug = 0;
        return $this->render('teacher/saveTeacher.html.twig', [
            'slug' => $slug,
            'pe' => $pe,
            'effectif' => $effectif,
            'formTeacher' => $form->createView(),
            'school' => $school,
            ]);
    }
 
}
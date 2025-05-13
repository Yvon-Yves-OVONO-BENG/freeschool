<?php

namespace App\Controller\SuperAdmin;

use App\Entity\User;
use App\Entity\Teacher;
use App\Form\TeacherType;
use App\Service\StrService;
use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/super-admin")]
class SuperAdminAddHeadmasterController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected UserPasswordHasherInterface $encoder, 
        protected SchoolYearRepository $schoolYearRepository, 
        )
    {}

    #[Route("/add-headmaster", name:"super_admin_addHeadmaster")]
    public function superAdminAddHeadmaster(Request $request)
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
        
        $schoolYear = $this->schoolYearRepository->find($request->request->get('schoolYear'));

        $mySession->set('schoolYear', $schoolYear);
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $mySession['pe'] = 0;
        $request->getSession()->set('mySession', $mySession);

        $teacher = new Teacher();
        $user = new User();  

        $form = $this->createForm(TeacherType::class, $teacher);

        $teacher->setSchoolYear($schoolYear); 

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $teacher->setFullName($this->strService->strToUpper($teacher->getFullName()));
            
            // on set les champs qui se sont pas pris en compte par le formulaire
            $teacher->setCreatedBy($this->getUser())
                ->setUpdatedBy($this->getUser());

            // On ajoute dans la BD
            $this->em->persist($teacher);
            $this->em->flush();

            $user->setUsername($teacher->getAdministrativeNumber().$teacher->getId())
                ->setFullName($teacher->getFullName())
                ->setTeacher($teacher);

            $hash = $this->encoder->hashPassword($user, ConstantsClass::DEFAULT_TEACHER_PASSWORD);

            $user->setPassword($hash)
                ->setRoles(['ROLE_'.strtoupper($teacher->getDuty()->getDuty())]);

            $this->em->persist($user);
            $this->em->flush(); 

            $this->addFlash('info', $this->translator->trans('Staff saved with success !'));
            $teacher = new Teacher();
            $form = $this->createForm(TeacherType::class, $teacher);
            
        }

        return $this->render('super_admin/saveHeadmaster.html.twig', [
            'formTeacher' => $form->createView(),
            'schoolYear' => $schoolYear,
            'school' => $school,
            ]);
    }

}

<?php

namespace App\Controller\Registration;

use App\Form\RegistrationHistoryType;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RegistrationHistoryRepository;
use App\Service\SolvableService;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class UpdateStudentFeesController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected SolvableService $solvableService,
        protected StudentRepository $studentRepository, 
        protected RegistrationRepository $registrationRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository, 
        )
    {}

    #[Route("/updateStudentFees/{headmasterFees<[0-1]{1}>}/{slugStudent}", name:"registration_updateStudentFees")]
    public function updateStudentFees(Request $request, string $slugStudent, int $headmasterFees = 0): Response
    {
       $mySession = $request->getSession();

       #mes variables témoin pour afficher les sweetAlert
       $mySession->set('ajout',null);
       $mySession->set('suppression', null);
       $mySession->set('miseAjour', null);
       $mySession->set('saisiNotes', null);

        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $student = $this->studentRepository->findOneBySlug(['slug' => $slugStudent]);

        $registrationHistory = $this->registrationHistoryRepository->findOneBy([
            'student' => $student
        ], [
            'createdAt' => 'DESC'
        ]);

        $registration = $this->registrationRepository->findOneBy(['student' => $student]);

        $registration->setSchoolFees($registration->getSchoolFees() - $registrationHistory->getSchoolFees())
            ->setApeeFees($registration->getApeeFees() - $registrationHistory->getApeeFees())
            ->setComputerFees($registration->getComputerFees() - $registrationHistory->getComputerFees())
            ->setMedicalBookletFees($registration->getMedicalBookletFees() - $registrationHistory->getMedicalBookletFees())
            ->setCleanSchoolFees($registration->getCleanSchoolFees() - $registrationHistory->getCleanSchoolFees())
            ->setPhotoFees($registration->getPhotoFees() - $registrationHistory->getPhotoFees())
            ->setExamFees($registration->getExamFees() - $registrationHistory->getExamFees())
            ->setStampFees($registration->getStampFees() - $registrationHistory->getStampFees())
        ;

        $form = $this->createForm(RegistrationHistoryType::class, $registrationHistory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) 
        {
            $registration->setSchoolFees($registration->getSchoolFees() + $registrationHistory->getSchoolFees())
                ->setApeeFees($registration->getApeeFees() + $registrationHistory->getApeeFees())
                ->setComputerFees($registration->getComputerFees() + $registrationHistory->getComputerFees())
                ->setMedicalBookletFees($registration->getMedicalBookletFees() + $registrationHistory->getMedicalBookletFees())
                ->setCleanSchoolFees($registration->getCleanSchoolFees() + $registrationHistory->getCleanSchoolFees())
                ->setPhotoFees($registration->getPhotoFees() + $registrationHistory->getPhotoFees())
                ->setExamFees($registration->getExamFees() + $registrationHistory->getExamFees())
                ->setStampFees($registration->getStampFees() + $registrationHistory->getStampFees())
        ;

           $this->em->persist($registrationHistory);
           $this->em->persist($registration);
           $this->em->flush();

           $this->addFlash("info", $this->translator->trans("Receipt changed successfully"));

           return $this->redirectToRoute("registration_schoolFees", [
                'headmasterFees' => $headmasterFees,
                'slugStudent' => $slugStudent,
           ]);
        }


        return $this->render("registration/updateStudentFees.html.twig", [
            'slugStudent' => $slugStudent,
            'student' => $student,
            'headmasterFees' => $headmasterFees,
            'registrationHistory' =>$registrationHistory,
            'registrationHistoryForm' => $form->createView(),
        ]);

    }


}

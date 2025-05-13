<?php

namespace App\Controller\Registration;

use App\Entity\Registration;
use App\Service\SchoolYearService;
use App\Entity\RegistrationHistory;
use App\Repository\StudentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RegistrationRepository;
use App\Repository\SchoolYearRepository;
use DateTime;
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

#[Route("/registration")]
class SaveSchoolFeesController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TranslatorInterface $translator, 
        protected StudentRepository $studentRepository, 
        protected SchoolYearService $schoolYearService, 
        protected SchoolYearRepository $schoolYearRepository ,
        protected RegistrationRepository $registrationRepository, 
        )
    {}

    #[Route("/saveSchoolFees/{slugStudent}", name:"registration_saveSchoolFees")]
    public function saveSchoolFees(Request $request, string $slugStudent): Response
    {
        // redirection au cas où la session a été interrompue ou au cas où le verrou est activé
       $mySession = $request->getSession();
       #mes variables témoin pour afficher les sweetAlert
       $mySession->set('ajout',null);
       $mySession->set('suppression', null);
       $mySession->set('miseAjour', null);
       $mySession->set('saisiNotes', null);

       // on recupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
       $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
       
        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {

            return $this->redirectToRoute('home_mainMenu');
        }

        $registrationHistory = new RegistrationHistory();

        $feesTable = [];
        
        $student = $this->studentRepository->findOneBy(['slug' => $slugStudent ]);
        
        $level = $student->getClassroom()->getLevel()->getLevel();
        
        if($request->request->has('saveSchoolFees')) 
        {
            $feesTable['schoolFees'] = $request->request->get('schoolFeesDeposit') ? $request->request->get('schoolFeesDeposit') : 0;
            $feesTable['apeeFees'] = $request->request->get('apeeFeesDeposit') ? $request->request->get('apeeFeesDeposit') : 0;
            $feesTable['computerFees'] = $request->request->get('computerFeesDeposit') ? $request->request->get('computerFeesDeposit') : 0;
            $feesTable['medicalBookletFees'] = $request->request->get('medicalBookletFeesDeposit') ? $request->request->get('medicalBookletFeesDeposit') : 0;
            $feesTable['cleanSchoolFees'] = $request->request->get('cleanSchoolFeesDeposit') ? $request->request->get('cleanSchoolFeesDeposit') : 0;
            $feesTable['photoFees'] = $request->request->get('photoFeesDeposit') ? $request->request->get('photoFeesDeposit') : 0;

            if ($level == 4 || $level == 6 || $level == 7) 
            {
                $feesTable['stampFees'] = $request->request->get('stampFeesDeposit') ? $request->request->get('stampFeesDeposit') : 0;
                $feesTable['examFees'] = $request->request->get('examFeesDeposit') ? $request->request->get('examFeesDeposit') :0;
            }
        }

        $now = new DateTime('now');
        $registrationHistory->setSchoolFees($feesTable['schoolFees'])
            ->setApeeFees($feesTable['apeeFees'])
            ->setComputerFees($feesTable['computerFees'])
            ->setMedicalBookletFees($feesTable['medicalBookletFees'])
            ->setCleanSchoolFees($feesTable['cleanSchoolFees'])
            ->setPhotoFees($feesTable['photoFees'])
            ->setStudent($student)
            ->setCreatedBy($this->getUser())
            ->setUpdatedBy($this->getUser())
            ->setSchoolYear($schoolYear)
            ->setCreatedAt($now);

            if ($level == 4 || $level == 6 || $level == 7) 
            {
                $registrationHistory->setExamFees($feesTable['examFees'])
                                    ->setStampFees($feesTable['stampFees']);
            }

            
        $this->em->persist($registrationHistory);

        $studentRegistration = $this->registrationRepository->findOneBy(['student' => $student]);

        if(is_null($studentRegistration))
        {
            $registration = new Registration();

            $registration->setSchoolFees($feesTable['schoolFees'])
            ->setApeeFees($feesTable['apeeFees'])
            ->setComputerFees($feesTable['computerFees'])
            ->setMedicalBookletFees($feesTable['medicalBookletFees'])
            ->setCleanSchoolFees($feesTable['cleanSchoolFees'])
            ->setPhotoFees($feesTable['photoFees'])
            ->setStudent($student)
            ->setCreatedBy($this->getUser())
            ->setCreatedAt($now)
            ->setSchoolYear($schoolYear)
            ->setUpdatedBy($this->getUser());

            if ($level == 4 || $level == 6 || $level == 7) 
            {
                $registrationHistory->setExamFees($feesTable['examFees'])
                                    ->setStampFees($feesTable['stampFees']);
            }

            $this->em->persist($registration);
        }else 
        {
            $studentRegistration->setSchoolFees($studentRegistration->getSchoolFees() + $feesTable['schoolFees'])
                ->setApeeFees($studentRegistration->getApeeFees() + $feesTable['apeeFees'])
                ->setComputerFees($studentRegistration->getComputerFees() + $feesTable['computerFees'])
                ->setMedicalBookletFees($studentRegistration->getMedicalBookletFees() + $feesTable['medicalBookletFees'])
                ->setCleanSchoolFees($studentRegistration->getCleanSchoolFees() + $feesTable['cleanSchoolFees'])
                ->setPhotoFees($studentRegistration->getPhotoFees() + $feesTable['photoFees'])
                ->setUpdatedBy($this->getUser())
                ->setUpdatedAt($now)
                ;

            if ($level == 4 || $level == 6 || $level == 7) 
            {
                $studentRegistration->setExamFees($studentRegistration->getExamFees() + $feesTable['examFees'])
                ->setStampFees($studentRegistration->getStampFees() + $feesTable['stampFees']);
            }

            $sumFees = (int)$studentRegistration->getApeeFees() + (int)$studentRegistration->getComputerFees() + (int)$studentRegistration->getCleanSchoolFees() + (int)$studentRegistration->getMedicalBookletFees() + $studentRegistration->getPhotoFees();

            if ($sumFees == 25000) 
            {
                $student->setSolvable(1);
            }else
            {
                $student->setSolvable(0);
            }

            $this->em->persist($student);
            $this->em->persist($studentRegistration);
        }

        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Fees saved with success !'));
        $mySession->set('ajout', 1);

        return $this->redirectToRoute('registration_schoolFees', ['slugStudent' => $slugStudent, 'a' => 1]);
    }

}
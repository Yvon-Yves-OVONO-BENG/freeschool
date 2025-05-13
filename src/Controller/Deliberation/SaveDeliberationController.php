<?php

namespace App\Controller\Deliberation;

use DateTime;
use App\Entity\Student;
use App\Entity\Classroom;
use App\Service\StrService;
use App\Entity\Registration;
use App\Entity\ConstantsClass;
use App\Service\QrcodeService;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\DecisionRepository;
use App\Repository\RepeaterRepository;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolYearRepository;
use App\Repository\SubSystemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/deliberation")
 */
class SaveDeliberationController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository,  
                                protected DecisionRepository $decisionRepository, 
                                protected StudentRepository $studentRepository, 
                                protected RepeaterRepository $repeaterRepository, 
                                protected Security $security, 
                                protected EntityManagerInterface $em, 
                                protected SchoolYearService $schoolYearService, 
                                protected TranslatorInterface $translator, 
                                protected QrcodeService $qrcodeService, 
                                protected SchoolRepository $schoolRepository, 
                                protected SchoolYearRepository $schoolYearRepository, 
                                protected StrService $strService, 
                                protected SubSystemRepository $subSystemRepository )
    {
    }

    /**
     * @Route("/saveDeliberation/{idC<[0-9]+>}", name="deliberation_saveDeliberation")
     */
    public function saveDeliberation(Request $request, int $idC): Response
    {
        $mySession = $request->getSession();
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        // On recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();
        

        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
        $subSystem = $this->subSystemRepository->find($mySession->get('subSystem')->getId());
        
        $school = $this->schoolRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        $schoolName = $school[0]->getFrenchName()." / ".$school[0]->getEnglishName();

        $now = new DateTime('now');

        // Classe sélectionnée
        $selectedClassroom = $this->classroomRepository->find($idC);

        $numberOfStudents = count($selectedClassroom->getStudents());

        // on passe les eleves redoublants et admis au next school year
        for ($i=1; $i <= $numberOfStudents ; $i++) 
        {
            $newStudent = new Student();
            $registration = new Registration();
            $nextClassroom = new Classroom;
            $repeater = null;
            $studentId = 'student'.$i;
            $studentDecision = 'decision'.$i;
            $studentNextClassroom = 'nextClassroom'.$i;
            $studentMotif = 'motif'.$i;

            $student = $this->studentRepository->find($request->request->get($studentId));
            $decision = $this->decisionRepository->find($request->request->get($studentDecision));

            $qrCode = null;

            $student->setDecision($decision)
                    ->setQrCode($qrCode)
            ;
            
            // on construit le new student pour le next school year et on met à jour le current student
            $newStudent->setFullName($student->getFullName())
                ->setBirthday($student->getBirthday())
                ->setBirthplace($student->getBirthplace())
                ->setPhoto($student->getPhoto())
                ->setRegistrationNumber($student->getRegistrationNumber())
                ->setCreatedBy($this->security->getUser())
                ->setCreatedAt($now)
                ->setSex($student->getSex())
                ->setPrevId($student->getId())
                ->setSchoolYear($nextSchoolYear)
                ->setSubSystem($subSystem)
                ->setSupprime(0)
                ->setEmailParent($student->getEmailParent())
                ;

            switch ($decision->getDecision()) 
            {
                case ConstantsClass::DECISION_PASSED:
                    $nextClassroom = $this->classroomRepository->find($request->request->get($studentNextClassroom));
                    
                    $repeater = $this->repeaterRepository->findOneByRepeater(ConstantsClass::REPEATER_NO);

                    /////je met à 0 tous ses frais dans la table RegistrationHistory
                    $registration->setApeeFees(0)
                            ->setComputerFees(0)
                            ->setCleanSchoolFees(0)
                            ->setMedicalBookletFees(0)
                            ->setPhotoFees(0)
                            ->setSchoolFees(0)
                            ->setStampFees(0)
                            ->setExamFees(0)
                            ->setCreatedBy($this->security->getUser())
                            ->setCreatedAt($now)
                            ->setSchoolYear($nextSchoolYear)
                            ->setStudent($newStudent)
                        ;
                break;

                case  ConstantsClass::DECISION_REAPETED:
                    // on recupère la classe de même nom du next Year
                    $nextClassroom = $this->classroomRepository->findOneBy([
                        'schoolYear' => $nextSchoolYear,
                        'classroom' => $selectedClassroom->getClassroom()
                    ]);
                    $repeater = $this->repeaterRepository->findOneByRepeater(ConstantsClass::REPEATER_YES);

                    /////je met à 0 tous ses frais dans la table RegistrationHistory
                    $registration->setApeeFees(0)
                            ->setComputerFees(0)
                            ->setCleanSchoolFees(0)
                            ->setMedicalBookletFees(0)
                            ->setPhotoFees(0)
                            ->setSchoolFees(0)
                            ->setStampFees(0)
                            ->setExamFees(0)
                            ->setCreatedBy($this->security->getUser())
                            ->setCreatedAt($now)
                            ->setSchoolYear($nextSchoolYear)
                            ->setStudent($newStudent)
                        ;
                break;

                case  ConstantsClass::DECISION_REAPETED_IF_FAILED:
                    // on recupère la classe de même nom du next Year
                    $nextClassroom = $this->classroomRepository->findOneBy([
                        'schoolYear' => $nextSchoolYear,
                        'classroom' => $selectedClassroom->getClassroom()
                    ]);
                    
                    $repeater = $this->repeaterRepository->findOneByRepeater(ConstantsClass::REPEATER_YES);

                    /////je met à 0 tous ses frais dans la table RegistrationHistory
                    $registration->setApeeFees(0)
                            ->setComputerFees(0)
                            ->setCleanSchoolFees(0)
                            ->setMedicalBookletFees(0)
                            ->setPhotoFees(0)
                            ->setSchoolFees(0)
                            ->setStampFees(0)
                            ->setExamFees(0)
                            ->setCreatedBy($this->security->getUser())
                            ->setCreatedAt($now)
                            ->setSchoolYear($nextSchoolYear)
                            ->setStudent($newStudent)
                        ;
                break;

                case ConstantsClass::DECISION_EXPELLED:
                    $motif = $request->request->get($studentMotif);
                    $student->setMotif($motif);
                break;

                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                    $motif = $request->request->get($studentMotif);
                    $student->setMotif($motif);
                break;

                case ConstantsClass::DECISION_RESIGNED:
                    $motif = $request->request->get($studentMotif);
                    $student->setMotif($motif);
                break;

                case ConstantsClass::DECISION_FINISHED:
                    $motif = $request->request->get($studentMotif);
                    $student->setMotif($motif);
                break;

                case ConstantsClass::DECISION_CATCHUPPED:
                    $motif = $request->request->get($studentMotif);
                    $student->setMotif($motif);
                break;
            }

            if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
            {
                $qrCode = $this->qrcodeService->qrcode($schoolName." : Ce bulletin appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$nextClassroom->getClassroom());

                $qrCodeFiche = $this->qrcodeService->qrcode($schoolName." : Cette fiche appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$nextClassroom->getClassroom());
                
                $qrCodeRollOfHonor = $this->qrcodeService->qrcode($schoolName." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$nextClassroom->getClassroom());

            } else 
            {
                $qrCode = $this->qrcodeService->qrcode($schoolName." : This report belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year : ".$schoolYear->getSchoolYear().", Classroom : ".$nextClassroom->getClassroom());

                $qrCodeFiche = $this->qrcodeService->qrcode($schoolName." : This sheet belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$nextClassroom->getClassroom());
            
                $qrCodeRollOfHonor = $this->qrcodeService->qrcode($schoolName." : This roll of honor belongs to the student: ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$nextClassroom->getClassroom());

            }

            #je fabrique mon slug
            // $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            // $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            // $characts   .= '1234567890'; 
            // $slug      = ''; 
    
            // for($i=0;$i < 15;$i++) 
            // { 
            //     $slug .= substr($characts,rand()%(strlen($characts)),1); 
            // }

            // //////j'extrait le dernier élève de la table
            // $dernierStudent = $this->studentRepository->findBy([],['id' => 'DESC'],1,0);

            // /////je récupère l'id du sernier utilisateur
            
            // if ($dernierStudent) 
            // {
            //     $id = $dernierStudent[0]->getId();
            // } 
            // else 
            // {
            //     $id = 1;
            // }

            $newStudent->setClassroom($nextClassroom)
                        ->setRepeater($repeater)
                        ->setQrCode($qrCode)
                        ->setQrCodeFiche($qrCodeFiche)
                        ->setQrCodeRollOfHonor($qrCodeRollOfHonor)
                        ->setSlug(uniqid('', true));

            $student->setNextClassroomName($nextClassroom->getClassroom());

            $this->em->persist($student);

            if(($decision->getDecision() == ConstantsClass::DECISION_PASSED) || ($decision->getDecision() == ConstantsClass::DECISION_REAPETED) || ($decision->getDecision() == ConstantsClass::DECISION_REAPETED_IF_FAILED))
            {
                $this->em->persist($newStudent);
                $this->em->persist($registration);
            }
        }
        // on precise dans la classe que la deliberation s'est déjà passée
        $selectedClassroom->setIsDeliberated(true);
        $this->em->persist($selectedClassroom);

        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Deliberations saved with success !'));

        return $this->redirectToRoute('deliberation_displayDeliberation', [
            'idC' => $idC,
            'notification' => 1,
        ]);
    }

}
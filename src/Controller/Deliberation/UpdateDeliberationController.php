<?php

namespace App\Controller\Deliberation;

use App\Entity\Student;
use App\Service\StrService;
use App\Entity\ConstantsClass;
use App\Service\QrcodeService;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\DecisionRepository;
use App\Repository\RepeaterRepository;
use App\Repository\ClassroomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
#[Route('/deliberation')]
class UpdateDeliberationController extends AbstractController
{
    public function __construct(
        private Security $security, 
        private StrService $strService,
        private EntityManagerInterface $em, 
        private QrcodeService $qrcodeService,  
        private TranslatorInterface $translator,
        private SchoolRepository $schoolRepository,
        private StudentRepository $studentRepository, 
        private SchoolYearService $schoolYearService, 
        private DecisionRepository $decisionRepository, 
        private RepeaterRepository $repeaterRepository, 
        private ClassroomRepository $classroomRepository,  
        )
    {}

    #[Route('/updateDeliberation/{idS<[0-9]+>}/{idC<[0-9]+>}', name: 'deliberation_updateDeliberation')]
    public function updateDeliberation(Request $request, int $idS, int $idC): Response
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

        $school = $this->schoolRepository->findOneBy([
            'schoolYear' => $schoolYear
        ]);

        $schoolName = $school->getFrenchName()." / ".$school->getEnglishName();

        // On recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // on recupère la classroom concernée
        $selectedClassroom = $this->classroomRepository->find($idC);

        // on recupère le student concerné
        $student = $this->studentRepository->find($idS);

        // on recupère le même élève du next year 
        $nextYearStudent = $this->studentRepository->findOneByPrevId($student);

        // on recupère la classe de même nom du next Year
        $repeatedClassroom = $this->classroomRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'classroom' => $selectedClassroom->getClassroom()
        ]);
        
        // on recupère la classe supérieure du nextYear
        $passedClassroom = $this->classroomRepository->find($request->request->get('nextClassroom'));

        $repeaterYes = $this->repeaterRepository->findOneByRepeater(ConstantsClass::REPEATER_YES);

        $repeaterNo = $this->repeaterRepository->findOneByRepeater(ConstantsClass::REPEATER_NO);

        $newDecision = $this->decisionRepository->find($request->request->get('decision'));
        $newMotif = $request->request->get('motif');

        $oldDecision = $student->getDecision();

        $newStudent = new Student();

        switch ($oldDecision->getDecision()) 
        {
            case ConstantsClass::DECISION_PASSED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setNextClassroomName($passedClassroom->getClassroom());
                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($passedClassroom);
                        }
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)->setNextClassroomName($repeatedClassroom->getClassroom());

                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($repeatedClassroom)
                                ->setRepeater($repeaterYes);
                        }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                            if($nextYearStudent != null)
                            {
                                $nextYearStudent->setClassroom($repeatedClassroom)
                                    ->setRepeater($repeaterYes);
                            }
                        
                    break;

                    
                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                            ->setNextClassroomName($repeatedClassroom->getClassroom());

                            if($nextYearStudent != null)
                            {
                                $nextYearStudent->setClassroom($repeatedClassroom)
                                    ->setRepeater($repeaterYes);
                            }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName(null)
                                ->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName(null)
                                ->setMotif(null);
                    break;
                }
            break;

            case ConstantsClass::DECISION_REAPETED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setDecision($newDecision)
                            ->setNextClassroomName($passedClassroom->getClassroom());
                            if($nextYearStudent != null)
                            {
                                $nextYearStudent->setClassroom($passedClassroom)
                                    ->setRepeater($repeaterNo);
                            }
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                            ->setNextClassroomName($repeatedClassroom->getClassroom());
                            if($nextYearStudent != null)
                            {
                                $nextYearStudent->setClassroom($repeatedClassroom)
                                    ->setRepeater($repeaterYes);
                            }
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName(null)
                                ->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName(null)
                                ->setMotif(null);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                            if($nextYearStudent != null)
                            {
                                $nextYearStudent->setClassroom($repeatedClassroom)
                                    ->setRepeater($repeaterYes);
                            }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;
                }
            break;

            case ConstantsClass::DECISION_EXPELLED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($passedClassroom->getClassroom())
                                ->setMotif(null);
                        
                        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
                        {
                            $qrCode = $this->qrcodeService->qrcode(($schoolName." : Ce bulletin appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                            $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : Cette fiche appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);
                            
                            $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                        } else 
                        {
                            $qrCode = $this->qrcodeService->qrcode(($schoolName." : This report belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year : ".$schoolYear->getSchoolYear().", Classroom : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                            $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : This sheet belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);
                        
                            $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : This roll of honor belongs to the student: ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                        }
                            
                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($passedClassroom)
                            ->setRepeater($repeaterNo)
                            ->setQrCode($qrCode)
                            ->setQrCodeFiche($qrCodeFiche)
                            ->setQrCodeRollOfHonor($qrCodeRollOfHonor);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($repeatedClassroom->getClassroom())
                                ->setMotif(null);
                        
                        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
                        {
                            $qrCode = $this->qrcodeService->qrcode(($schoolName." : Ce bulletin appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$repeatedClassroom->getClassroom()), $student->getSlug(), $school);

                            $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : Cette fiche appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$repeatedClassroom->getClassroom()), $student->getSlug(), $school);
                            
                            $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$repeatedClassroom->getClassroom()), $student->getSlug(), $school);

                        } else 
                        {
                            $qrCode = $this->qrcodeService->qrcode(($schoolName." : This report belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year : ".$schoolYear->getSchoolYear().", Classroom : ".$repeatedClassroom->getClassroom()), $student->getSlug(), $school);

                            $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : This sheet belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$repeatedClassroom->getClassroom()), $student->getSlug(), $school);
                        
                            $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : This roll of honor belongs to the student: ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$repeatedClassroom->getClassroom()), $student->getSlug(), $school);

                        }

                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent = new Student();
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($repeatedClassroom)
                            ->setRepeater($repeaterYes)
                            ->setQrCode($qrCode)
                            ->setQrCodeFiche($qrCodeFiche)
                            ->setQrCodeRollOfHonor($qrCodeRollOfHonor);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($repeatedClassroom)
                                ->setRepeater($repeaterYes);
                        }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                        $student->setDecision($newDecision)
                            ->setMotif(null);
                    break;
                }
            break;

            case ConstantsClass::DECISION_CATCHUPPED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($passedClassroom->getClassroom())
                                ->setMotif(null);
                            
                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($passedClassroom)
                            ->setRepeater($repeaterNo);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($repeatedClassroom->getClassroom())
                                ->setMotif(null);

                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent = new Student();
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($repeatedClassroom)
                            ->setRepeater($repeaterYes);
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setDecision($newDecision)
                            ->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($repeatedClassroom)
                                ->setRepeater($repeaterYes);
                        }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                    break;
                }
            break;

            case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($passedClassroom->getClassroom())
                                ->setMotif(null);
                            
                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($passedClassroom)
                            ->setRepeater($repeaterNo);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($repeatedClassroom->getClassroom())
                                ->setMotif(null);

                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent = new Student();
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($repeatedClassroom)
                            ->setRepeater($repeaterYes);
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setDecision($newDecision)
                            ->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($repeatedClassroom)
                                ->setRepeater($repeaterYes);
                        }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                    break;
                }
            break;

            case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($passedClassroom->getClassroom())
                                ->setMotif(null);
                            
                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($passedClassroom)
                            ->setRepeater($repeaterNo);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($repeatedClassroom->getClassroom())
                                ->setMotif(null);

                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent = new Student();
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($repeatedClassroom)
                            ->setRepeater($repeaterYes);
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setDecision($newDecision)
                            ->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($repeatedClassroom)
                                ->setRepeater($repeaterYes);
                        }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                    break;
                }
            break;

            case ConstantsClass::DECISION_RESIGNED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($passedClassroom->getClassroom())
                                ->setMotif(null);
                        
                        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
                        {
                            $qrCode = $this->qrcodeService->qrcode(($schoolName." : Ce bulletin appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                            $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : Cette fiche appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);
                            
                            $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                        } else 
                        {
                            $qrCode = $this->qrcodeService->qrcode(($schoolName." : This report belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year : ".$schoolYear->getSchoolYear().", Classroom : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                            $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : This sheet belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);
                        
                            $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : This roll of honor belongs to the student: ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$passedClassroom->getClassroom()), $student->getSlug(), $school);

                        }

                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($passedClassroom)
                            ->setRepeater($repeaterNo)
                            ->setQrCode($qrCode)
                            ->setQrCodeFiche($qrCodeFiche)
                            ->setQrCodeRollOfHonor($qrCodeRollOfHonor);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($repeatedClassroom->getClassroom())
                                ->setMotif(null);

                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent = new Student();
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($repeatedClassroom)
                            ->setRepeater($repeaterYes);
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setDecision($newDecision)
                            ->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($repeatedClassroom)
                                ->setRepeater($repeaterYes);
                        }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                    break;
                }
            break;

            case ConstantsClass::DECISION_FINISHED:
                switch ($newDecision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($passedClassroom->getClassroom())
                                ->setMotif(null);
                            
                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($passedClassroom)
                            ->setRepeater($repeaterNo);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $student->setDecision($newDecision)
                                ->setNextClassroomName($repeatedClassroom->getClassroom())
                                ->setMotif(null);

                        // on construit le new student pour le next school year et on met à jour le current student
                        $newStudent = new Student();
                        $newStudent->setFullName($student->getFullName())
                            ->setBirthday($student->getBirthday())
                            ->setBirthplace($student->getBirthplace())
                            ->setPhoto($student->getPhoto())
                            ->setRegistrationNumber($student->getRegistrationNumber())
                            ->setCreatedBy($this->security->getUser())
                            ->setUpdatedBy($this->security->getUser())
                            ->setSex($student->getSex())
                            ->setPrevId($student->getId())
                            ->setSchoolYear($nextSchoolYear)
                            ->setClassroom($repeatedClassroom)
                            ->setRepeater($repeaterYes);
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $student->setDecision($newDecision)
                            ->setMotif($newMotif);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $student->setDecision($newDecision)
                        ->setNextClassroomName($repeatedClassroom->getClassroom());

                        if($nextYearStudent != null)
                        {
                            $nextYearStudent->setClassroom($repeatedClassroom)
                                ->setRepeater($repeaterYes);
                        }
                        
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $student->setDecision($newDecision);
                        
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                    break;
                }
            break;
        }

        $this->em->persist($student);
        
        // si l'élève se trouvait déjà au next year
        if($nextYearStudent != null)
        {
            if(($newDecision->getDecision() == ConstantsClass::DECISION_EXPELLED) || 
            ($newDecision->getDecision() == ConstantsClass::DECISION_CATCHUPPED) )
            {
                foreach ($nextYearStudent->getReports() as $report) 
                {
                    $this->em->remove($report);
                }

                foreach ($nextYearStudent->getAbsences() as $absence) 
                {
                    $this->em->remove($absence);
                }

                foreach ($nextYearStudent->getRegistrationHistories() as $registrationHistorie) 
                {
                    $this->em->remove($registrationHistorie);
                }

                foreach ($nextYearStudent->getRegistrations() as $registration) 
                {
                    $this->em->remove($registration);
                }

                foreach ($nextYearStudent->getEvaluations() as $evaluation) 
                {
                    $this->em->remove($evaluation);
                }

                foreach ($nextYearStudent->getConseils() as $conseil) 
                {
                    $this->em->remove($conseil);
                }

                $this->em->remove($nextYearStudent);
    
            }else
            {
                $this->em->persist($nextYearStudent);
            }
        }


        if($newStudent->getFullName()) 
        {
            $this->em->persist($newStudent);
        }

        $this->em->flush();

        $this->addFlash('info', $this->translator->trans('Deliberation updated with success !'));
        $mySession->set('saisiNotes', 1);

        return $this->redirectToRoute('deliberation_displayDeliberation', [
            'idC' => $idC,
            'notification' => 1,
        ]);

    }

}
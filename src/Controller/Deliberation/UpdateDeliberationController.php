<?php

namespace App\Controller\Deliberation;

use App\Entity\ConstantsClass;
use App\Entity\Student;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\DecisionRepository;
use App\Repository\RepeaterRepository;
use App\Repository\StudentRepository;
use App\Service\SchoolYearService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/deliberation")
 */
class UpdateDeliberationController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository,  protected DecisionRepository $decisionRepository, protected StudentRepository $studentRepository, protected RepeaterRepository $repeaterRepository, protected Security $security, protected EntityManagerInterface $em,  protected SchoolYearService $schoolYearService, protected TranslatorInterface $translator)
    {
    }

    /**
     * @Route("/updateDeliberation/{idS<[0-9]+>}/{idC<[0-9]+>}", name="deliberation_updateDeliberation")
     */
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

        return $this->redirectToRoute('deliberation_displayDeliberation', [
            'idC' => $idC,
            'notification' => 1,
        ]);

    }

}
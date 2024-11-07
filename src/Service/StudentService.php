<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use App\Entity\Report;
use App\Entity\Student;
use App\Entity\Evaluation;
use App\Entity\SchoolYear;
use App\Entity\ReportTrash;
use App\Entity\AbsenceTrash;
use App\Entity\Classroom;
use App\Entity\ConseilTrash;
use App\Entity\Registration;
use App\Entity\StudentTrash;
use App\Entity\ConstantsClass;
use App\Entity\EvaluationTrash;
use App\Entity\RegistrationTrash;
use App\Repository\TermRepository;
use App\Repository\ReportRepository;
use App\Repository\SequenceRepository;
use App\Entity\RegistrationHistoryTrash;
use App\Repository\EvaluationRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RegistrationRepository;
use App\Repository\RegistrationHistoryRepository;

class StudentService
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected RegistrationRepository $registrationRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository)
    {
    }

    /**
     * On supprime l'élève et toutes ses notes et ses absences
     */

    public function deleteStudent(Student $student, User $user): void
    {
        //je récupère toutes les données concernant l'élève à supprimer
        $studentReports = $student->getReports();
        $studentAbsences = $student->getAbsences();
        $studentRegistrations = $student->getRegistrations();
        $studentEvaluations = $student->getEvaluations();
        $studentConseils = $student->getConseils();
        $studentFeesHistories = $student->getRegistrationHistories();
        
        ////je déclare les nouvelles instances corbeilles
        $studentTrash = new StudentTrash; 

        ////je mets dans ma corbeille
        $now = new DateTime('now');
        $studentTrash->setFullName($student->getFullName())
                    ->setBirthday($student->getBirthday())
                    ->setBirthplace($student->getBirthplace())
                    ->setRegistrationNumber($student->getRegistrationNumber())
                    ->setPhoto($student->getPhoto())
                    ->setClassroom($student->getClassroom()->getClassroom())
                    ->setSex($student->getSex())
                    ->setSchoolYear($student->getSchoolYear())
                    ->setDeletedAt($now)
                    ->setDeletedBy($user)
                    ->setFatherName($student->getFatherName())
                    ->setMotherName($student->getMotherName())
                    ->setTelephonePere($student->getTelephonePere())
                    ->setTelephoneMere($student->getTelephoneMere())
                    ->setRepeater($student->getRepeater())
                    ->setQrCode($student->getQrCode())
                    ->setNumeroWhatsapp($student->getNumeroWhatsapp())
                    ->setAutochtone($student->isAutochtone())
                    ->setDrepanocytose($student->isDrepanocytose())
                    ->setApte($student->isApte())
                    ->setAsthme($student->isAsthme())
                    ->setCovid($student->isCovid())
                    ->setAllergie($student->isAllergie())
                    ->setClubMulticulturel($student->isClubMulticulturel())
                    ->setClubScientifique($student->isClubScientifique())
                    ->setClubJournal($student->isClubJournal())
                    ->setClubEnvironnement($student->isClubEnvironnement())
                    ->setClubSante($student->isClubSante())
                    ->setClubRethorique($student->isClubRethorique())
                    ->setClubBilingue($student->isClubBilingue())
                    ->setClubLv2($student->isClubLv2())
                    ->setFrere($student->isFrere())
                    ->setSoeur($student->isSoeur())
                    ->setEnseignant($student->isEnseignant())
                    ->setQrCodeFiche($student->getQrCodeFiche())
                    ->setOperateur($student->getOperateur())
                    ->setModeAdmission($student->getModeAdmission())
                    ->setClasseEntree($student->getClasseEntree())
                    ->setClasseFrereSoeur($student->getClasseFrereSoeur())
                    ->setNumeroHcr($student->getNumeroHcr())
                    ->setProfessionPere($student->getProfessionPere())
                    ->setProfessionMere($student->getProfessionMere())
                    ->setTuteur($student->getTuteur())
                    ->setTelephoneTuteur($student->getTelephoneTuteur())
                    ->setPersonneAContacterEnCasUergence($student->getPersonneAContacterEnCasUergence())
                    ->setTelephonePersonneEnCasUrgence($student->getTelephonePersonneEnCasUrgence())
                    ->setDatePremiereEntreeEtablissementAt($student->getDatePremiereEntreeEtablissementAt())
                    ->setEtablisementFrequenteAnDernier($student->getEtablisementFrequenteAnDernier())
                    ->setSiOuiAllergie($student->getSiOuiAllergie())
                    ->setGroupeSanguin($student->getGroupeSanguin())
                    ->setRhesus($student->getRhesus())
                    ->setAutreConnaisanceEtablissement($student->getAutreConnaisanceEtablissement())
                    ->setNomPersonneEtablissement($student->getNomPersonneEtablissement())
                    ->setTelephonePersonneEtablissement($student->getTelephonePersonneEtablissement())
                    ->setSolvable($student->isSolvable())
                    ->setSubSystem($student->getSubSystem())
                    ->setSlug($student->getSlug())
                ;

        $this->em->persist($studentTrash);

        if(count($studentEvaluations))
        {
            // On supprime les évaluations de l'élève
            foreach ($studentEvaluations as $studentEvaluation) 
            {
                $studentEvaluationTrash = new EvaluationTrash;
                $studentEvaluationTrash->setStudentTrash($studentTrash)
                                    ->setSequence($studentEvaluation->getSequence())
                                    ->setCreatedBy($user)
                                    ->setUpdatedBy($user)
                                    ->setLesson($studentEvaluation->getLesson())
                                    ->setMark($studentEvaluation->getMark())
                                    ->setCreatedAt($now)
                                    ->setUpdatedAt($now)
                                    ;
                $this->em->persist($studentEvaluationTrash);

                $this->em->remove($studentEvaluation);
            }
                
        }

        
        if(count($studentConseils))
        {
            // On supprime les évaluations de l'élève
            foreach ($studentConseils as $studentConseil) 
            {
                $studentConseilTrash = new ConseilTrash;
                $studentConseilTrash->setStudentTrash($studentTrash)
                                    ->setTerm($studentConseil->getTerm())
                                    ->setCreatedBy($user)
                                    ->setUpdatedBy($user)
                                    ->setDecision($studentConseil->getDecision())
                                    ->setMotif($studentConseil->getMotif())
                                    ->setCreatedAt($now)
                                    ->setUpdatedAt($now)
                                    ;
                $this->em->persist($studentConseilTrash);
                
                $this->em->remove($studentConseil);
            }
                
        }

        if(count($studentAbsences))
        {
            // On supprime les heures d'absence de l'élève
            foreach ($studentAbsences as $absence) 
            {
                $studentAbsenceTrash = new AbsenceTrash;
                $studentAbsenceTrash->setStudentTrash($studentTrash)
                                    ->setTerm($absence->getTerm())
                                    ->setAbsence($absence->getAbsence())
                                    ->setCreatedBy($user)
                                    ->setUpdatedBy($user)
                                    ->setCreatedAt($now)
                                    ->setUpdatedAt($now)
                ;
                $this->em->persist($studentAbsenceTrash);
                $this->em->remove($absence);
            }

        }

        //On supprime les frais de l'élève de la table registration
        if(count($studentRegistrations))
        {
            foreach ($studentRegistrations as $studentRegistration) 
            {
                $studentRegistrationTrash = new RegistrationTrash;
                $studentRegistrationTrash->setApeeFees($studentRegistration->getApeeFees())
                                    ->setComputerFees($studentRegistration->getComputerFees())
                                    ->setCleanSchoolFees($studentRegistration->getCleanSchoolFees())
                                    ->setMedicalBookletFees($studentRegistration->getMedicalBookletFees())
                                    ->setPhotoFees($studentRegistration->getPhotoFees())
                                    ->setStudentTrash($studentTrash)
                ;
                $this->em->persist($studentRegistrationTrash);
                $this->em->remove($studentRegistration);
            }
            
        }
        
        if(count($studentFeesHistories))
        {
            // On supprime les frais de l'élève
            foreach ($studentFeesHistories as $studentFeesHistorie) 
            {
                $studentFeesHistoryTrash = new RegistrationHistoryTrash;
                $studentFeesHistoryTrash->setApeeFees($studentFeesHistorie->getApeeFees())
                                    ->setComputerFees($studentFeesHistorie->getComputerFees())
                                    ->setCleanSchoolFees($studentFeesHistorie->getCleanSchoolFees())
                                    ->setMedicalBookletFees($studentFeesHistorie->getMedicalBookletFees())
                                    ->setPhotoFees($studentFeesHistorie->getPhotoFees())
                                    ->setStudentTrash($studentTrash)
                ;
                $this->em->persist($studentFeesHistoryTrash);
                $this->em->remove($studentFeesHistorie);
            }

        }

        if(count($studentReports))
        {
            // On supprime les reports de l'élève
            foreach ($studentReports as $studentReport) 
            { 
                $studentReportTrash = new ReportTrash;
                $studentReportTrash->setStudentTrash($studentTrash)
                            ->setMoyenne($studentReport->getMoyenne())
                            ->setTerm($studentReport->getTerm())
                            ->setRang($studentReport->getRang())
                ;
                $this->em->persist($studentReportTrash);
                $this->em->remove($studentReport);
            }

        }
        
        // On supprime l'élève
        
        $this->em->remove($student);
        
        $this->em->flush();
    }

     /**
     * On supprime l'élève et toutes ses notes et ses absences
     */

    public function deleteStudentDeliberationCancel(Student $student, Classroom $classroom): void
    {
        //je récupère toutes les données concernant l'élève à supprimer
        $studentRegistrations = $student->getRegistrations();
        
        //On supprime les frais de l'élève de la table registration
        if(count($studentRegistrations))
        {
            foreach ($studentRegistrations as $studentRegistration) 
            {
                $this->em->remove($studentRegistration);
            }
            
        }

        // On supprime l'élève
        $this->em->remove($student);
        
        $classroom->setIsDeliberated(0);

        $this->em->persist($classroom);
        $this->em->flush();
    }

     /**
      * On ajoute l'élève et le déclare non classé dans les evaluastion déja enregistrées
      */
    public function addStudent(Student $student, User $user, Registration $registration, SchoolYear $schoolYear): void
    {
        $now = new DateTime('now');

        $student->setCreatedBy($user)
            ->setCreatedAt($now);

        // On verifie s'il y a déjà les notes enregistrées dans la classe
        // Si oui on déclare l'élève comme non classé dans ces évaluations
        $lessons = $student->getClassroom()->getLessons();
        $sequences = $this->sequenceRepository->findAll();

        foreach ($lessons as $lesson) 
        {
            if($lesson->getEvaluations())
            {
                foreach ($sequences as $sequence) 
                {
                    if($this->evaluationRepository->findOneBy([
                        'lesson' => $lesson,
                        'sequence' => $sequence
                    ]))
                    {
                        $studentEvaluation = new Evaluation;
                        $studentEvaluation->setLesson($lesson)
                            ->setSequence($sequence)
                            ->setStudent($student)
                            ->setMark(ConstantsClass::UNRANKED_MARK)
                            ->setCreatedBy($user)
                            ->setUpdatedBy($user)
                        ;

                        $this->em->persist($studentEvaluation);
                    }
                }
            }
            
        }

        // On verifie si les absences sont déjà saisies dans la classe

        // Si les bulletins sont déjà imprimés, on set le student comme non classé
        if($this->reportRepository->findAlreadyReport($student->getClassroom()))
        {
            $terms = $this->termRepository->findAll();

            foreach ($terms as $term) 
            {
                if($this->reportRepository->findAlreadyReport($student->getClassroom(), $term))
                {
                    $studentReport = new Report;

                    $studentReport
                        ->setStudent($student)
                        ->setTerm($term)
                        ->setMoyenne(ConstantsClass::UNRANKED_AVERAGE)
                        ->setRang(ConstantsClass::UNRANKED_RANK_DB)
                        ;
                    
                    $this->em->persist($studentReport);
                }

            }
        }

        /////je met à 0 tous ses frais dans la table Registration
        $registration->setApeeFees(0)
                    ->setComputerFees(0)
                    ->setCleanSchoolFees(0)
                    ->setMedicalBookletFees(0)
                    ->setPhotoFees(0)
                    ->setSchoolFees(0)
                    ->setStampFees(0)
                    ->setExamFees(0)
                    ->setCreatedBy($user)
                    ->setCreatedAt($now)
                    ->setSchoolYear($schoolYear)
                    ->setUpdatedBy($user)
                    ->setStudent($student)
                ;
            
        // On ajoute dans la BD
        $this->em->persist($student);
        $this->em->persist($registration);
        $this->em->flush();
    }


    /**
     * On déplace l'élève et toutes ses notes et ses absences
     */
    public function updateStudentForClassroom(Student $student, Student $newStudentToAdd, User $user, Classroom $newStudentClassroom): void
    {
        //je récupère toutes les données concernant l'élève à modifier
        $studentReports = $student->getReports();
        $studentAbsences = $student->getAbsences();
        $studentEvaluations = $student->getEvaluations();
        $studentRegistrations = $student->getRegistrations();
        $studentFeesHistories = $student->getRegistrationHistories();
        

        if(count($studentEvaluations))
        {
            // On supprime les évaluations de l'élève
            foreach ($studentEvaluations as $studentEvaluation) 
            {
                $this->em->remove($studentEvaluation);
            }
               
        }

        if(count($studentAbsences))
        {
            // On supprime les heures d'absence de l'élève
            foreach ($studentAbsences as $absence) 
            {
                $this->em->remove($absence);
            }

        }
        
        //On met à jour ses frais
        if(count($studentRegistrations))
        {
            foreach ($studentRegistrations as $studentRegistration) 
            {
                $studentRegistration->setStudent($newStudentToAdd);
                $this->em->persist($studentRegistration);
            }
            
        }
        
        if(count($studentFeesHistories))
        {
            foreach ($studentFeesHistories as $studentFeesHistorie) 
            {
                $studentFeesHistorie->setStudent($newStudentToAdd);
                $this->em->persist($studentFeesHistorie);
            }

        }

        if(count($studentReports))
        {
            // On supprime les reports de l'élève
            foreach ($studentReports as $studentReport) 
            {
                $this->em->remove($studentReport);
            }
        }
        
        // On verifie s'il y a déjà les notes enregistrées dans la classe
        // Si oui on déclare l'élève comme non classé dans ces évaluations
        $lessons = $newStudentClassroom->getLessons();
        $sequences = $this->sequenceRepository->findAll();

        foreach ($lessons as $lesson) 
        {
            if($lesson->getEvaluations())
            {
                foreach ($sequences as $sequence) 
                {
                    if($this->evaluationRepository->findOneBy([
                        'lesson' => $lesson,
                        'sequence' => $sequence
                    ]))
                    {
                        $studentEvaluation = new Evaluation;
                        $studentEvaluation->setLesson($lesson)
                            ->setSequence($sequence)
                            ->setStudent($newStudentToAdd)
                            ->setMark(ConstantsClass::UNRANKED_MARK)
                            ->setCreatedBy($user)
                            ->setUpdatedBy($user)
                        ;

                        $this->em->persist($studentEvaluation);
                    }
                }
            }
            
        }

        // Si les bulletins sont déjà imprimés, on set le student comme non classé
        if($this->reportRepository->findAlreadyReport($newStudentToAdd->getClassroom()))
        {
            $terms = $this->termRepository->findAll();

            foreach ($terms as $term) 
            {
                if($this->reportRepository->findAlreadyReport($newStudentToAdd->getClassroom(), $term))
                {
                    $studentReport = new Report;

                    $studentReport
                        ->setStudent($newStudentToAdd)
                        ->setTerm($term)
                        ->setMoyenne(ConstantsClass::UNRANKED_AVERAGE)
                        ->setRang(ConstantsClass::UNRANKED_RANK_DB)
                        ;
                    
                    $this->em->persist($studentReport);
                }

            }
        }
        
        $this->em->remove($student);
        $this->em->flush();
    }


    public function deleteStudentForClassroom(Student $student): void
    {
        //je récupère toutes les données concernant l'élève à supprimer
        $studentReports = $student->getReports();
        $studentAbsences = $student->getAbsences();
        $studentRegistrations = $student->getRegistrations();
        $studentEvaluations = $student->getEvaluations();
        $studentFeesHistories = $student->getRegistrationHistories();

        if(count($studentEvaluations))
        {
            // On supprime les évaluations de l'élève
            foreach ($studentEvaluations as $studentEvaluation) 
            {
                $this->em->remove($studentEvaluation);
            }
                
        }

        if(count($studentAbsences))
        {
            // On supprime les heures d'absence de l'élève
            foreach ($studentAbsences as $absence) 
            {
                $this->em->remove($absence);
            }

        }

        //On supprime les frais de l'élève de la table registration
        if(count($studentRegistrations))
        {
            foreach ($studentRegistrations as $studentRegistration) 
            {
                $this->em->remove($studentRegistration);
            }
            
        }
        
        if(count($studentFeesHistories))
        {
            // On supprime les frais de l'élève
            foreach ($studentFeesHistories as $studentFeesHistorie) 
            {
                $this->em->remove($studentFeesHistorie);
            }

        }

        if(count($studentReports))
        {
            // On supprime les reports de l'élève
            foreach ($studentReports as $studentReport) 
            {
                $this->em->remove($studentReport);
            }

        }
        
        // On supprime l'élève
        
        $this->em->remove($student);
        
        $this->em->flush();
    }
  

    //  public function studentPhotoManagement()
    //  {
    //     if($student->getPhoto())
    //     {
    //         $imagine->open(getcwd().'/images/students/'.$student->getPhoto())->resize(new Box(150, 200))->save(getcwd().'/images/students/'.$student->getPhoto());
    //     }
    //  }
}
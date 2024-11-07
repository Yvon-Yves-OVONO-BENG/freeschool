<?php

namespace App\Service;

use DateTime;
use App\Entity\User;
use App\Entity\Report;
use App\Entity\Absence;
use App\Entity\Conseil;
use App\Entity\Student;
use App\Entity\Evaluation;
use App\Entity\Registration;
use App\Entity\StudentTrash;
use App\Repository\TermRepository;
use App\Entity\RegistrationHistory;
use App\Repository\ReportRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class StudentTrashService
{
    public function __construct(
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected ReportRepository $reportRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected SchoolYearRepository $schoolYearRepository,
        )
    { }

    /**
     * fontion qui permet de restaurer un élève 
     *
     * @param Request $request
     * @param StudentTrash $studentTrash
     * @param User $user
     * @return void
     */
    public function restoreStudentTrash(Request $request, Student $studentTrash, User $user): void
    {
        //je récupère outes les données concernant l'élève à supprimer
        $studentReportsTrashs = $studentTrash->getReportTrashes();
        $studentAbsencesTrashs = $studentTrash->getAbsenceTrashes();
        $studentRegistrationsTrashs = $studentTrash->getRegistrationTrashes();
        $studentEvaluationsTrashs = $studentTrash->getEvaluationTrashes();
        $studentConseilsTrashs = $studentTrash->getConseilTrashes();
        $studentFeesHistoriesTrashs = $studentTrash->getRegistrationHistoryTrashes();
        
        ////je déclare les nouvelles instances corbeilles
        $student = new Student;
        $studentReport = new Report;
        $studentAbsence = new Absence;
        $studentRegistration = new Registration;
        $studentEvaluation = new Evaluation;
        $studentConseil = new Conseil;
        $studentFeesHistory = new RegistrationHistory; 
        

        $mySession = $request->getSession();
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
        
        $classroom = $this->classroomRepository->findBy([
            'classroom' => $studentTrash->getClassroom(),
            'schoolYear' => $schoolYear
        ]);
        
        $now = new DateTime('now');
        $student->setFullName($studentTrash->getFullName())
                    ->setBirthday($studentTrash->getBirthday())
                    ->setBirthplace($studentTrash->getBirthplace())
                    ->setRegistrationNumber($studentTrash->getRegistrationNumber())
                    ->setPhoto($studentTrash->getPhoto())
                    ->setClassroom($classroom[0])
                    ->setSex($studentTrash->getSex())
                    ->setSchoolYear($studentTrash->getSchoolYear())
                    ->setCreatedAt($now)
                    ->setCreatedBy($user)
                    ->setFatherName($studentTrash->getFatherName())
                    ->setMotherName($studentTrash->getMotherName())
                    ->setTelephonePere($studentTrash->getTelephonePere())
                    ->setTelephoneMere($studentTrash->getTelephoneMere())
                    ->setRepeater($studentTrash->getRepeater())
                    ->setQrCode($studentTrash->getQrCode())
                    ->setAutochtone($studentTrash->isAutochtone())
                    ->setDrepanocytose($studentTrash->isDrepanocytose())
                    ->setApte($studentTrash->isApte())
                    ->setAsthme($studentTrash->isAsthme())
                    ->setCovid($studentTrash->isCovid())
                    ->setAllergie($studentTrash->isAllergie())
                    ->setClubMulticulturel($studentTrash->isClubMulticulturel())
                    ->setClubScientifique($studentTrash->isClubScientifique())
                    ->setClubJournal($studentTrash->isClubJournal())
                    ->setClubEnvironnement($studentTrash->isClubEnvironnement())
                    ->setClubSante($studentTrash->isClubSante())
                    ->setClubBilingue($studentTrash->isClubBilingue())
                    ->setClubLv2($studentTrash->isClubLv2())
                    ->setNumeroWhatsapp($studentTrash->getNumeroWhatsapp())
                    ->setClubRethorique($studentTrash->isClubRethorique())
                    ->setFrere($studentTrash->isFrere())
                    ->setSoeur($studentTrash->isSoeur())
                    ->setEnseignant($studentTrash->isEnseignant())
                    ->setQrCodeFiche($studentTrash->getQrCodeFiche())
                    ->setOperateur($studentTrash->getOperateur())
                    ->setModeAdmission($studentTrash->getModeAdmission())
                    ->setClasseEntree($studentTrash->getClasseEntree())
                    ->setClasseFrereSoeur($studentTrash->getClasseFrereSoeur())
                    ->setNumeroHcr($studentTrash->getNumeroHcr())
                    ->setProfessionPere($studentTrash->getProfessionPere())
                    ->setProfessionMere($studentTrash->getProfessionMere())
                    ->setTuteur($studentTrash->getTuteur())
                    ->setTelephoneTuteur($studentTrash->getTelephoneTuteur())
                    ->setPersonneAContacterEnCasUergence($studentTrash->getPersonneAContacterEnCasUergence())
                    ->setTelephonePersonneEnCasUrgence($studentTrash->getTelephonePersonneEnCasUrgence())
                    ->setDatePremiereEntreeEtablissementAt($studentTrash->getDatePremiereEntreeEtablissementAt())
                    ->setEtablisementFrequenteAnDernier($studentTrash->getEtablisementFrequenteAnDernier())
                    ->setSiOuiAllergie($studentTrash->getSiOuiAllergie())
                    ->setGroupeSanguin($studentTrash->getGroupeSanguin())
                    ->setRhesus($studentTrash->getRhesus())
                    ->setAutreConnaisanceEtablissement($studentTrash->getAutreConnaisanceEtablissement())
                    ->setNomPersonneEtablissement($studentTrash->getNomPersonneEtablissement())
                    ->setTelephonePersonneEtablissement($studentTrash->getTelephonePersonneEtablissement())
                    ->setSolvable($studentTrash->isSolvable())
                    ->setSubSystem($studentTrash->getSubSystem())
                    ->setSlug($studentTrash->getSlug())
                ;
        
        // // On supprime l'élève
        $this->em->persist($student);
        
        
        if(count($studentEvaluationsTrashs))
        {
            // On supprime les évaluations de l'élève
            foreach ($studentEvaluationsTrashs as $studentEvaluationsTrash) 
            {
                $studentEvaluation = new Evaluation;
                $studentEvaluation->setStudent($student)
                                    ->setSequence($studentEvaluationsTrash->getSequence())
                                    ->setCreatedBy($user)
                                    ->setUpdatedBy($user)
                                    ->setLesson($studentEvaluationsTrash->getLesson())
                                    ->setMark($studentEvaluationsTrash->getMark())
                                    ->setCreatedAt($now)
                                    ->setUpdatedAt($now)
                                    ;
                $this->em->persist($studentEvaluation);

                $this->em->remove($studentEvaluationsTrash);
            }
                
        }

        if(count($studentConseilsTrashs))
        {
            // On supprime les évaluations de l'élève
            foreach ($studentConseilsTrashs as $studentConseilsTrash) 
            {
                $studentConseil = new Conseil;
                $studentConseil->setStudent($student)
                                    ->setTerm($studentConseilsTrash->getTerm())
                                    ->setCreatedBy($user)
                                    ->setUpdatedBy($user)
                                    ->setDecision($studentConseilsTrash->getDecision())
                                    ->setMotif($studentConseilsTrash->getMotif())
                                    ->setCreatedAt($now)
                                    ->setUpdatedAt($now)
                                    ;
                $this->em->persist($studentConseil);

                $this->em->remove($studentConseilsTrash);
            }
                
        }

        if(count($studentAbsencesTrashs))
        {
            // On supprime les heures d'absence de l'élève
            foreach ($studentAbsencesTrashs as $studentAbsencesTrash) 
            {
                $studentAbsence = new Absence;
                $studentAbsence->setStudent($student)
                                    ->setTerm($studentAbsencesTrash->getTerm())
                                    ->setAbsence($studentAbsencesTrash->getAbsence())
                                    ->setCreatedBy($user)
                                    ->setUpdatedBy($user)
                                    ->setCreatedAt($now)
                                    ->setUpdatedAt($now)
                ;
                $this->em->persist($studentAbsence);
                $this->em->remove($studentAbsencesTrash);
            }

        }

        //On supprime les frais de l'élève de la table registration
        if(count($studentRegistrationsTrashs))
        {
            foreach ($studentRegistrationsTrashs as $studentRegistrationsTrash) 
            {
                $studentRegistration = new Registration;
                $studentRegistration->setApeeFees($studentRegistrationsTrash->getApeeFees())
                                    ->setComputerFees($studentRegistrationsTrash->getComputerFees())
                                    ->setCleanSchoolFees($studentRegistrationsTrash->getCleanSchoolFees())
                                    ->setMedicalBookletFees($studentRegistrationsTrash->getMedicalBookletFees())
                                    ->setPhotoFees($studentRegistrationsTrash->getPhotoFees())
                                    ->setExamFees($studentRegistrationsTrash->getExamFees())
                                    ->setSchoolFees($studentRegistrationsTrash->getSchoolFees())
                                    ->setStudent($student)
                                    ->setSchoolYear($schoolYear)
                                    ->setCreatedAt($now)
                                    ->setUpdatedAt($now)
                                    ->setUpdatedBy($user)
                                    ->setCreatedBy($user)
                ;
                $this->em->persist($studentRegistration);
                $this->em->remove($studentRegistrationsTrash);
            }
            
        }
    
        if(count($studentFeesHistoriesTrashs))
        {
            // On supprime les frais de l'élève
            foreach ($studentFeesHistoriesTrashs as $studentFeesHistoriesTrash) 
            {
                $studentFeesHistory = new RegistrationHistory;
                $studentFeesHistory->setApeeFees($studentFeesHistoriesTrash->getApeeFees())
                                    ->setComputerFees($studentFeesHistoriesTrash->getComputerFees())
                                    ->setCleanSchoolFees($studentFeesHistoriesTrash->getCleanSchoolFees())
                                    ->setMedicalBookletFees($studentFeesHistoriesTrash->getMedicalBookletFees())
                                    ->setPhotoFees($studentFeesHistoriesTrash->getPhotoFees())
                                    ->setExamFees($studentFeesHistoriesTrash->getExamFees())
                                    ->setSchoolFees($studentFeesHistoriesTrash->getSchoolFees())
                                    ->setStudent($student)
                                    ->setSchoolYear($schoolYear)
                                    ->setCreatedAt($now)
                                    ->setUpdatedBy($user)
                                    ->setCreatedBy($user)
                ;
                $this->em->persist($studentFeesHistory);
                $this->em->remove($studentFeesHistoriesTrash);
            }

        }

        if(count($studentReportsTrashs))
        {
            // On supprime les reports de l'élève
            foreach ($studentReportsTrashs as $studentReportsTrash) 
            { 
                $studentReport = new Report;
                $studentReport->setStudent($student)
                            ->setTerm($studentReportsTrash->getTerm())
                            ->setMoyenne($studentReportsTrash->getMoyenne())
                            ->setRang($studentReportsTrash->getRang())
                ;
                $this->em->persist($studentReport);
                $this->em->remove($studentReportsTrash);
            }

        }
    
        $this->em->remove($studentTrash);
        
        $this->em->flush();
    }

    /**
     * fontion qui permet de restaurer un élève 
     *
     * @param Request $request
     * @param StudentTrash $studentTrash
     * @param User $user
     * @return void
     */
    public function restoreAllStudentTrash(Request $request, array $studentTrashs, User $user): void
    {
        foreach ($studentTrashs as $studentTrash) 
        {
            //je récupère outes les données concernant l'élève à supprimer
            $studentReportsTrashs = $studentTrash->getReportTrashes();
            $studentAbsencesTrashs = $studentTrash->getAbsenceTrashes();
            $studentRegistrationsTrashs = $studentTrash->getRegistrationTrashes();
            $studentEvaluationsTrashs = $studentTrash->getEvaluationTrashes();
            $studentConseilsTrashs = $studentTrash->getConseilTrashes();
            $studentFeesHistoriesTrashs = $studentTrash->getRegistrationHistoryTrashes();
            
            ////je déclare les nouvelles instances corbeilles
            $student = new Student;
            $studentReport = new Report;
            $studentAbsence = new Absence;
            $studentRegistration = new Registration;
            $studentEvaluation = new Evaluation;
            $studentConseil = new Conseil;
            $studentFeesHistory = new RegistrationHistory; 
            

            $mySession = $request->getSession();
            $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
            
            $classroom = $this->classroomRepository->findBy([
                'classroom' => $studentTrash->getClassroom(),
                'schoolYear' => $schoolYear
            ]);
            
            $now = new DateTime('now');
            $student->setFullName($studentTrash->getFullName())
                        ->setBirthday($studentTrash->getBirthday())
                        ->setBirthplace($studentTrash->getBirthplace())
                        ->setRegistrationNumber($studentTrash->getRegistrationNumber())
                        ->setPhoto($studentTrash->getPhoto())
                        ->setClassroom($classroom[0])
                        ->setSex($studentTrash->getSex())
                        ->setSchoolYear($studentTrash->getSchoolYear())
                        ->setCreatedAt($now)
                        ->setCreatedBy($user)
                        ->setFatherName($studentTrash->getFatherName())
                        ->setMotherName($studentTrash->getMotherName())
                        ->setTelephonePere($studentTrash->getTelephonePere())
                        ->setTelephoneMere($studentTrash->getTelephoneMere())
                        ->setRepeater($studentTrash->getRepeater())
                        ->setQrCode($studentTrash->getQrCode())
                        ->setAutochtone($studentTrash->isAutochtone())
                        ->setDrepanocytose($studentTrash->isDrepanocytose())
                        ->setApte($studentTrash->isApte())
                        ->setAsthme($studentTrash->isAsthme())
                        ->setCovid($studentTrash->isCovid())
                        ->setAllergie($studentTrash->isAllergie())
                        ->setClubMulticulturel($studentTrash->isClubMulticulturel())
                        ->setClubScientifique($studentTrash->isClubScientifique())
                        ->setClubJournal($studentTrash->isClubJournal())
                        ->setClubEnvironnement($studentTrash->isClubEnvironnement())
                        ->setClubSante($studentTrash->isClubSante())
                        ->setClubBilingue($studentTrash->isClubBilingue())
                        ->setClubLv2($studentTrash->isClubLv2())
                        ->setNumeroWhatsapp($studentTrash->getNumeroWhatsapp())
                        ->setClubRethorique($studentTrash->isClubRethorique())
                        ->setFrere($studentTrash->isFrere())
                        ->setSoeur($studentTrash->isSoeur())
                        ->setEnseignant($studentTrash->isEnseignant())
                        ->setQrCodeFiche($studentTrash->getQrCodeFiche())
                        ->setOperateur($studentTrash->getOperateur())
                        ->setModeAdmission($studentTrash->getModeAdmission())
                        ->setClasseEntree($studentTrash->getClasseEntree())
                        ->setClasseFrereSoeur($studentTrash->getClasseFrereSoeur())
                        ->setNumeroHcr($studentTrash->getNumeroHcr())
                        ->setProfessionPere($studentTrash->getProfessionPere())
                        ->setProfessionMere($studentTrash->getProfessionMere())
                        ->setTuteur($studentTrash->getTuteur())
                        ->setTelephoneTuteur($studentTrash->getTelephoneTuteur())
                        ->setPersonneAContacterEnCasUergence($studentTrash->getPersonneAContacterEnCasUergence())
                        ->setTelephonePersonneEnCasUrgence($studentTrash->getTelephonePersonneEnCasUrgence())
                        ->setDatePremiereEntreeEtablissementAt($studentTrash->getDatePremiereEntreeEtablissementAt())
                        ->setEtablisementFrequenteAnDernier($studentTrash->getEtablisementFrequenteAnDernier())
                        ->setSiOuiAllergie($studentTrash->getSiOuiAllergie())
                        ->setGroupeSanguin($studentTrash->getGroupeSanguin())
                        ->setRhesus($studentTrash->getRhesus())
                        ->setAutreConnaisanceEtablissement($studentTrash->getAutreConnaisanceEtablissement())
                        ->setNomPersonneEtablissement($studentTrash->getNomPersonneEtablissement())
                        ->setTelephonePersonneEtablissement($studentTrash->getTelephonePersonneEtablissement())
                        ->setSolvable($studentTrash->isSolvable())
                        ->setSubSystem($studentTrash->getSubSystem())
                        ->setSlug($studentTrash->getSlug())
                    ;
            
            // // On supprime l'élève
            $this->em->persist($student);
            
            
            if(count($studentEvaluationsTrashs))
            {
                // On supprime les évaluations de l'élève
                foreach ($studentEvaluationsTrashs as $studentEvaluationsTrash) 
                {
                    $studentEvaluation = new Evaluation;
                    $studentEvaluation->setStudent($student)
                                        ->setSequence($studentEvaluationsTrash->getSequence())
                                        ->setCreatedBy($user)
                                        ->setUpdatedBy($user)
                                        ->setLesson($studentEvaluationsTrash->getLesson())
                                        ->setMark($studentEvaluationsTrash->getMark())
                                        ->setCreatedAt($now)
                                        ->setUpdatedAt($now)
                                        ;
                    $this->em->persist($studentEvaluation);

                    $this->em->remove($studentEvaluationsTrash);
                }
                    
            }

            if(count($studentConseilsTrashs))
            {
                // On supprime les évaluations de l'élève
                foreach ($studentConseilsTrashs as $studentConseilsTrash) 
                {
                    $studentConseil = new Conseil;
                    $studentConseil->setStudent($student)
                                        ->setTerm($studentConseilsTrash->getTerm())
                                        ->setCreatedBy($user)
                                        ->setUpdatedBy($user)
                                        ->setDecision($studentConseilsTrash->getDecision())
                                        ->setMotif($studentConseilsTrash->getMotif())
                                        ->setCreatedAt($now)
                                        ->setUpdatedAt($now)
                                        ;
                    $this->em->persist($studentConseil);

                    $this->em->remove($studentConseilsTrash);
                }
                    
            }

            if(count($studentAbsencesTrashs))
            {
                // On supprime les heures d'absence de l'élève
                foreach ($studentAbsencesTrashs as $studentAbsencesTrash) 
                {
                    $studentAbsence = new Absence;
                    $studentAbsence->setStudent($student)
                                        ->setTerm($studentAbsencesTrash->getTerm())
                                        ->setAbsence($studentAbsencesTrash->getAbsence())
                                        ->setCreatedBy($user)
                                        ->setUpdatedBy($user)
                                        ->setCreatedAt($now)
                                        ->setUpdatedAt($now)
                    ;
                    $this->em->persist($studentAbsence);
                    $this->em->remove($studentAbsencesTrash);
                }

            }

            //On supprime les frais de l'élève de la table registration
            if(count($studentRegistrationsTrashs))
            {
                foreach ($studentRegistrationsTrashs as $studentRegistrationsTrash) 
                {
                    $studentRegistration = new Registration;
                    $studentRegistration->setApeeFees($studentRegistrationsTrash->getApeeFees())
                                        ->setComputerFees($studentRegistrationsTrash->getComputerFees())
                                        ->setCleanSchoolFees($studentRegistrationsTrash->getCleanSchoolFees())
                                        ->setMedicalBookletFees($studentRegistrationsTrash->getMedicalBookletFees())
                                        ->setPhotoFees($studentRegistrationsTrash->getPhotoFees())
                                        ->setExamFees($studentRegistrationsTrash->getExamFees())
                                        ->setSchoolFees($studentRegistrationsTrash->getSchoolFees())
                                        ->setStudent($student)
                                        ->setSchoolYear($schoolYear)
                                        ->setCreatedAt($now)
                                        ->setUpdatedAt($now)
                                        ->setUpdatedBy($user)
                                        ->setCreatedBy($user)
                    ;
                    $this->em->persist($studentRegistration);
                    $this->em->remove($studentRegistrationsTrash);
                }
                
            }
        
            if(count($studentFeesHistoriesTrashs))
            {
                // On supprime les frais de l'élève
                foreach ($studentFeesHistoriesTrashs as $studentFeesHistoriesTrash) 
                {
                    $studentFeesHistory = new RegistrationHistory;
                    $studentFeesHistory->setApeeFees($studentFeesHistoriesTrash->getApeeFees())
                                        ->setComputerFees($studentFeesHistoriesTrash->getComputerFees())
                                        ->setCleanSchoolFees($studentFeesHistoriesTrash->getCleanSchoolFees())
                                        ->setMedicalBookletFees($studentFeesHistoriesTrash->getMedicalBookletFees())
                                        ->setPhotoFees($studentFeesHistoriesTrash->getPhotoFees())
                                        ->setExamFees($studentFeesHistoriesTrash->getExamFees())
                                        ->setSchoolFees($studentFeesHistoriesTrash->getSchoolFees())
                                        ->setStudent($student)
                                        ->setSchoolYear($schoolYear)
                                        ->setCreatedAt($now)
                                        ->setUpdatedBy($user)
                                        ->setCreatedBy($user)
                    ;
                    $this->em->persist($studentFeesHistory);
                    $this->em->remove($studentFeesHistoriesTrash);
                }

            }

            if(count($studentReportsTrashs))
            {
                // On supprime les reports de l'élève
                foreach ($studentReportsTrashs as $studentReportsTrash) 
                { 
                    $studentReport = new Report;
                    $studentReport->setStudent($student)
                                ->setTerm($studentReportsTrash->getTerm())
                                ->setMoyenne($studentReportsTrash->getMoyenne())
                                ->setRang($studentReportsTrash->getRang())
                    ;
                    $this->em->persist($studentReport);
                    $this->em->remove($studentReportsTrash);
                }

            }
        
            $this->em->remove($studentTrash);
            
            $this->em->flush();
        }
        
    }

    /**
     * Fonction qui supprime définitivement un élève de la pubelle
     *
     * @param StudentTRash $studentTrash
     * @param User $user
     * @return void
     */
    public function deleteStudentTrash(Student $studentTrash): void
    {
        //je récupère toutes les données concernant l'élève à supprimer
        $studentReportTrashs = $studentTrash->getReports();
        $studentAbsenceTrashs = $studentTrash->getAbsences();
        $studentConseilTrashs = $studentTrash->getConseils();
        $studentEvaluationTrashs = $studentTrash->getEvaluations();
        $studentRegistrationTrashs = $studentTrash->getRegistrations();
        $studentFeesHistorieTrashs = $studentTrash->getRegistrationHistories();

        if(count($studentConseilTrashs))
        {
            // On supprime les conseils de l'élève
            foreach ($studentConseilTrashs as $studentConseilTrash) 
            {
                $this->em->remove($studentConseilTrash);
            }
                
        }

        if(count($studentEvaluationTrashs))
        {
            // On supprime les évaluations de l'élève
            foreach ($studentEvaluationTrashs as $studentEvaluationTrash) 
            {
                $this->em->remove($studentEvaluationTrash);
            }
                
        }

        if(count($studentAbsenceTrashs))
        {
            // On supprime les heures d'absence de l'élève
            foreach ($studentAbsenceTrashs as $absenceTrash) 
            {
                $this->em->remove($absenceTrash);
            }

        }

        //On supprime les frais de l'élève de la table registration
        if(count($studentRegistrationTrashs))
        {
            foreach ($studentRegistrationTrashs as $studentRegistrationTrash) 
            {
                $this->em->remove($studentRegistrationTrash);
            }
            
        }
        
        if(count($studentFeesHistorieTrashs))
        {
            // On supprime les frais de l'élève
            foreach ($studentFeesHistorieTrashs as $studentFeesHistorieTrash) 
            {
                $this->em->remove($studentFeesHistorieTrash);
            }

        }

        if(count($studentReportTrashs))
        {
            // On supprime les reports de l'élève
            foreach ($studentReportTrashs as $studentReportTrash) 
            { 
                $this->em->remove($studentReportTrash);
            }

        }
        
        // On supprime l'élève
        
        $this->em->remove($studentTrash);
        
        $this->em->flush();
    }

    /**
     * fonction qui vide la poubelle
     *
     * @param array $studentTrashs
     * @param User $user
     * @return void
     */
    public function deleteAllStudentTrash(array $studentTrashs): void
    {
        foreach ($studentTrashs as $studentTrash) 
        {
            //je récupère toutes les données concernant l'élève à supprimer
            $studentReportTrashs = $studentTrash->getReports();
            $studentAbsenceTrashs = $studentTrash->getAbsences();
            $studentConseilTrashs = $studentTrash->getConseils();
            $studentEvaluationTrashs = $studentTrash->getEvaluations();
            $studentRegistrationTrashs = $studentTrash->getRegistrations();
            $studentFeesHistorieTrashs = $studentTrash->getRegistrationHistories();

            if(count($studentConseilTrashs))
            {
                // On supprime les évaluations de l'élève
                foreach ($studentConseilTrashs as $studentConseilTrash) 
                {
                    $this->em->remove($studentConseilTrash);
                }
                    
            }

            if(count($studentEvaluationTrashs))
            {
                // On supprime les évaluations de l'élève
                foreach ($studentEvaluationTrashs as $studentEvaluationTrash) 
                {
                    $this->em->remove($studentEvaluationTrash);
                }
                    
            }

            if(count($studentAbsenceTrashs))
            {
                // On supprime les heures d'absence de l'élève
                foreach ($studentAbsenceTrashs as $absenceTrash) 
                {
                    $this->em->remove($absenceTrash);
                }

            }

            //On supprime les frais de l'élève de la table registration
            if(count($studentRegistrationTrashs))
            {
                foreach ($studentRegistrationTrashs as $studentRegistrationTrash) 
                {
                    $this->em->remove($studentRegistrationTrash);
                }
                
            }
            
            if(count($studentFeesHistorieTrashs))
            {
                // On supprime les frais de l'élève
                foreach ($studentFeesHistorieTrashs as $studentFeesHistorieTrash) 
                {
                    $this->em->remove($studentFeesHistorieTrash);
                }

            }

            if(count($studentReportTrashs))
            {
                // On supprime les reports de l'élève
                foreach ($studentReportTrashs as $studentReportTrash) 
                { 
                    $this->em->remove($studentReportTrash);
                }

            }
            
            // On supprime l'élève
            
            $this->em->remove($studentTrash);
            
            $this->em->flush();
        }
        
    }

}
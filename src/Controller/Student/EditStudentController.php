<?php

namespace App\Controller\Student;

use Imagine\Image\Box;
use Imagine\Gd\Imagine;
use App\Entity\Classroom;
use App\Form\StudentType;
use App\Entity\Evaluation;
use App\Service\StrService;
use App\Entity\ConstantsClass;
use App\Service\QrcodeService;
use App\Service\StudentService;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use App\Repository\SubSystemRepository;
use App\Repository\EvaluationRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RegistrationHistoryRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/student")]
class EditStudentController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em,  
        protected QrcodeService $qrcodeService, 
        protected StudentService $studentService, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected StudentRepository $studentRepository,
        protected SequenceRepository $sequenceRepository, 
        protected SubSystemRepository $subSystemRepository,
        protected EvaluationRepository $evaluationRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        protected RegistrationRepository $registrationRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository, 
        )
    {}

    #[Route("/editStudent/{slug}", name:"student_editStudent")]
    public function editStudent(Request $request, string $slug): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        ;

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

        if($mySession)
        {
            $verrou = $mySession->get('verrou');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }

        $imagine = new Imagine;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        // on recupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($mySession->get('schoolYear')->getId());
        $storedClassroom = new Classroom();

        $school = $this->schoolRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        $schoolName = $school[0]->getFrenchName()." / ".$school[0]->getEnglishName();

        #je récupère l'elève que je veux modifier
        $student = $this->studentRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $form = $this->createForm(StudentType::class, $student);

        // On recupère la classe avant modification de l'éleve
        $oldStudentClassroom = $student->getClassroom();

        $form->handleRequest($request);

        if(isset($matricule))
        {
            // On set le matricule si c'est géré automatiquement
            $student->setRegistrationNumber($matricule);
        }
        
        if ($form->isSubmitted() && $form->isValid()) 
        { 
            $qrCode = null;
            $data =  $form->getData();

            if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) {
                $qrCode = $this->qrcodeService->qrcode($schoolName." : Ce bulletin appartient à l'élève : ".$data->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear()." Classe : ".$student->getClassroom()->getClassroom());

                $qrCodeFiche = $this->qrcodeService->qrcode($schoolName." : Cette fiche appartient à l'élève : ".$data->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear()." Classe : ".$student->getClassroom()->getClassroom());
                
                $qrCodeRollOfHonor = $this->qrcodeService->qrcode($schoolName." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$data->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear()." Classe : ".$student->getClassroom()->getClassroom());

            } else 
            {
                $qrCode = $this->qrcodeService->qrcode($schoolName." : This report belongs to the student : ".$data->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber())." School Year : ".$schoolYear->getSchoolYear()." Classroom : ".$student->getClassroom()->getClassroom());

                $qrCodeFiche = $this->qrcodeService->qrcode($schoolName." : This sheet belongs to the student : ".$data->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber())." School Year  : ".$schoolYear->getSchoolYear()." Classroom : ".$student->getClassroom()->getClassroom());
            
                $qrCodeRollOfHonor = $this->qrcodeService->qrcode($schoolName." : This roll of honor belongs to the student: ".$data->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber())." School Year  : ".$schoolYear->getSchoolYear()." Classroom : ".$student->getClassroom()->getClassroom());

            }

            $autochtone = $request->request->get('autochtone');
            $drepanocytose = $request->request->get('drepanocytose');
            $apte = $request->request->get('apte');
            $asthme = $request->request->get('asthme');
            $covid = $request->request->get('covid');
            $allergie = $request->request->get('allergie');
            $clubMulticulturel = $request->request->get('clubMulticulturel');
            $clubScience = $request->request->get('clubScience');
            $clubJournal = $request->request->get('clubJournal');
            $clubEnvironnement = $request->request->get('clubEnvironnement');
            $clubSante = $request->request->get('clubSante');
            $clubRethorique = $request->request->get('clubRethorique');
            $clubFrere = $request->request->get('clubFrere');
            $clubSoeur = $request->request->get('clubSoeur');
            $clubEnseignant = $request->request->get('clubEnseignant');
            // on enlève les carctères speciaux et on met en majuscule
            // le fullName et le birthplace

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
            $dernierStudent = $this->studentRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierStudent) 
            {
                $id = $dernierStudent[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $student->setFullName($this->strService->strToUpper($student->getFullName()))
                    ->setRegistrationNumber($this->strService->strToUpper($student->getRegistrationNumber()))
                    ->setBirthplace($this->strService->strToUpper($student->getBirthplace()))
                    ->setQrcode($qrCode)
                    ->setAutochtone($autochtone)
                    ->setDrepanocytose($drepanocytose)
                    ->setApte($apte)
                    ->setAsthme($asthme)
                    ->setCovid($covid)
                    ->setAllergie($allergie)
                    ->setClubMulticulturel($clubMulticulturel)
                    ->setClubScientifique($clubScience)
                    ->setClubJournal($clubJournal)
                    ->setClubEnvironnement($clubEnvironnement)
                    ->setClubSante($clubSante)
                    ->setClubRethorique($clubRethorique)
                    ->setFrere($clubFrere)
                    ->setSoeur($clubSoeur)
                    ->setEnseignant($clubEnseignant)
                    ->setQrCodeFiche($qrCodeFiche)
                    ->setQrCodeRollOfHonor($qrCodeRollOfHonor)
                    ->setSlug($slug.$id)
                    ->setSubSystem($subSyste)
                    ->setSchoolYear($schoolYear);
                    
            
            if($student->getId()) // Si le id existe alors c'est une modification
            {
                // On recupère la classe après modification de l'élève
                $newStudentClassroom = $student->getClassroom();               
                $prevId = $student->getPrevId();  

                if($newStudentClassroom->getId() == $oldStudentClassroom->getId())
                {
                    // Si la classe n'a pas été modifiée
                    $qrCode = null;
                    $data =  $form->getData();

                    $qrCode = $this->qrcodeService->qrcode($schoolName." : Ce bulletin appartient à l'élève : ".$data->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." année scolaire : ".$schoolYear->getSchoolYear());
                    
                    $student->setUpdatedBy($this->getUser())
                            ->setQrCode($qrCode);

                    $this->em->flush();

                }else
                {
                    // Si la classe a été modifié
                    $user = $this->getUser();
                    
                    // On ajoute l'élève dans la nouvelle classe en le déclarant non classé dans les évaluations déjà enregistrées
                    // $this->studentService->updateStudentForClassroom($student, $newStudentToAdd, $this->getUser(), $newStudentClassroom);

                    // On supprime l'élève de la classe avec toutes ses notes et ses absences
                    // $this->studentService->deleteStudentForClassroom($student);

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
                    // if(count($studentRegistrations))
                    // {
                    //     foreach ($studentRegistrations as $studentRegistration) 
                    //     {
                    //         $this->em->remove($studentRegistration);
                    //     }
                        
                    // }
                    
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

                    // Si les bulletins sont déjà imprimés, on set le student comme non classé
                    // if($this->reportRepository->findAlreadyReport($newStudentToAdd->getClassroom()))
                    // {
                    //     $terms = $this->termRepository->findAll();

                    //     foreach ($terms as $term) 
                    //     {
                    //         if($this->reportRepository->findAlreadyReport($newStudentToAdd->getClassroom(), $term))
                    //         {
                    //             $studentReport = new Report;

                    //             $studentReport
                    //                 ->setStudent($newStudentToAdd)
                    //                 ->setTerm($term)
                    //                 ->setMoyenne(ConstantsClass::UNRANKED_AVERAGE)
                    //                 ->setRang(ConstantsClass::UNRANKED_RANK_DB)
                    //                 ;
                                
                    //             $this->em->persist($studentReport);
                    //         }

                    //     }
                    // }
                    
                    $this->em->flush();
                
                }

                $this->addFlash('info', $this->translator->trans('Student updated successfully'));
                
                $mySession->set('miseAjour', 1);
                // On redimensionne la photo au cas où elle a été modifiée
                // $imageOptimizerService->resize('images/students/'.$student->getPhoto());
                if($student->getPhoto())
                {
                    $imagine->open(getcwd().'/images/students/'.$student->getPhoto())->resize(new Box(150, 200))->save(getcwd().'/images/students/'.$student->getPhoto());
                }

                // On se redirige sur la page d'affichage des élèves
                return $this->redirectToRoute('student_displayStudent', [
                    'id' => $student->getClassroom()->getId(),
                    'm' => 1,
                ]);
                

            }

        }

        $registeredStudents = $storedClassroom->getStudents();
        $numberOfStudentInSchool = count($this->studentRepository->findBy(['schoolYear' => $schoolYear]));
        
        return $this->render('student/saveStudent.html.twig', [
            'formStudent' => $form->createView(),
            'registeredStudents' => $registeredStudents,
            'storedClassroom' => $storedClassroom,
            'slug' => $slug,
            'student' => $student,
            'numberOfStudentInSchool' => $numberOfStudentInSchool,
            'school' => $school[0],
            ]);
    }
}

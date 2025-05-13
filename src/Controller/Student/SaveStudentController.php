<?php

namespace App\Controller\Student;

use Imagine\Image\Box;
use App\Entity\Student;
use Imagine\Gd\Imagine;
use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Form\StudentType;
use App\Service\StrService;
use App\Entity\Registration;
use App\Service\QrcodeService;
use App\Service\StudentService;
use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\SubSystemRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\VerrouInsolvableRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/student")]
class SaveStudentController extends AbstractController
{
    public function __construct(
        protected StrService $strService, 
        protected EntityManagerInterface $em,  
        protected QrcodeService $qrcodeService, 
        protected StudentService $studentService, 
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository, 
        protected SchoolYearService $schoolYearService, 
        protected SubSystemRepository $subSystemRepository,
        protected SchoolYearRepository $schoolYearRepository, 
        protected VerrouInsolvableRepository $verrouInsolvableRepository, 
        )
    {}

    #[Route('/saveStudent', name: 'student_saveStudent')]
    public function saveStudent(Request $request): Response
    {
        $maSession = $request->getSession();

        $maSession->set('ajout',null);
        $maSession->set('suppression', null);
        $maSession->set('miseAjour', null);
        $maSession->set('saisiNotes', null);
         

        if($maSession)
        {
            $schoolYear = $maSession->get('schoolYear');
            $subSystem = $maSession->get('subSystem');
            
        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $slug = 0;
        $subSyste = $this->subSystemRepository->find($subSystem->getId());
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        if($maSession)
        {
            $verrou = $maSession->get('verrou');

        }else {
            return $this->redirectToRoute("app_logout");
        }


        $imagine = new Imagine;
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }
        
        // on recupère le schoolYear de la BD pour qu'il soit suivi par le EntityManager au moment du persist
        $schoolYear = $this->schoolYearRepository->find($maSession->get('schoolYear')->getId());
        $storedClassroom = new Classroom();

        $school = $this->schoolRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        $schoolName = $school[0]->getFrenchName()." / ".$school[0]->getEnglishName();

        $student = new Student();
        $registration = new Registration();
        // dd($request->request);

        // if($request->request->has('student'))
        // {
        //     str_replace(' ', '', $request->request->get('student')['registrationNumber']);
        //     // Si le matricule doit être générer automatiquement
        //     if($request->request->get('student')['registrationNumber'] === '') 
        //     {
        //         // on contruit le matricule
        //         $lastId = $this->studentRepository->findMaxId();

        //         if($lastId)
        //         {
        //             $maxId = $lastId[0]->getId();
        //             $maxId++;
        //         }else 
        //         {
        //             $maxId = 1;
        //         }
        //         $matricule = 'FSE'.substr($schoolYear->getSchoolYear(), 0, 4).'FS'.$maxId;
        //     }
        // } 
        
        
        $form = $this->createForm(StudentType::class, $student);
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
                $qrCode = $this->qrcodeService->qrcode($schoolName." : Ce bulletin appartient à l'élève : ".$data->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$student->getClassroom()->getClassroom());

                $qrCodeFiche = $this->qrcodeService->qrcode($schoolName." : Cette fiche appartient à l'élève : ".$data->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$student->getClassroom()->getClassroom());
                
                $qrCodeRollOfHonor = $this->qrcodeService->qrcode($schoolName." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$data->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$student->getClassroom()->getClassroom());

            } else 
            {
                $qrCode = $this->qrcodeService->qrcode($schoolName." : This report belongs to the student : ".$data->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year : ".$schoolYear->getSchoolYear().", Classroom : ".$student->getClassroom()->getClassroom());

                $qrCodeFiche = $this->qrcodeService->qrcode($schoolName." : This sheet belongs to the student : ".$data->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$student->getClassroom()->getClassroom());
            
                $qrCodeRollOfHonor = $this->qrcodeService->qrcode($schoolName." : This roll of honor belongs to the student: ".$data->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$student->getClassroom()->getClassroom());

            }
            
            
            // On redimensionne la photo au cas où elle a été modifiée
            if($student->getPhoto())
            {
                $imagine->open(getcwd().'/images/students/'.$student->getPhoto())->resize(new Box(150, 200))->save(getcwd().'/images/students/'.$student->getPhoto());
            }
            
            // on enlève les carctères speciaux et on met en majuscule
            // le fullName et le birthplace
            /// je recupere letat du conge dans le formulaire 
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
            $clubBilingue = $request->request->get('clubBilingue');
            $clubLv2 = $request->request->get('clubLv2');

            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait le dernier élève de la table
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
                    ->setClubBilingue($clubBilingue)
                    ->setClubLv2($clubLv2)
                    ->setSlug($slug.$id)
                    ->setSubSystem($subSyste)
                    ->setSupprime(0)
                    ->setSchoolYear($schoolYear);
            
            $storedClassroom = $student->getClassroom();
            
            $this->studentService->addStudent($student, $this->getUser(), $registration, $schoolYear);

            $this->addFlash('info', $this->translator->trans('Student save with success !'));
                
            $maSession->set('ajout', 1);

            // on vide le formulaire en conservant uniquement la classe
            // $storedClassroom = $student->getClassroom();
            $student = new Student();
            $student->setClassroom($storedClassroom);
            $form = $this->createForm(StudentType::class, $student);
            
        }
        
        $registeredStudents = $storedClassroom->getStudents();
        $numberOfStudentInSchool = count($this->studentRepository->findBy(['schoolYear' => $schoolYear]));
        $slug = 0;
        return $this->render('student/saveStudent.html.twig', [
            'formStudent' => $form->createView(),
            'registeredStudents' => $registeredStudents,
            'storedClassroom' => $storedClassroom,
            'slug' => $slug,
            'student' => $student,
            'school' => $school[0],
            'numberOfStudentInSchool' => $numberOfStudentInSchool,

        ]);
    }

}

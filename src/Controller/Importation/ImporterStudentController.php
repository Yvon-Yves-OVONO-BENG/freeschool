<?php

namespace App\Controller\Importation;

use App\Entity\Classroom;
use DateTime;
use App\Entity\Student;
use App\Entity\User;
use App\Service\StrService;
use App\Entity\Registration;
use App\Service\ImportExcel;
use App\Entity\ConstantsClass;
use App\Service\QrcodeService;
use App\Repository\SexRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\RepeaterRepository;
use App\Repository\ClassroomRepository;
use App\Repository\SubSystemRepository;
use App\Repository\SchoolYearRepository;
use App\Service\ClassroomService;
use App\Service\StudentService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

#[IsGranted('ROLE_USER', message: 'Accès refusé. Connectez-vous')]
class ImporterStudentController extends AbstractController
{
    public function __construct(
        private StrService $strService,
        private EntityManagerInterface $em,
        private SexRepository $sexRepository,
        private QrcodeService $qrcodeService,
        private StudentService $studentService,
        private TranslatorInterface $translator,
        private SchoolRepository $schoolRepository,
        private ClassroomService $classroomService,
        private StudentRepository $studentRepository, 
        private RepeaterRepository $repeaterRepository,
        private ClassroomRepository $classroomRepository,
        private SubSystemRepository $subSystemRepository,
        private SchoolYearRepository $schoolYearRepository,
        )
    {}

    #[Route('/importation-student', name: 'importation_student')]
    public function importationStudent(Request $request, ImportExcel $importExcel): Response
    {
        $mySession = $request->getSession();

        $currentUser = $this->getUser();
        if (
            $currentUser instanceof User
            && in_array(ConstantsClass::ROLE_ADMIN, $currentUser->getRoles(), true)
            && $currentUser->isStudentManagementBlocked()
        ) {
            $this->addFlash('error', $this->translator->trans(
                "Le proviseur a désactivé votre autorisation d'ajouter ou de modifier un élève."
            ));

            return $this->redirectToRoute('student_displayStudent', [
                'headmasterFees' => 0,
                'id' => 0,
                'a' => 0,
                'm' => 0,
                's' => 0,
            ]);
        }
        
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');
        }
        else 
        {
            return $this->redirectToRoute("app_logout");
        }

        $school = $this->schoolRepository->findOneBy([
            'schoolYear' => $schoolYear
        ]);

        $schoolName = $school->getFrenchName()." / ".$school->getEnglishName();
        $selectedClassroom = new Classroom;
        
        if ($request->request->has('import')) 
        {
            $selectedClassroom = $this->classroomRepository->find($request->request->get('classroom'));

            // Par exemple, supposons que le fichier est uploadé via un formulaire
            $file = $request->files->get('excelfile');
            if ($file) 
            {
                //je récupère l'extension
                $extension = $file->getClientOriginalExtension();
                $mimeType = $file->getMimeType();

                //je verifie l'extension
                if (!in_array($extension, ['xls', 'xlsx']) || 
                !in_array($mimeType, ['application/vnd.ms-excel', 
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])) 
                {
                    $this->addFlash('info', $this->translator->trans('This file is not an Excel file !'));
        
                    $mySession->set('suppression', 1);
                }

                // Sauvegarder le fichier temporairement
                $filePath = $file->getPathname();
                // Importer les données
                $datas = $importExcel->import($filePath);
                // on recupère les élèves de la classroom à afficher
            
                foreach ($datas as $data) 
                {
                    $sex = 1;
                    if ($data[4] == 'M') 
                    {
                        $sex = 1;
                    } 
                    else 
                    {
                        $sex = 2;
                    }

                    if (strtoupper($data[5]) == 'NON') 
                    {
                        $repeat = $this->repeaterRepository->findOneBy(['repeater' => 'Non']);
                    } 
                    else 
                    {
                        $repeat = $this->repeaterRepository->findOneBy(['repeater' => 'Oui']);
                    }

                    $student = new Student();
                    $registration = new Registration();
                    // dump($data[2]);
                    $datenaiss = new DateTime($data[2]);
                    // $datenaiss = \DateTime::createFromFormat('d/m/Y', trim($data[2]));
                //   dd($datenaiss);
                    $slug = md5(uniqid('', true).random_bytes(5));

                    if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
                    {
                        $qrCode = $this->qrcodeService->qrcode(($schoolName." : Ce bulletin appartient à l'élève : ".$data[0]." de matricule : ".$data[1].", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$selectedClassroom->getClassroom()), $slug, $school);

                        $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : Cette fiche appartient à l'élève : ".$data[0]." de matricule : ".$data[1]." Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$selectedClassroom->getClassroom()), $slug, $school);
                        
                        $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$data[0]." de matricule : ".$data[1].", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$selectedClassroom->getClassroom()), $slug, $school);

                    } else 
                    {
                        $qrCode = $this->qrcodeService->qrcode(($schoolName." : This report belongs to the student : ".$data[0]." register number : ".$data[1].", School Year : ".$schoolYear->getSchoolYear().", Classroom : ".$selectedClassroom->getClassroom()), $slug, $school);

                        $qrCodeFiche = $this->qrcodeService->qrcode(($schoolName." : This sheet belongs to the student : ".$data[0]." register number : ".$data[1].", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$selectedClassroom->getClassroom()), $slug, $school);
                    
                        $qrCodeRollOfHonor = $this->qrcodeService->qrcode(($schoolName." : This roll of honor belongs to the student: ".$data[0]." register number : ".$data[1].", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$selectedClassroom->getClassroom()), $slug, $school);

                    }

                    
                    $student
                    ->setSchoolYear($selectedClassroom->getSchoolYear())
                    ->setSubSystem($selectedClassroom->getSubSystem())
                    ->setQrCode($qrCode)
                    ->setQrCodeFiche($qrCodeFiche)
                    ->setQrCodeRollOfHonor($qrCodeRollOfHonor)
                    ->setRepeater($repeat)
                    ->setSlug($slug)
                    ->setPhoto('avatar.png')
                    ->setFullName($data[1])
                    ->setRegistrationNumber($data[0])
                    ->setBirthday($datenaiss)
                    ->setBirthplace($data[3])
                    ->setSex($this->sexRepository->find($sex))
                    ->setClassroom($selectedClassroom)
                    ;
                    
                    $this->studentService->addStudentImport($student, $this->getUser(), $registration, $selectedClassroom->getSchoolYear());
                }

                $this->em->flush();
            
                $this->addFlash('info',  $this->translator->trans('Import successfuly completed !'));
                
                $mySession->set('saisiNotes', 1);

                // On recupère les classroom
                $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

            }
        }
        
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
        
        $classrooms = $this->classroomService->splitClassrooms($classrooms);

        return $this->render('importation/importationStudent.html.twig', [
            'school' => $school,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
        ]);
    
    }
}

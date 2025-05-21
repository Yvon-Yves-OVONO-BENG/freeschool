<?php

namespace App\Controller;

use App\Entity\Classroom;
use App\Service\StrService;
use App\Entity\ConstantsClass;
use App\Service\QrcodeService;
use App\Service\ClassroomService;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\ClassroomRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class GenerationQrCodeController extends AbstractController
{
    public function __construct(
        private StrService $strService,
        private EntityManagerInterface $em,
        private QrcodeService $qrcodeService,
        private TranslatorInterface $translator,
        private SchoolRepository $schoolRepository,
        private ClassroomService $classroomService,
        private StudentRepository $studentRepository,
        private ClassroomRepository $classroomRepository,
    )
    {}

    #[Route('/generation-qr-code', name: 'generation_qr_code')]
    public function GenerationQrCode(Request $request): Response
    {
        $mySession = $request->getSession();

        
        $schoolYear = $mySession->get('schoolYear');
        $subSystem = $mySession->get('subSystem');
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        $classrooms = $this->classroomService->splitClassrooms($classrooms);

        $selectedClassroom = new Classroom();
        
        if ($request->request->get('classroomId')) 
        {
            $classroom = $this->classroomRepository->find((int)$request->request->get('classroomId'));

            $students = $this->studentRepository->findBy([
                'classroom' => $classroom,
                'schoolYear' => $schoolYear,
            ]);

            foreach ($students as $student) 
            {
                $qrCode = null;
                $qrCodeFiche = null;
                $qrCodeRollOfHonor = null;

                if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
                {
                    $qrCode = $this->qrcodeService->qrcode($school->getFrenchName()." : Ce bulletin appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$student->getClassroom()->getClassroom());

                    $qrCodeFiche = $this->qrcodeService->qrcode($school->getFrenchName()." : Cette fiche appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber())." Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$student->getClassroom()->getClassroom());
                    
                    $qrCodeRollOfHonor = $this->qrcodeService->qrcode($school->getFrenchName()." : Ce TABLEAU D'HONNEUR appartient à l'élève : ".$student->getFullName()." de matricule : ".$this->strService->strToUpper($student->getRegistrationNumber()).", Année Scolaire : ".$schoolYear->getSchoolYear().", Classe : ".$student->getClassroom()->getClassroom());

                } else 
                {
                    $qrCode = $this->qrcodeService->qrcode($school->getEnglishName()." : This report belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year : ".$schoolYear->getSchoolYear().", Classroom : ".$student->getClassroom()->getClassroom());

                    $qrCodeFiche = $this->qrcodeService->qrcode($school->getEnglishName()." : This sheet belongs to the student : ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$student->getClassroom()->getClassroom());
                
                    $qrCodeRollOfHonor = $this->qrcodeService->qrcode($school->getEnglishName()." : This roll of honor belongs to the student: ".$student->getFullName()." register number : ".$this->strService->strToUpper($student->getRegistrationNumber()).", School Year  : ".$schoolYear->getSchoolYear().", Classroom : ".$student->getClassroom()->getClassroom());

                }

                $student->setQrcode($qrCode)
                        ->setQrCodeFiche($qrCodeFiche)
                        ->setQrCodeRollOfHonor($qrCodeRollOfHonor);

                $this->em->persist($student);
            }

            $this->em->flush();

            $this->addFlash('info', $this->translator->trans('QR Code generate with success !'));

            $mySession->set('saisiNotes', 1);

            return $this->redirectToRoute('home_dashboard');

        }

        return $this->render('report/generateQrCode.html.twig', [
            'school' => $school,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,

        ]);
    }
}

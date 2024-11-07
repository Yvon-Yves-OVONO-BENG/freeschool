<?php

namespace App\Controller\Student;

use App\Service\SchoolYearService;
use App\Repository\StudentRepository;
use App\Repository\SchoolRepository;
use App\Service\PrintSchoolAttendanceCertificateService;
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

#[Route("/student")]
class PrintSchoolAttendanceCertificateController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected StudentRepository $studentRepository,  
        protected PrintSchoolAttendanceCertificateService $printSchoolAttendanceCertificateService, 
        )
    {}

    #[Route("/print-school-attendance-certificate/{slug}", name:"print_eschool_attendance_certificate")]
    public function printSchoolAttendanceCertificate(string $slug, Request $request): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        
        
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

        $student = $this->studentRepository->findOneBySlug([
            'slug' => $slug
        ]);
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printSchoolAttendanceCertificateService->print($student, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("School attendance certificate of ".$student->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Certificat de scolarité de ".$student->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
    }

}

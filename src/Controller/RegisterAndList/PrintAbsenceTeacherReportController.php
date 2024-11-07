<?php

namespace App\Controller\RegisterAndList;

use App\Repository\SchoolRepository;
use App\Repository\AbsenceTeacherRepository;
use App\Repository\SchoolYearRepository;
use App\Service\PrintAbsenceTeacherService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintAbsenceTeacherReportController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearRepository $schoolYearRepository,
        protected AbsenceTeacherRepository $absenceTeacherRepository, 
        protected PrintAbsenceTeacherService $printAbsenceTeacherService, 
        )
    {}

    #[Route("/printAbsenceTeacherReport", name:"printAbsenceTeacherReport")]
    public function printAbsenceTeacherReport(Request $request): Response
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
        }
        else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);
        
        $allAbsencesTeachers = $this->absenceTeacherRepository->getAbsencesTeacher($schoolYear);
        
        $pdf =  $this->printAbsenceTeacherService->printAbsenceTeachersReports($allAbsencesTeachers,$school, $schoolYear, );

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output("Absence teacher report", "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output("Fiche de report de absences des enseignants", "I"), 200, ['Content-Type' => 'application/pdf']);
        }
         
    }

}

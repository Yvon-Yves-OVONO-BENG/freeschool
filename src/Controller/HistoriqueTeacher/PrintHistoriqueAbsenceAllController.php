<?php

namespace App\Controller\HistoriqueTeacher;

use App\Repository\HistoriqueTeacherRepository;
use App\Repository\TeacherRepository;
use App\Repository\SchoolRepository;
use App\Service\HistoriqueAbsenceTeacherService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */
#[Route('/historiqueTeacher')]
class PrintHistoriqueAbsenceAllController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        protected HistoriqueTeacherRepository $historiqueTeacherRepository,
        protected HistoriqueAbsenceTeacherService $historiqueAbsenceTeacherService, 
        )
    {}

    #[Route('/print-historic-teacher-all', name: 'print_historic_teacher_all')]
    public function printHistoricTeacherAll(Request $request): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);
        
        $historiques = $this->historiqueTeacherRepository->toutesLesHistoriques();
        
        $pdf = $this->historiqueAbsenceTeacherService->printHistoricAttendance($school, $schoolYear, $historiques);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("All Absence"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Toutes les absences"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }

    }
}
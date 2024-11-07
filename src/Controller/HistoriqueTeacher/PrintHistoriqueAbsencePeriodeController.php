<?php

namespace App\Controller\HistoriqueTeacher;

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
class PrintHistoriqueAbsencePeriodeController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        protected HistoriqueAbsenceTeacherService $historiqueAbsenceTeacherService, 
        )
    {}

    #[Route('/print-historic-teacher-periode', name: 'print_historic_teacher_periode')]
    public function printHistoricTeacherPeriode(Request $request, string $slug): Response
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

        $teacher = $this->teacherRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $pdf = $this->historiqueAbsenceTeacherService->printHistoricAttendance($school, $schoolYear, $teacher,);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Attendance of ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Assiduité de ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }

    }
}

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
class PrintHistoriqueAbsenceController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        protected HistoriqueTeacherRepository $historiqueTeacherRepository,
        protected HistoriqueAbsenceTeacherService $historiqueAbsenceTeacherService, 
        )
    {}

    #[Route('/print-historic-teacher/{slug}/{all<[0-1]>}/{periode}', name: 'print_historic_teacher')]
    public function printHistoricTeacher(Request $request, $slug = 0, int $all = 0, int $periode = 0): Response
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
        $teacher = null;

        if ($all == 1) {

            $historiques = $this->historiqueTeacherRepository->toutesLesHistoriques();
            $pdf = $this->historiqueAbsenceTeacherService->printHistoricAttendance($school, $schoolYear, $teacher, $historiques);
            
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("All attendance"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Toutes les assiduités"), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            }

        } 

        if($slug != 0)
        {
            $teacher = $this->teacherRepository->findOneBySlug([
                'slug' => $slug
            ]);
            
            $pdf = $this->historiqueAbsenceTeacherService->printHistoricAttendance($school, $schoolYear, $teacher);
            
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Attendance of ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Assiduité de ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            }
        }

        if ($periode == 1) 
        {
            $dateDebut = date_create($request->request->get('dateDebut'));
            $dateFin = date_create($request->request->get('dateFin'));

            $historiques = $this->historiqueTeacherRepository->historiqueAssiduitePeriode($dateDebut, $dateFin);
            
            $pdf = $this->historiqueAbsenceTeacherService->printHistoricAttendance($school, $schoolYear, $teacher, $historiques, $periode, $dateDebut, $dateFin);
            
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Attendance for the ".date_format($dateDebut, 'd-m-Y')." au ".date_format($dateFin, 'd-m-Y')), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Assiduité allant de ".date_format($dateDebut, 'd-m-Y')." au ".date_format($dateFin, 'd-m-Y')), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            }

        }
      
    }
}

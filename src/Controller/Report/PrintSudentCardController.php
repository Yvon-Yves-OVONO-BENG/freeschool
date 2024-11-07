<?php

namespace App\Controller\Report;

use App\Service\ReportService;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class PrintSudentCardController extends AbstractController
{
    public function __construct(
        protected ReportService $reportService, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository,
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/printStudentCard/{slugClassroom}/{slugStudent}", name:"report_printStudentCard")]
    public function printSudentCard(Request $request, string $slugClassroom, string $slugStudent = ""): Response
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
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        $students = [];
        $selectedClassroom = $this->classroomRepository->findOneBySlug(['slug' => $slugClassroom]);

        if ($slugStudent != null) 
        {
            $students[] = $this->studentRepository->findOneBySlug(['slug' => $slugStudent]);
        } 
        else 
        {
            $students = $this->studentRepository->findBy([
                'classroom' => $selectedClassroom
            ], [
                'fullName' => 'ASC'
            ]);
        }

        $pdf = $this->reportService->printStudentCard($students, $school, $schoolYear, $selectedClassroom, $subSystem);

        if($slugStudent != null)
        {
            if ($subSystem->getId() == 1 ) 
            {
            return new Response($pdf->Output(utf8_decode("Student card of ".$students[0]->getFullName()), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Carte d'idntité scolaire de ".$students[0]->getFullName()), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }
        else
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Student card of ".$selectedClassroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Cartes d'identité scolaires de la ".$selectedClassroom->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }

    }
}

<?php

namespace App\Controller\Report;

use App\Service\GeneralService;
use App\Repository\TermRepository;
use App\Service\RollOfHonorService;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use App\Repository\StudentRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class PrintRollOfHonorController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected GeneralService $generalService, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository,
        protected RollOfHonorService $rollOfHonorService,
        protected ClassroomRepository $classroomRepository, 
        )
    {}


    #[Route("/printRollOfHonor/{slug}/{slugTerm}/{slugStudent}", name:"report_printRollOfHonor")]
    public function printRollOfHonor(Request $request, string $slug = "", string $slugTerm = "", string $slugStudent = ""): Response
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
        
        // trimestre sélectionné
        $selectedTerm = $this->termRepository->findOneBySlug(['slug' => $slugTerm]);
        // classe sélectionnée
        $selectedClassroom = $this->classroomRepository->findOneBySlug(['slug' => $slug]);
        //Effectif de la classe
        $numberOfStudents = $this->generalService->getNumberOfStudents($selectedClassroom);

        $reports = $this->reportRepository->findStudentToPrintRollOfHonor($selectedClassroom, $selectedTerm);

        
        if($slugStudent != null)
        {
            $student = $this->studentRepository->findOneBySlug(['slug' => $slugStudent ]);
            $idS = $this->studentRepository->findOneBySlug(['slug' => $slugStudent ])->getId();
            $reports = [clone $reports[$this->rollOfHonorService->getStudentIndex($reports, $idS)]];
        }

        // on imprime les tableaux d'honneur
        $pdf = $this->rollOfHonorService->printRollOfHonor($reports, $school, $selectedTerm, $selectedClassroom, $schoolYear, $numberOfStudents, $subSystem);

        if($slugStudent != null)
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Roll of honor of ".$student->getFullName()." - term ".$selectedTerm->getTerm()), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Tableau d'honneur de ".$student->getFullName()." - trimestre ".$selectedTerm->getTerm()), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }
        else
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Roll of honor of ".$selectedClassroom->getClassroom()." - term ".$selectedTerm->getTerm()), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Tableau d'honneur de la ".$selectedClassroom->getClassroom()." - trimestre ".$selectedTerm->getTerm()), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }
        
    }

}

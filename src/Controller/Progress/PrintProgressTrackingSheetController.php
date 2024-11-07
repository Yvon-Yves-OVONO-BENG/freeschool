<?php

namespace App\Controller\Progress;

use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\SubjectRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\PrintProgressTrakingSheetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/progress")]
class PrintProgressTrackingSheetController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected LessonRepository $lessonRepository, 
        protected TeacherRepository $teacherRepository, 
        protected SubjectRepository $subjectReposirory, 
        protected ClassroomRepository $classroomRepository, 
        protected PrintProgressTrakingSheetService $printProgressTrakingSheetService,
        )
    {}

    #[Route("/print-progress-tracking-sheet/{slugTeacher}", name:"print_progress_tracking_sheet")]
    public function printProgressTrackingSheet(Request $request, string $slugTeacher = ""): Response
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

        //////Je récupère l'enseignant, la matière et la classe
        $teacher = $this->teacherRepository->findOneBySlug(['slug' => $slugTeacher]);
        // $subject = $this->subjectReposirory->find($idS);
        ////je récupère la lecçon prévue en fonction de l'enseigant, 
        ////la matière et la classe d'une année scolaire
        $lessonPlains = $this->lessonRepository->findBy([
            'teacher' => $teacher,
            ]);

        $pdf = $this->printProgressTrakingSheetService->printProgressTrakingSheet($teacher, $schoolYear, $lessonPlains, $school);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Progress Tracking sheet of ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Fiche de progression de ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']);
        }
        
        

    }
}

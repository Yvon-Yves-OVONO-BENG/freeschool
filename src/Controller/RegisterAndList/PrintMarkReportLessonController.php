<?php

namespace App\Controller\RegisterAndList;

use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Service\RegisterAndListService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintMarkReportLessonController extends AbstractController
{
    public function __construct(
        protected LessonRepository $lessonRepository, 
        protected SchoolRepository $schoolRepository,    
        protected TeacherRepository $teacherRepository, 
        protected RegisterAndListService $registerAndListService, 
        )
    {}

    #[Route("/printMarkReportLesson/{slugTeacher}", name:"printMarkReportLesson")]
    public function printMarkReport(Request $request, string $slugTeacher = ""): Response
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

        $teachers = [];

        // Si c'est le relevé d'un seul ensegnant qui est demandé
       
        $teacherId = $request->request->get('teacher');

        if ($slugTeacher != null) 
        {
            $teachers[] = $this->teacherRepository->findOneBySlug(['slug' => $slugTeacher]);
        }
		elseif($teacherId != 0)
		{
            $teachers[] = $this->teacherRepository->find($teacherId);

		}else // si tous les relevés sont demandés
		{
			$teachers = $this->teacherRepository->findAllToDisplay($schoolYear, $subSystem);
        }
        
        $lessonId = $request->request->get('lessonId');
        // je récupère la leçon
        $lesson = $this->lessonRepository->find($lessonId);
           

        // On contruit les relevés de note de la leçon
        $markReports = $this->registerAndListService->getMarkReportLesson($lesson);
        
        // On imprime les relevés de notes
        $pdf =  $this->registerAndListService->printMarkReports($markReports,  $schoolYear,$school);
        
        if ($slugTeacher != null)
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Mark report of ".$teachers[0]->getFullName), "I"), 200, ['Content-Type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Relevé de notes de ".$teachers[0]->getFullName), "I"), 200, ['Content-Type' => 'application/pdf']);
            }
        }
        else
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Mark report"), "I"), 200, ['Content-Type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Relevé de notes"), "I"), 200, ['Content-Type' => 'application/pdf']);
            }

        }
    
    }

}

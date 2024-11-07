<?php

namespace App\Controller\Classroom;

use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use App\Service\PrintLessonClassroomService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/classroom")]
class PrintLessonClassroomController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected SchoolYearService $schoolYearService, 
        protected ClassroomRepository $classroomRepository, 
        protected PrintLessonClassroomService $printLessonClassroomService, 
        )
    {}

    #[Route("/print-lesson-classroom/{slug}", name:"print_lesson_classroom")]
    public function printLessonClassroom(string $slug, Request $request): Response
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

        $classroom = $this->classroomRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $lessons = $classroom->getLessons();
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printLessonClassroomService->print($classroom, $lessons, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output("Lesson of - ".$classroom->getClassroom(), "I" ), 200, ['Content-Type' => 'application/pdf']);
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Lecon de la classe de - ".$classroom->getClassroom()), "I" ), 200, ['Content-Type' => 'application/pdf']);
        }
        
        
    }

}

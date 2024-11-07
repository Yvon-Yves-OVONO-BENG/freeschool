<?php

namespace App\Controller\Teacher;

use App\Service\SchoolYearService;
use App\Repository\SchoolRepository;
use App\Repository\TeacherRepository;
use App\Service\PrintLessonTeacherService;
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

 #[Route("/teacher")]
class PrintLessonTeacherController extends AbstractController
{
    public function __construct(
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository,
        protected TeacherRepository $teacherRepository, 
        protected SchoolYearService $schoolYearService, 
        protected PrintLessonTeacherService $printLessonTeacherService, 
        )
    {}

    #[Route("/print-lesson-teacher/{slug}", name:"print_lesson_teacher")]
    public function printLessonTeacher(string $slug, Request $request): Response
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

        $teacher = $this->teacherRepository->findOneBySlug([
            'slug' => $slug
        ]);

        $lessons = $teacher->getLessons();
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $pdf = $this->printLessonTeacherService->print($teacher, $lessons, $school, $schoolYear);

        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Lesson teacher of ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } 
        else 
        {
            return new Response($pdf->Output(utf8_decode("Leçon de  ".$teacher->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
    }

}

<?php

namespace App\Controller\Lesson;

use App\Entity\Classroom;
use App\Service\ClassroomService;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/lesson")]
class DisplayLessonController extends AbstractController
{
    public function __construct(protected LessonRepository $lessonRepository, protected ClassroomRepository $classroomRepository, protected ClassroomService $classroomService, protected SchoolRepository $schoolRepository,)
    {
    }

    #[Route("/displayLesson/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}/{slug}", name:"lesson_displayLesson")]
    public function displayLesson(Request $request, string $slug = "", int $a = 0, int $m = 0, int $s = 0): Response
    {
        $mySession = $request->getSession();

        $mySession = $request->getSession();

        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);

        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
            $mySession->set('saisiNotes', null);
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
            $mySession->set('saisiNotes', null);
        }

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        if($slug)
        {
            // On ajoute le classroom à la request pour permettre l'affichage des lessons
            //  et non le formulaire de choix de la classe
            $request->request->set('classroom', $this->classroomRepository->findOneBySlug(['slug' => $slug])->getId());
        }

        $methodIsPost = false;

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);
        
        $selectedClassroom = new Classroom();
        $classrooms = [];
        $lessons = [];

        if($idc = $request->request->get('classroom'))
        {
            $methodIsPost = true;

            $selectedClassroom = $this->classroomRepository->find($idc);
            $lessons = $this->lessonRepository->findAllToDisplay($selectedClassroom, $subSystem);
        }

        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
                
        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        
        return $this->render('lesson/displayLesson.html.twig', [
            'lessons' => $lessons,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'methodIsPost' => $methodIsPost,
            'school' => $school,
        
        ]);
    }

}

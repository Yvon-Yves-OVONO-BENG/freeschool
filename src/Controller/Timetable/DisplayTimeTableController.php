<?php

namespace App\Controller\Timetable;

use App\Entity\Classroom;
use App\Service\ClassroomService;
use App\Repository\SchoolRepository;
use App\Repository\ClassroomRepository;
use App\Repository\TimeTableRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

 #[Route("/timetable")]
class DisplayTimeTableController extends AbstractController
{
    public function __construct(
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected TimeTableRepository $timeTableRepository, 
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route('/display-time-table/{slug}', name: 'display_time_table')]
    public function displayTimeTable(Request $request, string $slug = ""): Response
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
        $timeTables = [];
        
        if($idc = $request->request->get('classroom'))
        {
            $methodIsPost = true;

            $selectedClassroom = $this->classroomRepository->find($idc);
            
            $timeTables = $this->timeTableRepository->findBy([
                'classroom' => $selectedClassroom,
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ]);
        }
       
        
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
                
        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        
        
        return $this->render('timetable/displayTimeTable.html.twig', [
            'timeTables' => $timeTables,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'methodIsPost' => $methodIsPost,
            'school' => $school,
        ]);
    }
}

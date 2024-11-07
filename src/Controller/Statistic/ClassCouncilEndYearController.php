<?php

namespace App\Controller\Statistic;

use App\Repository\TermRepository;
use App\Repository\ClassroomRepository;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Service\ClassroomService;
use App\Service\CouncilEndYearService;
use App\Service\GeneralService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/statistic")]
class ClassCouncilEndYearController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected LessonRepository $classCouncil, 
        protected GeneralService $generalService, 
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected ClassroomRepository $classroomRepository, 
        protected CouncilEndYearService $councilEndYearService, 
        )
    {}
     
    #[Route("/classCouncilEndYear/{slug}", name:"statistic_classCouncil_endYear")]
    public function classCouncil(Request $request, string $slug): Response
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
        
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear ]);
      
        // trimestre sélectionné
        // classe sélectionnée
        $selectedClassroom = $this->classroomRepository->findOneBySlug(['slug' => $slug ]);
        //Effectif de la classe
        $numberOfStudents = $this->generalService->getNumberOfStudents($selectedClassroom);
        // Effectif garçons
        $numberOfBoys = $this->generalService->getNumberOfBoys($selectedClassroom);
        // Effectif filles
        $numberOfGirls = $this->generalService->getNumberOfGirls($selectedClassroom);
        // nombre de lessons
        $numberOfLessons = $this->generalService->getNumberOfLessons($selectedClassroom);

        // nombre de redoublants
        $numberOfRepeaters = $this->generalService->getNumberOfRepeaters($selectedClassroom);

        $pdf = $this->councilEndYearService->printClassCouncilEndYear($schoolYear, $selectedClassroom, $numberOfStudents, $numberOfBoys, $numberOfGirls, $school, $numberOfRepeaters, $subSystem);
        
        if ($subSystem->getId() == 1 ) 
        {
            return new Response($pdf->Output(utf8_decode("Class council end year of ".$selectedClassroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        } else {
            return new Response($pdf->Output(utf8_decode("Conseil de classe de fin d'année de la ".$selectedClassroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
        }
        
        
    }
}
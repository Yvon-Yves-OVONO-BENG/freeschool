<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Repository\TermRepository;
use App\Repository\CycleRepository;
use App\Repository\LevelRepository;
use App\Repository\SchoolRepository;
use App\Repository\SubjectRepository;
use App\Repository\ClassroomRepository;
use App\Service\ClassroomService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class BestResultsOfYearController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository, 
        protected CycleRepository $cycleRepository, 
        protected LevelRepository $levelRepository, 
        protected ClassroomService $classroomService,
        protected SchoolRepository $schoolRepository, 
        protected SubjectRepository $subjectRepository, 
        protected ClassroomRepository $classroomRepository, 
        )
    {}

    #[Route("/best-result-of-year", name:"best_result_of_year")]
    public function bestStudentsPerClass(Request $request): Response
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
        // on recupèere les trimestres
        $terms = $this->termRepository->findBy([], ['term' => 'ASC']);

        // on recupèere toutes les classes
        $subjects = $this->subjectRepository->findBy(['schoolYear' => $schoolYear, 'subSystem' => $subSystem,], ['subject' => 'ASC']);
        $cycles = $this->cycleRepository->findAll();
        $levels = $this->levelRepository->findAll();

        $classroomss = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
        
        $classrooms = $this->classroomService->splitClassrooms($classroomss);

        return $this->render('register_and_list/bestResultsOfYear.html.twig', [
            'terms' => $terms,
            'subjects' => $subjects,
            'classrooms' => $classrooms,
            'selectedClassroom' => $classroomss[0],
            'levels' => $levels,
            'cycles' => $cycles,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
            'school' => $school,
        ]);
    }

}

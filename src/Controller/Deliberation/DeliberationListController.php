<?php

namespace App\Controller\Deliberation;

use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\DecisionRepository;
use App\Repository\SchoolRepository;
use App\Service\ClassroomService;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/deliberation")
 */
class DeliberationListController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository, protected DecisionRepository $decisionRepository, protected ClassroomService $classroomService,protected SchoolRepository $schoolRepository)
    {
    }

    /**
     * @Route("/deliberationList", name="deliberation_deliberationList")
     */
    public function deliberationList(Request $request): Response
    {
        $mySession = $request->getSession();
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        // on recupère toutes les classes
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
           
        $decisions = $this->decisionRepository->findAll();
        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        
        return $this->render('deliberation/deliberationList.html.twig', [
            'classrooms' => $classrooms,
            'decisions' => $decisions,
            'school' => $school,
        ]);
    }
 
}
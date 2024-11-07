<?php

namespace App\Controller\Deliberation;

use App\Entity\ConstantsClass;
use App\Repository\ClassroomRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\DecisionRepository;
use App\Repository\SchoolRepository;
use App\Service\DeliberationService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/deliberation")
 */
class PrintDeliberationListController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository,  protected DeliberationService $deliberationService, protected DecisionRepository $decisionRepository, protected SchoolRepository $schoolRepository )
    {
    }

    /**
     * @Route("/printDeliberationList", name="deliberation_printDeliberationList")
     */
    public function printDeliberationList(Request $request): Response
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

        $classrooms = [];
        $idC = $request->request->get('classroom');
        
        $decision = $this->decisionRepository->find($request->request->get('decision'));
        
        if((int)$idC != 0)
        {
            // c'est la liste d'une seule classe est demandée
            $selectedClassroom = $this->classroomRepository->find((int)$idC);
            
            if(count($selectedClassroom->getStudents()) && $selectedClassroom->isIsDeliberated())
            {
                $classrooms[] = $selectedClassroom;
            }

        }else
        {
            //Si les listes de toutes les classes sont demandées on recupère toutes les classes déjà délibérées
            if($this->isGranted(ConstantsClass::ROLE_CENSOR))
            {
                $allClassrooms = $this->classroomRepository->findCensorClassrooms($this->getUser()->getTeacher(), $schoolYear, $subSystem);
            }else 
            {
                $allClassrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
                
            }
       
            foreach($allClassrooms as $classroom)
            {
                if(count($classroom->getStudents())  && $classroom->isIsDeliberated())
                {
                    $classrooms[] = $classroom;
                }
            }
        }

        $decisio = $decision->getDecision();
        
        $allStudentList = $this->deliberationService->getStudentDeliberationList($classrooms, $decision);
       
        $pdf = $this->deliberationService->printStudentDeliberationList($allStudentList, $school, $schoolYear, $decisio);
        
        return new Response($pdf->Output(), 200, ['Content-Type' => 'application/pdf']);

    }

}
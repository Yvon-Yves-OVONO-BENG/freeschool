<?php

namespace App\Controller\Deliberation;

use App\Repository\ClassroomRepository;
use App\Repository\NextYearRepository;
use App\Repository\SchoolRepository;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\SchoolYearRepository;
use App\Service\ClassroomService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\HttpFoundation\Request;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/deliberation")
 */
class StudentProvisoryListController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository, protected SchoolYearRepository $schoolYearRepository, protected NextYearRepository $nextYearRepository,  protected ClassroomService $classroomService, protected SchoolRepository $schoolRepository)
    {
    }

    /**
     * @Route("/studentProvisoryList", name="delibeartion_studentProvisoryList")
     */
    public function studentProvisoryList(Request $request)
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

        $nextSchoolYearLabel = $this->nextYearRepository->findAll()[0]->getNextYear();
        
        $schoolYearExplode = explode('-', $nextSchoolYearLabel);
        
        $year1 = (int)$schoolYearExplode[0];
        $year2 = (int)$year1 - 1;
        
        $nextYearSchoolName = $year2.'-'.$year1;
        
        $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $nextSchoolYearLabel]);
        
        if ($schoolYear) 
        {
            // on recupèere toutes les classes
            $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

            if ($classrooms == null) 
            {
                $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $nextYearSchoolName]);
                $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            }
            
        }else
        {
            $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $nextYearSchoolName]);
            // on recupèere toutes les classes
            $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            
        }

        // On scinde les classes par niveau
        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        
        return $this->render('deliberation/studentProvisoryList.html.twig', [
            'classrooms' => $classrooms,
            'school' => $school,
        ]);
    }


    
}

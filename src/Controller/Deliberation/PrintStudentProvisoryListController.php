<?php

namespace App\Controller\Deliberation;

use App\Entity\ConstantsClass;
use App\Repository\ClassroomRepository;
use App\Repository\NextYearRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\SchoolRepository;
use App\Repository\SchoolYearRepository;
use App\Service\RegisterAndListService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

/**
 * @Route("/deliberation")
 */
class PrintStudentProvisoryListController extends AbstractController
{
    public function __construct(protected ClassroomRepository $classroomRepository, protected SchoolYearRepository $schoolYearRepository, protected NextYearRepository $nextYearRepository,  protected SchoolRepository $schoolRepository, protected RegisterAndListService $registerAndListService)
    {
    }

    /**
     * @Route("/printStudentProvisoryList", name="deliberation_printStudentProvisoryList")
     */
    public function printStudentProvisoryList(Request $request): Response
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

        $nextSchoolYearLabel = $this->nextYearRepository->findAll()[0]->getNextYear();

        ///////////////////
        $schoolYearExplode = explode('-', $nextSchoolYearLabel);
        
        $year1 = (int)$schoolYearExplode[0];
        $year2 = (int)$year1 - 1;
        
        $nextYearSchoolName = $year2.'-'.$year1;
        ///////////////////

        $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $nextSchoolYearLabel]);

        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        if ($school == null) 
        {
            $schoolYear = $this->schoolYearRepository->findOneBy(['schoolYear' => $nextYearSchoolName]);
            $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);
        }

        ///////////////////
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

            $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);
        }
        ////////////////////
        
        $classrooms = [];
        $idC = $request->request->get('classroom');

        if($idC != 0)
        {
            // Si on veut la liste d'une seule classe
            $selectedClassroom = $this->classroomRepository->find($idC);

            if(count($selectedClassroom->getStudents()))
            {
                $classrooms[] = $selectedClassroom;
            }

        }else
        {
            // Si toutes les classes on recupère toutes les classes
            if($this->isGranted(ConstantsClass::ROLE_CENSOR))
            {
                $allClassrooms = $this->classroomRepository->findCensorClassrooms($this->getUser()->getTeacher(), $schoolYear, $subSystem);
            }else 
            {
                $allClassrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
                
            }
            
            foreach($allClassrooms as $classroom)
            {
                if(count($classroom->getStudents()))
                {
                    $classrooms[] = $classroom;
                }
            }
        }

        
        $studentList = $this->registerAndListService->getStudentList($classrooms, $schoolYear);

        $pdf = $this->registerAndListService->printStudentList($studentList, $school, $schoolYear, $subSystem);
        
        return new Response($pdf->Output(), 200, ['Content-Type' => 'application/pdf']);
    }

}
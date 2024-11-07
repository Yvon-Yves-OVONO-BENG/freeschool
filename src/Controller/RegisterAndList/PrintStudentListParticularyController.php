<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Repository\SchoolRepository;
use App\Repository\CountryRepository;
use App\Repository\StudentRepository;
use App\Repository\HandicapRepository;
use App\Repository\MovementRepository;
use App\Repository\ClassroomRepository;
use App\Service\RegisterAndListService;
use App\Repository\EthnicGroupRepository;
use App\Repository\HandicapTypeRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/register_and_list")]
class PrintStudentListParticularyController extends AbstractController
{
    public function __construct(
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository,  
        protected CountryRepository $countryRepository, 
        protected MovementRepository $movementRepository, 
        protected HandicapRepository $handicapRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EthnicGroupRepository $ethnicGroupRepository, 
        protected RegisterAndListService $registerAndListService, 
        protected HandicapTypeRepository $handicapTypeRepository,
        )
    {}

    #[Route("/printStudentListParticulary", name:"register_and_list_printStudentListParticulary")]
    public function printStudentListParticulary(Request $request)
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
        

        $school = $this->schoolRepository->findOneBySchoolYear($schoolYear);

        // $selectedClassroom = $this->classroomRepository->find($request->request->get('classroom'));
        $selectedEthnicGroup = $this->ethnicGroupRepository->find($request->request->get('ethnicGroup'));
        $selectedMovement = $this->movementRepository->find($request->request->get('movement'));
        $selectedHandicap = $this->handicapRepository->find($request->request->get('handicap'));
        $selectedHandicapType = $this->handicapTypeRepository->find($request->request->get('handicapType'));
        $selectedCountry = $this->countryRepository->find($request->request->get('country'));

        $classrooms = [];
        $allStudents = [];
        if($request->request->get('classroom') == 0)
        {
            // on recupèere toutes les classes
            if($this->isGranted(ConstantsClass::ROLE_CENSOR))
            {
                /**
                 * @var User
                 */
                $user = $this->getUser();
                $classrooms = $this->classroomRepository->findCensorClassrooms($user->getTeacher(), $schoolYear, $subSystem);
            }else 
            {
                $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);
            }
        }else
        {
            $classrooms[] = $this->classroomRepository->find($request->request->get('classroom'));
        }

        // on recupère les élèves à imprimer
        foreach ($classrooms as $classroom) 
        {
            $students =  $this->studentRepository->findParticularStudent($schoolYear, $classroom, $selectedEthnicGroup, $selectedMovement, $selectedHandicap, $selectedHandicapType, $selectedCountry);

            if(!empty($students))
            {
                $allStudents[] = $students;

            }
        }

        $pdf = $this->registerAndListService->printParticularStudent($allStudents, $school, $schoolYear, $selectedEthnicGroup, $selectedMovement, $selectedHandicap, $selectedHandicapType, $selectedCountry);

        if($request->request->get('classroom') == 0)
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Student List Particulary"), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("List Particulier des élèves"), "I"), 200, ['content-type' => 'application/pdf']);
            }
            
        }
        else
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Student List Particulary of ".$classrooms[0]->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Liste des élèves particuliers de la ".$classrooms[0]->getClassroom()), "I"), 200, ['content-type' => 'application/pdf']);
            }
        }
        

    }

}

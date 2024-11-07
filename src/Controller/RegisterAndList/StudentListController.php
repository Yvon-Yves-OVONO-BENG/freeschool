<?php

namespace App\Controller\RegisterAndList;

use App\Entity\ConstantsClass;
use App\Service\ClassroomService;
use App\Repository\SchoolRepository;
use App\Repository\CountryRepository;
use App\Repository\HandicapRepository;
use App\Repository\MovementRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EthnicGroupRepository;
use App\Repository\HandicapTypeRepository;
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
class StudentListController extends AbstractController
{
    public function __construct(
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected CountryRepository $countryRepository, 
        protected MovementRepository $movementRepository, 
        protected HandicapRepository $handicapRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected EthnicGroupRepository $ethnicGroupRepository, 
        protected HandicapTypeRepository $handicapTypeRepository, 
        )
    {}


    #[Route("/studentList", name:"register_and_list_studentList")]
    public function studentList(Request $request): Response
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

        $classrooms = $this->classroomService->splitClassrooms($classrooms);

        // on recupère eléménts des listes deroulantes à afficher
        $countries = $this->countryRepository->findBy([], ['country' => 'ASC']);
        $movements = $this->movementRepository->findBy([], ['movement' => 'ASC']);
        $handicaps = $this->handicapRepository->findBy([], ['handicap' => 'ASC']);
        $handicapTypes = $this->handicapTypeRepository->findBy([], ['handicapType' => 'ASC']);
        $ethnicGroups = $this->ethnicGroupRepository->findBy([], ['ethnicGroup' => 'ASC']);

        return $this->render('register_and_list/studentList.html.twig', [
            'classrooms' => $classrooms,
            'countries' => $countries,
            'movements' => $movements,
            'handicapTypes' => $handicapTypes,
            'ethnicGroups' => $ethnicGroups,
            'handicaps' => $handicaps,
            'school' => $school,
        ]);
    }

}

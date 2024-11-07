<?php

namespace App\Controller\Registration;

use App\Entity\Registration;
use App\Service\FeesService;
use App\Service\ClassroomService;
use App\Repository\FeesRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\ClassroomRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class SchoolFeesController extends AbstractController
{
    public function __construct(
        protected FeesService $feesService, 
        protected FeesRepository $feesRepository, 
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected RegistrationRepository $registrationRepository, 
        )
    {}

    #[Route("/schoolFees/{headmasterFees<[0-1]{1}>}/{slugStudent}", name:"registration_schoolFees")]
    public function schoolFees(Request $request, int $headmasterFees = 0, string $slugStudent = null): Response
    {
        $mySession = $request->getSession();
        #mes variables témoin pour afficher les sweetAlert
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        $schoolYear = $mySession->get('schoolYear');
        $subSystem = $mySession->get('subSystem');

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        if(!$mySession)
        {
            return $this->redirectToRoute("app_logout");
        }
        
        $schoolYear = $mySession->get('schoolYear');

        //on récupère les frais exigibles de l'année en cours
        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);

        $registration = new Registration();

        //on récupère les classes pour la liste déroulante
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        $student = $this->studentRepository->findOneBySlug(['slug' => $slugStudent]);

        //on récupère la classe del'élève
        $selectedClassroom = $student->getClassroom();

        //on récupère les versements de l'élève
        $studentRegistration = $this->registrationRepository->findOneBy(['student' => $student]);
        // dd($studentRegistration);
        $feesTable = [];

        if(!is_null($studentRegistration))
        {
            $registration = clone $studentRegistration;
            
        }

        ///j'appele mon service feesService
        $feesTable = $this->feesService->getFeesTable($selectedClassroom, $fees);

        $classrooms = $this->classroomService->splitClassrooms($classrooms);
        return $this->render('registration/schoolFees.html.twig', [
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'student' => $student,
            'registration' => $registration,
            'feesTable' => $feesTable,
            'headmasterFees' => $headmasterFees,
            'school' => $school,
        ]);
    }

}

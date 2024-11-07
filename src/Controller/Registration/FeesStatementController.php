<?php

namespace App\Controller\Registration;

use App\Entity\Classroom;
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
use App\Repository\RegistrationHistoryRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/registration")]
class FeesStatementController extends AbstractController
{
    public function __construct(
        protected FeesService $feesService, 
        protected FeesRepository $feesRepository, 
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected RegistrationRepository $registrationRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository,  
        )
    {}
    
    #[Route("/feesStatement/{headmasterFees<[0-1]{1}>}/{slugClassroom}", name:"registration_feesStatement")]
    public function feesStatement(Request $request, int $headmasterFees = 0, string $slugClassroom = null): Response
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
        if($slugClassroom != null)
        {
            // On ajoute le classroom à la request pour permettre l'affichage des students
            //  et non le formulaire de choix de la classe
            $id = $this->classroomRepository->findOneBySlug(['slug' => $slugClassroom])->getId();
            $request->request->set('classroom', $id);
        }
       
        $methodIsPost = false;

        $emptyRegistration = new Registration;

        $registrationHistories = [];
        $feesTable = [];

        $schoolYear = $mySession->get('schoolYear');

        // On recupère les differents frais de l'année en cours
        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);
        
        $selectedClassroom = new Classroom();
        $classrooms = [];
        $students = [];
        $numberOfStudentInSchool = 0;
        $classroomLevel = 0;

        if($request->request->has('classroom'))
        {
            $methodIsPost = true;

            $idc = $request->request->get('classroom');
            $selectedClassroom = $this->classroomRepository->find($idc);
            $students = $this->studentRepository->findAllToDisplay($selectedClassroom, $schoolYear);

            $classroomLevel = $selectedClassroom->getLevel()->getLevel();

            ///j'appele mon service feesService
            $feesTable = $this->feesService->getFeesTable($selectedClassroom, $fees);

            // On recupère l'historique des versements de chaque élève
            foreach ($students as $student) 
            {
                // $registrationHistories[] = [
                //     'student' => $student, 
                //     'history' => $this->registrationHistoryRepository->findBy(['student' => $student], ['createdAt' => 'DESC'])
                // ];
                $registrationHistories[] = [
                    'student' => $student, 
                    'registration' => $this->registrationRepository->findBy(['student' => $student], ['createdAt' => 'DESC'])
                ];

            }
        }

        // on recupère les classes
        $classrooms = $this->classroomRepository->findForSelect($schoolYear, $subSystem);

        // On scinde les classes par niveau
        $classrooms = $this->classroomService->splitClassrooms($classrooms);

        $numberOfStudentInSchool = count($this->studentRepository->findBy(['schoolYear' => $schoolYear]));
        
        return $this->render('registration/feesStatement.html.twig', [
            'school' => $school,
            'students' => $students,
            'feesTable' => $feesTable,
            'classrooms' => $classrooms,
            'methodIsPost' => $methodIsPost,
            'classroomLevel' => $classroomLevel,
            'headmasterFees' => $headmasterFees,
            'emptyRegistration' => $emptyRegistration,
            'selectedClassroom' => $selectedClassroom,
            'registrationHistories' => $registrationHistories,
            'numberOfStudentInSchool' => $numberOfStudentInSchool,
        ]);
    }

}
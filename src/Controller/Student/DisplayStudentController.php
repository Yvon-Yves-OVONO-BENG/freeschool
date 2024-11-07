<?php

namespace App\Controller\Student;

use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Service\ClassroomService;
use App\Repository\FeesRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\ClassroomRepository;
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

#[Route("/student")]
class DisplayStudentController extends AbstractController
{
    public function __construct(
        protected FeesRepository $feesRepository, 
        protected ClassroomService $classroomService, 
        protected SchoolRepository $schoolRepository,
        protected StudentRepository $studentRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository, 
        )
    {}

    #[Route("/displayStudent/{headmasterFees<[0-1]{1}>}/{id<[0-9]+>}/{a<[0-1]{1}>}/{m<[0-1]{1}>}/{s<[0-1]{1}>}", name:"student_displayStudent")]
    public function displayStudent(Request $request, int $headmasterFees = 0, int $id = 0, int $a = 0, int $m = 0, int $s = 0): Response
    {
        $mySession = $request->getSession();
        if ($a == 1 || $m == 0 || $s == 0) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout',null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', null);

        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la mise à jour
        if ($m == 1) 
        {
            #mes variables témoin pour afficher les sweetAlert
            $mySession->set('ajout', null);
            $mySession->set('suppression', null);
            $mySession->set('miseAjour', 1);
        }

        #je teste si le témoin n'est pas vide pour savoir s'il vient de la suppression
        if ($s == 1) 
        {
            $mySession->set('ajout',null);
            $mySession->set('suppression', 1);
            $mySession->set('miseAjour', null);
        }
        
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }

        $methodIsPost = false;

        $school = $this->schoolRepository->findOneBySchoolYear(['schoolYear' => $schoolYear]);

        // On recupères les frais de l'année en cours à exploiter dans la session du chef d'établissement et de l'intendant
        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);
        
        $selectedClassroom = new Classroom();
        $classrooms = [];
        $students = [];
        $schoolFees = 0;
        $apeeFees = 0;
        $computerFees = 0;
        $numberOfStudentInSchool = 0;
        $registrationHistories = [];

        if($request->request->has('classroom'))
        {
            $methodIsPost = true;

            $idc = $request->request->get('classroom');
            $selectedClassroom = $this->classroomRepository->find($idc);
            $students = $this->studentRepository->findAllToDisplay($selectedClassroom, $schoolYear);

            if($selectedClassroom->getLevel()->getCycle()->getCycle() == 1)
            {
                $schoolFees = $fees->getSchoolFees1();
                $apeeFees = $fees->getApeeFees1();
                $computerFees = $fees->getComputerFees1();
            }else
            {
                $schoolFees = $fees->getSchoolFees2();
                $apeeFees = $fees->getApeeFees2();
                $computerFees = $fees->getComputerFees2();
            }

            foreach ($students as $student) 
            {
                $registrationHistories[] = [
                    'student' => $student, 
                    'history' => $this->registrationHistoryRepository->findBy(['student' => $student], ['createdAt' => 'DESC'])
                ];
            }
        }
        
        $medicalBookletFees = $fees->getMedicalBookletFees();
        if ($medicalBookletFees == null) 
        {
            $medicalBookletFees = 0;
        }

        $cleanSchoolFees = $fees->getCleanSchoolFees();
        if ($cleanSchoolFees == null) 
        {
            $cleanSchoolFees = 0;
        }

        $photoFees = $fees->getPhotoFees();
        if ($photoFees == null) 
        {
            $photoFees = 0;
        }

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

        $numberOfStudentInSchool = count($this->studentRepository->findBy(['schoolYear' => $schoolYear, 'subSystem' => $subSystem]));
        
        if($id)
        {
            // On ajoute le classroom à la request pour permettre l'affichage des students
            //  et non le formulaire de choix de la classe
            $mySession->set('classroom', $id);
            $selectedClassroom = $this->classroomRepository->find($id);

            $students = $this->studentRepository->findBy([
                'classroom' => $selectedClassroom,
                'supprime' => 0,
            ], ['fullName' => 'ASC' ]);

            $methodIsPost = true;
        }

        
        return $this->render('student/displayStudent.html.twig', [
            'students' => $students,
            'classrooms' => $classrooms,
            'selectedClassroom' => $selectedClassroom,
            'schoolFees' => $schoolFees,
            'apeeFees' => $apeeFees,
            'computerFees' => $computerFees,
            'medicalBookletFees' => $medicalBookletFees,
            'cleanSchoolFees' => $cleanSchoolFees,
            'photoFees' => $photoFees,
            'headmasterFees' => $headmasterFees,
            'numberOfStudentInSchool' => $numberOfStudentInSchool,
            'methodIsPost' => $methodIsPost,
            'registrationHistories' => $registrationHistories,
            'school' => $school,
        ]);
    }

}

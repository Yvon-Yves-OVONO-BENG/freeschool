<?php

namespace App\Controller\Report;

use App\Entity\Term;
use App\Entity\Classroom;
use App\Service\FeesService;
use App\Entity\ConstantsClass;
use App\Entity\Sequence;
use App\Service\ClassroomService;
use App\Repository\FeesRepository;
use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\ClassroomRepository;
use App\Repository\VerrouReportRepository;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\VerrouInsolvableRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\RegistrationHistoryRepository;
use App\Repository\SequenceRepository;
use App\Repository\VerrouSequenceRepository;
use App\Service\SequenceService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class TranscriptStudentController extends AbstractController
{
    public function __construct(
        protected FeesService $feesService, 
        protected TermRepository $termRepository, 
        protected FeesRepository $feesRepository, 
        protected SequenceService $sequenceService,
        protected SchoolRepository $schoolRepository,
        protected ClassroomService $classroomService,
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository,
        protected ClassroomRepository $classroomRepository, 
        protected VerrouReportRepository $verrouReportRepository, 
        protected VerrouSequenceRepository $verrouSequenceRepository, 
        protected VerrouInsolvableRepository $verrouInsolvableRepository, 
        protected RegistrationHistoryRepository $registrationHistoryRepository, 
        )
    {}

    #[Route("/transcript-student", name:"transcript_student")]
    public function transcriptStudent(Request $request): Response
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
        
        $selectedClassroom = new Classroom();
        $classrooms = [];
        $students = [];

        $terms = [];
        $selectedTerm = new Term;
        $selectedSequence = new Sequence;

        $methodIsPost = false;

        // On recupères les frais de l'année en cours à exploiter dans la session du chef d'établissement et de l'intendant
        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);
        $apeeFees = 0;
        $computerFees = 0;
        $feesTable = [];

        $period = $request->request->get('period');
	    $firstPeriodLetter = substr($period, 0, 1);
        $idP = substr($period, 1);
        
        if($idc = $request->request->get('classroom'))
        {
            $methodIsPost = true;
            $selectedClassroom = $this->classroomRepository->find($idc);
            
            ///j'appele mon service feesService
            $feesTable = $this->feesService->getFeesTable($selectedClassroom, $fees);

            $students = $this->studentRepository->findBy([
                'classroom' => $selectedClassroom,
            ], [
                'fullName' => 'ASC'
            ]);
            
            if ($firstPeriodLetter == 's') 
            {
                $selectedSequence = $this->sequenceRepository->find($idP);
            } 
            elseif($firstPeriodLetter == 't') 
            {
                $selectedTerm = $this->termRepository->find($idP);
            }
            
            
            $idc = $request->request->get('classroom');
            $selectedClassroom = $this->classroomRepository->find($idc);
            $students = $this->studentRepository->findAllToDisplay($selectedClassroom, $schoolYear);

            if($selectedClassroom->getLevel()->getCycle()->getCycle() == 1)
            {
                $apeeFees = $fees->getApeeFees1();
                $computerFees = $fees->getComputerFees1();
            }else
            {
                $apeeFees = $fees->getApeeFees2();
                $computerFees = $fees->getComputerFees2();
            }
        }

        $medicalBookletFees = $fees->getMedicalBookletFees();
        $cleanSchoolFees = $fees->getCleanSchoolFees();
        $photoFees = $fees->getPhotoFees();

        // on recupère les trimestres
        $term1 = $this->termRepository->findOneByTerm(1);
        $term2 = $this->termRepository->findOneByTerm(2);
        $term3 = $this->termRepository->findOneByTerm(3);
        $term0 = $this->termRepository->findOneByTerm(0);

        // on recupère les verrouReport pour utiliser leurs états dans la requete des terms
        $term1IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term1
        ])->isVerrouReport();
        $term2IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term2
        ])->isVerrouReport();
        $term3IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term3
        ])->isVerrouReport();
        $term0IsLocked = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'term' => $term0
        ])->isVerrouReport();

        // on recupère les six sequences pour recupérer les verrouReport liés aux trimestres
        $sequence1 = $this->sequenceRepository->findOneBySequence(1);
        $sequence2 = $this->sequenceRepository->findOneBySequence(2);
        $sequence3 = $this->sequenceRepository->findOneBySequence(3);
        $sequence4 = $this->sequenceRepository->findOneBySequence(4);
        $sequence5 = $this->sequenceRepository->findOneBySequence(5);
        $sequence6 = $this->sequenceRepository->findOneBySequence(6);

        $sequence1IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence1
        ])->isVerrouSequence();

        $sequence2IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence2
        ])->isVerrouSequence();

        $sequence3IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence3
        ])->isVerrouSequence();

        $sequence4IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence4
        ])->isVerrouSequence();

        $sequence5IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence5
        ])->isVerrouSequence();

        $sequence6IsLocked = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $schoolYear,
            'sequence' => $sequence6
        ])->isVerrouSequence();

        // on recupère les séquences à afficher
        $sequences = $this->sequenceRepository->findForMark($term1IsLocked, $term2IsLocked, $term3IsLocked, $sequence1IsLocked, $sequence2IsLocked, $sequence3IsLocked, $sequence4IsLocked, $sequence5IsLocked, $sequence6IsLocked);

        // On enlève la séquence 6 de la liste s'il s'agit de l'enseignement technique
        $sequences = $this->sequenceService->removeSequence6($sequences, $schoolYear);

        // on recupère les trimestres à afficher
        $terms = $this->termRepository->findTermForReport($term1IsLocked, $term2IsLocked, $term3IsLocked, $term0IsLocked);
        // $terms = $this->termRepository->findBy([], ['term' => 'ASC']);
        
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
        

        //////// On recupère les differents frais de l'année en cours
        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);

        $registrationHistories = [];
        
        // On recupère l'historique des versements de chaque élève
        foreach ($students as $student) 
        {
            $registrationHistories[] = [
                'student' => $student, 
                'history' => $this->registrationHistoryRepository->findBy(['student' => $student], ['createdAt' => 'DESC'])
            ];
        }

        /////je récupère le verrou insolvable
        $verrouInsolvable = $this->verrouInsolvableRepository->find(1)->isVerrouInsolvable();

        
        return $this->render('report/transcriptStudent.html.twig', [
            'terms' => $terms,
            'school' => $school,
            'apeeFees' => $apeeFees,
            'students' => $students,
            'photoFees' => $photoFees,
            'feesTable' => $feesTable,
            'sequences' => $sequences,
            'classrooms' => $classrooms,
            'methodIsPost' => $methodIsPost,
            'computerFees' => $computerFees,
            'selectedTerm' => $selectedTerm,
            'cleanSchoolFees' => $cleanSchoolFees,
            'verrouInsolvable' => $verrouInsolvable,
            'selectedSequence' => $selectedSequence,
            'selectedClassroom' => $selectedClassroom,
            'annualTerm' => ConstantsClass::ANNUEL_TERM,
            'medicalBookletFees' => $medicalBookletFees,
            'registrationHistories' => $registrationHistories,
            'registrationHistories' => $registrationHistories,

        ]);
    }

}

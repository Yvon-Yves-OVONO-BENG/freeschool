<?php

namespace App\Controller\Problems;

use App\Entity\ConstantsClass;
use App\Entity\Sequence;
use App\Entity\Term;
use App\Repository\TermRepository;
use App\Repository\CycleRepository;
use App\Repository\LevelRepository;
use App\Repository\SchoolRepository;
use App\Repository\SubjectRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Repository\LessonRepository;
use App\Repository\SequenceRepository;
use App\Repository\StudentRepository;
use App\Repository\VerrouReportRepository;
use App\Repository\VerrouSequenceRepository;
use App\Service\ClassroomService;
use App\Service\SequenceService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/problems")]
class DetailsEvaluationsTermEleveController extends AbstractController
{
    public function __construct( 
        protected EntityManagerInterface $em,
        protected TermRepository $termRepository,
        protected SequenceService $sequenceService,
        protected ClassroomService $classroomService,
        protected LessonRepository $lessonRepository,
        protected SchoolRepository $schoolRepository, 
        protected StudentRepository $studentRepository,
        protected SequenceRepository $sequenceRepository,
        protected ClassroomRepository $classroomRepository, 
        protected EvaluationRepository $evaluationRepository,
        protected VerrouReportRepository $verrouReportRepository,
        protected VerrouSequenceRepository $verrouSequenceRepository,
        )
    {}

    
    #[Route("/details-evaluations-term-student/{slugStudent}/{termId}/{slugClassroom}", name:"details_evaluations_term_student")]
    public function detailsEvaluationEleve(Request $request, string $slugStudent, int $termId = null, string $slugClassroom)
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

        #je récupère las classe
        $classroom = $this->classroomRepository->findOneBy(['slug' => $slugClassroom]);

        // Récupérer toutes les matières attribuées à la classe
        $subjects = $this->lessonRepository->findBy(['classroom' => $classroom]);

        #je récupère l'lève
        $student = $this->studentRepository->findOneBy(['slug' => $slugStudent ]);

        #je récupère le trimestre
        $term = $this->termRepository->find($termId);

        // Récupérer les évaluations de l'élève pour le trimestre
        $evaluations = $this->evaluationRepository->getEvaluationsByEleveAndTrimestre($student, $term->getTerm());

        #les sequences
        $sequences = $this->sequenceRepository->findBy(['term' => $term]);

        // Associer les matières aux évaluations
        $resultats = [];
        foreach ($subjects as $subject) 
        {
            $subjectId = $subject->getSubject()->getId();
            $resultats[$subjectId] = [
                'nomSubject' => $subject->getSubject()->getSubject(),
                'evaluations' => []
            ];
        }

        foreach ($evaluations as $evaluation) 
        {
            $subjectId = $evaluation['subjectId'];
            $sequenceId = $evaluation['sequenceId'];

            if (isset($resultats[$subjectId])) 
            {
                $resultats[$subjectId]['evaluations'][$sequenceId] = $evaluation['mark']; // ou autre info
            }
        }
        
        return $this->render('problems/details_eleve.html.twig', [
            'studentId' => $student->getId(),
            'student' => $student,
            'resultats' => $resultats,
            'sequence' => "",
            'sequences' => $sequences,
            'term' => $term,
            'school' => $school,
        ]);
    }
}
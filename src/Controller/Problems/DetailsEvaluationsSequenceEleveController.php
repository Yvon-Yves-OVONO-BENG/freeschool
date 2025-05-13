<?php

namespace App\Controller\Problems;

use App\Repository\TermRepository;
use App\Repository\SchoolRepository;
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
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/problems")]
class DetailsEvaluationsSequenceEleveController extends AbstractController
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

    
    #[Route("/details-evaluations-sequence-student/{slugStudent}/{sequenceId}/{slugClassroom}", name:"details_evaluations_sequence_student")]
    public function detailsEvaluationsSEquenceEleve(Request $request, string $slugStudent, int $sequenceId, string $slugClassroom)
    {
        $mySession = $request->getSession();
        
        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);

        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

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

        #les sequences
        $sequence = $this->sequenceRepository->find($sequenceId);

        // Récupérer les évaluations de l'élève pour le trimestre
        $evaluations = $this->evaluationRepository->getEvaluationsByEleveAndSequence($student, $sequence);

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
                $resultats[$subjectId]['evaluations']['evaluationId'] = $evaluation['evaluationId']; // ou autre info
            }
        }
        // dd($resultats);
        return $this->render('problems/details_eleve.html.twig', [
            'studentId' => $student->getId(),
            'student' => $student,
            'resultats' => $resultats,
            'sequence' => $sequence,
            'school' => $school,
            'term' => "",
        ]);
    }
}
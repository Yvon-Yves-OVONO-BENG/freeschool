<?php

namespace App\Controller\Report;

use App\Repository\ClassroomRepository;
use App\Repository\LessonRepository;
use App\Service\SchoolYearService;
use App\Repository\StudentRepository;
use App\Repository\SchoolRepository;
use App\Repository\SequenceRepository;
use App\Repository\TermRepository;
use App\Service\PrintTranscriptService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @IsGranted("ROLE_USER", message="Accès refusé. Espace reservé uniquement aux abonnés")
 *
 */

#[Route("/report")]
class PrintTranscriptTermController extends AbstractController
{
    public function __construct(
        protected TermRepository $termRepository,
        protected TranslatorInterface $translator, 
        protected SchoolRepository $schoolRepository, 
        protected SchoolYearService $schoolYearService, 
        protected StudentRepository $studentRepository,  
        protected SequenceRepository $sequenceRepository,
        protected ClassroomRepository $classroomRepository,
        protected LessonRepository $lessonRepository,
        protected PrintTranscriptService $printTranscriptService, 
        )
    {}

    #[Route("/print-transcript-term-student/{slugStudent}/{slugTerm}", name:"print_transcript_term_student")]
    #[Route("/print-transcript-term-classroom/{slugClassroom}/{slugTerm}", name:"print_transcript_term_classroom")]
    public function printTranscript(Request $request, string $slugStudent = null, string $slugClassroom = null, string $slugTerm = null, $sequenceId = 0): Response
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
        
        $verrou = $mySession->get('verrou');
        
        if(!$this->schoolYearService->getAccess($verrou))
        {
            return $this->redirectToRoute('home_mainMenu');
        }

        $student = $this->studentRepository->findOneBySlug([
            'slug' => $slugStudent
        ]);
        $school = $this->schoolRepository->findOneBy(['schoolYear' => $schoolYear]);

        $classroom = $this->classroomRepository->findOneBy(['slug' => $slugClassroom]);

        $term = null;
        $sequence = null;
        
        if($slugTerm && !$slugClassroom)
        {
            $term = $this->termRepository->findOneBy(['slug' => $slugTerm]);

            $releves = $this->lessonRepository->getEvaluationsByStudentAndTrimester($student->getId(), $term->getId());
            
            $pdf = $this->printTranscriptService->printTranscriptStudentTerm($subSystem, $schoolYear, $school, $student->getClassroom(), $releves, $student, $term, $sequence);

        }

        if ($request->request->has('slugClassroom') && $request->request->has('term')) 
        {
            $term = $this->termRepository->find($request->request->get('term'));
            $classroom = $this->classroomRepository->findOneBy(['slug' => $request->request->get('slugClassroom')] );
            
            $relevesTermClasse = $this->lessonRepository->getSubjectsWithGradesByClassAndTrimester($classroom->getId(), $term->getId());
            
            // foreach ($releveTermClasse as $lesson) 
            // {
            //     $studentId = $lesson['studentId'];
        
            //     // Initialise les données de l'élève si non déjà présentes
            //     if (!isset($relevesTermClasse[$studentId])) 
            //     {
            //         $relevesTermClasse[$studentId] = [
            //             'studentName' => $lesson['studentName'],
            //             'teacher' => $lesson['teacher'],
            //             'subjects' => [],
            //         ];
            //     }
        
            //     // Ajouter les matières et notes
            //     $relevesTermClasse[$studentId]['subjects'][] = [
            //         'studentName' => $lesson['studentName'],
            //         'subjectName' => $lesson['subjectName'],
            //         'teacher' => $lesson['teacher'],
            //         'eval1' => $lesson['eval1'],
            //         'eval2' => $lesson['eval2'],
            //     ];
            // }
            
            if (count($relevesTermClasse) == 0) 
            {
                $pdf = $this->printTranscriptService->printTranscriptClasseTermEmpty($subSystem, $schoolYear, $school, $classroom, $term);
            } 
            else 
            {
                $pdf = $this->printTranscriptService->printTranscriptClasseTerm($subSystem, $schoolYear, $school, $classroom , $relevesTermClasse, $term);
            }
            
        }
        
        
       
        if ($request->request->has('slugClassroom') && $request->request->has('term')) 
        {
            $classroom = $this->classroomRepository->findOneBy(['slug' => $request->request->get('slugClassroom')] );
            
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Transcript's of ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Relevé de notes de ".$classroom->getClassroom()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            }
        } 
        else 
        {
            if ($subSystem->getId() == 1 ) 
            {
                return new Response($pdf->Output(utf8_decode("Transcript's of ".$student->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            } 
            else 
            {
                return new Response($pdf->Output(utf8_decode("Relevé de notes de ".$student->getFullName()), "I"), 200, ['Content-Type' => 'application/pdf']) ;
            }
        }
        
        
    }

}

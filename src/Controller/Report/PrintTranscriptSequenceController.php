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
class PrintTranscriptSequenceController extends AbstractController
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

    #[Route("/print-transcript-sequence-student/{slugStudent}/{sequenceId}", name:"print_transcript_sequence_student")]
    #[Route("/print-transcript-sequence-classroom/{slugClassroom}/{sequenceId}", name:"print_transcript_sequence_classroom")]
    public function printTranscriptSequence(Request $request, ?string $slugStudent = null, ?string $slugClassroom = null, int $sequenceId = 0): Response
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
        
        if ($sequenceId && !$slugClassroom) 
        {
            $sequence = $this->sequenceRepository->find($sequenceId);
            $releves = $this->lessonRepository->getEvaluationsByStudentAndSequence($student->getId(), $sequence->getId());
            
            $pdf = $this->printTranscriptService->printTranscriptStudentSequence($subSystem, $schoolYear, $school, $student->getClassroom(), $releves, $student, $term, $sequence);
        } 
        

        if ($request->request->has('slugClassroom') && $request->request->has('sequenceId')) 
        {
            $sequence = $this->sequenceRepository->find($request->request->get('sequenceId'));
            $classroom = $this->classroomRepository->findOneBy(['slug' => $request->request->get('slugClassroom')] );
            
            $relevesSequenceClasse = $this->lessonRepository->getTranscriptsByClassAndSequence($classroom->getId(), $sequence->getId());
            
            $pdf = $this->printTranscriptService->printTranscriptClasseSequence($subSystem, $schoolYear, $school, $classroom , $relevesSequenceClasse, $student, $term, $sequence);
        }
        
       
        if ($request->request->has('slugClassroom') && $request->request->has('sequenceId')) 
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

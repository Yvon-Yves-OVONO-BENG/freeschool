<?php

namespace App\Controller\Report;

use App\Entity\ConstantsClass;
use App\Repository\LessonRepository;
use App\Repository\SchoolRepository;
use App\Repository\StudentRepository;
use App\Repository\TermRepository;
use App\Service\PrintTranscriptService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DisplayTranscriptController extends AbstractController
{
    public function __construct(
        private TermRepository $termRepository,
        private SchoolRepository $schoolRepository,
        private LessonRepository $lessonRepository,
        private StudentRepository $studentRepository,
        private PrintTranscriptService $printTranscriptService
    )
    {}

    #[Route('/display-transcript/{slugStudent}/{slugTerm}', name: 'display_transcript')]
    public function DisplayTranscript($slugStudent, $slugTerm): Response
    {
        $student = $this->studentRepository->findOneBy([
            'slug' => $slugStudent
        ]);

        if ($student) 
        {
            $school = $this->schoolRepository->findOneBy([
                'schoolYear' => $student->getSchoolYear()
            ]);

            $subSystem = $student->getSubSystem()->getSubSystem();

            $term = $this->termRepository->findOneBy(['slug' => $slugTerm]);
        
            $releves = $this->lessonRepository->getAnnualReportByStudent($student->getId());
            
            return $this->render('report/display_transcript.html.twig', [
                'releves' => $releves,
                'student' => $student,
                'school' => $school,
                'term' => $term,
                'subSystem' => $subSystem
            ]);
        } 
        else 
        {
            return $this->redirectToRoute('page_error');
        }
        

        
    }
}

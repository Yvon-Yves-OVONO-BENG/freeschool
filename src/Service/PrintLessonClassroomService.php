<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Repository\LessonRepository;
use App\Repository\RegistrationRepository;
use Doctrine\Common\Collections\Collection;

class PrintLessonClassroomService 
{
    public function __construct(
        protected GeneralService $generalService, 
        protected LessonRepository $lessonRepository,
        protected RegistrationRepository $registrationRepository,
        )
    {}


    /**
     * Imprime les lesson d'un enseignant
     *
     * @param Teacher $teacher
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return PDF
     */
    public function print(Classroom $classroom, Collection $lessons, School $school, SchoolYear $schoolYear): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom);
        $pdf->SetFont('Times', '', 12);

        $i=1;
        foreach ($lessons as $lesson) 
        {
            if ($i % 2 != 0) 
            {
                $pdf->SetFillColor(219,238,243);
            }
            else 
            {
                $pdf->SetFillColor(255,255,255);
            }

            // on recupère ses lessons
            $otherTeacherLessons = $this->lessonRepository->findTeacherLessonsInClassroom($classroom, $lesson->getTeacher() );
            $counter = count($otherTeacherLessons);

            $pdf->Cell(10, 5, utf8_decode($i), 1, 0, 'C', true);
            $pdf->Cell(90, 5, utf8_decode($lesson->getTeacher()->getFullName()), 1, 0, 'L', true);

            $pdf->Cell(90, 5, utf8_decode($lesson->getSubject()->getSubject()), 1, 1, 'C', true);

            $i++;
        }
       

        //////////////////////////////

        return $pdf;
    }

    public function getHeader(PDF $pdf, Classroom $classroom): PDF
    {
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode('COURS DISPENSES EN '), 0, 2, 'C');
        $pdf->SetFont('Times', 'BI', 14);
        $pdf->Cell(0, 5, utf8_decode('COURSES TAUGHT IN'), 0, 2, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(0, 5, utf8_decode("Classe / Classroom : ".$classroom->getClassroom()), 0, 0, 'C');
        
        $pdf->Ln();
        $pdf->Ln();

        $pdf->Cell(10, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->Cell(90, 5, utf8_decode('ENSEIGNANT / TEACHER'), 1, 0, 'C', true);
        $pdf->Cell(90, 5, utf8_decode('COURS / COURSE'), 1, 1, 'C', true);

        return $pdf;
    }


}
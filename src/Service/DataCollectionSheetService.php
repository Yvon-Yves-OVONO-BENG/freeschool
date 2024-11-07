<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\School;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\SubSystem;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use App\Entity\ReportElements\Pagination;

class DataCollectionSheetService
{
    public function __construct(
        protected DutyRepository $dutyRepository,
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected TeacherRepository $teacherRepository, 
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    { }

    /**
     * Imprime le registre de reférence et le procès verbal des notes
     *
     * @param array $classroomReportsForRegister
     * @param School $school
     * @param SchoolYear $schoolYear
     * @param integer $numberOfLessons
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param array $sequence
     * @param integer $pv
     * @return Pagiation
     */
    public function printDataCollectionSheet(Term $term, Subject $subject, array $teachersClassrooms, SchoolYear $schoolYear, School $school, SubSystem $subSystem): Pagination
    {

        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellStudentNameWidth = 60;
        $cellNumberWidth = 6;
        $cellSubjectWidth = $cellStudentNameWidth+$cellNumberWidth;

        $cellHeight2 = 5*18/30;

        $pdf = new Pagination();

        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

        $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

        $pdf = $this->getEnteteFiche($pdf, $term, $subject, $subSystem);
        
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 4, utf8_decode("1er cycle"), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', '', 9);
        $pdf = $this->getEnteteTableau($pdf, $subSystem);

        // $pdf->SetFont('Arial', '', 10);

        ///CYCLE 1
        
        $pdf->SetFont('Times', '', 9);
        $numero = 0;
        foreach ($teachersClassrooms as $teachersClassroom) 
        {
            if ($teachersClassroom->getClassroom()->getLevel()->getCycle()->getCycle() == 1) 
            {
                $numero++;
                $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getClassroom()->getClassroom()), 'LRT', 1, 'C');
            
                $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getTeacher()->getFullName()), 'LRB', 0, 'C');
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->SetXY($x, $y-4);

                $pdf->Cell((85/3)/2, 4*2, utf8_decode($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()), 1, 0, 'C');

                $pdf->Cell((85/3)/2, 4*2, utf8_decode($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()), 1, 0, 'C');


                $pdf->Cell((85/3)/2, 4*2, utf8_decode(
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                    ), 1, 0, 'C');

                $pdf->Cell((85/3)/2, 4*2, utf8_decode(
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                    ), 1, 0, 'C');

                //////////////////
                if (($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode(
                        number_format(((
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                        )/($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()))*100, 2)
                    ), 1, 0, 'C');
                }
                

                if (($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode(
                        number_format(((
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                        )/($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq6()))*100, 2)
                    ), 1, 0, 'C');
                }

                $pdf->Cell((80/3)/2, 4*2, utf8_decode((
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6())*2), 1, 0, 'C');

                $pdf->Cell((80/3)/2, 4*2, utf8_decode((
                $teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6())*2), 1, 0, 'C');

                /////////////////////
                $pdf->Cell((80/3)/2, 4*2, utf8_decode((
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                )*2), 1, 0, 'C');

                $pdf->Cell((80/3)/2, 4*2, utf8_decode((
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                )*2), 1, 0, 'C');

                ////////////////////

                if (($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode(number_format(((
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                    )*2)/(($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq6())*2)*100,2)), 1, 0, 'C');
                }

                /////////////////
                if (($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode(number_format((($teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                    )*2)/(($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq6())*2)*100,2)), 1, 0, 'C');
                }

                /////////////////
                if (($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()

                ) == 0) 
                {
                    $pdf->Cell((25/2), 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((25/2), 4*2, utf8_decode(number_format(((
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +

                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                    )*2)/(($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq6() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq6()
                    )*2)*100,2)), 1, 0, 'C');
                }
                // dd(count($teachersClassroom->getClassroom()->getStudents()));
                $pdf->Cell((25/2), 4*2, utf8_decode("00"), 1, 0, 'C');

                ////////////////
                $pdf->Cell((40/3), 4*2, utf8_decode("00"), 1, 0, 'C');
                $pdf->Cell((40/3), 4*2, utf8_decode("00"), 1, 0, 'C');
                $pdf->Cell((40/3), 4*2, utf8_decode("00"), 1, 1, 'C');
                
                

            }

        }
        if($numero % 10 == 0 && $numero > 10)
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);
            $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getClassroom()->getClassroom()), 'LRT', 1, 'C');
    
            $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getTeacher()->getFullName()), 'LRB', 1, 'C');
        }
            
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(50, 6, utf8_decode("Total"), 1, 0, 'C', true);

        /////////////
        
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);

        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);

        $pdf->Cell((25/2), 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((25/2), 6, utf8_decode("00"), 1, 0, 'C', true);

        $pdf->Cell((40/3), 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((40/3), 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((40/3), 6, utf8_decode("00"), 1, 1, 'C', true);


        /////////////////2ND CYCLE //////////////////////////
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

        $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

        $pdf = $this->getEnteteFiche($pdf, $term, $subject, $subSystem);
        
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 4, utf8_decode("2nd cycle"), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', '', 9);
        $pdf = $this->getEnteteTableau($pdf, $subSystem);

        //////////CYCLE 2
        
        $pdf->SetFont('Times', '', 9);
        $numer = 0;
        foreach ($teachersClassrooms as $teachersClassroom) 
        {
            if ($teachersClassroom->getClassroom()->getLevel()->getCycle()->getCycle() == 2) 
            {
                $numer++;
                $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getClassroom()->getClassroom()), 'LRT', 1, 'C');
            
                $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getTeacher()->getFullName()), 'LRB', 0, 'C');

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->SetXY($x, $y-4);

                $pdf->Cell((85/3)/2, 4*2, utf8_decode($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()), 1, 0, 'C');

                $pdf->Cell((85/3)/2, 4*2, utf8_decode($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()), 1, 0, 'C');


                $pdf->Cell((85/3)/2, 4*2, utf8_decode($teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
            ), 1, 0, 'C');

                $pdf->Cell((85/3)/2, 4*2, utf8_decode($teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
            ), 1, 0, 'C');

                //////////////////
                if (($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode(
                        number_format((($teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                        $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                        )/($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                        $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()))*100, 2)
                    ), 1, 0, 'C');
                }
                

                if (($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((85/3)/2, 4*2, utf8_decode(
                        number_format((($teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                        $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                        )/($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                        $teachersClassroom->getNbreLessonPratiquePrevueSeq6()))*100, 2)
                    ), 1, 0, 'C');
                }

                $pdf->Cell((80/3)/2, 4*2, utf8_decode(($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6())*2), 1, 0, 'C');

                $pdf->Cell((80/3)/2, 4*2, utf8_decode(($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6())*2), 1, 0, 'C');

                /////////////////////
                $pdf->Cell((80/3)/2, 4*2, utf8_decode((
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                )*2), 1, 0, 'C');

                $pdf->Cell((80/3)/2, 4*2, utf8_decode(($teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                )*2), 1, 0, 'C');

                ////////////////////

                if (($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode(number_format(((
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                    )*2)/(($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq6())*2)*100,2)), 1, 0, 'C');
                }

                /////////////////
                if (($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()) == 0) 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((80/3)/2, 4*2, utf8_decode(number_format(((
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                    )*2)/(($teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq6())*2)*100,2)), 1, 0, 'C');
                }

                /////////////////
                if (($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                $teachersClassroom->getNbreLessonTheoriquePrevueSeq6() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                $teachersClassroom->getNbreLessonPratiquePrevueSeq6()

                ) == 0) 
                {
                    $pdf->Cell((25/2), 4*2, utf8_decode("00"), 1, 0, 'C');
                } else 
                {
                    $pdf->Cell((25/2), 4*2, utf8_decode(number_format((($teachersClassroom->getNbreLessonTheoriqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq1() + 
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteSeq6() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                    $teachersClassroom->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                    )*2)/(($teachersClassroom->getNbreLessonTheoriquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonTheoriquePrevueSeq6() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq1() + 
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq2() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq3() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq4() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq5() +
                    $teachersClassroom->getNbreLessonPratiquePrevueSeq6()
                    )*2)*100,2)), 1, 0, 'C');
                }

                $pdf->Cell((25/2), 4*2, utf8_decode("00"), 1, 0, 'C');

                ////////////////
                $pdf->Cell((40/3), 4*2, utf8_decode("00"), 1, 0, 'C');
                $pdf->Cell((40/3), 4*2, utf8_decode("00"), 1, 0, 'C');
                $pdf->Cell((40/3), 4*2, utf8_decode("00"), 1, 1, 'C');
            }
            
        }

        if($numer % 8 == 0 && $numer > 8)
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);
            $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getClassroom()->getClassroom()), 'LRT', 1, 'C');
    
            $pdf->Cell(50, 4, utf8_decode($teachersClassroom->getTeacher()->getFullName()), 'LRB', 1, 'C');
        }
        
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(50, 6, utf8_decode("Total"), 1, 0, 'C', true);

        /////////////
        
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((85/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);

        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((80/3)/2, 6, utf8_decode("00"), 1, 0, 'C', true);

        $pdf->Cell((25/2), 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((25/2), 6, utf8_decode("00"), 1, 0, 'C', true);

        $pdf->Cell((40/3), 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((40/3), 6, utf8_decode("00"), 1, 0, 'C', true);
        $pdf->Cell((40/3), 6, utf8_decode("00"), 1, 1, 'C', true);

        //////////////NOUVELLE PAGE
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

        $pdf->SetFont('Times', 'B', 15);
        $pdf->Cell(0, 6, utf8_decode("OBSERVATIONS GENERALES"), 1, 1, 'C', true);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(277/4, 6, utf8_decode("TAUX DE COUVERTURE DES "), "LR", 0, 'C');
        $pdf->Cell(277/4, 6, utf8_decode("TAUX DE COUVERTURE DES "), "LR", 0, 'C');
        $pdf->Cell(277/4, 6*2, utf8_decode("TAUX D'ASSIDUITE"), "LRB", 0, 'C');
        $pdf->Cell(277/4, 6*2, utf8_decode("TAUX DE REUSSITE"), "LRB", 1, 'C');

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->SetXY($x, $y-6);
        $pdf->Cell(277/4, 6, utf8_decode("PROGRAMMES"), "LRB", 0, 'C');
        $pdf->Cell(277/4, 6, utf8_decode("HEURES D'ENSEIGNEMENT"), "LRB", 1, 'C');
        // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 0, 'C');
        // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 1, 'C');

        $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
        $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
        $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
        $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 1, 'C');

        $pdf->SetFont('Times', 'BU', 12);
        $pdf->Cell(277, 6*2, utf8_decode("SUGGESTIONS"), "LR", 1, 'L');
        $pdf->Cell(277, 6*10, utf8_decode(""), "LRB", 1, 'L');
        $pdf->Ln(6);

        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(250, 6, utf8_decode("LE CHEF D'ETABLISSEMENT"), 0, 1, 'R');



        return $pdf;
    }

    /**
     * L'INTITULE DE LA FICHE
     */
    public function getEnteteFiche(Pagination $pdf,Term $term, Subject $subject, SubSystem $subSystem)
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE )
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 7, utf8_decode("FICHE DE COLLECTE DES DONNEES STATISTIQUES RELATIVES AUX EVALUATIONS DES TAUX DE COUVERTURE "), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode("DES PROGRAMMES, DES HEURES D'ENSEIGNEMENT ET DES TAUX D'ASSIDUITE ET REUSSITE"), 0, 1, 'C');
            $pdf->Ln();
            // $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(140, 5, utf8_decode("Matière : ".strtoupper($subject->getSubject())), 0, 0, 'C');
            $pdf->Cell(100, 5, utf8_decode(strtoupper($term->getTerm())), 0, 1, 'C');
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 7, utf8_decode("STATISTICAL DATA COLLECTION SHEET RELATING TO EVALUATIONS OF COVERAGE RATES "), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode("PROGRAMS, TEACHING HOURS AND ATTENDANCE AND SUCCESS RATES"), 0, 1, 'C');
            $pdf->Ln();
            // $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(140, 5, utf8_decode("Matière : ".strtoupper($subject->getSubject())), 0, 0, 'C');
            $pdf->Cell(100, 5, utf8_decode(strtoupper($term->getTerm())), 0, 1, 'C');
        }
            
       
        
        return $pdf;
    }

    /**
     * ENTETE DU TABLEAU
     */
    public function getEnteteTableau(Pagination $pdf, SubSystem $subSystem)
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(50, 5*5, utf8_decode("Classes / "), 1, 0, 'C', true);
            // $pdf->Cell(30, 5*2.5, utf8_decode("Nom de"), 1, 0, 'C', true);
            $pdf->Cell(85, 5, utf8_decode("TAUX DE COUVERTURE DES PROGRAMMES PAR"), 'LTR', 0, 'C', true);
            $pdf->Cell(80, 5, utf8_decode("TAUX DE COUVERTURE DES HEURES"), 'LTR', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("TAUX"), 'LTR', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("TAUX DE"), 'LTR', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+50, $y);
            $pdf->Cell(85, 5, utf8_decode("RAPPORT A L'ANNEE"), 'LRB', 0, 'C', true);
            $pdf->Cell(80, 5, utf8_decode("D'ENSEIGNEMENT PAR RAPPORT A L'ANNEE"), 'LRB', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("D'ASSIDUITE"), 'LRB', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("DE REUSSITE"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+50, $y);
            // $pdf->Cell(30, 5*2.5, utf8_decode("de l'enseignant"), 1, 0, 'C', true);
            $pdf->Cell((85/3), 5, utf8_decode("Leçons"), 'LR', 0, 'C', true);
            $pdf->Cell((85/3), 5, utf8_decode("Leçons"), 'LR', 0, 'C', true);
            $pdf->Cell((85/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell((80/3), 5*2, utf8_decode("Heures dues"), 'LRB', 0, 'C', true);
            $pdf->Cell((80/3), 5*2, utf8_decode("Heures Faites"), 'LRB', 0, 'C', true);
            $pdf->Cell((80/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell((25/2), 5*3, utf8_decode("Ens"), 'LRB', 0, 'C', true);
            $pdf->Cell((25/2), 5*3, utf8_decode("Elev"), 'LRB', 0, 'C', true);

            $pdf->Cell((40/3), 5*3, utf8_decode("Eff Eval"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("M>=10"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("%"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x, $y-10);
            $pdf->Cell(50, 5, utf8_decode("Enseignant"), 'LR', 0, 'C', true);
            $pdf->Cell(85/3, 5, utf8_decode("prévues"), 'LRB', 0, 'C', true);
            $pdf->Cell(85/3, 5, utf8_decode("faites"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+50, $y);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);

            $pdf->Cell((80/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Prat"), 1, 1, 'C', true);
        }else
        {
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(50, 5*5, utf8_decode("Classes / "), 1, 0, 'C', true);
            // $pdf->Cell(30, 5*2.5, utf8_decode("Nom de"), 1, 0, 'C', true);
            $pdf->Cell(85, 5, utf8_decode("COVERAGE RATE OF PROGRAMS BY"), 'LTR', 0, 'C', true);
            $pdf->Cell(80, 5, utf8_decode("TCOVERAGE TIMES"), 'LTR', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("RATE"), 'LTR', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("RATE"), 'LTR', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+50, $y);
            $pdf->Cell(85, 5, utf8_decode("REPORT TO THE YEAR"), 'LRB', 0, 'C', true);
            $pdf->Cell(80, 5, utf8_decode("OF TEACHING COMPARED TO THE YEAR"), 'LRB', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("ATTENDANCE"), 'LRB', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("OF SUCCESS"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+50, $y);
            // $pdf->Cell(30, 5*2.5, utf8_decode("de l'enseignant"), 1, 0, 'C', true);
            $pdf->Cell((85/3), 5, utf8_decode("Lessons"), 'LR', 0, 'C', true);
            $pdf->Cell((85/3), 5, utf8_decode("Lessons"), 'LR', 0, 'C', true);
            $pdf->Cell((85/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell((80/3), 5*2, utf8_decode("Hours due"), 'LRB', 0, 'C', true);
            $pdf->Cell((80/3), 5*2, utf8_decode("Hours done"), 'LRB', 0, 'C', true);
            $pdf->Cell((80/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell((25/2), 5*3, utf8_decode("Ens"), 'LRB', 0, 'C', true);
            $pdf->Cell((25/2), 5*3, utf8_decode("Elev"), 'LRB', 0, 'C', true);

            $pdf->Cell((40/3), 5*3, utf8_decode("Eff Eval"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("M>=10"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("%"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x, $y-10);
            $pdf->Cell(50, 5, utf8_decode("Teacher"), 'LR', 0, 'C', true);
            $pdf->Cell(85/3, 5, utf8_decode("planned"), 'LRB', 0, 'C', true);
            $pdf->Cell(85/3, 5, utf8_decode("done"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+50, $y);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell((85/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);

            $pdf->Cell((80/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell((80/3)/2, 5, utf8_decode("Prat"), 1, 1, 'C', true);
        }
        return $pdf;
    }

    /**
     * Compte le nombre d'heures par semaine d'un enseignant
     */
    public function getHoursPerWeek(Teacher $teacher): int
    {
        $numberOfHours = 0;
        $lessons = $teacher->getLessons();

        foreach($lessons as $lesson)
        {
            $numberOfHours += $lesson->getWeekHours();
        }

        return $numberOfHours;

    }

    
}
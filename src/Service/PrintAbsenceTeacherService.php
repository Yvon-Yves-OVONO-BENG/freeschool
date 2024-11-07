<?php

namespace App\Service;
use App\Entity\School;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Repository\TermRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintAbsenceTeacherService
{
    public function __construct(
        protected RequestStack $request,
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected TeacherRepository $teacherRepository, 
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    
    /**
     * Imprime le repertoire de la classe
     *
     * @param School $school
     * @return PDF
     */
    public function printAbsenceTeachersReports(array $allAbsencesTeachers, School $school, SchoolYear $schoolYear): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new PDF();

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
        
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
        
        // Entête de la liste
        $pdf = $this->getHeaderTeacherList($pdf, $school);

        // entête du tableau
        $pdf = $this->getTableHeaderTeacherList($pdf, $cellHeaderHeight2);

        // contenu du tableau
        $pdf->SetFont('Times', '', $fontSize);
        $numero = 0;
        $numberOfStudents = count($allAbsencesTeachers);
        
        foreach($allAbsencesTeachers as $allAbsencesTeacher)
        {
            $numero++;
            if ($numero % 2 != 0) 
            {
                $pdf->SetFillColor(219,238,243);
            }else {
                $pdf->SetFillColor(255,255,255);
            }
            $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
            $pdf->SetFont('Times', '', $fontSize-1);
            $pdf->Cell(80, $cellBodyHeight2, utf8_decode($allAbsencesTeacher->getTeacher()->getFullName()), 1, 0, 'L', true);

            
          
            $pdf->Cell(20, $cellBodyHeight2, $allAbsencesTeacher->getTerm()->getTerm() == 1 ? $allAbsencesTeacher->getAbsenceTeacher() : 0, 1, 0, 'C', true);
            $pdf->Cell(20, $cellBodyHeight2, $allAbsencesTeacher->getTerm()->getTerm() == 2 ? $allAbsencesTeacher->getAbsenceTeacher() : 0, 1, 0, 'C', true);
            $pdf->Cell(20, $cellBodyHeight2, $allAbsencesTeacher->getTerm()->getTerm() == 3 ? $allAbsencesTeacher->getAbsenceTeacher() : 0, 1, 0, 'C', true);

            $pdf->Cell(20, $cellBodyHeight2, ($allAbsencesTeacher->getTerm()->getTerm() == 1 ? $allAbsencesTeacher->getAbsenceTeacher() : 0) + ($allAbsencesTeacher->getTerm()->getTerm() == 2 ? $allAbsencesTeacher->getAbsenceTeacher() : 0) + ($allAbsencesTeacher->getTerm()->getTerm() == 3 ? $allAbsencesTeacher->getAbsenceTeacher() : 0), 1, 0, 'C', true);
            
            $pdf->Ln();

            if( ($numero % 30) == 0 && $numberOfStudents > 30) /*On passe à une nouvelle page après 30 lignes*/
            {
                // On insère une page
                $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
                // Entête de la liste
                $pdf = $this->getHeaderTeacherList($pdf, $school);

                // entête du tableau
                $pdf = $this->getTableHeaderTeacherList($pdf, $cellHeaderHeight2);

                $pdf->SetFont('Times', '', $fontSize);

            }
        }
        $pdf = $this->generalService->doAt($pdf, $school, $cellHeaderHeight2);
        

        return $pdf;
    }


    /**
     * Entête de la fiche de la liste des élèves
     *
     * @param PDF $pdf
     * @param School $school
     * @param Classroom $classroom
     * @return PDF
     */
    public function getHeaderTeacherList(PDF $pdf, School $school): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Times', 'B', 14);

        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->Cell(0, 5, 'ABSENCES DES ENSEIGNANTS', 0, 2, 'C');
        } else 
        {
            $pdf->Cell(0, 5, "ABSENCE OF TEACHERS", 0, 2, 'C');
        }
        
        $pdf->Ln(2);

        $pdf->Cell(35, 5, '', 0, 0, 'C');
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        return $pdf;
    }


    /**
     * Entête du tableau de la liste des élèves
     *
     * @param PDF $pdf
     * @param integer $cellHeaderHeight2
     * @return PDF
     */
    public function getTableHeaderTeacherList(PDF $pdf, int $cellHeaderHeight2): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeaderHeight2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Trimstre 1'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Trimestre 2'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Trimestre 3'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Annuel'), 1, 0, 'C', true);
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeaderHeight2, utf8_decode("First and last names"), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode("Term 1"), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode("Term 2"), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode("Term 3"), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode("Annual"), 1, 0, 'C', true);
            $pdf->Ln();
        }

        return $pdf;
    }


    
}
<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\SchoolYear;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Entity\Department;

class PrintSubjectByDepartmentService 
{
    public function __construct(
        protected GeneralService $generalService,)
    {}


    public function print(Department $department, School $school, SchoolYear $schoolYear): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $department);

        $pdf->Ln(10);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(0, 5, utf8_decode('Matières / Subjects'), 1, 1, 'C', true);
        
        $pdf->SetFont('Times', '', 12);
        foreach ($department->getSubjects() as $subject) 
        {
            $pdf->Cell(0, 5, utf8_decode($subject->getSubject()), 1, 1, 'L');
        }

        return $pdf;
    }

    public function getHeader(PDF $pdf, Department $department): PDF
    {
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode('LES MATIERES DU DEPARTEMENT'), 0, 2, 'C');
        $pdf->SetFont('Times', 'BI', 14);
        $pdf->Cell(0, 5, utf8_decode("THE DEPARTMENT'S SUBJECT"), 0, 2, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(190, 5, utf8_decode($department->getDepartment()), 0, 0, 'C');
        $pdf->Ln();

        return $pdf;
    }


}
<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\Student;
use App\Entity\SchoolYear;
use App\Entity\ReportElements\PDF;

class QuitusService 
{   
    public function __construct(protected GeneralService $generalService)
    {}
    
    public function printStudentQuitus(School $school, SchoolYear $schoolYear, Student $student): PDF 
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;
        $cellHeaderHeight = 6.52;

        $numberWith = 10;
        $fullNameWith = 60;
        $feesWith = 30;

        $pdf =  new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'L', 10, $fontSize-3);
        
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderQuitus($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
        
        // Entête de la liste
        $pdf = $this->getHeaderStudentQuitus($pdf, $school, $student);

        $pdf->Ln(5);
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(70, 5, utf8_decode("NOM ET PRENOM DE L'ELEVE : "), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(100, 5, utf8_decode($student->getFullName()), 0, 1, 'L');
        $pdf->SetFont('Arial', 'I', 11);
        $pdf->Cell(100, 5, utf8_decode("NAME AND SURNAME OF STUDENT : "), 0, 1, 'L');

        
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(70, 5, utf8_decode("DATE ET LIEU DE NAISSANCE : "), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 5, utf8_decode(date_format($student->getBirthday(), 'd-m-Y') ), 0, 0, 'L');
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(5, 5, utf8_decode("A"), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(100, 5, utf8_decode($student->getBirthplace() ), 0, 1, 'L');

        $pdf->SetFont('Arial', 'I', 11);
        $pdf->Cell(100, 5, utf8_decode("DATE AND PLACE BIRTH : "), 0, 0, 'L');
        $pdf->Cell(100, 5, utf8_decode("AT "), 0, 1, 'L');

        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(20, 5, utf8_decode("SEXE :"), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(10, 5, utf8_decode($student->getSex()->getSex()), 0, 0, 'L');
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(20, 5, 'Classe : ', 0, 0, 'R');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(15, 5, utf8_decode($student->getClassroom()->getClassroom()), 0, 0, 'L');
        
        $pdf->SetFont('Arial', '', 11);
        $pdf->Cell(60, 5, utf8_decode('N° DE TELEPHOE DU PARENT : '), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(50, 5, utf8_decode($student->getTelephonePere() ? $student->getTelephonePere() : "//"), 0, 1, 'L');

        $pdf->SetFont('Arial', 'I', 11);
        $pdf->Cell(33, 5, utf8_decode("SEX : "), 0, 0, 'L');
        $pdf->Cell(35, 5, utf8_decode("CLASS : "), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode("PARENTS PHONE NUMBER : "), 0, 1, 'L');
        $pdf->Ln(10);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(190, 5, utf8_decode("MOTIF DE PAIEMENT : "), 0, 1, 'C');
        $pdf->SetFont('Arial', 'BI', 11);
        $pdf->Cell(190, 5, utf8_decode("REASON OF PAIEMENT : "), 0, 1, 'C');
        $pdf->Ln(5);
        $pdf->Cell(30, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(40, 5, utf8_decode("FRAIS EXIGIBLES : "), 0, 0, 'L');
        $pdf->Cell(30, 5, utf8_decode(""), 1, 1, 'L');
        $pdf->Cell(30, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->SetFont('Arial', 'I', 11);
        $pdf->Cell(40, 5, utf8_decode("SCHOOL FEES : "), 0, 0, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->Cell(63, 5, utf8_decode("FRAIS D'EXAMENS OFFICIELS : "), 0, 0, 'L');
        $pdf->Cell(30, 5, utf8_decode(""), 1, 1, 'L');
        $pdf->Cell(30, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->SetFont('Arial', 'I', 11);
        $pdf->Cell(40, 5, utf8_decode("EXAMINATION FEES : "), 0, 0, 'L');
        $pdf->Ln(5);

        $pdf->SetFont('Arial', 'B', 11);
        $pdf->Cell(30, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->Cell(63, 5, utf8_decode("MONTANT A VERSER : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode(""), 1, 1, 'L');
        $pdf->Cell(30, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->SetFont('Arial', 'I', 11);
        $pdf->Cell(40, 5, utf8_decode("AMOUNT TO BE PAID : "), 0, 0, 'L');
        $pdf->Ln();

        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(0, $cellHeaderHeight, utf8_decode('Fait à '.$school->getPlace().', Le ____________________________'), 0, 0, 'R');
        $pdf->Ln($cellHeaderHeight*3);

        $pdf->Cell($numberWith + $fullNameWith, $cellHeaderHeight, "", 0, 0, 'C');

        $pdf->Cell($feesWith*2, $cellHeaderHeight, '', 0, 0, 'C');
        $pdf->Cell($feesWith*2, $cellHeaderHeight, utf8_decode("L'INTENDANT / THE BURSAR"), 0, 0, 'C');
        $pdf->Ln();

        return $pdf;
    }

    public function getHeaderStudentQuitus(PDF $pdf, School $school, Student $student, int $numberOfQuitus = 0): PDF
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, utf8_decode('QUITUS DE PAIEMENT DES FRAIS EXIGIBLES / FRAIS EXAMENS OFFICIELS'), 'LRT', 2, 'C');
        $pdf->SetFont('Arial', 'BI', 12);
        $pdf->Cell(0, 5, utf8_decode('SCHOOL / EXAMINATION FEES PAYMENT PASS / FRAIS EXAMENS OFFICIELS'), 'LR', 2, 'C');
        $pdf->SetFont('Arial', 'B', 12);

        $level = $student->getClassroom()->getLevel()->getLevel();
        switch ($level) {
            case 1:
                $pdf->Cell(0, 5, utf8_decode('NIVEAU / LEVEL : 6ème - N° : '.$numberOfQuitus), 'LBR', 2, 'C');
                break;

            case 2:
                $pdf->Cell(0, 5, utf8_decode('NIVEAU / LEVEL : 5ème - N° : '.$numberOfQuitus), 'LBR', 2, 'C');
                break;

            case 3:
                $pdf->Cell(0, 5, utf8_decode('NIVEAU / LEVEL : 4ème - N° : '.$numberOfQuitus), 'LBR', 2, 'C');
                break;

            case 4:
                $pdf->Cell(0, 5, utf8_decode('NIVEAU / LEVEL : 3ème - N° : '.$numberOfQuitus), 'LBR', 2, 'C');
                break;

            case 5:
                $pdf->Cell(0, 5, utf8_decode('NIVEAU / LEVEL : 2nde - N° : '.$numberOfQuitus), 'LBR', 2, 'C');
                break;

            case 6:
                $pdf->Cell(0, 5, utf8_decode('NIVEAU / LEVEL : 1ère - N° : '.$numberOfQuitus), 'LBR', 2, 'C');
                break;

            case 7:
                $pdf->Cell(0, 5, utf8_decode('NIVEAU / LEVEL : Tle - N° : '.$numberOfQuitus), 'LBR', 2, 'C');
                break;
            
            
        }

        return $pdf;
    }
}
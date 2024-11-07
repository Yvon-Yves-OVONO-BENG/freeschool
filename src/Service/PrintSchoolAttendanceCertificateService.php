<?php

namespace App\Service;

use App\Entity\EtatDepense;
use App\Entity\School;
use App\Entity\EtatFinance;
use App\Entity\SchoolYear;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Entity\Student;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;

class PrintSchoolAttendanceCertificateService 
{
    public function __construct(
        protected GeneralService $generalService, 
        protected RegistrationRepository $registrationRepository, 
        protected FeesRepository $feesRepository)
    {}


    /**
     * Imprime le certificat de scolarité
     *
     * @param Student $student
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return PDF
     */
    public function print(Student $student, School $school, SchoolYear $schoolYear): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $student);

        $pdf->Ln(10);
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(0, 5, utf8_decode('Je soussigné(e) _ _ _ _ _ __ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 1, 'L');
        
        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(0, 5, utf8_decode('I undersigned'), 0, 1, 'L');
        
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(0, 5, utf8_decode("Proviseur du ".$school->getFrenchName().", certifie que"), 0, 1, 'L');

        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(0, 5, utf8_decode("Principal of".$school->getEnglishName().", certify that"), 0, 1, 'L');
        $pdf->Ln();

        
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(0, 5, utf8_decode($student->getFullName()), 0, 1, 'L');
        $pdf->Ln();
        
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode("Né(e) le :"), 0, 0, 'L');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(25, 5, utf8_decode(date_format($student->getBirthday(), 'd-m-Y' )), 0, 0, 'L');
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(10, 5, utf8_decode("à"), 0, 0, 'L');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(25, 5, utf8_decode($student->getBirthplace() ), 0, 1, 'L');

        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('Born on'), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('at'), 0, 1, 'L');
        $pdf->Ln();

        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('Fils(Fille) de '), 0, 0, 'L');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(45, 5, utf8_decode($student->getFatherName() ? $student->getFatherName() : "PND"), 0, 1, 'L');
        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('Son(Daugther) of '), 0, 1, 'L');
        $pdf->Ln();

        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('Et de '), 0, 0, 'L');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(45, 5, utf8_decode($student->getMotherName() ? $student->getMotherName() : "PND"), 0, 1, 'L');
        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('And of '), 0, 1, 'L');
        $pdf->Ln();

        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(125, 5, utf8_decode('Est(a été) régulièrement inscrit(e) dans mon établissement en classe de '), 0, 0, 'L');

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(25, 5, utf8_decode($student->getClassroom()->getClassroom() ), 0, 1, 'L');

        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode('Is(was) a pupil in my school in  '), 0, 1, 'L');
        
        
        $pdf->Ln();

        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode("Pour l'année scolaire "), 0, 0, 'L');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(45, 5, utf8_decode($student->getSchoolYear()->getSchoolYear()), 0, 0, 'L');
        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(35, 5, utf8_decode("N° matricule "), 0, 0, 'L');
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(45, 5, utf8_decode($student->getRegistrationNumber()), 0, 1, 'L');
       
        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(90, 5, utf8_decode('For the academic year'), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('Register number'), 0, 1, 'L');
        $pdf->Ln();

        $pdf->SetFont('Times', '', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(90, 5, utf8_decode('En foi de quoi ce certificat lui est établie et délivré pour servir et valoir ce que de droit./-'), 0, 1, 'L');

        $pdf->SetFont('Times', 'I', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(45, 5, utf8_decode('This attendance certificate is issued to serve where ever need be./-'), 0, 1, 'L');
        $pdf->Ln();
        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'UB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(110, 5, utf8_decode("Le Proviseur"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IUB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(111, 5, utf8_decode("The Principal"), 0, 1, 'R');


        //////////////////////////////

        return $pdf;
    }

    public function getHeader(PDF $pdf, Student $student): PDF
    {
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode('CERTIFICAT DE SCOLARITE'), 0, 2, 'C');
        $pdf->SetFont('Times', 'BI', 14);
        $pdf->Cell(0, 5, utf8_decode('SCHOOL ATTENDANCE CERTIFICATE'), 0, 2, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(190, 5, utf8_decode("N° _ _ _ _ / ".$student->getSchoolYear()->getSchoolYear()), 0, 0, 'C');
        $pdf->Ln();

        return $pdf;
    }


}
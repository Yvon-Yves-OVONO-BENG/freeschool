<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\Student;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Service\ChiffreEnLettreService;
use App\Entity\ReportElements\Pagination;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PaymentReceiptService 
{
    

    public function __construct(
        protected RequestStack $request,
        protected StrService $strService, 
        protected GeneralService $generalService, 
        protected FeesRepository $feesRepository, 
        protected  RegistrationRepository $registrationRepository, 
        )
    {}


    /**
     * Imprime les états des frais académiques de chaque classe
     *
     * @param array $classrooms
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    
    public function printPaymentReceiptService(array $registrationHistories, School $school, SchoolYear $schoolYear, Student $student): Pagination 
    {
        $fontSize = 11;
        $cellHeaderHeight1 = 4;
        $cellHeaderHeight = 6.52;

        $numberWith = 10;
        $fullNameWith = 60;
        $feesWith = 30;

        $totalWith = 22;

        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);

        $classroom = $student->getClassroom();
        
        $apeeFees = $fees->getApeeFees2();
        $computerFees = $fees->getComputerFees2();

        $medicalBookletFees = $fees->getMedicalBookletFees();
        $cleanSchoolFees = $fees->getCleanSchoolFees();
        $photoFees = $fees->getPhotoFees();


        $pdf =  new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);
        
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf->Ln();
        $pdf->Ln();

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 20);
            $pdf->Cell(0, 5, utf8_decode('REÇU DE VERSEMENTS DES FRAIS APEE '), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 18);
            $pdf->Ln();
            $pdf->SetX(30);
            $pdf->Cell(100, 5, utf8_decode('Reçu de  : '.$student->getFullName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Classe : '.utf8_decode($student->getClassroom()->getClassroom()), 0, 2, 'R');

        }else
        {
            $pdf->SetFont('Times', 'B', 20);
            $pdf->Cell(0, 5, utf8_decode('RECEIPT FOR PAYMENT PTA FEES'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 18);
            $pdf->Ln();
            $pdf->SetX(30);
            $pdf->Cell(100, 5, utf8_decode('Receipt of  : '.$student->getFullName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Class : '.utf8_decode($student->getClassroom()->getClassroom()), 0, 2, 'R');
        }

        if(empty($registrationHistories))
        {
            $pdf->Ln($cellHeaderHeight*2);
            $pdf->SetFont('Times', 'B', 20);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell(0, $cellHeaderHeight, utf8_decode('Aucun versement effectué en date'), 0, 0, 'C');
            }else
            {
                $pdf->Cell(0, $cellHeaderHeight, utf8_decode('No payment made on date'), 0, 0, 'C');
            }
            $pdf->Ln($cellHeaderHeight*3);

            return $pdf;
        };

        $totalApeeFees = 0;
        $totalComputerFees = 0;
        $totalMedicalBookletFees = 0;
        $totalCleanSchoolFees = 0;
        $totalPhotoFees = 0;

        foreach ($registrationHistories as $registrationHistory) 
        {
            $totalApeeFees += $registrationHistory->getApeeFees();
            $totalComputerFees += $registrationHistory->getComputerFees();
            $totalMedicalBookletFees += $registrationHistory->getMedicalBookletFees();
            $totalCleanSchoolFees += $registrationHistory->getCleanSchoolFees();
            $totalPhotoFees += $registrationHistory->getPhotoFees();
        }

        $pdf->Ln();
        $pdf->SetFont('Times', 'B', 14);
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(135, 10, utf8_decode('AVANCE'), 1, 0, 'C', true);
            $pdf->Cell(135, 10, utf8_decode('RESTE'), 1, 1, 'C', true);
        }else
        {
            $pdf->Cell(135, 10, utf8_decode('ADVANCE'), 1, 0, 'C', true);
            $pdf->Cell(135, 10, utf8_decode('REST'), 1, 1, 'C', true);
        }

        $avance  = $totalApeeFees + $totalComputerFees + $totalMedicalBookletFees + $totalCleanSchoolFees + $totalPhotoFees ;

        $feess = $apeeFees +$computerFees + $medicalBookletFees + $cleanSchoolFees + $photoFees;

        $reste = $feess - $avance;

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(30, 8, utf8_decode('En chiffre :'), 'L', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($avance.' FCFA'), 0, 0, 'C');
            $pdf->Cell(30, 8, utf8_decode('En chiffre :'), 'L', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($reste.' FCFA'), 'R', 1, 'C');

            $pdf->Cell(30, 8, utf8_decode('En lettre :'), 'LB', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($this->strService->strtoupper((new ChiffreEnLettreService($avance, 'Francs CFA'))->convert('fr-FR'))), 'RB', 0, 'C');

            $pdf->Cell(30, 8, utf8_decode('En lettre :'), 'LB', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($this->strService->strtoupper((new ChiffreEnLettreService($reste, 'Francs CFA'))->convert('fr-FR'))), 'RB', 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(135, 8, utf8_decode('SOMME A PAYER'), 'LT', 0, 'C', true);
            $pdf->Cell(135, 8, utf8_decode('25 000 FCFA'), 1, 1, 'C', true);
            $pdf->Cell(135, 10, utf8_decode('OBSERVATION'), 'LBT', 0, 'C', true);
        
            if($feess - $avance == 0)
            {
                $pdf->Cell(135, 10, utf8_decode('SOLDE'), 1, 0, 'C', true);
            }else 
            {
                $pdf->Cell(135, 10, utf8_decode('NON SOLDE'), 1, 1, 'C', true);
            }

            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(270, $cellHeaderHeight, utf8_decode('Fait à '.$school->getPlace().', Le ____________________________'), 0, 0, 'R');
            $pdf->Ln($cellHeaderHeight*3);

            $pdf->Cell($numberWith + $fullNameWith, $cellHeaderHeight, "", 0, 0, 'C');

            $pdf->Cell(($feesWith*2)+30, $cellHeaderHeight, '', 0, 0, 'C');
            $pdf->Cell($feesWith*2, $cellHeaderHeight, "L'INTENDANTE(E)", 0, 0, 'C');
        }else
        {
            $pdf->Cell(30, 8, utf8_decode('In numbers :'), 'L', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($avance.' FCFA'), 0, 0, 'C');
            $pdf->Cell(30, 8, utf8_decode('In numbers :'), 'L', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($reste.' FCFA'), 'R', 1, 'C');

            $pdf->Cell(30, 8, utf8_decode('In letters :'), 'LB', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($this->strService->strtoupper((new ChiffreEnLettreService($avance, 'Francs CFA'))->convert('fr-FR'))), 'RB', 0, 'C');

            $pdf->Cell(30, 8, utf8_decode('In letters :'), 'LB', 0, 'L');
            $pdf->Cell(105, 8, utf8_decode($this->strService->strtoupper((new ChiffreEnLettreService($reste, 'Francs CFA'))->convert('fr-FR'))), 'RB', 1, 'C');
            $pdf->Ln(3);
            $pdf->Cell(135, 8, utf8_decode('AMOUNT TO BE PAID'), 'LT', 0, 'C', true);
            $pdf->Cell(135, 8, utf8_decode('25 000 FCFA'), 1, 1, 'C', true);
            $pdf->Cell(135, 10, utf8_decode('OBSERVATION'), 'LBT', 0, 'C', true);
        
            if($feess - $avance == 0)
            {
                $pdf->Cell(135, 10, utf8_decode('SOLD OUT'), 1, 0, 'C', true);
            }else 
            {
                $pdf->Cell(135, 10, utf8_decode('NOT SOLD'), 1, 1, 'C', true);
            }

            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(270, $cellHeaderHeight, utf8_decode('Done at'.$school->getPlace().', On ____________________________'), 0, 0, 'R');
            $pdf->Ln($cellHeaderHeight*3);

            $pdf->Cell($numberWith + $fullNameWith, $cellHeaderHeight, "", 0, 0, 'C');

            $pdf->Cell(($feesWith*2)+30, $cellHeaderHeight, '', 0, 0, 'C');
            $pdf->Cell($feesWith*2, $cellHeaderHeight, "The Bursar", 0, 0, 'C');
        }
        $pdf->Ln();

        return $pdf;
    }
}
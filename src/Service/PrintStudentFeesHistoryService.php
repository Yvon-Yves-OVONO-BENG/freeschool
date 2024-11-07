<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\Student;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintStudentFeesHistoryService 
{

    public function __construct(
        protected RequestStack $request,
        protected GeneralService $generalService, 
        protected FeesRepository $feesRepository, 
        protected RegistrationRepository $registrationRepository, 
        )
    {}


    /**
     * Imprime les états des frais académiques de chaque classe
     *
     * @param array $classrooms
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return PDF
     */
    public function getHeaderStudentListFeesHistory(PDF $pdf, School $school, Student $student): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('HISTORIQUE DES VERSEMENTS DES FRAIS APEE'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, utf8_decode('Elève : '.$student->getFullName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Classe : '.utf8_decode($student->getClassroom()->getClassroom()), 0, 2, 'R');
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('FEES PAYMENT HISTORY'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, utf8_decode('Student : '.$student->getFullName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Class : '.utf8_decode($student->getClassroom()->getClassroom()), 0, 2, 'R');
            $pdf->Ln();
        }

        return $pdf;
    }
    
    public function printStudentRegistrationHistory(array $registrationHistories, School $school, SchoolYear $schoolYear, Student $student): PDF 
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;
        $cellHeaderHeight = 6.52;

        $numberWith = 10;
        $fullNameWith = 60;
        $feesWith = 30;

        $totalWith = 22;

        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);

        $classroom = $student->getClassroom();

        if($student->getClassroom()->getLevel()->getCycle()->getCycle() == 1)
        {
            $apeeFees = $fees->getApeeFees1();
            $computerFees = $fees->getComputerFees1();

            if($student->getClassroom()->getLevel()->getLevel() == 4)
            {
                $stampFees = $fees->getStampFees3eme();
            }

        }else
        {
            $apeeFees = $fees->getApeeFees2();
            $computerFees = $fees->getComputerFees2();

            if($student->getClassroom()->getLevel()->getLevel() == 6 )
            {
                $stampFees = $fees->getStampFees1ere();
            }elseif($student->getClassroom()->getLevel()->getLevel() == 7 )
            {
                $stampFees = $fees->getStampFeesTle();
            }
        }

        $medicalBookletFees = $fees->getMedicalBookletFees();
        $cleanSchoolFees = $fees->getCleanSchoolFees();
        $photoFees = $fees->getPhotoFees();

        $pdf =  new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);
        
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
        
        // Entête de la liste
        $pdf = $this->getHeaderStudentListFeesHistory($pdf, $school, $student);
        
        
        $pdf = $this->tableHistoryHedaer($pdf, $totalWith, $numberWith, $cellHeaderHeight, $fullNameWith, $feesWith, $apeeFees, $computerFees, $medicalBookletFees, $cleanSchoolFees, $photoFees, $stampFees = 0, $classroom);
        
        
        if(empty($registrationHistories))
        {
            $pdf->Ln($cellHeaderHeight*2);
            $pdf->SetFont('Times', 'B', 20);

            $mySession =  $this->request->getSession();

            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell(0, $cellHeaderHeight, utf8_decode('Aucun versement effectué en date'), 0, 0, 'C');
            }else
            {
                $pdf->Cell(0, $cellHeaderHeight, utf8_decode('No payment made to date'), 0, 0, 'C');
            }
            $pdf->Ln($cellHeaderHeight*3);

            return $pdf;
        };
        $totalApeeFees = 0;
        $totalComputerFees = 0;
        $totalMedicalBookletFees = 0;
        $totalCleanSchoolFees = 0;
        $totalPhotoFees = 0;
        $totalStampFees = 0;

        $number = 0;
        foreach ($registrationHistories as $registrationHistory) 
        {
            $number++;
            $pdf->SetFont('Times', '', 10);
            $pdf->SetX(10);
            $pdf->Cell($numberWith, $cellHeaderHeight, $number, 1, 0, 'C');
            $pdf->Cell($fullNameWith-25, $cellHeaderHeight, utf8_decode($registrationHistory->getCreatedBy()->getFullName() ), 1, 0, 'C');

            $mySession =  $this->request->getSession();

            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($fullNameWith-25, $cellHeaderHeight, utf8_decode($registrationHistory->getCreatedAt() ? date_format($registrationHistory->getCreatedAt() , 'd-m-Y à H:i:s'): "//"), 1, 0, 'C');
            }else
            {
                $pdf->Cell($fullNameWith-25, $cellHeaderHeight, utf8_decode($registrationHistory->getCreatedAt() ? date_format($registrationHistory->getCreatedAt() , 'd-m-Y at H:i:s'): "//"), 1, 0, 'C');
            }

            $pdf->Cell($feesWith-12, $cellHeaderHeight, number_format($registrationHistory->getApeeFees(), 0, '.', ' '), 1, 0, 'C');
            $pdf->Cell($feesWith-12, $cellHeaderHeight, number_format($registrationHistory->getComputerFees(), 0, '.', ' '), 1, 0, 'C');
            $pdf->Cell($feesWith-12, $cellHeaderHeight, number_format($registrationHistory->getMedicalBookletFees(), 0, '.', ' '), 1, 0, 'C');
            $pdf->Cell($feesWith-12, $cellHeaderHeight, number_format($registrationHistory->getCleanSchoolFees(), 0, '.', ' '), 1, 0, 'C');
            $pdf->Cell($feesWith-12, $cellHeaderHeight, number_format($registrationHistory->getPhotoFees(), 0, '.', ' '), 1, 0, 'C');

            $apeeFee = $registrationHistory->getApeeFees() ;
            $computerFee = $registrationHistory->getComputerFees();
            $medicalBookletFee = $registrationHistory->getMedicalBookletFees();
            $cleanSchoolFee = $registrationHistory->getCleanSchoolFees();
            $photoFee = $registrationHistory->getPhotoFees();

            $totalApeeFees += $registrationHistory->getApeeFees() ;
            $totalComputerFees += $registrationHistory->getComputerFees();
            $totalMedicalBookletFees += $registrationHistory->getMedicalBookletFees();
            $totalCleanSchoolFees += $registrationHistory->getCleanSchoolFees();
            $totalPhotoFees += $registrationHistory->getPhotoFees();

            $pdf->Cell($totalWith, $cellHeaderHeight, number_format($apeeFee + $computerFee + $medicalBookletFee + $cleanSchoolFee + $photoFee , 0, '.', ' '), 1, 0, 'C');
            
            $pdf->Ln();

        }
        

        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetX(10);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($numberWith + $fullNameWith+10, $cellHeaderHeight, 'Totaux des versements', 1, 0, 'C', true);
        }else
        {
            $pdf->Cell($numberWith + $fullNameWith+10, $cellHeaderHeight, 'Payment Totals', 1, 0, 'C', true);
        }
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalApeeFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalComputerFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalMedicalBookletFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalCleanSchoolFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalPhotoFees, 0, '.', ' '), 1, 0, 'C', true);

        $pdf->Cell($totalWith, $cellHeaderHeight,  number_format($totalApeeFees + $totalComputerFees + $totalMedicalBookletFees + $totalCleanSchoolFees + $totalPhotoFees , 0, '.', ' '), 1, 0, 'C', true);
        
        
        $pdf->Ln();
        $pdf->SetX(10);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($numberWith + $fullNameWith+10, $cellHeaderHeight, utf8_decode('Impayés'), 1, 0, 'C', true);
        }else
        {
            $pdf->Cell($numberWith + $fullNameWith+10, $cellHeaderHeight, utf8_decode('Unpaid'), 1, 0, 'C', true);

        }
       
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($apeeFees - $totalApeeFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($computerFees - $totalComputerFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($medicalBookletFees - $totalMedicalBookletFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($cleanSchoolFees - $totalCleanSchoolFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($photoFees - $totalPhotoFees, 0, '.', ' '), 1, 0, 'C', true);

        $pdf->Cell($totalWith, $cellHeaderHeight,  number_format($apeeFees + $computerFees + $medicalBookletFees + $cleanSchoolFees + $photoFees - $totalApeeFees - $totalComputerFees - $totalMedicalBookletFees - $totalCleanSchoolFees - $totalPhotoFees , 0, '.', ' '), 1, 0, 'C', true);
      
        $pdf->Ln();

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($apeeFees + $computerFees + $medicalBookletFees + $cleanSchoolFees + $photoFees + $stampFees - $totalApeeFees - $totalComputerFees - $totalMedicalBookletFees - $totalCleanSchoolFees - $totalPhotoFees - $totalStampFees == 0)
            {
                $etatGlobal = 'Soldé';
            }else 
            {
                $etatGlobal = 'Non soldé';
            }
        }else
        {
            if($apeeFees + $computerFees + $medicalBookletFees + $cleanSchoolFees + $photoFees + $stampFees - $totalApeeFees - $totalComputerFees - $totalMedicalBookletFees - $totalCleanSchoolFees - $totalPhotoFees - $totalStampFees == 0)
            {
                $etatGlobal = 'Pay';
            }else 
            {
                $etatGlobal = 'Unpay';
            }
        }


        $pdf->SetX(10);
        $pdf->Cell($numberWith + $fullNameWith+10, $cellHeaderHeight, 'Observation', 1, 0, 'C', true);
        
        $pdf->Cell(($feesWith*4)-8, $cellHeaderHeight, utf8_decode($etatGlobal), 1, 0, 'C', true);
        $pdf->Ln($cellHeaderHeight*3);
        

        

        $pdf->Ln();
        $pdf->Ln();

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(190, $cellHeaderHeight, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _ _'), 0, 0, 'R');
            $pdf->Ln($cellHeaderHeight*3);

            $pdf->Cell($numberWith + $fullNameWith, $cellHeaderHeight, "", 0, 0, 'C');

            $pdf->Cell(($feesWith*2)-7, $cellHeaderHeight, '', 0, 0, 'C');
            $pdf->Cell(190, $cellHeaderHeight, "L'INTENDANTE(E)", 0, 0, 'L');
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(190, $cellHeaderHeight, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _ _'), 0, 0, 'R');
            $pdf->Ln($cellHeaderHeight*3);

            $pdf->Cell($numberWith + $fullNameWith, $cellHeaderHeight, "", 0, 0, 'C');

            $pdf->Cell(($feesWith*2)-7, $cellHeaderHeight, '', 0, 0, 'C');
            $pdf->Cell(190, $cellHeaderHeight, "The Bursar", 0, 0, 'L');
            $pdf->Ln();
        }

        return $pdf;
    }

    public function tableHistoryHedaer(PDF $pdf, int $totalWith, int $numberWith, int $cellHeaderHeight, int $fullNameWith, int $feesWith,  int $apeeFees, int $computerFees, int $medicalBookletFees, int $cleanSchoolFees, int $photoFees, int $stampFees, Classroom $classroom): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->SetX(10);
            $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith-25, $cellHeaderHeight*2, utf8_decode('Par'), 1, 0, 'C', true);
            $pdf->Cell($fullNameWith-25, $cellHeaderHeight*2, utf8_decode('Date de versement'), 1, 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('APEE'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Informat'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Livret Med'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);
            $pdf->Cell($totalWith, $cellHeaderHeight*2,  utf8_decode('Montant à payer'), 1, 1, 'C', true);
        }else
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->SetX(10);
            $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith-25, $cellHeaderHeight*2, utf8_decode('By'), 1, 0, 'C', true);
            $pdf->Cell($fullNameWith-25, $cellHeaderHeight*2, utf8_decode('Payment date'), 1, 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('PTA'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('IT'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Med. Book'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);
            $pdf->Cell($totalWith, $cellHeaderHeight*2,  utf8_decode('Amount to be paid'), 1, 1, 'C', true);
        }

        $pdf->SetY($pdf->GetY()-$cellHeaderHeight);
        $pdf->SetX(45);
        $pdf->Cell($numberWith + $fullNameWith-25);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $apeeFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $computerFees.' F CFA', 'LBR', 0, 'C', true);

        $pdf->Cell($feesWith-12, $cellHeaderHeight, $medicalBookletFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $cleanSchoolFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $photoFees.' F CFA', 'LBR', 0, 'C', true);


        $pdf->Ln();

        return $pdf;
    }

    


}
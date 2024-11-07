<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\Student;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Service\GeneralService;
use App\Entity\ReportElements\Pagination;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;

class RegistrationService 
{
    public function __construct(
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
     * @return Pagination
     */
    public function printSchoolFeesStatement(array $classrooms, School $school, SchoolYear $schoolYear): Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;
        $cellHeaderHeight = 6;

        $numberWith = 8;
        $fullNameWith = 70;
        $feesWith = 22;
        $totalWith = 22;

        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);


        $pdf = new Pagination();

        foreach ($classrooms as $classroom) 
        {
            $apeeFeesAdvanceTotal = 0;
            $apeeRestTotal = 0;

            $computerFeesAdvanceTotal = 0;
            $computerFeesRestTotal = 0;

            $medicalBookletFeesAdvanceTotal = 0;
            $medicalBookletFeesRestTotal = 0;

            $cleanSchoolFeesAdvanceTotal = 0;
            $cleanSchoolFeesRestTotal = 0;

            $photoFeesAdvanceTotal = 0;
            $photoFeesRestTotal = 0;

            $stampFeesAdvanceTotal = 0;
            $stampFeesRestTotal = 0;

            if($classroom->getLevel()->getCycle()->getCycle() == 1)
            {
                $apeeFees = $fees->getApeeFees1();
                $computerFees = $fees->getComputerFees1();

                if ($classroom->getLevel()->getLevel() == 4 ) 
                {
                    $stampFees = $fees->getStampFees3eme();
                }
            }
            else
            {
                $apeeFees = $fees->getApeeFees2();
                $computerFees = $fees->getComputerFees2();

                if ($classroom->getLevel()->getLevel() == 6) 
                {
                    $stampFees = $fees->getStampFees1ere();

                }
                elseif ($classroom->getLevel()->getLevel() == 7) 
                {
                    $stampFees = $fees->getStampFeesTle();
                }
            }

            $medicalBookletFees = $fees->getMedicalBookletFees();
            $cleanSchoolFees = $fees->getCleanSchoolFees();
            $photoFees = $fees->getPhotoFees();

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);
            
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
            
            // Entête de la liste
            $pdf = $this->getHeaderStudentList($pdf, $school, $classroom);
    
            // entête du tableau
            if ($classroom->getLevel()->getLevel() == 4 || $classroom->getLevel()->getLevel() == 6 || $classroom->getLevel()->getLevel() == 7 ) 
            {
                $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight, $numberWith, $fullNameWith, $feesWith, $totalWith, $apeeFees, $computerFees, $medicalBookletFees, $cleanSchoolFees, $photoFees,$stampFees, $classroom);
            } 
            else 
            {
                $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight, $numberWith, $fullNameWith, $feesWith, $totalWith, $apeeFees, $computerFees, $medicalBookletFees, $cleanSchoolFees, $photoFees, $stampFees = 0, $classroom);
            }
            
            $students = $classroom->getStudents();
            $numberOfStudents = count($students);

            $pdf->SetFont('Times', '', 10);
            $number = 0;
            foreach ($students as $student) 
            {
                $registration = $student->getRegistration();

                if(!is_null($registration))
                {
                    $apeeFeesAdvance = $registration->getApeeFees();
                    $apeeRest = $apeeFees - $apeeFeesAdvance;

                    $computerFeesAdvance = $registration->getComputerFees();
                    $computerFeesRest = $computerFees - $computerFeesAdvance;

                    //////////
                    $medicalBookletFeesAdvance = $registration->getMedicalBookletFees();
                    $medicalBookletFeesRest = $medicalBookletFees - $medicalBookletFeesAdvance;

                    $cleanSchoolFeesAdvance = $registration->getCleanSchoolFees();
                    $cleanSchoolFeesRest = $cleanSchoolFees - $cleanSchoolFeesAdvance;

                    $photoFeesAdvance = $registration->getPhotoFees();
                    $photoFeesRest = $photoFees - $photoFeesAdvance;

                    $stampFeesAdvance = $registration->getStampFees();
                    $stampFeesRest = $stampFees - $stampFeesAdvance;

                    //////
    
                    $apeeFeesAdvanceTotal += $apeeFeesAdvance;
                    $apeeRestTotal += $apeeRest;
    
                    $computerFeesAdvanceTotal += $computerFeesAdvance;
                    $computerFeesRestTotal += $computerFeesRest;

                    ////
                    $medicalBookletFeesAdvanceTotal += $medicalBookletFeesAdvance;
                    $medicalBookletFeesRestTotal += $medicalBookletFeesRest;
    
                    $cleanSchoolFeesAdvanceTotal += $cleanSchoolFeesAdvance;
                    $cleanSchoolFeesRestTotal += $cleanSchoolFeesRest;
    
                    $photoFeesAdvanceTotal += $photoFeesAdvance;
                    $photoFeesRestTotal += $photoFeesRest;

                    $stampFeesAdvanceTotal += $stampFeesAdvance;
                    $stampFeesRestTotal += $stampFeesRest;

                }else 
                {
                    $apeeFeesAdvance = 0;
                    $apeeRest = $apeeFees - $apeeFeesAdvance;

                    $computerFeesAdvance = 0;
                    $computerFeesRest = $computerFees - $computerFeesAdvance;
                    //////
                    $medicalBookletFeesAdvance = 0;
                    $medicalBookletFeesRest = $medicalBookletFees - $medicalBookletFeesAdvance;

                    $cleanSchoolFeesAdvance = 0;
                    $cleanSchoolFeesRest = $cleanSchoolFees - $cleanSchoolFeesAdvance;

                    $photoFeesAdvance = 0;
                    $photoFeesRest = $photoFees - $photoFeesAdvance;

                    $stampFeesAdvance = 0;
                    $stampFeesRest = $stampFees - $stampFeesAdvance;

                    /////
    
                    $apeeFeesAdvanceTotal += $apeeFeesAdvance;
                    $apeeRestTotal += $apeeRest;
    
                    $computerFeesAdvanceTotal += $computerFeesAdvance;
                    $computerFeesRestTotal += $computerFeesRest;
                    ////

                    $medicalBookletFeesAdvanceTotal += $medicalBookletFeesAdvance;
                    $medicalBookletFeesRestTotal += $medicalBookletFeesRest;
    
                    $cleanSchoolFeesAdvanceTotal += $cleanSchoolFeesAdvance;
                    $cleanSchoolFeesRestTotal += $cleanSchoolFeesRest;
    
                    $photoFeesAdvanceTotal += $photoFeesAdvance;
                    $photoFeesRestTotal += $photoFeesRest;

                    $stampFeesAdvanceTotal += $stampFeesAdvance;
                    $stampFeesRestTotal += $stampFeesRest;

                } 

                $number++;
                $pdf->Cell($numberWith, $cellHeaderHeight, $number, 1, 0, 'C');
                $pdf->Cell($fullNameWith+10, $cellHeaderHeight, utf8_decode($student->getFullName()), 1, 0, 'L');

                $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeFeesAdvance, 0, '.', ' '), 1, 0, 'C');
                $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeRest, 0, '.', ' '), 1, 0, 'C');
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesAdvance, 0, '.', ' '), 1, 0, 'C');
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesRest, 0, '.', ' '), 1, 0, 'C');
                
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesAdvance, 0, '.', ' '), 1, 0, 'C');
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesRest, 0, '.', ' '), 1, 0, 'C');

                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesAdvance, 0, '.', ' '), 1, 0, 'C');
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesRest, 0, '.', ' '), 1, 0, 'C');

                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesAdvance, 0, '.', ' '), 1, 0, 'C');
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesRest, 0, '.', ' '), 1, 0, 'C');

                if ($classroom->getLevel()->getLevel() == 4 || $classroom->getLevel()->getLevel() == 6 || $classroom->getLevel()->getLevel() == 7) 
                {
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($stampFeesAdvance, 0, '.', ' '), 1, 0, 'C');
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($stampFeesRest, 0, '.', ' '), 1, 0, 'C');

                    $pdf->Cell($totalWith+5, $cellHeaderHeight,  number_format($apeeRest + $computerFeesRest + $medicalBookletFeesRest + $cleanSchoolFeesRest + $photoFeesRest + $stampFeesRest, 0, '.', ' '), 1, 1, 'C');
                }else 
                {
                    $pdf->Cell($totalWith+5, $cellHeaderHeight,  number_format($apeeRest + $computerFeesRest + $medicalBookletFeesRest + $cleanSchoolFeesRest + $photoFeesRest, 0, '.', ' '), 1, 1, 'C');
                }

                if($number % 17 == 0 && $numberOfStudents > 17)
                {
                    // On insère une page
                    $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);
                    
                    // Administrative Header
                    $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
                    
                    // Entête de la liste
                    $pdf = $this->getHeaderStudentList($pdf, $school, $classroom);
            
                    // entête du tableau
                    $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight, $numberWith, $fullNameWith, $feesWith, $totalWith, $apeeFees, $computerFees, $medicalBookletFees, $cleanSchoolFees, $photoFees, $stampFees, $classroom);
                }

            }

            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell($fullNameWith + $numberWith+10, $cellHeaderHeight, utf8_decode('Totaux'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeRestTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);
            
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);
            
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);

            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);


            if ($classroom->getLevel()->getLevel() == 4 || $classroom->getLevel()->getLevel() == 6 || $classroom->getLevel()->getLevel() == 7) 
            {
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($stampFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
                $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($stampFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);

                $pdf->Cell($totalWith+5, $cellHeaderHeight,  number_format($apeeRestTotal + $computerFeesRestTotal + $medicalBookletFeesRestTotal + $cleanSchoolFeesRestTotal + $photoFeesRestTotal + $stampFeesRestTotal, 0, '.', ' '), 1, 1, 'C', true);
            }else 
            {
                $pdf->Cell($totalWith+5, $cellHeaderHeight,  number_format($apeeRestTotal + $computerFeesRestTotal + $medicalBookletFeesRestTotal + $cleanSchoolFeesRestTotal + $photoFeesRestTotal, 0, '.', ' '), 1, 1, 'C', true);
            }

            
            
            
        }

        return $pdf;
    }


    public function getHeaderStudentList(Pagination $pdf, School $school, Classroom $classroom): Pagination
    {
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode('ETAT DES PAIEMENTS DES FRAIS DE SCOLARITE'), 0, 2, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(100, 5, utf8_decode(""), 0, 0, 'L');
        // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'L');
        $pdf->Cell(90, 5, 'Classe : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
        $pdf->Ln();

        return $pdf;
    }

    public function getHeaderStudentListFeesHistory(Pagination $pdf, School $school, Student $student): Pagination
    {
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode('REÇU DES VERSEMENTS DES FRAIS DE SCOLARITE'), 0, 2, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(100, 5, utf8_decode('Elève : '.$student->getFullName()), 0, 0, 'L');
        $pdf->Cell(90, 5, 'Classe : '.utf8_decode($student->getClassroom()->getClassroom()), 0, 2, 'R');
        $pdf->Ln();

        return $pdf;
    }


    public function getHeaderStudentQuitus(Pagination $pdf, School $school, Student $student, int $numberOfQuitus): Pagination
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

    public function getTableHeaderStudentList(Pagination $pdf, int $cellHeaderHeight, int $numberWith, int $fullNameWith, int $feesWith, int $totalWith, int $apeeFees, int $computerFees, int $medicalBookletFees, int $cleanSchoolFees, int $photoFees, int $stampFees, Classroom $classroom): Pagination
    {
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
        $pdf->Cell($fullNameWith+10, $cellHeaderHeight*2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
        $pdf->Cell($feesWith+10, $cellHeaderHeight, utf8_decode('APEE'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Informatique'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Livret Med.'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);

        if ($classroom->getLevel()->getLevel() == 4 || $classroom->getLevel()->getLevel() == 6 || $classroom->getLevel()->getLevel() == 7) 
        {
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Timbre'), 'LTR', 0, 'C', true);
        }
        $pdf->Cell($totalWith+5, $cellHeaderHeight*2,  utf8_decode('Montant à payer'), 1, 0, 'C', true);
        $pdf->Ln();

        $pdf->SetY($pdf->GetY()-$cellHeaderHeight);

        $pdf->Cell($numberWith + $fullNameWith+10);
        $pdf->Cell($feesWith+10, $cellHeaderHeight/2, $apeeFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $computerFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $medicalBookletFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $cleanSchoolFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $photoFees.' F CFA', 'LBR', 0, 'C', true);
        
        if ($classroom->getLevel()->getLevel() == 4 || $classroom->getLevel()->getLevel() == 6 || $classroom->getLevel()->getLevel() == 7) 
        {
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $stampFees.' F CFA', 'LBR', 0, 'C', true);
        }


        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 8);
        $pdf->Cell($numberWith + $fullNameWith+10);
        $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
        $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);


        if ($classroom->getLevel()->getLevel() == 4 || $classroom->getLevel()->getLevel() == 6 || $classroom->getLevel()->getLevel() == 7) 
        {
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
        }

        $pdf->Ln();
        $pdf->SetFont('Times', '', 10);
        return $pdf;
    }
    
    public function printStudentRegistrationHistory(array $registrationHistories, School $school, SchoolYear $schoolYear, Student $student): Pagination 
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

        $pdf =  new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
        
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
        
        // Entête de la liste
        $pdf = $this->getHeaderStudentListFeesHistory($pdf, $school, $student);
        
        
        $pdf = $this->tableHistoryHedaer($pdf, $totalWith, $numberWith, $cellHeaderHeight, $fullNameWith, $feesWith, $apeeFees, $computerFees, $medicalBookletFees, $cleanSchoolFees, $photoFees, $stampFees = 0, $classroom);
        
        
        if(empty($registrationHistories))
        {
            $pdf->Ln($cellHeaderHeight*2);
            $pdf->SetFont('Times', 'B', 20);
            $pdf->Cell(0, $cellHeaderHeight, utf8_decode('Aucun versement effectué en date'), 0, 0, 'C');
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
            $pdf->SetX(30);
            $pdf->Cell($numberWith, $cellHeaderHeight, $number, 1, 0, 'C');
            $pdf->Cell($fullNameWith-25, $cellHeaderHeight, utf8_decode($registrationHistory->getCreatedAt() ? date_format($registrationHistory->getCreatedAt() , 'd-m-Y à H:i:s'): "//"), 1, 0, 'C');
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
        $pdf->SetX(30);
        $pdf->Cell($numberWith + $fullNameWith-25, $cellHeaderHeight, 'Totaux des versements', 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalApeeFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalComputerFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalMedicalBookletFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalCleanSchoolFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($totalPhotoFees, 0, '.', ' '), 1, 0, 'C', true);

        $pdf->Cell($totalWith, $cellHeaderHeight,  number_format($totalApeeFees + $totalComputerFees + $totalMedicalBookletFees + $totalCleanSchoolFees + $totalPhotoFees , 0, '.', ' '), 1, 0, 'C', true);
        
        
        $pdf->Ln();
        $pdf->SetX(30);
        $pdf->Cell($numberWith + $fullNameWith-25, $cellHeaderHeight, utf8_decode('Impayés'), 1, 0, 'C', true);
       
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($apeeFees - $totalApeeFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($computerFees - $totalComputerFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($medicalBookletFees - $totalMedicalBookletFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($cleanSchoolFees - $totalCleanSchoolFees, 0, '.', ' '), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight,  number_format($photoFees - $totalPhotoFees, 0, '.', ' '), 1, 0, 'C', true);

        $pdf->Cell($totalWith, $cellHeaderHeight,  number_format($apeeFees + $computerFees + $medicalBookletFees + $cleanSchoolFees + $photoFees - $totalApeeFees - $totalComputerFees - $totalMedicalBookletFees - $totalCleanSchoolFees - $totalPhotoFees , 0, '.', ' '), 1, 0, 'C', true);
      
        $pdf->Ln();

        if($apeeFees + $computerFees + $medicalBookletFees + $cleanSchoolFees + $photoFees + $stampFees - $totalApeeFees - $totalComputerFees - $totalMedicalBookletFees - $totalCleanSchoolFees - $totalPhotoFees - $totalStampFees == 0)
        {
            $etatGlobal = 'Soldé';
        }else 
        {
            $etatGlobal = 'Non soldé';
        }
        $pdf->SetX(30);
        $pdf->Cell($numberWith + $fullNameWith-25, $cellHeaderHeight, 'Observation', 1, 0, 'C', true);
        
        $pdf->Cell(($feesWith*4)-8, $cellHeaderHeight, utf8_decode($etatGlobal), 1, 0, 'C', true);
            $pdf->Ln($cellHeaderHeight*3);
        

        

        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(0, $cellHeaderHeight, utf8_decode('Fait à '.$school->getPlace().', Le ____________________________'), 0, 0, 'R');
        $pdf->Ln($cellHeaderHeight*3);

        $pdf->Cell($numberWith + $fullNameWith, $cellHeaderHeight, $registrationHistories[0]->getCreatedBy()->getTeacher()->getDuty()->getDuty(), 0, 0, 'C');

        $pdf->Cell($feesWith*2, $cellHeaderHeight, '', 0, 0, 'C');
        $pdf->Cell($feesWith*2, $cellHeaderHeight, utf8_decode($school->getHeadmaster()->getDuty()->getDuty()), 0, 0, 'C');
        $pdf->Ln();

        return $pdf;
    }

    public function tableHistoryHedaer(Pagination $pdf, int $totalWith, int $numberWith, int $cellHeaderHeight, int $fullNameWith, int $feesWith,  int $apeeFees, int $computerFees, int $medicalBookletFees, int $cleanSchoolFees, int $photoFees, int $stampFees, Classroom $classroom): Pagination
    {
        $pdf->SetFont('Times', 'B', 8);
        $pdf->SetX(30);
        $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
        $pdf->Cell($fullNameWith-25, $cellHeaderHeight*2, utf8_decode('Date de versement'), 1, 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('APEE'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Informat'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Livret Med'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);

        
        $pdf->Cell($totalWith, $cellHeaderHeight*2,  utf8_decode('Montant à payer'), 1, 1, 'C', true);

        $pdf->SetY($pdf->GetY()-$cellHeaderHeight);
        $pdf->SetX(30);
        $pdf->Cell($numberWith + $fullNameWith-25);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $apeeFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $computerFees.' F CFA', 'LBR', 0, 'C', true);

        $pdf->Cell($feesWith-12, $cellHeaderHeight, $medicalBookletFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $cleanSchoolFees.' F CFA', 'LBR', 0, 'C', true);
        $pdf->Cell($feesWith-12, $cellHeaderHeight, $photoFees.' F CFA', 'LBR', 0, 'C', true);


        $pdf->Ln();

        return $pdf;
    }

    public function printStudentQuitus(School $school, SchoolYear $schoolYear, Student $student, int $numberOfQuitus): Pagination 
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;
        $cellHeaderHeight = 6.52;

        $numberWith = 10;
        $fullNameWith = 60;
        $feesWith = 30;

        $pdf =  new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);
        
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
        
        // Entête de la liste
        $pdf = $this->getHeaderStudentQuitus($pdf, $school, $student, $numberOfQuitus);

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


}
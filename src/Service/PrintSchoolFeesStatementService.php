<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\Pagination;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintSchoolFeesStatementService 
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
            $apeeFeesRestTotal = 0;

            $computerFeesAdvanceTotal = 0;
            $computerFeesRestTotal = 0;

            $medicalBookletFeesAdvanceTotal = 0;
            $medicalBookletFeesRestTotal = 0;

            $cleanSchoolFeesAdvanceTotal = 0;
            $cleanSchoolFeesRestTotal = 0;

            $photoFeesAdvanceTotal = 0;
            $photoFeesRestTotal = 0;


            if($classroom->getLevel()->getCycle()->getCycle() == 1)
            {
                $apeeFees = $fees->getApeeFees1();
                $computerFees = $fees->getComputerFees1();

                if ($classroom->getLevel()->getLevel() == 4 ) 
                {
                    $stampFees = $fees->getStampFees3eme();
                }
            }else
            {
                $apeeFees = $fees->getApeeFees2();
                $computerFees = $fees->getComputerFees2();

                if ($classroom->getLevel()->getLevel() == 6) 
                {
                    $stampFees = $fees->getStampFees1ere();

                }elseif ($classroom->getLevel()->getLevel() == 7) 
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
            } else 
            {
                $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight, $numberWith, $fullNameWith, $feesWith, $totalWith, $apeeFees, $computerFees, $medicalBookletFees, $cleanSchoolFees, $photoFees, $stampFees = 0, $classroom);
            }
            
            
           

            $students = $classroom->getStudents();
            $numberOfStudents = count($students);

            $pdf->SetFont('Times', '', 10);
            $number = 0;

            foreach ($students as $student) 
            {
                $registrations = $student->getRegistrations();
                if ($number % 2 != 0) 
                {
                    $pdf->SetFillColor(219,238,243);
                }else {
                    $pdf->SetFillColor(255,255,255);
                }
                
                foreach ($registrations as $registration) 
                {
                    $apeeFeesAdvance = $registration->getApeeFees();
                    $apeeFeesRest = $apeeFees - $apeeFeesAdvance;
                    
                    $computerFeesAdvance = $registration->getComputerFees();
                    $computerFeesRest = $computerFees - $computerFeesAdvance;

                    //////////
                    $medicalBookletFeesAdvance = $registration->getMedicalBookletFees();
                    $medicalBookletFeesRest = $medicalBookletFees - $medicalBookletFeesAdvance;

                    $cleanSchoolFeesAdvance = $registration->getCleanSchoolFees();
                    $cleanSchoolFeesRest = $cleanSchoolFees - $cleanSchoolFeesAdvance;

                    $photoFeesAdvance = $registration->getPhotoFees();
                    $photoFeesRest = $photoFees - $photoFeesAdvance;

                    //////

                    $apeeFeesAdvanceTotal += $apeeFeesAdvance;
                    $apeeFeesRestTotal += $apeeFeesRest;

                    $computerFeesAdvanceTotal += $computerFeesAdvance;
                    $computerFeesRestTotal += $computerFeesRest;

                    ////
                    $medicalBookletFeesAdvanceTotal += $medicalBookletFeesAdvance;
                    $medicalBookletFeesRestTotal += $medicalBookletFeesRest;

                    $cleanSchoolFeesAdvanceTotal += $cleanSchoolFeesAdvance;
                    $cleanSchoolFeesRestTotal += $cleanSchoolFeesRest;

                    $photoFeesAdvanceTotal += $photoFeesAdvance;
                    $photoFeesRestTotal += $photoFeesRest;

                    /////
                    $number++;
                    $pdf->SetX(30);
                    $pdf->Cell($numberWith, $cellHeaderHeight, $number, 1, 0, 'C', true);
                    $pdf->Cell($fullNameWith+10, $cellHeaderHeight, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                    $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeFeesAdvance, 0, '.', ' '), 1, 0, 'C', true);
                    $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeFeesRest, 0, '.', ' '), 1, 0, 'C', true);
                    
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesAdvance, 0, '.', ' '), 1, 0, 'C', true);
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesRest, 0, '.', ' '), 1, 0, 'C', true);
                    
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesAdvance, 0, '.', ' '), 1, 0, 'C', true);
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesRest, 0, '.', ' '), 1, 0, 'C', true);

                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesAdvance, 0, '.', ' '), 1, 0, 'C', true);
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesRest, 0, '.', ' '), 1, 0, 'C', true);

                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesAdvance, 0, '.', ' '), 1, 0, 'C', true);
                    $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesRest, 0, '.', ' '), 1, 0, 'C', true);
                    
                    $pdf->Cell($totalWith+5, $cellHeaderHeight,  number_format($apeeFeesRest + $computerFeesRest + $medicalBookletFeesRest + $cleanSchoolFeesRest + $photoFeesRest, 0, '.', ' '), 1, 1, 'C', true);
                
                }
            }

            $pdf->SetFillColor(200, 200, 200);
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetX(30);

            $mySession =  $this->request->getSession();

            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($fullNameWith + $numberWith+10, $cellHeaderHeight, utf8_decode('Totaux'), 1, 0, 'C', true);
            }else
            {
                $pdf->Cell($fullNameWith + $numberWith+10, $cellHeaderHeight, utf8_decode('Totals'), 1, 0, 'C', true);
            }

            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);
            
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);
            
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);

            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesAdvanceTotal, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFeesRestTotal, 0, '.', ' '), 1, 0, 'C', true);


            $pdf->Cell($totalWith+5, $cellHeaderHeight,  number_format($apeeFeesRestTotal + $computerFeesRestTotal + $medicalBookletFeesRestTotal + $cleanSchoolFeesRestTotal + $photoFeesRestTotal, 0, '.', ' '), 1, 1, 'C', true);
            

            /////
            $pdf->SetFont('Times', 'B', 15);
            $pdf->SetX(30);

            $mySession =  $this->request->getSession();
            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($fullNameWith + $numberWith+10, $cellHeaderHeight, utf8_decode('Montant Reçu'), 1, 0, 'C', true);
            }else
            {
                $pdf->Cell($fullNameWith + $numberWith+10, $cellHeaderHeight, utf8_decode('Reveived amount'), 1, 0, 'C', true);

            }
            
            $pdf->Cell($feesWith+133, $cellHeaderHeight, number_format($apeeFeesAdvanceTotal + $computerFeesAdvanceTotal + $medicalBookletFeesAdvanceTotal + $cleanSchoolFeesAdvanceTotal + $photoFeesAdvanceTotal, 0, '.', ' ')." FCFA", 1, 0, 'C', true);

            
        }

        return $pdf;
    }


    public function getHeaderStudentList(Pagination $pdf, School $school, Classroom $classroom): Pagination
    {
        $mySession =  $this->request->getSession();
        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('ETAT GENERAL DES PAIEMENTS DES FRAIS DE SCOLARITE'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, utf8_decode(""), 0, 0, 'L');
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Classe : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('GENERAL STATUS OF TUITION PAYMENTS'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, utf8_decode(""), 0, 0, 'L');
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
            $pdf->Ln();
        }

        return $pdf;
    }

   
    public function getTableHeaderStudentList(Pagination $pdf, int $cellHeaderHeight, int $numberWith, int $fullNameWith, int $feesWith, int $totalWith, int $apeeFees, int $computerFees, int $medicalBookletFees, int $cleanSchoolFees, int $photoFees, int $stampFees, Classroom $classroom): Pagination
    {
        $mySession =  $this->request->getSession();
        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetX(30);
            $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith+10, $cellHeaderHeight*2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Cell($feesWith+10, $cellHeaderHeight, utf8_decode('APEE'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Informatique'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Livret Med.'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);

            $pdf->Cell($totalWith+5, $cellHeaderHeight*2,  utf8_decode('Montant à payer'), 1, 0, 'C', true);
            $pdf->Ln();

            $pdf->SetY($pdf->GetY()-$cellHeaderHeight);
            
            $pdf->SetX(30);
            $pdf->Cell($numberWith + $fullNameWith+10);
            $pdf->Cell($feesWith+10, $cellHeaderHeight/2, $apeeFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $computerFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $medicalBookletFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $cleanSchoolFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $photoFees.' F CFA', 'LBR', 0, 'C', true);
            
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 8);
            $pdf->SetX(30);
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
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetX(30);
            $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith+10, $cellHeaderHeight*2, utf8_decode('First and last names'), 1, 0, 'C', true);
            $pdf->Cell($feesWith+10, $cellHeaderHeight, utf8_decode('PTA'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('IT'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Med. Book.'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);

            $pdf->Cell($totalWith+5, $cellHeaderHeight*2,  utf8_decode('Amount to be paid'), 1, 0, 'C', true);
            $pdf->Ln();

            $pdf->SetY($pdf->GetY()-$cellHeaderHeight);
            
            $pdf->SetX(30);
            $pdf->Cell($numberWith + $fullNameWith+10);
            $pdf->Cell($feesWith+10, $cellHeaderHeight/2, $apeeFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $computerFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $medicalBookletFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $cleanSchoolFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $photoFees.' F CFA', 'LBR', 0, 'C', true);
            
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 8);
            $pdf->SetX(30);
            $pdf->Cell($numberWith + $fullNameWith+10);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
        }

        $pdf->Ln();
        $pdf->SetFont('Times', '', 10);
        return $pdf;
    }
    
}
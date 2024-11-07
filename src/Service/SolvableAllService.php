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

class SolvableAllService 
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
    public function printStudentSolvableAll(array $classrooms, School $school, SchoolYear $schoolYear): Pagination
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
            $pdf = $this->generalService->getHeaderStudentList($pdf, $school, $classroom);
    
            // entête du tableau
            $pdf = $this->generalService->getTableHeaderStudentList($pdf, $cellHeaderHeight, $numberWith, $fullNameWith, $feesWith, $totalWith, $apeeFees, $computerFees, $medicalBookletFees, $cleanSchoolFees, $photoFees, $stampFees = 0, $classroom);
            
            
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
                    $avance = $apeeFeesAdvance + $computerFeesAdvance + $medicalBookletFeesAdvance + $cleanSchoolFeesAdvance + $photoFeesAdvance;
                    if($avance == 25000)
                    {
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
            
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format($apeeFees*$number, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight, number_format(0, 0, '.', ' '), 1, 0, 'C', true);

            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($computerFees*$number, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format(0, 0, '.', ' '), 1, 0, 'C', true);
            
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($medicalBookletFees*$number, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format(0, 0, '.', ' '), 1, 0, 'C', true);
            
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($cleanSchoolFees*$number, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format(0, 0, '.', ' '), 1, 0, 'C', true);

            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format($photoFees*$number, 0, '.', ' '), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight, number_format(0, 0, '.', ' '), 1, 0, 'C', true);

            ///totaux
            $pdf->Cell($totalWith+5, $cellHeaderHeight,  number_format(0, 0, '.', ' '), 1, 1, 'C', true);

            /////
            $pdf->SetFont('Times', 'B', 15);
            $pdf->SetX(30);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($fullNameWith + $numberWith+10, $cellHeaderHeight, utf8_decode('Montant Reçu'), 1, 0, 'C', true);
            }else
            {
                $pdf->Cell($fullNameWith + $numberWith+10, $cellHeaderHeight, utf8_decode('Received amount'), 1, 0, 'C', true);

            }
            
            $pdf->Cell($feesWith+133, $cellHeaderHeight, number_format($apeeFees*$number + $computerFees*$number + $medicalBookletFees*$number + $cleanSchoolFees*$number + $photoFees*$number, 0, '.', ' ')." FCFA", 1, 0, 'C', true);


        }

        return $pdf;
    }

    
}
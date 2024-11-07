<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\EtatDepense;
use App\Entity\EtatFinance;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\Pagination;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintEtatFinanceService 
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
     * @param EtatDepense $EtatDepense
     * @param EtatFinance $EtatFinance
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return Pagination
     */
    public function print(array $etatFinance,  int $apee, int $computer, int $cleanSchool, int $medicalBooklet, int $stamp, int $photo, SchoolYear $schoolYear, School $school): Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeaderStudentList($pdf);

        $pdf->Ln(10);

        $pdf = $this->getHeaderTable($pdf);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Arial', '', 12);
        $pdf->SetFillColor(200,200, 200);
        $pdf->Cell(5, 5, utf8_decode(""), 0, 0, 'C');

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(22, 5, utf8_decode("Encaissé"), 1, 0, 'C', true);
        }else
        {
            $pdf->Cell(22, 5, utf8_decode("Cashed"), 1, 0, 'C', true);
        }
            
        $pdf->Cell(35, 5, utf8_decode($etatFinance['APEE']), 1, 0, 'C');
        $pdf->Cell(45, 5, utf8_decode($etatFinance['INFORMATIQUE']), 1, 0, 'C');
        $pdf->Cell(40, 5, utf8_decode($etatFinance['CLEAN_SCHOOL']), 1, 0, 'C');
        $pdf->Cell(35, 5, utf8_decode($etatFinance['LIVRET_MEDICAL']), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode($etatFinance['TIMBRE']), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode($etatFinance['PHOTO']), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(35, 5, utf8_decode($etatFinance['APEE'] + $etatFinance['INFORMATIQUE'] + $etatFinance['CLEAN_SCHOOL'] + $etatFinance['LIVRET_MEDICAL'] + $etatFinance['PHOTO'] + $etatFinance['TIMBRE']), 1, 1, 'C', true);
        ////////////

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(5, 5, utf8_decode(""), 0, 0, 'C');

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(22, 5, utf8_decode("Dépenses"), 1, 0, 'C', true);
        }else
        {
            $pdf->Cell(22, 5, utf8_decode("Expenses"), 1, 0, 'C', true);
        }
        
        $pdf->Cell(35, 5, utf8_decode($apee), 1, 0, 'C');
        $pdf->Cell(45, 5, utf8_decode($computer), 1, 0, 'C');
        $pdf->Cell(40, 5, utf8_decode($medicalBooklet), 1, 0, 'C');
        $pdf->Cell(35, 5, utf8_decode($cleanSchool), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode($stamp), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode($photo), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(35, 5, utf8_decode($apee + $computer + $medicalBooklet + $cleanSchool + $stamp + $photo), 1, 1, 'C', true);

        //////////////////////////////

        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(5, 5, utf8_decode(""), 0, 0, 'C');

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(22, 5, utf8_decode("En caisse"), 1, 0, 'C', true);
        }else
        {
            $pdf->Cell(22, 5, utf8_decode("In Box"), 1, 0, 'C', true);
        }
        
        $pdf->Cell(35, 5, utf8_decode($etatFinance['APEE'] - $apee), 1, 0, 'C');
        $pdf->Cell(45, 5, utf8_decode($etatFinance['INFORMATIQUE'] - $computer), 1, 0, 'C');
        $pdf->Cell(40, 5, utf8_decode($etatFinance['LIVRET_MEDICAL'] - $medicalBooklet), 1, 0, 'C');
        $pdf->Cell(35, 5, utf8_decode($etatFinance['CLEAN_SCHOOL'] - $cleanSchool), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode($etatFinance['TIMBRE'] - $stamp), 1, 0, 'C');
        $pdf->Cell(30, 5, utf8_decode($etatFinance['PHOTO'] - $photo), 1, 0, 'C');
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(35, 5, utf8_decode($etatFinance['APEE'] + $etatFinance['INFORMATIQUE'] + $etatFinance['LIVRET_MEDICAL'] + $etatFinance['CLEAN_SCHOOL'] + $etatFinance['PHOTO'] + $etatFinance['TIMBRE'] - $apee - $computer - $medicalBooklet - $cleanSchool - $stamp - $photo), 1, 1, 'C', true);

        $pdf->SetFont('Arial', 'BI', 9);
        
        

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(270, 5, utf8_decode("NB : les montants sont en FCFA"), 0, 0, 'R');
        }else
        {
            $pdf->Cell(270, 5, utf8_decode("NB : The amounts are in FCFA"), 0, 0, 'R');
        }
            

        return $pdf;
    }

    public function getHeaderStudentList(Pagination $pdf): Pagination
    {
        $pdf->SetFont('Times', 'B', 14);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, 5, utf8_decode('ETAT DES FINANCES'), 0, 2, 'C');
        }else
        {
            $pdf->Cell(0, 5, utf8_decode('FINANCIAL STATEMENT'), 0, 2, 'C');
        }
            
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(100, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->Ln();

        return $pdf;
    }

    public function getHeaderTable(Pagination $pdf): Pagination
    {
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(200,200, 200);
        $pdf->Cell(5, 5, utf8_decode(""), 0, 0, 'C');

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(22, 5, utf8_decode("ETATS"), 1, 0, 'C', true);
            $pdf->Cell(35, 5, utf8_decode("APEE"), 1, 0, 'C', true);
            $pdf->Cell(45, 5, utf8_decode("Frais d'informatique"), 1, 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("Livret médical"), 1, 0, 'C', true);
            $pdf->Cell(35, 5, utf8_decode("Clean School"), 1, 0, 'C', true);
            $pdf->Cell(30, 5, utf8_decode("Timbre"), 1, 0, 'C', true);
            $pdf->Cell(30, 5, utf8_decode("Photo"), 1, 0, 'C', true);
            $pdf->Cell(35, 5, utf8_decode("TOTAL"), 1, 1, 'C', true);
        }else
        {
            $pdf->Cell(22, 5, utf8_decode("STATES"), 1, 0, 'C', true);
            $pdf->Cell(35, 5, utf8_decode("PTA"), 1, 0, 'C', true);
            $pdf->Cell(45, 5, utf8_decode("IT Fees"), 1, 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("Medical Book."), 1, 0, 'C', true);
            $pdf->Cell(35, 5, utf8_decode("Clean School"), 1, 0, 'C', true);
            $pdf->Cell(30, 5, utf8_decode("Stamp"), 1, 0, 'C', true);
            $pdf->Cell(30, 5, utf8_decode("Photo"), 1, 0, 'C', true);
            $pdf->Cell(35, 5, utf8_decode("TOTAL"), 1, 1, 'C', true);
        }

        return $pdf;
    }

}
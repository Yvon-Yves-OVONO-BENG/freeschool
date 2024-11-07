<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\EtatFinance;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\Pagination;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintEtatDepenseService 
{
    public function __construct(
        protected RequestStack $request,
        protected GeneralService $generalService, 
        protected FeesRepository $feesRepository, 
        protected RegistrationRepository $registrationRepository, 
        )
    { }

    /**
     * Imprime les états des frais académiques de chaque classe
     *
     * @param EtatFinance $EtatFinance
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return Pagination
     */
    public function print(Array $depenses, SchoolYear $schoolYear, School $school, int $sommeAPEE, int $sommeComputer, int $sommeMedicalBooklet, int $sommeCleanSchool, int $sommePhoto, int $sommeStamp): Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf);

        $pdf->Ln(5);

        $pdf = $this->getHeaderTable($pdf);

        $pdf->SetFont('Arial', '', 12);
        $pdf->SetFillColor(200,200, 200);

        $numberOfSpending = count($depenses);
        $number = 0;
        foreach ($depenses as $depense) 
        {
            $number++;
            if ($number % 2 != 0) 
            {
                $pdf->SetFillColor(219,238,243);
            }else {
                $pdf->SetFillColor(255,255,255);
            }
            // $pdf->Cell(10, 5, utf8_decode(""), 0, 0, 'C');
            $pdf->Cell(15, 7, utf8_decode($number), 1, 0, 'C', true);
            
            $pdf->Cell(45, 7, utf8_decode(date_format($depense->getCreatedAt(), 'd-m-Y H:i:s')), 1, 0, 'C', true);

            $pdf->Cell(25, 7, utf8_decode($depense->getMontant()), 1, 0, 'C', true);

            $pdf->Cell(25, 7, utf8_decode($depense->getRubrique()->getRubrique()), 1, 0, 'C', true);

            if (strlen($depense->getMotif()) > 64 ) 
            {
                $pdf->SetFont('Arial', '', 8);
            }
            else
            {
                $pdf->SetFont('Arial', '', 12);
            }
            $pdf->Cell(170, 7, utf8_decode($depense->getMotif()), 1, 1, 'L', true);

            $pdf->SetFont('Arial', '', 12);

            // if ($number % 11 == 0 && $numberOfSpending > 11) 
            // {
            //     // On insère une page
            //     $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize);

            //     // Administrative Header
            //     $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
                
            //     //TITRE DU DOCUMENT
            //     $pdf = $this->getHeader($pdf);
            //     $pdf->Ln(10);
            
            // }
        }

        ////////////////
        $pdf->Ln(5);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->SetFillColor(200,200, 200);
        $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'C');

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(70, 5, utf8_decode("Rubriques"), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode("Dépenses"), 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("APEE"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeAPEE), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Frais d'informatique"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeComputer), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Livret médical"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeMedicalBooklet), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Clean School"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeCleanSchool), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Photo"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommePhoto), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Timbre"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeStamp), 1, 1, 'C');
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("TOTAL"), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode($sommeAPEE + $sommeComputer + $sommeMedicalBooklet + $sommeCleanSchool + $sommePhoto + $sommeStamp), 1, 1, 'C', true);
            $pdf->Cell(10, 5, utf8_decode(""), 0, 0, 'L');
        }else
        {
            $pdf->Cell(70, 5, utf8_decode("Sections"), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode("Expenses"), 1, 1, 'C', true);
            $pdf->SetFont('Arial', '', 12);
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("PTA"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeAPEE), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("IT Fees"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeComputer), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Medical Book."), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeMedicalBooklet), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Clean School"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeCleanSchool), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Photo"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommePhoto), 1, 1, 'C');
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("Stamp"), 1, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode($sommeStamp), 1, 1, 'C');
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(60, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("TOTAL"), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode($sommeAPEE + $sommeComputer + $sommeMedicalBooklet + $sommeCleanSchool + $sommePhoto + $sommeStamp), 1, 1, 'C', true);
            $pdf->Cell(10, 5, utf8_decode(""), 0, 0, 'L');
        }

        return $pdf;
    }

    public function getHeader(Pagination $pdf): Pagination
    {
        $pdf->SetFont('Times', 'B', 14);
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, 5, utf8_decode('ETAT DES FINANCES DES DEPENSES'), 0, 2, 'C');
        }else
        {
            $pdf->Cell(0, 5, utf8_decode('FINANCIAL STATEMENT OF EXPENSES'), 0, 2, 'C');
        }
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(100, 5, utf8_decode(""), 0, 0, 'L');
        $pdf->Ln();

        return $pdf;
    }

    public function getHeaderTable(Pagination $pdf): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Arial', 'BI', 9);
            $pdf->Cell(10, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(240, 5, utf8_decode("NB : les montants sont en FCFA"), 0, 1, 'R');
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(200,200, 200);
            // $pdf->Cell(5, 5, utf8_decode(""), 0, 0, 'C');
            $pdf->Cell(15, 5, utf8_decode("N°"), 1, 0, 'C', true);
            $pdf->Cell(45, 5, utf8_decode("Date dépense"), 1, 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("Montant"), 1, 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("Rubrique"), 1, 0, 'C', true);
            $pdf->Cell(170, 5, utf8_decode("Motif"), 1, 1, 'C', true);
        }else
        {
            $pdf->SetFont('Arial', 'BI', 9);
            $pdf->Cell(10, 5, utf8_decode(""), 0, 0, 'L');
            $pdf->Cell(240, 5, utf8_decode("NB : The amounts are in FCFA"), 0, 1, 'R');
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->SetFillColor(200,200, 200);
            // $pdf->Cell(10, 5, utf8_decode(""), 0, 0, 'C');
            $pdf->Cell(15, 5, utf8_decode("N°"), 1, 0, 'C', true);
            $pdf->Cell(45, 5, utf8_decode("Expense date"), 1, 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("Amount"), 1, 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("Section"), 1, 0, 'C', true);
            $pdf->Cell(170, 5, utf8_decode("Motif"), 1, 1, 'C', true);
        }

        return $pdf;
    }

}
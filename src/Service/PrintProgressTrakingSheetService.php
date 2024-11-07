<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\School;
use App\Entity\Teacher;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Repository\SexRepository;
use App\Entity\ReportElements\PDF;
use App\Entity\ReportElements\Pagination;
use App\Repository\TermRepository;
use App\Repository\SkillRepository;
use App\Repository\LessonRepository;
use App\Repository\ReportRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintProgressTrakingSheetService 
{
    public function __construct(
        protected RequestStack $request,
        protected SexRepository $sexRepository, 
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected SkillRepository $skillRepository, 
        protected ReportRepository $reportRepository, 
        protected LessonRepository $lessonRepository, 
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        )
    {}

    // Imprime la liste des premiers par classe
    public function printProgressTrakingSheet(Teacher $teacher, SchoolYear $schoolYear, array $lessonPlains, School $school): Pagination
    {
        $pdf = new Pagination();

        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellTableHeight = 5.5;
        $cellTableClassroom = 25;
        $cellTableObservation = 26;
        $cellTablePresence = 36;
        $cellTablePresence3 = $cellTablePresence/3 ;

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

        $pdf->SetFillColor(230, 230, 230);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderTicket($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

        // Entête de la fiche
        $pdf->SetFont('Arial', 'B', $fontSize+4);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, 7, utf8_decode('FICHE DE SUIVI DES PROGRESSIONS'), 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', $fontSize);
            $pdf->Cell(5, 7, utf8_decode(""), 0, 0, 'C');
            $pdf->Cell(40, 7, utf8_decode("Nom de l'enseignant : "), 0, 0, 'L');
            $pdf->Cell(70, 7, utf8_decode($teacher->getFullName()), 0, 0, 'L');
            $pdf->Cell(25, 7, utf8_decode("Département : "), 0, 0, 'L');
            $pdf->Cell(60, 7, utf8_decode($teacher->getDepartment()->getDepartment()), 0, 0, 'L');
            $pdf->Cell(15, 7, utf8_decode("Grade : "), 0, 0, 'L');
            $pdf->Cell(20, 7, utf8_decode($teacher->getGrade()->getGrade()), 0, 0, 'L');
            $pdf->Cell(15, 7, utf8_decode("Tel : "), 0, 0, 'L');
            $pdf->Cell(20, 7, utf8_decode($teacher->getPhoneNumber()), 0, 1, 'L');
        }else
        {
            $pdf->Cell(0, 7, utf8_decode('PROGRESS TRACKING SHEET'), 0, 1, 'C');
            $pdf->SetFont('Arial', 'B', $fontSize);
            $pdf->Cell(5, 7, utf8_decode(""), 0, 0, 'C');
            $pdf->Cell(40, 7, utf8_decode("Name of teacher : "), 0, 0, 'L');
            $pdf->Cell(70, 7, utf8_decode($teacher->getFullName()), 0, 0, 'L');
            $pdf->Cell(25, 7, utf8_decode("Department : "), 0, 0, 'L');
            $pdf->Cell(60, 7, utf8_decode($teacher->getDepartment()->getDepartment()), 0, 0, 'L');
            $pdf->Cell(15, 7, utf8_decode("Rank : "), 0, 0, 'L');
            $pdf->Cell(20, 7, utf8_decode($teacher->getGrade()->getGrade()), 0, 0, 'L');
            $pdf->Cell(15, 7, utf8_decode("Phone : "), 0, 0, 'L');
            $pdf->Cell(20, 7, utf8_decode($teacher->getPhoneNumber()), 0, 1, 'L');
        }
        // $pdf->Cell(150, 7, utf8_decode($school->getFrenchName()), 0, 1, 'C');
        $pdf->Ln(3);

        // Entête du tableau
        $pdf = $this->getTableHeaderRateOfPresence($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $cellTableObservation, $cellTablePresence3);
    
        $mySession = $this->request->getSession();
        
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');

        }
              
        $pdf->SetFillColor(255,255,255);
        $numero = 1;

        $totalLessonPrevueSeq1 = 0;
        $totalLessonPrevueSeq2 = 0;
        $totalLessonPrevueSeq3 = 0;
        $totalLessonPrevueSeq4 = 0;
        $totalLessonPrevueSeq5 = 0;
        $totalLessonPrevueSeq6 = 0;

        $totalLessonFaiteSeq1 = 0;
        $totalLessonFaiteSeq2 = 0;
        $totalLessonFaiteSeq3 = 0;
        $totalLessonFaiteSeq4 = 0;
        $totalLessonFaiteSeq5 = 0;
        $totalLessonFaiteSeq6 = 0;

        foreach ($lessonPlains as $lessonPlain) 
        {   
            if ($numero % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }else
            {
                $pdf->SetFillColor(224,235,255);
            }
                
            $pdf->SetFont('Arial', '', $fontSize);
            $pdf->Cell($cellTableClassroom , $cellTableHeight*1.5, utf8_decode($lessonPlain->getClassroom()->getClassroom()), 1, 0, 'C', true);
        
            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($lessonPlain->getNbreLessonTheoriquePrevueSeq1() + $lessonPlain->getNbreLessonPratiquePrevueSeq1()), 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($lessonPlain->getNbreLessonTheoriquePrevueSeq2() + $lessonPlain->getNbreLessonPratiquePrevueSeq2()), 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($lessonPlain->getNbreLessonTheoriquePrevueSeq3() + $lessonPlain->getNbreLessonPratiquePrevueSeq3()), 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($lessonPlain->getNbreLessonTheoriquePrevueSeq4() + $lessonPlain->getNbreLessonPratiquePrevueSeq4()), 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($lessonPlain->getNbreLessonTheoriquePrevueSeq5() + $lessonPlain->getNbreLessonPratiquePrevueSeq5()), 1, 0, 'C', true);
            
            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($lessonPlain->getNbreLessonTheoriquePrevueSeq6() + $lessonPlain->getNbreLessonPratiquePrevueSeq6()), 1, 0, 'C', true);


            ///////////////////LECON FAITES
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq1() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq1() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq1() 
            ), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, (
                $lessonPlain->getNbreLessonTheoriquePrevueSeq1() + 
                $lessonPlain->getNbreLessonPratiquePrevueSeq1()) ? utf8_decode(
                number_format(((
                    $lessonPlain->getNbreLessonTheoriqueFaiteSeq1() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq1() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq1() 
                )/($lessonPlain->getNbreLessonTheoriquePrevueSeq1() + 
                $lessonPlain->getNbreLessonPratiquePrevueSeq1())
                )*100,2)) : "00", 1, 0, 'C', true);

            ///////////
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq2() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq2() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq2()
            ), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, (
                $lessonPlain->getNbreLessonTheoriquePrevueSeq2() + 
                $lessonPlain->getNbreLessonPratiquePrevueSeq2()) ? utf8_decode(number_format((
                    ($lessonPlain->getNbreLessonTheoriqueFaiteSeq2() + 
                    $lessonPlain->getNbreLessonPratiqueFaiteSeq2() +
                    $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                    $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq2()
                    )/($lessonPlain->getNbreLessonTheoriquePrevueSeq2() + $lessonPlain->getNbreLessonPratiquePrevueSeq2()))*100,2)) :"00", 1, 0, 'C', true);

            ///////////
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq3() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq3() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq3()
            ), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, (
                $lessonPlain->getNbreLessonTheoriquePrevueSeq3() + 
                $lessonPlain->getNbreLessonPratiquePrevueSeq3()) ? utf8_decode(number_format(((
                    $lessonPlain->getNbreLessonTheoriqueFaiteSeq3() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq3() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq3()
                    )/($lessonPlain->getNbreLessonTheoriquePrevueSeq3() + $lessonPlain->getNbreLessonPratiquePrevueSeq3()))*100,2)):"00", 1, 0, 'C', true);

            ///////////
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq4() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq4() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq4()
            ), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, (
                $lessonPlain->getNbreLessonTheoriquePrevueSeq4() + 
                $lessonPlain->getNbreLessonPratiquePrevueSeq4()) ? utf8_decode(number_format(((
                    $lessonPlain->getNbreLessonTheoriqueFaiteSeq4() + 
                    $lessonPlain->getNbreLessonPratiqueFaiteSeq4() +
                    $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                    $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq4()
                    )/($lessonPlain->getNbreLessonTheoriquePrevueSeq4() + $lessonPlain->getNbreLessonPratiquePrevueSeq4()))*100,2)):"00", 1, 0, 'C', true);

            ///////////
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq5() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq5() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq5()
            ), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, (
                $lessonPlain->getNbreLessonTheoriquePrevueSeq5() + 
                $lessonPlain->getNbreLessonPratiquePrevueSeq5()) ? utf8_decode(number_format(((
                    $lessonPlain->getNbreLessonTheoriqueFaiteSeq5() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq5() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq5()
                    )/($lessonPlain->getNbreLessonTheoriquePrevueSeq5() + $lessonPlain->getNbreLessonPratiquePrevueSeq5()))*100,2)):"00", 1, 0, 'C', true);

            ///////////
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq6() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq6() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
            ), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, (
                $lessonPlain->getNbreLessonTheoriquePrevueSeq6() + 
                $lessonPlain->getNbreLessonPratiquePrevueSeq6()) ? utf8_decode(number_format(((
                    $lessonPlain->getNbreLessonTheoriqueFaiteSeq6() + 
                $lessonPlain->getNbreLessonPratiqueFaiteSeq6() +
                $lessonPlain->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                $lessonPlain->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                    )/($lessonPlain->getNbreLessonTheoriquePrevueSeq6() + $lessonPlain->getNbreLessonPratiquePrevueSeq6()))*100,2)):"00", 1, 0, 'C', true);

            ///////////
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriquePrevueSeq1() + $lessonPlain->getNbreLessonPratiquePrevueSeq1() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq2() + $lessonPlain->getNbreLessonPratiquePrevueSeq2() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq3() + $lessonPlain->getNbreLessonPratiquePrevueSeq3() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq4() + $lessonPlain->getNbreLessonPratiquePrevueSeq4() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq5() + $lessonPlain->getNbreLessonPratiquePrevueSeq5() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq6() + $lessonPlain->getNbreLessonPratiquePrevueSeq6()
            ), 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight*1.5, utf8_decode(
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq1() + $lessonPlain->getNbreLessonPratiqueFaiteSeq1() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq2() + $lessonPlain->getNbreLessonPratiqueFaiteSeq2() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq3() + $lessonPlain->getNbreLessonPratiqueFaiteSeq3() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq4() + $lessonPlain->getNbreLessonPratiqueFaiteSeq4() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq5() + $lessonPlain->getNbreLessonPratiqueFaiteSeq5() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq6() + $lessonPlain->getNbreLessonPratiqueFaiteSeq6()
            ), 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight*1.5, ($lessonPlain->getNbreLessonTheoriquePrevueSeq1() + $lessonPlain->getNbreLessonPratiquePrevueSeq1() +
            $lessonPlain->getNbreLessonTheoriquePrevueSeq2() + $lessonPlain->getNbreLessonPratiquePrevueSeq2() +
            $lessonPlain->getNbreLessonTheoriquePrevueSeq3() + $lessonPlain->getNbreLessonPratiquePrevueSeq3() +
            $lessonPlain->getNbreLessonTheoriquePrevueSeq4() + $lessonPlain->getNbreLessonPratiquePrevueSeq4() +
            $lessonPlain->getNbreLessonTheoriquePrevueSeq5() + $lessonPlain->getNbreLessonPratiquePrevueSeq5() +
            $lessonPlain->getNbreLessonTheoriquePrevueSeq6() + $lessonPlain->getNbreLessonPratiquePrevueSeq6()) ? utf8_decode(
                number_format(($lessonPlain->getNbreLessonTheoriqueFaiteSeq1() + $lessonPlain->getNbreLessonPratiqueFaiteSeq1() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq2() + $lessonPlain->getNbreLessonPratiqueFaiteSeq2() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq3() + $lessonPlain->getNbreLessonPratiqueFaiteSeq3() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq4() + $lessonPlain->getNbreLessonPratiqueFaiteSeq4() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq5() + $lessonPlain->getNbreLessonPratiqueFaiteSeq5() +
                $lessonPlain->getNbreLessonTheoriqueFaiteSeq6() + $lessonPlain->getNbreLessonPratiqueFaiteSeq6())/($lessonPlain->getNbreLessonTheoriquePrevueSeq1() + $lessonPlain->getNbreLessonPratiquePrevueSeq1() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq2() + $lessonPlain->getNbreLessonPratiquePrevueSeq2() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq3() + $lessonPlain->getNbreLessonPratiquePrevueSeq3() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq4() + $lessonPlain->getNbreLessonPratiquePrevueSeq4() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq5() + $lessonPlain->getNbreLessonPratiquePrevueSeq5() +
                $lessonPlain->getNbreLessonTheoriquePrevueSeq6() + $lessonPlain->getNbreLessonPratiquePrevueSeq6()),2)
            ):"00", 1, 1, 'C', true);

            $totalLessonPrevueSeq1 += $lessonPlain->getNbreLessonTheoriquePrevueSeq1() + $lessonPlain->getNbreLessonPratiquePrevueSeq1();
            $totalLessonPrevueSeq2 += $lessonPlain->getNbreLessonTheoriquePrevueSeq2() + $lessonPlain->getNbreLessonPratiquePrevueSeq2();
            $totalLessonPrevueSeq3 += $lessonPlain->getNbreLessonTheoriquePrevueSeq3() + $lessonPlain->getNbreLessonPratiquePrevueSeq3();
            $totalLessonPrevueSeq4 += $lessonPlain->getNbreLessonTheoriquePrevueSeq4() + $lessonPlain->getNbreLessonPratiquePrevueSeq4();
            $totalLessonPrevueSeq5 += $lessonPlain->getNbreLessonTheoriquePrevueSeq5() + $lessonPlain->getNbreLessonPratiquePrevueSeq5();
            $totalLessonPrevueSeq6 += $lessonPlain->getNbreLessonTheoriquePrevueSeq6() + $lessonPlain->getNbreLessonPratiquePrevueSeq6();

            ////////////////////////////////////////
            $totalLessonFaiteSeq1 += $lessonPlain->getNbreLessonTheoriqueFaiteSeq1() + $lessonPlain->getNbreLessonPratiqueFaiteSeq1();
            $totalLessonFaiteSeq2 += $lessonPlain->getNbreLessonTheoriqueFaiteSeq2() + $lessonPlain->getNbreLessonPratiqueFaiteSeq2();
            $totalLessonFaiteSeq3 += $lessonPlain->getNbreLessonTheoriqueFaiteSeq3() + $lessonPlain->getNbreLessonPratiqueFaiteSeq3();
            $totalLessonFaiteSeq4 += $lessonPlain->getNbreLessonTheoriqueFaiteSeq4() + $lessonPlain->getNbreLessonPratiqueFaiteSeq4();
            $totalLessonFaiteSeq5 += $lessonPlain->getNbreLessonTheoriqueFaiteSeq5() + $lessonPlain->getNbreLessonPratiqueFaiteSeq5();
            $totalLessonFaiteSeq6 += $lessonPlain->getNbreLessonTheoriqueFaiteSeq6() + $lessonPlain->getNbreLessonPratiqueFaiteSeq6();
            
            $numero ++;
            
        }
        
        
        if ($totalLessonPrevueSeq1 == 0 || $totalLessonPrevueSeq2 == 0 || $totalLessonPrevueSeq3 == 0 || $totalLessonPrevueSeq4 == 0 || $totalLessonPrevueSeq5 == 0 || $totalLessonPrevueSeq6 == 0) 
        {
            $mySession =  $this->request->getSession();

            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellTableClassroom+253 , $cellTableHeight*1.5, utf8_decode('VEUILLEZ RENSEIGNER LE NOMBRE DE LECONS PREVUES'), 1, 0, 'C', true);
            }else
            {
                $pdf->Cell($cellTableClassroom+253 , $cellTableHeight*1.5, utf8_decode('PLEASE INDICATE THE NUMBER OF LESSONS PLANNED'), 1, 0, 'C', true);
            }
        }else 
        {
            $pdf->SetFont('Arial', 'B', $fontSize);
            $pdf->SetFillColor(230,230,230);
            $pdf->Cell($cellTableClassroom, $cellTableHeight*1.5, 'TOTAL', 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($totalLessonPrevueSeq1), 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($totalLessonPrevueSeq2), 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($totalLessonPrevueSeq3), 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($totalLessonPrevueSeq4), 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($totalLessonPrevueSeq5), 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight*1.5, utf8_decode($totalLessonPrevueSeq6), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode($totalLessonFaiteSeq1), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(number_format(($totalLessonFaiteSeq1/$totalLessonPrevueSeq1)*100, 2)), 1, 0, 'C', true);

            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode($totalLessonFaiteSeq2), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(number_format(($totalLessonFaiteSeq2/$totalLessonPrevueSeq2)*100, 2)), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode($totalLessonFaiteSeq3), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(number_format(($totalLessonFaiteSeq3/$totalLessonPrevueSeq3)*100, 2)), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode($totalLessonFaiteSeq4), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(number_format(($totalLessonFaiteSeq4/$totalLessonPrevueSeq4)*100, 2)), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode($totalLessonFaiteSeq5), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(number_format(($totalLessonFaiteSeq5/$totalLessonPrevueSeq5)*100, 2)), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode($totalLessonFaiteSeq6), 1, 0, 'C', true);
            $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight*1.5, utf8_decode(number_format(($totalLessonFaiteSeq6/$totalLessonPrevueSeq6)*100, 2)), 1, 0, 'C', true);

            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight*1.5, utf8_decode(
                $totalLessonPrevueSeq1 + $totalLessonPrevueSeq2 + $totalLessonPrevueSeq3 +
                + $totalLessonPrevueSeq4 + $totalLessonPrevueSeq5 + $totalLessonPrevueSeq6), 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight*1.5, utf8_decode($totalLessonFaiteSeq1 + $totalLessonFaiteSeq2 + $totalLessonFaiteSeq3 + $totalLessonFaiteSeq4 + $totalLessonFaiteSeq5 + $totalLessonFaiteSeq6), 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight*1.5, utf8_decode(number_format(($totalLessonFaiteSeq1 + $totalLessonFaiteSeq2 + $totalLessonFaiteSeq3 + $totalLessonFaiteSeq4 + $totalLessonFaiteSeq5 + $totalLessonFaiteSeq6)/($totalLessonPrevueSeq1 + $totalLessonPrevueSeq2 + $totalLessonPrevueSeq3 +
            + $totalLessonPrevueSeq4 + $totalLessonPrevueSeq5 + $totalLessonPrevueSeq6), 2)), 1, 1, 'C', true);
            $pdf->Cell($cellTableClassroom, $cellTableHeight*4, 'Observations', 1, 0, 'C');
            $pdf->Cell($cellTablePresence+50 , $cellTableHeight*4, utf8_decode(''), 1, 0, 'C');

            $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight*4, utf8_decode(''), 1, 0, 'C');
            $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight*4, utf8_decode(''), 1, 0, 'C');
            $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight*4, utf8_decode(''), 1, 0, 'C');
            $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight*4, utf8_decode(''), 1, 0, 'C');
            $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight*4, utf8_decode(''), 1, 0, 'C');
            $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight*4, utf8_decode(''), 1, 0, 'C');

            $pdf->Cell(($cellTablePresence+5) , $cellTableHeight*4, '', 1, 0, 'C');
        }
        
        return $pdf;
    }

    public function getTableHeaderRateOfPresence(Pagination $pdf, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTablePresence, int $cellTableObservation, int $cellTablePresence3): Pagination
    {
        $pdf->SetFont('Arial', 'B', $fontSize);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellTableClassroom, $cellTableHeight*3, 'Classes', 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($cellTablePresence+50 , $cellTableHeight*2, utf8_decode('NOMBRE DE LECONS PREVUES'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence+90 , $cellTableHeight, utf8_decode('NOMBRE DE LECONS FAITES'), 1, 0, 'C', true);
        }else
        {
            $pdf->Cell($cellTableClassroom, $cellTableHeight*3, 'Classes', 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($cellTablePresence+50 , $cellTableHeight*2, utf8_decode('NUMBER OF LESSONS PLANNED'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence+90 , $cellTableHeight, utf8_decode('NUMBER OF LESSONS DONE'), 1, 0, 'C', true);
        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x-($cellTablePresence+90), $y + $cellTableHeight);
        $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight, utf8_decode('Eval 1'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight, utf8_decode('Eval 2'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight, utf8_decode('Eval 3'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight, utf8_decode('Eval 4'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight, utf8_decode('Eval 5'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+90)/6 , $cellTableHeight, utf8_decode('Eval 6'), 1, 0, 'C', true);

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x, $y + $cellTableHeight-$cellTableHeight*2);
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellTablePresence+5 , $cellTableHeight*2, 'TOTAL ANNUEL', 1, 1, 'C', true);
        }else
        {
            $pdf->Cell($cellTablePresence+5 , $cellTableHeight*2, 'TOTAL ANNUAL', 1, 1, 'C', true);

        }

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->SetXY($x+$cellTableClassroom, $y);
        $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight, utf8_decode('Eval 1'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight, utf8_decode('Eval 2'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight, utf8_decode('Eval 3'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight, utf8_decode('Eval 4'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight, utf8_decode('Eval 5'), 1, 0, 'C', true);
        $pdf->Cell(($cellTablePresence+50)/6 , $cellTableHeight, utf8_decode('Eval 6'), 1, 0, 'C', true);

        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('nbr'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('%'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('nbr'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('%'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('nbr'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('%'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('nbr'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('%'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('nbr'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('%'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('nbr'), 1, 0, 'C', true);
        $pdf->Cell((($cellTablePresence+90)/6)/2 , $cellTableHeight, utf8_decode('%'), 1, 0, 'C', true);


        

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight, 'Prev', 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight, 'Fait', 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight, '%', 1, 1, 'C', true);
        }else
        {
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight, 'Plan.', 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight, 'Done', 1, 0, 'C', true);
            $pdf->Cell(($cellTablePresence+5)/3 , $cellTableHeight, '%', 1, 1, 'C', true);
        }

        return $pdf;
    }

    
    // Affiche une kigne de la fiche statistique d'assiduité des élèves
    

}
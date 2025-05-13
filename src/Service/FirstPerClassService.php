<?php

namespace App\Service;

use App\Entity\ConstantsClass;
use App\Entity\Term;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Repository\SexRepository;
use App\Entity\ReportElements\PDF;
use App\Entity\ReportElements\Pagination;
use App\Entity\SubSystem;
use App\Repository\TermRepository;
use App\Repository\SkillRepository;
use App\Repository\LessonRepository;
use App\Repository\ReportRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class FirstPerClassService 
{
    public function __construct(
        protected RequestStack $request,
        protected SexRepository $sexRepository, 
        protected TermRepository $termRepository, 
        protected GeneralService $generalService, 
        protected SkillRepository $skillRepository, 
        protected LessonRepository $lessonRepository, 
        protected ReportRepository $reportRepository, 
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        )
    {}

    // Imprime la liste des premiers par classe
    public function printBestStudentPerClass(array $bestReports, Term $term, SchoolYear $schoolYear, School $school, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf = new Pagination();

            $fontSize = 10;
            $cellHeaderHeight = 3;
            $cellTableHeight = 5.5;
            $cellTableClassroom = 25;
            $cellTableObservation = 26;
            $cellTablePresence = 36;
            $cellTablePresence3 = $cellTablePresence/3 ;

            // On définit le titre du trimestre
            if($term->getTerm() == 0)
            {
                $termTitle = 'ANNUEL';
            }else
            {
                $termTitle = 'TRIMESTRE '.$term->getTerm();
            }

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            $pdf->SetFillColor(200, 200, 200);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // Entête de la fiche
            $pdf->SetFont('Times', 'B', $fontSize+4);
            $pdf->Cell(190, 7, utf8_decode('LISTE DES PREMIERS PAR CLASSE'), 0, 1, 'C');
            
            $pdf->Cell(0, 7, utf8_decode($termTitle), 0, 1, 'C');
            // $pdf->Cell(150, 7, utf8_decode($school->getFrenchName()), 0, 1, 'C');
            $pdf->Ln(3);

           // Entête du tableau
           $pdf = $this->generalService->getTableHeaderPaginationFirstPerClass($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $cellTableObservation, $cellTablePresence3, $subSystem);
        
            $mySession = $this->request->getSession();
            
            if($mySession)
            {
                $schoolYear = $mySession->get('schoolYear');
                $subSystem = $mySession->get('subSystem');
            }
                
            $pdf->SetFillColor(255,255,255);
            $numero = 1;

            foreach ($bestReports as $bestReport) 
            {   
                if ($numero % 2 == 1) 
                {
                    $pdf->SetFillColor(255,255,255);
                }else
                {
                    $pdf->SetFillColor(224,235,255);
                }

                if ($bestReport->getStudent()->getSchoolYear()->getSchoolYear() == $schoolYear->getSchoolYear() && $bestReport->getTerm() == $term && $bestReport->getStudent()->getSubSystem()->getSubSystem() == ConstantsClass::FRANCOPHONE) 
                {
                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode($numero), 1, 0, 'C', true);
                    $pdf->Cell($cellTableClassroom , $cellTableHeight*1.5, utf8_decode($bestReport->getStudent()->getClassroom()->getClassroom()), 1, 0, 'C', true);
                
                    $pdf->Cell($cellTablePresence+80 , $cellTableHeight*1.5, utf8_decode($bestReport->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, utf8_decode($bestReport->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, utf8_decode(number_format($bestReport->getMoyenne(), 2)), 1, 1, 'C', true);
                    
                    $numero ++;
                }
            
            
            }
            
            $pdf->Ln($cellTableHeight*6);
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->Cell(190 , $cellTableHeight, utf8_decode('Fait à '.$school->getPlace().' le _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 1, 'R');
            $pdf->Ln($cellTableHeight);

            if ($school->isPublic()) 
            {
                if ($school->isLycee()) 
                {
                    $pdf->Cell(129 , $cellTableHeight, utf8_decode('Le Proviseur'), 0, 1, 'R');
                } else 
                {
                    $pdf->Cell(129 , $cellTableHeight, utf8_decode('Le Directeur'), 0, 1, 'R');
                }
                
                
            } else 
            {
                $pdf->Cell(129 , $cellTableHeight, utf8_decode('Le Principal'), 0, 1, 'R');
            }
            
            
            $pdf->Cell(40 , $cellTableHeight, utf8_decode(''), 0, 1, 'R');

        }
        else
        {
            $pdf = new Pagination();

            $fontSize = 10;
            $cellHeaderHeight = 3;
            $cellTableHeight = 5.5;
            $cellTableClassroom = 25;
            $cellTableObservation = 26;
            $cellTablePresence = 36;
            $cellTablePresence3 = $cellTablePresence/3 ;

            // On définit le titre du trimestre
            if($term->getTerm() == 0)
            {
                $termTitle = 'ANNUAL';
            }else
            {
                $termTitle = 'TERM '.$term->getTerm();
            }

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            $pdf->SetFillColor(200, 200, 200);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // Entête de la fiche
            $pdf->SetFont('Times', 'B', $fontSize+4);
            $pdf->Cell(190, 7, utf8_decode('LIST OF FIRST BY CLASS'), 0, 1, 'C');
            $pdf->Cell(0, 7, utf8_decode($termTitle), 0, 1, 'C');
            // $pdf->Cell(150, 7, utf8_decode($school->getFrenchName()), 0, 1, 'C');
            $pdf->Ln(3);

            // Entête du tableau
            $pdf = $this->generalService->getTableHeaderPaginationFirstPerClass($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $cellTableObservation, $cellTablePresence3, $subSystem);
        
            $mySession = $this->request->getSession();
            
            if($mySession)
            {
                $schoolYear = $mySession->get('schoolYear');
                $subSystem = $mySession->get('subSystem');

            }
                
            $pdf->SetFillColor(255,255,255);
            $numero = 1;

            foreach ($bestReports as $bestReport) 
            {   
                if ($numero % 2 == 1) 
                {
                    $pdf->SetFillColor(255,255,255);
                }else
                {
                    $pdf->SetFillColor(224,235,255);
                }

                if ($bestReport->getStudent()->getSchoolYear()->getSchoolYear() == $schoolYear->getSchoolYear() && $bestReport->getTerm() == $term && $bestReport->getStudent()->getSubSystem()->getSubSystem() == ConstantsClass::ANGLOPHONE) 
                {
                    
                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode($numero), 1, 0, 'C', true);
                    $pdf->Cell($cellTableClassroom , $cellTableHeight*1.5, utf8_decode($bestReport->getStudent()->getClassroom()->getClassroom()), 1, 0, 'C', true);
                
                    $pdf->Cell($cellTablePresence+80 , $cellTableHeight*1.5, utf8_decode($bestReport->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, utf8_decode($bestReport->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, utf8_decode(number_format($bestReport->getMoyenne(), 2)), 1, 1, 'C', true);
                    
                    $numero ++;
                }
            
            
            }
            
            $pdf->Ln($cellTableHeight*6);
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->Cell(190 , $cellTableHeight, utf8_decode('Done at  '.$school->getPlace().' on _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ __ '), 0, 1, 'R');
            $pdf->Ln($cellTableHeight);

            if ($school->isPublic()) 
            {
                if ($school->isLycee()) 
                {
                    $pdf->Cell(129 , $cellTableHeight, utf8_decode('The Principal'), 0, 1, 'R');
                } else 
                {
                    $pdf->Cell(129 , $cellTableHeight, utf8_decode('Le Director'), 0, 1, 'R');
                }
                
                
            } else 
            {
                $pdf->Cell(129 , $cellTableHeight, utf8_decode('The Principal'), 0, 1, 'R');
            }
            $pdf->Cell(40 , $cellTableHeight, utf8_decode(''), 0, 1, 'R');

        }
        
        return $pdf;
    }


}
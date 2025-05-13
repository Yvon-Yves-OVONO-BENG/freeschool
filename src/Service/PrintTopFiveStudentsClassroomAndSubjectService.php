<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Entity\Term;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Repository\SexRepository;
use App\Entity\ReportElements\PDF;
use App\Entity\ReportElements\Pagination;
use App\Entity\Subject;
use App\Entity\SubSystem;
use App\Repository\TermRepository;
use App\Repository\SkillRepository;
use App\Repository\LessonRepository;
use App\Repository\ReportRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintTopFiveStudentsClassroomAndSubjectService 
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

    /**
     * fonction qui imprime les 5 premiers élèves par matière
     *
     * @param array $topFiveStudents
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param SubSystem $subSystem
     * @param Subject $subject
     * @return Pagination
     */
    public function printTopFiveStudentsClassroomAndSubjectService(array $topFiveStudents, SchoolYear $schoolYear, School $school, SubSystem $subSystem, Subject $subject, Classroom  $classroom, Term $term): Pagination
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

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            $pdf->SetFillColor(200, 200, 200);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // Entête de la fiche
            $pdf->SetFont('Times', 'B', $fontSize+4);
            $pdf->Cell(190, 7, utf8_decode("LISTE DES CINQS PREMIERS ELEVES EN ".$subject->getSubject()), 0, 1, 'C');
            $pdf->Cell(190, 7, utf8_decode("CLASSE : ".$classroom->getClassroom()), 0, 1, 'C');

            if ($term->getTerm() == 0) 
            {
                $pdf->Cell(0, 7, utf8_decode("ANNUEL"), 0, 1, 'C');
            } 
            else 
            {
                $pdf->Cell(0, 7, utf8_decode("TRIMESTRE ".$term->getTerm()), 0, 1, 'C');
            }
            
            $pdf->Ln(7);

            // Entête du tableau
            $pdf = $this->generalService->getTableHeaderPagination($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $cellTableObservation, $cellTablePresence3, $subSystem);
        
            $mySession = $this->request->getSession();
            
            if($mySession)
            {
                $schoolYear = $mySession->get('schoolYear');
                $subSystem = $mySession->get('subSystem');
            }
                
            $pdf->SetFillColor(255,255,255);
            
            ///////APPEL DE LE LIGNE DU TABLEAU
            $numero = 1;
            foreach ($topFiveStudents as $topFiveStudent) 
            {   
                if ($numero % 2 == 1) 
                {
                    $pdf->SetFillColor(255,255,255);
                }else
                {
                    $pdf->SetFillColor(224,235,255);
                }

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode($numero), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence+45 , $cellTableHeight*1.5, utf8_decode($topFiveStudent['fullName']), 1, 0, 'L', true);
                $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, utf8_decode($topFiveStudent['sexe']), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, utf8_decode(number_format($topFiveStudent['moyenne'], 2)), 1, 0, 'C', true);
                $pdf->Cell($cellTableClassroom , $cellTableHeight*1.5, utf8_decode($topFiveStudent['classroom']), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence , $cellTableHeight*1.5, utf8_decode(date_format($topFiveStudent['dateNaissance'],'d/m/Y')), 1, 1, 'C', true);
                
                
                $numero ++;
                
            
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

            

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            $pdf->SetFillColor(200, 200, 200);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // Entête de la fiche
            $pdf->SetFont('Times', 'B', $fontSize+4);
            $pdf->Cell(190, 7, utf8_decode("SCHOOL TOP FIVE STUDENTS IN ".$subject->getSubject()), 0, 1, 'C');
            
            $pdf->Cell(190, 7, utf8_decode("CLASS : ".$classroom->getClassroom()), 0, 1, 'C');

            if ($term->getTerm() == 0) 
            {
                $pdf->Cell(0, 7, utf8_decode("ANNUAL"), 0, 1, 'C');
            } 
            else 
            {
                $pdf->Cell(0, 7, utf8_decode("TERM ".$term->getTerm()), 0, 1, 'C');
            }
            $pdf->Ln(7);

            // Entête du tableau
            $pdf = $this->generalService->getTableHeaderPagination($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $cellTableObservation, $cellTablePresence3, $subSystem);
        
            $mySession = $this->request->getSession();
            
            if($mySession)
            {
                $schoolYear = $mySession->get('schoolYear');
                $subSystem = $mySession->get('subSystem');

            }
                
            $pdf->SetFillColor(255,255,255);
            
            ///////APPEL DE LE LIGNE DU TABLEAU
            $numero = 1;
            foreach ($topFiveStudents as $topFiveStudent) 
            {   
                if ($numero % 2 == 1) 
                {
                    $pdf->SetFillColor(255,255,255);
                }else
                {
                    $pdf->SetFillColor(224,235,255);
                }

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode($numero), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence+45 , $cellTableHeight*1.5, utf8_decode($topFiveStudent['fullName']), 1, 0, 'L', true);
                $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, utf8_decode($topFiveStudent['sexe']), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, utf8_decode(number_format($topFiveStudent['moyenne'], 2)), 1, 0, 'C', true);
                $pdf->Cell($cellTableClassroom , $cellTableHeight*1.5, utf8_decode($topFiveStudent['classroom']), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence , $cellTableHeight*1.5, utf8_decode(date_format($topFiveStudent['dateNaissance'],'d/m/Y')), 1, 1, 'C', true);
                
                
                $numero ++;
                
            
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
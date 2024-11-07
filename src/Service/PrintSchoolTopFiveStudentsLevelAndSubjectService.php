<?php

namespace App\Service;

use App\Entity\ConstantsClass;
use App\Entity\Level;
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

class PrintSchoolTopFiveStudentsLevelAndSubjectService 
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
     * fonction qui imprime les 5 premiers élèves par matière d'un niveau
     *
     * @param array $topFiveStudents
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param SubSystem $subSystem
     * @param Level $level
     * @param Subject $subject
     * @return Pagination
     */
    public function printSchoolTopFiveStudentsLevelAndSubjectService(array $topFiveStudents, SchoolYear $schoolYear, School $school, SubSystem $subSystem, Level $level, Subject $subject): Pagination
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
            
            $pdf->Cell(95, 7, utf8_decode("NIVEAU  "), 0, 0, 'R');
            switch ($level->getLevel()) 
            {
                case 1:
                    $pdf->Cell(10, 7, utf8_decode(ConstantsClass::LEVEL_1), 0, 1, 'L');
                    break;

                case 2:
                    $pdf->Cell(10, 7, utf8_decode(ConstantsClass::LEVEL_2), 0, 1, 'L');
                    break;

                case 3:
                    $pdf->Cell(10, 7, utf8_decode(ConstantsClass::LEVEL_3), 0, 1, 'L');
                    break;

                case 4:
                    $pdf->Cell(10, 7, utf8_decode(ConstantsClass::LEVEL_4), 0, 1, 'L');
                    break;

                case 5:
                    $pdf->Cell(10, 7, utf8_decode(ConstantsClass::LEVEL_5), 0, 1, 'L');
                    break;

                case 6:
                    $pdf->Cell(10, 7, utf8_decode(ConstantsClass::LEVEL_6), 0, 1, 'L');
                    break;

                case 7:
                    $pdf->Cell(10, 7, utf8_decode(ConstantsClass::LEVEL_7), 0, 1, 'L');
                    break;
                
                
            }
            $pdf->Ln(3);

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
            $pdf = $this->generalService->ligneDeMesTableauxMeilleursEleves($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $schoolYear, $topFiveStudents);
        
            
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
            $pdf->Cell(190, 7, utf8_decode("LEVEL  ".$level->getLevel()), 0, 1, 'C');
            $pdf->Ln(3);

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
            $pdf = $this->generalService->ligneDeMesTableauxMeilleursEleves($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $schoolYear, $topFiveStudents);
        
            
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
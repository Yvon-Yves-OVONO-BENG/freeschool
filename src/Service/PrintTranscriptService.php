<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\School;
use App\Entity\Student;
use App\Entity\Sequence;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Entity\SubSystem;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;

class PrintTranscriptService 
{
    public function __construct(
        protected GeneralService $generalService, 
        protected RegistrationRepository $registrationRepository, 
        protected FeesRepository $feesRepository)
    {}


    /**
     * Imprime le relevé de notes séquence
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $releves
     * @param Student|null $student
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @param integer $envoie
     * @param [type] $filePath
     * @return PDF
     */
    public function printTranscriptStudentSequence(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $releves, ?Student $student = null, ?Term $term = null, ?Sequence $sequence = null, $envoie = 0, $filePath = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

        $pdf->Ln(10);
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode($student ? "Nom de l'élève / Student's name : ".$student->getFullName(): ""), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($student ? "NIU : ".$student->getRegistrationNumber(): ""), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Enseignant / Teacher'), 1, 0, 'C', true);
        $pdf->Cell(50, 5, utf8_decode('Notes / Mark'), 1, 1, 'C', true);
        
        $i = 1;
        $pdf->SetFont('Times', '', 11);
        foreach ($releves as $releve) 
        {
            // dd($releve);
            if ($i % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }
            elseif ($i % 2 == 0)
            {
                $pdf->SetFillColor(224,235,255);
            }
            
            
            $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 9);
            $pdf->Cell(60, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 11);
            if (!$releve['mark']) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['mark'] == 0.1) 
            {
                $pdf->Cell(50, 5, utf8_decode("//"), 1, 1, 'C', true);

            }
            else
            {
                $pdf->Cell(50, 5, utf8_decode($releve['mark'] ? $releve['mark'] : "Pas de note / No Not"), 1, 1, 'C', true);
            }


            $i = $i + 1;
        }
        
        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;

        $pdf->SetFont('Times', 'B', 12);
        if($school->isPublic())
        {
            if($school->isLycee())
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                }
                
            }else
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                }
                
            }
        }else
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
            }else
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
            }
        }

        //////////////////////////////

        if ($envoie == 0) 
        {
            $pdf->AliasNbPages();
            return $pdf;
        } 
        else 
        {
            $pdf->Output('F', $filePath);
            return $pdf;
        }
    
    }

    /**
     * Undocumented function
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $releves
     * @param Student|null $student
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @param integer $envoie
     * @param [type] $filePath
     * @return PDF
     */
    public function sendTranscriptStudentSequence(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $releves, ?Student $student = null, ?Term $term = null, ?Sequence $sequence = null, $filePath = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

        $pdf->Ln(10);
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode($student ? "Nom de l'élève / Student's name : ".$student->getFullName(): ""), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($student ? "NIU : ".$student->getRegistrationNumber(): ""), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Enseignant / Teacher'), 1, 0, 'C', true);
        $pdf->Cell(50, 5, utf8_decode('Notes / Mark'), 1, 1, 'C', true);
        
        $i = 1;
        $pdf->SetFont('Times', '', 11);
        
        foreach ($releves['subjects'] as $releve) 
        {
            // dd($releve);
            if ($i % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }
            elseif ($i % 2 == 0)
            {
                $pdf->SetFillColor(224,235,255);
            }
            
            
            $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 9);
            $pdf->Cell(60, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 11);
            if (!$releve['mark']) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['mark'] == 0.1) 
            {
                $pdf->Cell(50, 5, utf8_decode("//"), 1, 1, 'C', true);

            }
            else
            {
                $pdf->Cell(50, 5, utf8_decode($releve['mark'] ? $releve['mark'] : "Pas de note / No Not"), 1, 1, 'C', true);
            }


            $i = $i + 1;
        }
        
        

        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;

        $pdf->SetFont('Times', 'B', 12);
        if($school->isPublic())
        {
            if($school->isLycee())
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                }
                
            }else
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                }
                
            }
        }else
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
            }else
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
            }
        }

        //////////////////////////////

        // Sauvegarder le PDF dans un fichier
        $pdf->Output('F', $filePath);
        return $pdf;
        
    }


    /**
     * releve de l'élève d'un trimestre
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $releves
     * @param Student|null $student
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function printTranscriptStudentTerm(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $releves, ?Student $student = null, ?Term $term = null, ?Sequence $sequence = null, int $envoie = 0, $filePath = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

        $pdf->Ln(10);
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode($student ? "Nom de l'élève / Student's name : ".$student->getFullName(): ""), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($student ? "NIU : ".$student->getRegistrationNumber(): ""), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Enseignant / Teacher'), 1, 0, 'C', true);

        switch ($term->getTerm()) 
        {
            case 1:
                $pdf->Cell(25, 5, utf8_decode('Eval 1'), 1, 0, 'C', true);
                $pdf->Cell(25, 5, utf8_decode('Eval 2'), 1, 1, 'C', true);
                break;
            
            case 2:
                $pdf->Cell(25, 5, utf8_decode('Eval 3'), 1, 0, 'C', true);
                $pdf->Cell(25, 5, utf8_decode('Eval 4'), 1, 1, 'C', true);
                break;

            case 3:
                $pdf->Cell(25, 5, utf8_decode('Eval 5'), 1, 0, 'C', true);
                $pdf->Cell(25, 5, utf8_decode('Eval 6'), 1, 1, 'C', true);
                break;
        }
        

        $i = 1;
        $pdf->SetFont('Times', '', 11);
        foreach ($releves as $releve) 
        {
            // dd($releve);
            if ($i % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }
            elseif ($i % 2 == 0)
            {
                $pdf->SetFillColor(224,235,255);
            }
            
            
            $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 9);
            $pdf->Cell(60, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 11);

            if (!$releve['evaluation1'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['evaluation1'] == 0.1) 
            {
                $pdf->Cell(25, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(25, 5, utf8_decode($releve['evaluation1'] ? $releve['evaluation1'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['evaluation2'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['evaluation2'] == 0.1) 
            {
                $pdf->Cell(25, 5, utf8_decode("//"), 1, 1, 'C', true);

            }
            else
            {
                $pdf->Cell(25, 5, utf8_decode($releve['evaluation2'] ? $releve['evaluation2'] : "//"), 1, 1, 'C', true);
            }

            $i = $i + 1;
        }
        
        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;

        $pdf->SetFont('Times', 'B', 12);
        if($school->isPublic())
        {
            if($school->isLycee())
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                }
                
            }else
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                }
                
            }
        }else
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
            }else
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
            }
        }


        //////////////////////////////
        if ($envoie == 0) 
        {
            $pdf->AliasNbPages();
            return $pdf;
        } 
        else 
        {
            $pdf->Output('F', $filePath);
            return $pdf;
        }
        
        
    }


    /**
     * Undocumented function
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $releves
     * @param Student|null $student
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function sendTranscriptStudentTerm(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $releves, ?Student $student = null, ?Term $term = null, ?Sequence $sequence = null, int $envoie = 0, $filePath = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

        $pdf->Ln(10);$pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode($student ? "Nom de l'élève / Student's name : ".$student->getFullName(): ""), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($student ? "NIU : ".$student->getRegistrationNumber(): ""), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
        $pdf->Cell(60, 5, utf8_decode('Enseignant / Teacher'), 1, 0, 'C', true);

        switch ($term->getTerm()) 
        {
            case 1:
                $pdf->Cell(25, 5, utf8_decode('Eval 1'), 1, 0, 'C', true);
                $pdf->Cell(25, 5, utf8_decode('Eval 2'), 1, 1, 'C', true);
                break;
            
            case 2:
                $pdf->Cell(25, 5, utf8_decode('Eval 3'), 1, 0, 'C', true);
                $pdf->Cell(25, 5, utf8_decode('Eval 4'), 1, 1, 'C', true);
                break;

            case 3:
                $pdf->Cell(25, 5, utf8_decode('Eval 5'), 1, 0, 'C', true);
                $pdf->Cell(25, 5, utf8_decode('Eval 6'), 1, 1, 'C', true);
                break;
        }
        

        $i = 1;
        $pdf->SetFont('Times', '', 11);
        foreach ($releves['subjects'] as $releve) 
        {
            // dd($releve);
            if ($i % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }
            elseif ($i % 2 == 0)
            {
                $pdf->SetFillColor(224,235,255);
            }
            
            
            $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 9);
            $pdf->Cell(60, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 11);
            
            if (!$releve['evaluation1'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }
            
            if($releve['evaluation1'] == 0.1) 
            {
                $pdf->Cell(25, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(25, 5, utf8_decode($releve['evaluation1'] ? $releve['evaluation1'] : "//"), 1, 0, 'C', true);
            }
            
            if (!$releve['evaluation2'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['evaluation2'] == 0.1) 
            {
                $pdf->Cell(25, 5, utf8_decode("//"), 1, 1, 'C', true);

            }
            else
            {
                $pdf->Cell(25, 5, utf8_decode($releve['evaluation2'] ? $releve['evaluation2'] : "//"), 1, 1, 'C', true);
            }

            $i = $i + 1;
            
        }
        
        $pdf->Ln();
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;

        $pdf->SetFont('Times', 'B', 12);
        if($school->isPublic())
        {
            if($school->isLycee())
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                }
                
            }else
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                }
                
            }
        }else
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
            }else
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
            }
        }


        //////////////////////////////
        if ($envoie == 0) 
        {
            $pdf->AliasNbPages();
            return $pdf;
        } 
        else 
        {
            $pdf->Output('F', $filePath);
            return $pdf;
        }
        
        
    }


    /**
     * relevés de notes annuels d'un élève
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $releves
     * @param Student|null $student
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function printTranscriptStudentAnnual(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $releves, ?Student $student = null, ?Term $term = null, ?Sequence $sequence = null, $envoie = 0, $filePath = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

        $pdf->Ln(10);
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode($student ? "Nom de l'élève / Student's name : ".$student->getFullName(): ""), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($student ? "NIU : ".$student->getRegistrationNumber(): ""), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(30, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(50, 5, utf8_decode('Enseignants / Teachers'), 1, 0, 'C', true);

        $pdf->Cell(15, 5, utf8_decode('Eval 1'), 1, 0, 'C', true);
        $pdf->Cell(15, 5, utf8_decode('Eval 2'), 1, 0, 'C', true);
        
        $pdf->Cell(15, 5, utf8_decode('Eval 3'), 1, 0, 'C', true);
        $pdf->Cell(15, 5, utf8_decode('Eval 4'), 1, 0, 'C', true);
        
        $pdf->Cell(15, 5, utf8_decode('Eval 5'), 1, 0, 'C', true);
        $pdf->Cell(15, 5, utf8_decode('Eval 6'), 1, 1, 'C', true);
            
        

        $i = 1;
        $pdf->SetFont('Times', '', 11);
        foreach ($releves as $releve) 
        {
            // dd($releve);
            if ($i % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }
            elseif ($i % 2 == 0)
            {
                $pdf->SetFillColor(224,235,255);
            }
            
            $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
            $pdf->SetFont('Times', '', 7);
            $pdf->Cell(30, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
            $pdf->Cell(50, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 11);

            if (!$releve['eval1'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }
            
            if($releve['eval1'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval1'] ? $releve['eval1'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval2'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval2'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval2'] ? $releve['eval2'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval3'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval3'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval3'] ? $releve['eval3'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval4'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval4'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval4'] ? $releve['eval4'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval5'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval5'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval5'] ? $releve['eval5'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval6'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval6'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 1, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval6'] ? $releve['eval6'] : "//"), 1, 1, 'C', true);
            }
            $i = $i + 1;
        }
        
        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;

        $pdf->SetFont('Times', 'B', 12);
        if($school->isPublic())
        {
            if($school->isLycee())
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                }
                else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                }
                
            }else
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                }
                
            }
        }else
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
            }else
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
            }
        }


        //////////////////////////////

        if ($envoie == 0) 
        {
            $pdf->AliasNbPages();
            return $pdf;
        } 
        else 
        {
            $pdf->Output('F', $filePath);
            return $pdf;
        }
    }


    /**
     * Undocumented function
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $releves
     * @param Student|null $student
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @param [type] $filePath
     * @return PDF
     */
    public function sendTranscriptStudentAnnual(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $releves, ?Student $student = null, ?Term $term = null, ?Sequence $sequence = null, $filePath = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

        $pdf->Ln(10);
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode($student ? "Nom de l'élève / Student's name : ".$student->getFullName(): ""), 0, 1, 'C');
        $pdf->Cell(0, 5, utf8_decode($student ? "NIU : ".$student->getRegistrationNumber(): ""), 0, 1, 'C');
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 10);
        $pdf->Cell(30, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(50, 5, utf8_decode('Enseignants / Teachers'), 1, 0, 'C', true);

        $pdf->Cell(15, 5, utf8_decode('Eval 1'), 1, 0, 'C', true);
        $pdf->Cell(15, 5, utf8_decode('Eval 2'), 1, 0, 'C', true);
        
        $pdf->Cell(15, 5, utf8_decode('Eval 3'), 1, 0, 'C', true);
        $pdf->Cell(15, 5, utf8_decode('Eval 4'), 1, 0, 'C', true);
        
        $pdf->Cell(15, 5, utf8_decode('Eval 5'), 1, 0, 'C', true);
        $pdf->Cell(15, 5, utf8_decode('Eval 6'), 1, 1, 'C', true);
            
        

        $i = 1;
        $pdf->SetFont('Times', '', 11);
        foreach ($releves['subjects'] as $releve) 
        {
            // dd($releve);
            if ($i % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }
            elseif ($i % 2 == 0)
            {
                $pdf->SetFillColor(224,235,255);
            }
            
            $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
            $pdf->SetFont('Times', '', 7);
            $pdf->Cell(30, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
            $pdf->Cell(50, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
            $pdf->SetFont('Times', '', 11);

            if (!$releve['eval1'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }
            
            if($releve['eval1'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval1'] ? $releve['eval1'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval2'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval2'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval2'] ? $releve['eval2'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval3'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval3'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval3'] ? $releve['eval3'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval4'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval4'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval4'] ? $releve['eval4'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval5'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval5'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval5'] ? $releve['eval5'] : "//"), 1, 0, 'C', true);
            }

            if (!$releve['eval6'] ) 
            {
                $pdf->SetFillColor(255,181,145);
            }

            if($releve['eval6'] == 0.1) 
            {
                $pdf->Cell(15, 5, utf8_decode("//"), 1, 1, 'C', true);

            }
            else
            {
                $pdf->Cell(15, 5, utf8_decode($releve['eval6'] ? $releve['eval6'] : "//"), 1, 1, 'C', true);
            }
            $i = $i + 1;
            
        }
        
        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;

        $pdf->SetFont('Times', 'B', 12);
        if($school->isPublic())
        {
            if($school->isLycee())
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                }
                else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                }
                
            }else
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                }
                
            }
        }else
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
            }else
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
            }
        }

        //////////////////////////////
        // Sauvegarder le PDF dans un fichier
        $pdf->Output('F', $filePath);

        return $pdf;
    }


    /**
     * releves de notes de la classe d'une séquence
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $relevesSequenceClasse
     * @param Student|null $student
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function printTranscriptClasseSequence(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $relevesSequenceClasse, Student $student = null, ?Term $term = null, ?Sequence $sequence = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();
        foreach ($relevesSequenceClasse as $releve) 
        {
            // dd($releve);
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

            $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

            $pdf->Ln(10);
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode($releve['studentName'] ? "Nom de l'élève / Student's name : ".$releve['studentName'] : ""), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode($releve['studentName'] ? "NIU : ".$releve['registrationNumber'] : ""), 0, 1, 'C');
            $pdf->Ln();
            
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode('Enseignant / Teacher'), 1, 0, 'C', true);
            $pdf->Cell(50, 5, utf8_decode('Notes / Mark'), 1, 1, 'C', true);

            $i = 1;
            $pdf->SetFont('Times', '', 11);
            foreach ($releve['subjects'] as $releve) 
            {
                if ($i % 2 == 1) 
                {
                    $pdf->SetFillColor(255,255,255);
                }
                elseif ($i % 2 == 0)
                {
                    $pdf->SetFillColor(224,235,255);
                }
                
                
                $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
                $pdf->Cell(60, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
                $pdf->SetFont('Times', '', 9);
                $pdf->Cell(60, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
                $pdf->SetFont('Times', '', 11);
                if (!$releve['mark']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }
                $pdf->Cell(50, 5, utf8_decode($releve['mark'] ? $releve['mark'] : "Pas de note / No Not"), 1, 1, 'C', true);

                $i = $i + 1;
            }
        }
        
        
        $pdf->Ln();
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

        $pdf->SetFont('Times', 'IB', 12);
        $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
        $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

        $pdf->Ln();
        $pdf->Ln();

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;

        $pdf->SetFont('Times', 'B', 12);
        if($school->isPublic())
        {
            if($school->isLycee())
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                }
                
            }else
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                }
                
            }
        }else
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
            }else
            {
                $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
            }
        }


        //////////////////////////////

        return $pdf;
    }


    /**
     * relevé de notes d'une classe d'un trimestre
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $relevesTermClasse
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function printTranscriptClasseTerm(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $relevesTermClasse, ?Term $term = null, ?Sequence $sequence = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;
        
        $pdf = new PDF();
        foreach ($relevesTermClasse as $releve) 
        {
            // dd($releve);
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

            $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

            $pdf->Ln(10);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode($releve['studentName'] ? "Nom de l'élève / Student's name : ".$releve['studentName'] : ""), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode($releve['studentName'] ? "NIU : ".$releve['registrationNumber'] : ""), 0, 1, 'C');
            $pdf->Ln();
            
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
            $pdf->Cell(60, 5, utf8_decode('Enseignant / Teacher'), 1, 0, 'C', true);

            switch ($term->getTerm()) 
            {
                case 1:
                    $pdf->Cell(25, 5, utf8_decode('Eval 1'), 1, 0, 'C', true);
                    $pdf->Cell(25, 5, utf8_decode('Eval 2'), 1, 1, 'C', true);
                    break;
                
                case 2:
                    $pdf->Cell(25, 5, utf8_decode('Eval 3'), 1, 0, 'C', true);
                    $pdf->Cell(25, 5, utf8_decode('Eval 4'), 1, 1, 'C', true);
                    break;

                case 3:
                    $pdf->Cell(25, 5, utf8_decode('Eval 5'), 1, 0, 'C', true);
                    $pdf->Cell(25, 5, utf8_decode('Eval 6'), 1, 1, 'C', true);
                    break;
            }
            
            $i = 1;
            $pdf->SetFont('Times', '', 11);
            foreach ($releve['subjects'] as $releve) 
            {
                if ($i % 2 == 1) 
                {
                    $pdf->SetFillColor(255,255,255);
                }
                elseif ($i % 2 == 0)
                {
                    $pdf->SetFillColor(224,235,255);
                }
                
                $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
                $pdf->Cell(60, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
                $pdf->SetFont('Times', '', 9);
                $pdf->Cell(60, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
                $pdf->SetFont('Times', '', 11);

                if (!$releve['evaluation1']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }

                if ($releve['evaluation1'] == 0.0) 
                {
                    $pdf->Cell(25, 5, utf8_decode("//"), 1, 0, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(25, 5, utf8_decode($releve['evaluation1'] ? $releve['evaluation1'] : "//"), 1, 0, 'C', true);
                }
                
                if (!$releve['evaluation2']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }

                if ($releve['evaluation2'] == 0.0) 
                {
                    $pdf->Cell(25, 5, utf8_decode("//"), 1, 1, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(25, 5, utf8_decode($releve['evaluation2'] ? $releve['evaluation2'] : "//"), 1, 1, 'C', true);
                }
                $i = $i + 1;
            }

            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
            $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

            $pdf->SetFont('Times', 'IB', 12);
            $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
            $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');

            $pdf->Ln();
            $pdf->Ln();

            $totalCellWidth = 90;
            $cellHeight = 3;
            $cellWidth = $totalCellWidth/3;

            $pdf->SetFont('Times', 'B', 12);
            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                    }else
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                    }
                    
                }else
                {
                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                    }else
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                    }
                    
                }
            }else
            {
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
                }
            }
           
        }
        
        


        //////////////////////////////

        return $pdf;
    }


    /**
     * releves annuel d'une classe
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param array $relevesTermClasse
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function printTranscriptClasseAnnual(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, array $relevesTermClasse, ?Term $term = null, ?Sequence $sequence = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();
        
        foreach ($relevesTermClasse as $releve) 
        {
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

            $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

            $pdf->Ln(10);
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode($releve['studentName'] ? "Nom de l'élève / Student's name : ".$releve['studentName'] : ""), 0, 1, 'C');
            $pdf->Cell(0, 5, utf8_decode($releve['studentName'] ? "NIU : ".$releve['registrationNumber'] : ""), 0, 1, 'C');
            $pdf->Ln();
            
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(15, 5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(30, 5, utf8_decode('Matières / Subjects'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(50, 5, utf8_decode('Enseignant / Teacher'), 1, 0, 'C', true);

            
            $pdf->Cell(15, 5, utf8_decode('Eval 1'), 1, 0, 'C', true);
            $pdf->Cell(15, 5, utf8_decode('Eval 2'), 1, 0, 'C', true);
            
            $pdf->Cell(15, 5, utf8_decode('Eval 3'), 1, 0, 'C', true);
            $pdf->Cell(15, 5, utf8_decode('Eval 4'), 1, 0, 'C', true);
            
            $pdf->Cell(15, 5, utf8_decode('Eval 5'), 1, 0, 'C', true);
            $pdf->Cell(15, 5, utf8_decode('Eval 6'), 1, 1, 'C', true);
        
            
            $i = 1;
            $pdf->SetFont('Times', '', 11);
            foreach ($releve['subjects'] as $releve) 
            {
                if ($i % 2 == 1) 
                {
                    $pdf->SetFillColor(255,255,255);
                }
                elseif ($i % 2 == 0)
                {
                    $pdf->SetFillColor(224,235,255);
                }
                
                
                $pdf->Cell(15, 5, utf8_decode($i), 1, 0, 'C', true);
                $pdf->SetFont('Times', '', 9);
                $pdf->Cell(30, 5, utf8_decode($releve['subjectName']), 1, 0, 'L', true);
                $pdf->SetFont('Times', '', 9);
                $pdf->Cell(50, 5, utf8_decode($releve['teacher']), 1, 0, 'L', true);
                $pdf->SetFont('Times', '', 11);
                
                if (!$releve['eval1']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }
                

                if ($releve['eval1'] == 0.1) 
                {
                    $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(15, 5, utf8_decode($releve['eval1'] ? $releve['eval1'] : "//"), 1, 0, 'C', true);
                }
                
                
                if (!$releve['eval2']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }

                if ($releve['eval2'] == 0.1) 
                {
                    $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(15, 5, utf8_decode($releve['eval2'] ? $releve['eval2'] : "//"), 1, 0, 'C', true);
                }

                if (!$releve['eval3']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }

                if ($releve['eval3'] == 0.1) 
                {
                    $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(15, 5, utf8_decode($releve['eval3'] ? $releve['eval3'] : "//"), 1, 0, 'C', true);
                }

                if (!$releve['eval4']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }

                if ($releve['eval4'] == 0.1) 
                {
                    $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(15, 5, utf8_decode($releve['eval4'] ? $releve['eval4'] : "//"), 1, 0, 'C', true);
                }

                if (!$releve['eval5']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }

                if ($releve['eval5'] == 0.1) 
                {
                    $pdf->Cell(15, 5, utf8_decode("//"), 1, 0, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(15, 5, utf8_decode($releve['eval5'] ? $releve['eval5'] : "//"), 1, 0, 'C', true);
                }

                if (!$releve['eval6']) 
                {
                    $pdf->SetFillColor(255,181,145);
                }

                if ($releve['eval6'] == 0.1) 
                {
                    $pdf->Cell(15, 5, utf8_decode("//"), 1, 1, 'C', true);
                } 
                else 
                {
                    $pdf->Cell(15, 5, utf8_decode($releve['eval6'] ? $releve['eval6'] : "//"), 1, 1, 'C', true);
                }
                $i = $i + 1;
            }

            $pdf->Ln();
            $pdf->Ln();
    
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
            $pdf->Cell(160, 5, utf8_decode('Fait à '.$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');
    
            $pdf->SetFont('Times', 'IB', 12);
            $pdf->Cell(5, 5, utf8_decode(''), 0, 0, 'L');
            $pdf->Cell(118, 5, utf8_decode('Done in '.$school->getPlace()), 0, 1, 'R');
    
            $pdf->Ln();
            $pdf->Ln();
    
            $totalCellWidth = 90;
            $cellHeight = 3;
            $cellWidth = $totalCellWidth/3;
    
            $pdf->SetFont('Times', 'B', 12);
            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Censeur"), 0, 1, 'R');
                    }else
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Vice Principal"), 0, 1, 'R');
                    }
                    
                }else
                {
                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Surveillant Général"), 0, 1, 'R');
                    }else
                    {
                        $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Attache Supervisor"), 0, 1, 'R');
                    }
                    
                }
            }else
            {
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("Le Principal"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+83, $cellHeight, utf8_decode("The Principal"), 0, 1, 'R');
                }
            }
        }
        
       


        //////////////////////////////

        return $pdf;
    }


    /**
     * Pas de notes pour imprimer lesrelevés
     *
     * @param SubSystem $subSystem
     * @param SchoolYear $schoolYear
     * @param School $school
     * @param Classroom $classroom
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function printTranscriptClasseTermEmpty(SubSystem $subSystem, SchoolYear $schoolYear, School $school, Classroom $classroom, ?Term $term = null, ?Sequence $sequence = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();
        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeader($pdf, $classroom, $term, $sequence);

        $pdf->Ln(10);
        $pdf->Ln();
        
        $pdf->SetFont('Times', 'B', 11);
        $pdf->Cell(0, 5, utf8_decode("PAS ENCORE EVALUE CE TRIMESTRE"), 0, 0, 'C');
        $pdf->Ln(10);
        $pdf->Cell(0, 5, utf8_decode('NOT YET EVALUATED THIS TERM'), 0, 0, 'C');

        
        //////////////////////////////

        return $pdf;
    }

    /**
     * Ente des pdfs
     *
     * @param PDF $pdf
     * @param Classroom $classroom
     * @param Term|null $term
     * @param Sequence|null $sequence
     * @return PDF
     */
    public function getHeader(PDF $pdf, Classroom $classroom, Term $term = null, Sequence $sequence = null): PDF
    {
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 5, utf8_decode('RELEVE DE NOTES'), 0, 2, 'C');
        $pdf->SetFont('Times', 'BI', 14);
        $pdf->Cell(0, 5, utf8_decode('TRANSCRIPT'), 0, 2, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);

        $pdf->Cell(100, 5, utf8_decode("Classe / Class : ".$classroom->getClassroom()."  ---  "), 0, 0, 'R');
        if ($sequence) 
        {
            $pdf->Cell(190, 5, utf8_decode("Evaluation : ".$sequence->getSequence()), 0, 0, 'L');
        } 
        elseif($term->getTerm() != 0)
        {
            $pdf->Cell(90, 5, utf8_decode("Trimestre / Term : ".$term->getTerm()), 0, 0, 'L');
        }
        elseif($term->getTerm() == 0)
        {
            $pdf->Cell(190, 5, utf8_decode("Annuel / Annual"), 0, 0, 'L');
        }

        return $pdf;
    }

}
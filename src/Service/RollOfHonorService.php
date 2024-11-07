<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\School;
use App\Entity\Classroom;
use App\Entity\SubSystem;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;

class RollOfHonorService
{
    public function __construct(protected GeneralService $generalService)
    {}

    /**
     * Imprime les tableaux d'honneur
     *
     * @param array $reports
     * @param School $school
     * @param Term $term
     * @param Classroom $classroom
     * @param SchoolYear $schoolYear
     * @param integer $numberOfStudents
     * @return FPDF
     */
    public function printRollOfHonor(array $reports, School $school, Term $term, Classroom $classroom, SchoolYear $schoolYear, int $numberOfStudents, SubSystem $subSystem): PDF
    {
        $pdf = new PDF();

        if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
        {
            if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
            {
                switch ($term->getTerm()) 
                {
                    case 1:
                        $termName = ' PREMIER TRIMESTRE ';
                    break;
                    
                    case 2:
                        $termName = ' DEUXIEME TRIMESTRE ';
                    break;

                    case 3:
                        $termName = ' TROISIEME TRIMESTRE ';
                    break;
                }
            }else
            {
                $termName = ' TOUTE ';
            }
        } else 
        {
            if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
            {
                switch ($term->getTerm()) 
                {
                    case 1:
                        $termName = ' FIRST TERM ';
                    break;
                    
                    case 2:
                        $termName = ' SECOND TERM ';
                    break;

                    case 3:
                        $termName = ' THIRD TERM ';
                    break;
                }
            }else
            {
                $termName = ' ALL ';
            }
        }
        
            

        $counter = 0;
        foreach($reports as $studentReport)
        {
            $pdf->AddPage('L');
            $pdf->SetFont('Times', '', 8);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $x0Logo = 95;
            $y0Logo = 14;
            $x0Filigrane = 70;
            $y0Filigrane = 55.5;

            ////cadre
            $pdf->Cell(25, 5, $pdf->Image('build/custom/images/th.jpg', $x0Filigrane-70, $y0Filigrane-55.5, 297, 210) , 0, 1, 'C', 0);

            //  filigrane
            // $pdf->Image('images/school/'.$school->getFiligree(), $x0Filigrane+18, $y0Filigrane+15, -100);

            $student = $studentReport->getStudent();
            $moyenne = $studentReport->getMoyenne();
            $encouragement = '';
            $congratulation = '';

            if($moyenne >= ConstantsClass::ENCOURAGEMENT)
            {
                $encouragement = 'X';
            }

            if($moyenne >= ConstantsClass::CONGRATULATION)
            {
                $congratulation = 'X';

            }

            // Entête des tableaux d'honneur

            $pdf->Cell(0, 3, "", 0, 1, 'C');
            $pdf->Cell(0, 3, "", 0, 1, 'C');
            $pdf->Cell(0, 3, "", 0, 1, 'C');
            $pdf->Cell(0, 3, "", 0, 1, 'C');

            // French and English Administrative Zone
            $pdf->Cell(20, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, $school->getFrenchCountry(), 0, 0, 'C');
            $pdf->Cell(100, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, $school->getEnglishCountry(), 0, 1, 'C');

            //////Devise
            $pdf->Cell(20, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, $school->getFrenchCountryMotto(), 0, 0, 'C');
            $pdf->Cell(100, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, $school->getEnglishCountryMotto(), 0, 1, 'C');

            //////etoiles
            $pdf->Cell(20, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 0, 'C');
            $pdf->Cell(100, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 1, 'C');

            ////Ministere
            $pdf->Cell(20, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, $school->getFrenchMinister(), 0, 0, 'C');
            $pdf->Cell(100, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3,  $school->getEnglishMinister(), 0, 1, 'C');

            //////etoiles
            $pdf->Cell(20, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 0, 'C');
            $pdf->Cell(100, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 1, 'C');

            /////etablissement
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(20, 3, "", 0, 0, 'C');
            if(strlen($school->getFrenchName()) > 35)
            {
                $pdf->SetFont('Times', 'B', 8);
                $pdf->Cell(70, 3, utf8_decode($school->getFrenchName()), 0, 0, 'C');
                $pdf->Cell(100, 3, "", 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 10);
            }else
            {
                $pdf->Cell(70, 3, utf8_decode($school->getFrenchName()), 0, 0, 'C');
                $pdf->Cell(100, 3, "", 0, 0, 'C');

            }
            
            if(strlen($school->getFrenchName()) > 35)
            {
                $pdf->SetFont('Times', 'B', 8);
                $pdf->Cell(70, 3, utf8_decode($school->getEnglishName()), 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 10);
            }else
            {
                $pdf->Cell(70, 3, utf8_decode($school->getEnglishName()), 0, 0, 'C');

            }
            $pdf->SetFont('Times', '', 7);
            $pdf->Ln();
            $pdf->Cell(20, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, utf8_decode($school->getFrenchMotto()), 0, 0, 'C');
            $pdf->Cell(100, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, utf8_decode($school->getEnglishMotto()), 0, 1, 'C');

            //////etoiles
            $pdf->Cell(20, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 0, 'C');
            $pdf->Cell(100, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 1, 'C');

           
            $pdf->SetFont('Times', 'B', 6);
            $pdf->Cell(20, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 3, 'BP : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 0, 'C');
            $pdf->Cell(100, 4, "", 0, 0, 'C');
            
            $pdf->Cell(70, 3, 'PO Box : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 1, 'C');

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 10);

            $pdf->Cell(20, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, utf8_decode('Année Scolaire : '.$schoolYear->getSchoolYear()), 0, 0, 'C');
            $pdf->Cell(100, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, 'School Year : '.$schoolYear->getSchoolYear(), 0, 1, 'C');
            $pdf->Ln();

            /* Logo de l'établissement*/

            $pdf->Image('images/school/'.$school->getLogo(), $x0Logo+35, $y0Logo+10, -110); 
            // $pdf->Image('images/school/logofiligrane.jpg', $x0Logo-5, $y0Logo+55, -90); 
            $pdf->Image('images/school/'.$school->getFiligree(), $x0Logo-5, $y0Logo+55, -90); 

            // contenu du tableau d'honneur
            $pdf->SetXY($x, $y);
            
            $pdf->Ln(60);
            $pdf->SetTextColor(170,0,0);
            $pdf->SetFont('Times', 'B', 25);

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(0, 5, "TABLEAU D'HONNEUR", 0, 0, 'C');
            }else
            {
                $pdf->Cell(0, 5, "ROLL OF HONNOR", 0, 0, 'C');
            }
            

            $pdf->Ln();
            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetTextColor(0);
            $pdf->SetFont('Times', 'BI', 14);

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(0, 5, utf8_decode("Le Conseil de classe en vertu des pouvoirs qui lui sont conférés, décerne ce tableau d'honneur"), 0, 0, 'C');
            }else
            {
                $pdf->Cell(0, 5, utf8_decode("The class council, by virtue of the powers conferred upon it, awards this honor roll"), 0, 0, 'C');
            }
            

            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 14);
            $pdf->SetFillColor(0,200,255);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(23, 7, utf8_decode("À l'élève "), 0, 0, 'L');
            }else
            {
                $pdf->Cell(25, 7, utf8_decode("At student "), 0, 0, 'L');
            }
            
            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(191, 7, utf8_decode($student->getFullName()), 1, 0, 'C', true);

            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(40, 5, utf8_decode('De la classe de : '), 0, 0, 'L');
            }else
            {
                $pdf->Cell(40, 5, utf8_decode('From the class of : '), 0, 0, 'L');
            }
            
            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(100, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'C');

            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln();

            $pdf->SetFont('Times', 'BI', 12);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                {
                    $pdf->Cell(115, 5, utf8_decode('Pour son travail et sa conduite durant le').utf8_decode($termName." de l'année scolaire ").$schoolYear->getSchoolYear(), 0, 0, 'L');
                }else
                {
                    $pdf->Cell(115, 5, utf8_decode("Pour son travail et sa conduite durant toute l'année scolaire").$schoolYear->getSchoolYear(), 0, 0, 'L');
                }

            }else
            {
                if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                {
                    $pdf->Cell(115, 5, utf8_decode('For his work and conduct during the ').utf8_decode($termName." of the school year").$schoolYear->getSchoolYear(), 0, 0, 'L');
                }else
                {
                    $pdf->Cell(115, 5, utf8_decode("For his work and conduct throughout the school year ").$schoolYear->getSchoolYear(), 0, 0, 'L');
                }

            }
            
            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('Times', 'BI', 16);
            $pdf->SetFillColor(0,200,255);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(50, 7, 'Moyenne obtenue ', 0, 0, 'L');
            }else
            {
                $pdf->Cell(50, 7, 'Average obtained ', 0, 0, 'L');
            }

            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(20, 7, $this->generalService->formatMark($moyenne), 'LBT', 0, 'C',true);
            $pdf->Cell(15, 7, ' / 20', 'RBT', 0, 'L');

            $pdf->SetFillColor(0,200,255);
            $pdf->Cell(10, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(20, 7, 'Rang ', 0, 0, 'L');
            }else
            {
                $pdf->Cell(20, 7, 'Rank ', 0, 0, 'L');
            }
            

            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(20, 7, utf8_decode($this->generalService->formatRank( $studentReport->getRang(), $student->getSex()->getSex())), 'LBT', 0, 'C',true);
            $pdf->Cell(15, 7, ' / '.$numberOfStudents, 'RBT', 0, 'L');

            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln(20);

            $pdf->SetTextColor(170,0,0);
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(20, 4, '', 0, 0, 'C');

            
            if($encouragement == 'X')
            {
                $pdf->SetFillColor(0,200,255);
                $pdf->Cell(5, 4, '', 1, 0, 'C', true);
                $pdf->SetFillColor(0);

            }else
            {
                $pdf->Cell(5, 4, '', 1, 0, 'C');
            }
            
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(42, 4, 'Avec Encouragements', 0, 0, 'L');
            }else
            {
                $pdf->Cell(42, 4, 'With Encouragement', 0, 0, 'L');
            }
            

            
            $pdf->Ln();
            $pdf->Cell(115, 3, '', 0, 0, 'C');
            
            $pdf->Ln();

            $pdf->SetTextColor(170,0,0);
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(20, 4, '', 0, 0, 'C');

            if($congratulation == 'X')
            {
                $pdf->SetFillColor(0,200,255);
                $pdf->Cell(5, 4, '', 1, 0, 'C', true);
                $pdf->SetFillColor(0);

            }else
            {
                $pdf->Cell(5, 4, '', 1, 0, 'C');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell(42, 4, utf8_decode('Avec Félicitations'), 0, 1, 'L');
            }else
            {
                $pdf->Cell(42, 4, utf8_decode('With Congratulations'), 0, 1, 'L');
            }
            


            $pdf->SetFont('Times', '', 12);
            $pdf->SetTextColor(0);
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(230, 3, utf8_decode($school->getPlace()).', le _ _ _ _ _ _ _', 0, 1, 'R');
            }else
            {
                $pdf->Cell(230, 3, utf8_decode($school->getPlace()).', On _ _ _ _ _ _ _', 0, 1, 'R');
            }
            

            $pdf->Ln();

            if ($school->isPublic()) 
            {
                if ($school->isLycee()) 
                {
                    if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
                    {
                        $pdf->Cell(204, 3, 'Le Proviseur', 0, 0, 'R');
                    } else 
                    {
                        $pdf->Cell(204, 3, 'The Principal', 0, 0, 'R');
                    }
                } else 
                {
                    if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
                    {
                        $pdf->Cell(204, 3, 'Le Directeur', 0, 0, 'R');
                    } else 
                    {
                        $pdf->Cell(204, 3, 'The Director', 0, 0, 'R');
                    }
                }
                
            } else 
            {
                if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
                {
                    $pdf->Cell(204, 3, 'Le Principal', 0, 0, 'R');
                } else {
                    $pdf->Cell(204, 3, 'The Principal', 0, 0, 'R');
                }
            }
            
            /*Photos*/
            $x0Photo = 168;
            $y0Photo = 61;
            $x0Encouragement = 21;
            $y0Encouragement = 107.5;
            $x0Felicitation = 21;
            $y0Felicitation = 113.5;

            if($student->getPhoto())
            {
                $pdf->Image('images/students/'.$student->getPhoto(), 220, 105, 50, 50);
            }else
            {
                if($student->getSex()->getSex() == 'F')
                {
                    $pdf->Image('images/students/fille.jpg', 220, 105, 50, 50);
                }
                else
                {
                    $pdf->Image('images/students/garcon.jpg', 220, 105, 50, 50);
                }
                
            }

            #qrCode du tableau d'honneur
            if($student->getQrCodeRollOfHonor())
            {
                $pdf->Image('images/qrCode/'.$student->getQrCodeRollOfHonor(), 55, 165, 30, 30);
            }

            /*Avec encouragements*/

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Text($x0Encouragement+10, $y0Encouragement+46, $encouragement);

            /*Avec felicitations*/
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Text($x0Felicitation+10, $y0Felicitation+47, $congratulation);

            
        }
       
        return $pdf;
        
    }

    
    /**
     * Retourne la position de l'élève dans le tableau des reports
     *
     * @param array $reports
     * @param integer $idS
     * @return integer
     */
    public function getStudentIndex(array $reports, int $idS): int
    {
        for($i = 0; $i < count($reports); $i++)
        {
            if($reports[$i]->getStudent()->getId() == $idS)
            {
                return $i;
            }
        }
    }
}
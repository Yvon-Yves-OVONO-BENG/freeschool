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
                        $termName = ' 1er TRIMESTRE ';
                    break;
                    
                    case 2:
                        $termName = ' 2ème TRIMESTRE ';
                    break;

                    case 3:
                        $termName = ' 3ème TRIMESTRE ';
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
                        $termName = ' 1st TERM ';
                    break;
                    
                    case 2:
                        $termName = ' 2nd TERM ';
                    break;

                    case 3:
                        $termName = ' 3rd TERM ';
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

            if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
            {
                $pdf->Cell(25, 5, $pdf->Image('build/custom/images/background.png', $x0Filigrane-70, $y0Filigrane-55.5, 297, 210) , 0, 1, 'C', 0);
            }
            else
                {
                $pdf->Cell(25, 5, $pdf->Image('build/custom/images/backgroundEn.png', $x0Filigrane-70, $y0Filigrane-55.5, 297, 210) , 0, 1, 'C', 0);

            }
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
            // $pdf->Cell(20, 3, "", 0, 0, 'C');
            // $pdf->Cell(70, 3, $school->getFrenchCountry(), 0, 0, 'C');
            // $pdf->Cell(100, 3, "", 0, 0, 'C');
            // $pdf->Cell(70, 3, $school->getEnglishCountry(), 0, 1, 'C');

            //////Devise
            // $pdf->Cell(20, 4, "", 0, 0, 'C');
            // $pdf->Cell(70, 4, $school->getFrenchCountryMotto(), 0, 0, 'C');
            // $pdf->Cell(100, 4, "", 0, 0, 'C');
            // $pdf->Cell(70, 4, $school->getEnglishCountryMotto(), 0, 1, 'C');

            //////etoiles
            // $pdf->Cell(20, 4, "", 0, 0, 'C');
            // $pdf->Cell(70, 4, "******", 0, 0, 'C');
            // $pdf->Cell(100, 4, "", 0, 0, 'C');
            // $pdf->Cell(70, 4, "******", 0, 1, 'C');

            // ////Ministere
            $pdf->Cell(20, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3, "", 0, 0, 'C');
            $pdf->Cell(65, 3, "", 0, 0, 'C');
            $pdf->Cell(70, 3,  "", 0, 1, 'C');

            //////etoiles
            $pdf->Cell(20, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "", 0, 0, 'C');
            $pdf->Cell(65, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "", 0, 1, 'C');
            $pdf->Ln(7);

            /////etablissement
            // Couleur verte principale
            $green = [27, 96, 43];

            // Couleur rouge
            $red = [210, 25, 35];

            $pdf->SetTextColor($green[0], $green[1], $green[2]);
            $pdf->SetFont('Times', 'B', 10);

            $pdf->Cell(35, 3, "", 0, 0, 'C');

            // Nom français de l'établissement
            if (strlen($school->getFrenchName()) > 35) {
                $pdf->SetFont('Times', 'B', 8);
                $pdf->Cell(
                    70,
                    3,
                    utf8_decode($school->getFrenchName()),
                    0,
                    0,
                    'C'
                );

                $pdf->Cell(80, 3, "", 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 10);
            } else {
                $pdf->Cell(
                    70,
                    3,
                    utf8_decode($school->getFrenchName()),
                    0,
                    0,
                    'C'
                );

                $pdf->Cell(80, 3, "", 0, 0, 'C');
            }

            // Nom anglais de l'établissement
            if (strlen($school->getEnglishName()) > 35) {
                $pdf->SetFont('Times', 'B', 8);
                $pdf->Cell(
                    70,
                    3,
                    utf8_decode($school->getEnglishName()),
                    0,
                    0,
                    'C'
                );

                $pdf->SetFont('Times', 'B', 10);
            } else {
                $pdf->Cell(
                    70,
                    3,
                    utf8_decode($school->getEnglishName()),
                    0,
                    0,
                    'C'
                );
            }

            $pdf->Ln();

            /*
            |--------------------------------------------------------------------------
            | DEVISES EN ROUGE
            |--------------------------------------------------------------------------
            */
            $pdf->SetTextColor($red[0], $red[1], $red[2]);
            $pdf->SetFont('Times', 'I', 7);

            $pdf->Cell(35, 3, "", 0, 0, 'C');

            $pdf->Cell(
                70,
                3,
                utf8_decode($school->getFrenchMotto()),
                0,
                0,
                'C'
            );

            $pdf->Cell(80, 3, "", 0, 0, 'C');

            $pdf->Cell(
                70,
                3,
                utf8_decode($school->getEnglishMotto()),
                0,
                1,
                'C'
            );

            /*
            |--------------------------------------------------------------------------
            | ÉTOILES
            |--------------------------------------------------------------------------
            */

            // Retour à la couleur verte
            $pdf->SetTextColor($green[0], $green[1], $green[2]);
            $pdf->SetFont('Times', '', 7);

            $pdf->Cell(35, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 0, 'C');
            $pdf->Cell(80, 4, "", 0, 0, 'C');
            $pdf->Cell(70, 4, "******", 0, 1, 'C');

            /*
            |--------------------------------------------------------------------------
            | BOÎTE POSTALE ET TÉLÉPHONE
            |--------------------------------------------------------------------------
            */

            $pdf->SetFont('Times', 'B', 7);

            $pdf->Cell(35, 4, "", 0, 0, 'C');

            $pdf->Cell(
                70,
                3,
                'BP : ' . utf8_decode($school->getPobox())
                . '  Tel : ' . $school->getTelephone(),
                0,
                0,
                'C'
            );

            $pdf->Cell(80, 4, "", 0, 0, 'C');

            $pdf->Cell(
                70,
                3,
                'PO Box : ' . utf8_decode($school->getPobox())
                . '  Tel : ' . $school->getTelephone(),
                0,
                1,
                'C'
            );

            /*
            |--------------------------------------------------------------------------
            | ANNÉE SCOLAIRE
            |--------------------------------------------------------------------------
            | Le libellé reste vert.
            | La valeur de l'année scolaire est affichée en rouge.
            |--------------------------------------------------------------------------
            */

            $pdf->SetFont('Times', 'B', 10);

            $schoolYearValue = $schoolYear->getSchoolYear();

            $frenchLabel = utf8_decode('Année Scolaire : ');
            $englishLabel = 'School Year : ';

            /**
             * Affiche un texte composé d'un libellé vert
             * et d'une valeur rouge, centré dans une zone.
             */
            $printColoredSchoolYear = function (
                $pdf,
                float $cellWidth,
                string $label,
                string $value,
                array $green,
                array $red
            ): void {
                $startX = $pdf->GetX();
                $startY = $pdf->GetY();

                $labelWidth = $pdf->GetStringWidth($label);
                $valueWidth = $pdf->GetStringWidth($value);
                $totalWidth = $labelWidth + $valueWidth;

                // Position pour centrer les deux textes dans la cellule
                $textX = $startX + (($cellWidth - $totalWidth) / 2);

                // Libellé en vert
                $pdf->SetXY($textX, $startY);
                $pdf->SetTextColor($green[0], $green[1], $green[2]);
                $pdf->Cell($labelWidth, 3, $label, 0, 0, 'L');

                // Année scolaire en rouge
                $pdf->SetTextColor($red[0], $red[1], $red[2]);
                $pdf->Cell($valueWidth, 3, $value, 0, 0, 'L');

                // Replacer le curseur à la fin de la zone
                $pdf->SetXY($startX + $cellWidth, $startY);
            };

            // Espace gauche
            $pdf->Cell(35, 3, "", 0, 0, 'C');

            // Année scolaire en français
            $printColoredSchoolYear(
                $pdf,
                70,
                $frenchLabel,
                $schoolYearValue,
                $green,
                $red
            );

            // Espace central
            $pdf->Cell(80, 3, "", 0, 0, 'C');

            // Année scolaire en anglais
            $printColoredSchoolYear(
                $pdf,
                70,
                $englishLabel,
                $schoolYearValue,
                $green,
                $red
            );

            // Passer à la ligne suivante
            $pdf->Ln(3);

            // Restaurer la couleur verte pour la suite du document
            $pdf->SetTextColor($green[0], $green[1], $green[2]);
            $pdf->Ln();

            /* Logo de l'établissement*/

            $pdf->Image('images/school/'.$school->getLogo(), $x0Logo+43, $y0Logo+5, -150); 
            // $pdf->Image('images/school/logofiligrane.jpg', $x0Logo-5, $y0Logo+55, -90); 
            // $pdf->Image('images/school/'.$school->getFiligree(), $x0Logo+3, $y0Logo+55, -120); 

            // contenu du tableau d'honneur
            $pdf->SetXY($x, $y);
            
            $pdf->Ln(60);
            $pdf->SetTextColor(170,0,0);
            $pdf->SetFont('Times', 'BI', 25);

            // if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            // {
            //     $pdf->Cell(0, 5, "", 0, 0, 'C');
            //     $pdf->Image('images/th-fr.png', $x0Logo-13, $y0Logo+45, -1100); 
            // }
            // else
            // {
            //     $pdf->Cell(0, 5, "", 0, 0, 'C');
            //     $pdf->Image('images/th-en.png', $x0Logo-13, $y0Logo+45, -1100); 
            // }
            

            $pdf->Ln();
            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetTextColor(0);
            $pdf->SetFont('Times', 'BI', 14);

            // if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            // {
            //     $pdf->Cell(0, 5, utf8_decode("Le Conseil de classe en vertu des pouvoirs qui lui sont conférés, décerne ce tableau d'honneur"), 0, 0, 'C');
            // }else
            // {
            //     $pdf->Cell(0, 5, utf8_decode("The class council, by virtue of the powers conferred upon it, awards this honor roll"), 0, 0, 'C');
            // }
            

            $pdf->Ln(19);

            $pdf->SetFont('Times', 'B', 14);
            $pdf->SetFillColor(255,255,255);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(23, 7, utf8_decode(""), 0, 0, 'L');
            }else
            {
                $pdf->Cell(25, 7, utf8_decode(""), 0, 0, 'L');
            }
            
            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(191, 7, utf8_decode($student->getFullName()), 0, 0, 'C', false);

            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln(7);

            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(40, 5, utf8_decode(''), 0, 0, 'L');
            }else
            {
                $pdf->Cell(40, 5, utf8_decode(' '), 0, 0, 'L');
            }
            
            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'C');

            $pdf->SetFont('Times', 'B', 16);
            $pdf->Cell(50, 5, utf8_decode(" "), 0, 0, 'C');
            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(50, 5, utf8_decode($student->getRegistrationNumber()), 0, 0, 'C');

            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln(7);

            $pdf->SetFont('Times', 'BI', 10);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                {
                    $pdf->Cell(95, 5, '', 0, 0, 'C');
                    $pdf->Cell(80, 5, utf8_decode($termName), 0, 0, 'L');
                    $pdf->Cell(15, 5, $schoolYear->getSchoolYear(), 0, 0, 'L');
                }else
                {
                    $pdf->Cell(115, 5, utf8_decode("Pour son travail et sa conduite durant toute l'année scolaire").$schoolYear->getSchoolYear(), 0, 0, 'L');
                }

            }else
            {
                if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                {
                    $pdf->Cell(95, 5, '', 0, 0, 'C');
                    $pdf->Cell(80, 5, utf8_decode($termName), 0, 0, 'L');
                    $pdf->Cell(15, 5, $schoolYear->getSchoolYear(), 0, 0, 'L');
                }else
                {
                    $pdf->Cell(115, 5, utf8_decode("For his work and conduct throughout the school year ").$schoolYear->getSchoolYear(), 0, 0, 'L');
                }

            }
            
            $pdf->Ln();
            $pdf->Ln(6);

            $pdf->SetFont('Times', 'BI', 16);
            $pdf->SetFillColor(0,200,255);
            $pdf->Cell(20, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(50, 7, '', 0, 0, 'L');
            }else
            {
                $pdf->Cell(50, 7, ' ', 0, 0, 'L');
            }

            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(20, 7, $this->generalService->formatMark($moyenne), 0, 0, 'C', false);
            $pdf->Cell(15, 7, ' ', 0, 0, 'L');

            $pdf->SetFillColor(0,200,255);
            $pdf->Cell(10, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(50, 7, ' ', 0, 0, 'L');
            }else
            {
                $pdf->Cell(50, 7, ' ', 0, 0, 'L');
            }
            

            $pdf->SetFont('Times', 'BI', 16);
            $pdf->Cell(20, 7, utf8_decode($this->generalService->formatRank( $studentReport->getRang(), $student->getSex()->getSex())), 0, 0, 'R',false);
            $pdf->Cell(15, 7, '  '.$numberOfStudents, 0, 0, 'L');

            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln(20);

            $pdf->SetTextColor(170,0,0);
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(20, 4, '', 0, 0, 'C');

            
            if($encouragement == 'X')
            {
                $pdf->SetFillColor(0,200,255);
                
                $pdf->SetFillColor(0);

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
                
                $pdf->SetFillColor(0);

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
                $pdf->Image('images/students/'.$student->getPhoto(), 237, 82, 40, 53);
            }else
            {
                if($student->getSex()->getSex() == 'F')
                {
                    $pdf->Image('images/students/fille.jpg', 237, 82, 40, 53);
                }
                else
                {
                    $pdf->Image('images/students/garcon.jpg', 237, 82, 40, 53);
                }
                
            }

            #qrCode du tableau d'honneur
            if($student->getQrCodeRollOfHonor())
            {
                $pdf->Image('images/qrcode/'.$student->getQrCodeRollOfHonor(), 35.5, 172, 22, 22);
            }

            /*Avec encouragements*/

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Text($x0Encouragement+15, $y0Encouragement+48, $encouragement);

            /*Avec felicitations*/
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Text($x0Felicitation+15, $y0Felicitation+51, $congratulation);

            
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
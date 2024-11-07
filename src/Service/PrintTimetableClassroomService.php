<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use App\Entity\EtatDepense;
use App\Entity\School;
use App\Entity\EtatFinance;
use App\Entity\SchoolYear;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;

class PrintTimetableClassroomService 
{
    public function __construct(
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
     * @param Array $timeTables
     * @return PDF
     */
    public function print(array $timeTables, School $school, SchoolYear $schoolYear, Classroom $classroom): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'L', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderEmploiDuTemps($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->getHeaderTitle($pdf);

        $largeur = 45;
        $hauteur = 8 ;
        $pdf->SetFont('Times', 'B', 18);
        $pdf->Cell(0, 5, utf8_decode("Classe / Classroom : ". $classroom->getClassroom()), 0, 0, 'C');
        $pdf->Ln();

        //////////////
        $lundi7H30 = "";
        $mardi7H30 = "";
        $mercredi7H30 = "";
        $jeudi7H30 = "";
        $vendredi7H30 = "";

        //////////////
        $lundi8H25 = "";
        $mardi8H25 = "";
        $mercredi8H25 = "";
        $jeudi8H25 = "";
        $vendredi8H25 = "";

        /////////////
        $lundi9H20 = "";
        $mardi9H20 = "";
        $mercredi9H20 = "";
        $jeudi9H20 = "";
        $vendredi9H20 = "";

        //////////
        $lundi10H30 = "";
        $mardi10H30 = "";
        $mercredi10H30 = "";
        $jeudi10H30 = "";
        $vendredi10H30 = "";

        //////////
        $lundi11H25 = "";
        $mardi11H25 = "";
        $mercredi11H25 = "";
        $jeudi11H25 = "";
        $vendredi11H25 = "";

        /////////
        $lundi12H20 = "";
        $mardi12H20 = "";
        $mercredi12H20 = "";
        $jeudi12H20 = "";
        $vendredi12H20 = "";

        //////
        $lundi13H40 = "";
        $mardi13H40 = "";
        $mercredi13H40 = "";
        $jeudi13H40 = "";
        $vendredi13H40 = "";


        ////////////
        $lundi14H35 = "";
        $mardi14H35 = "";
        $mercredi14H35 = "";
        $jeudi14H35 = "";
        $vendredi14H35 = "";


        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '07:30:00') 
            {
                $lundi7H30 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '07:30:00') 
            {
                $mardi7H30 = $timeTable;
                
            }
        }
        
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '07:30:00') 
            {
                $mercredi7H30 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '07:30:00') 
            {
                $jeudi7H30 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '07:30:00') 
            {
                $vendredi7H30 = $timeTable;
            }
                
        }
                

        ////////////
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '08:25:00') 
            {
                $lundi8H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '08:25:00') 
            {
                $mardi8H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '08:25:00') 
            {
                $mercredi8H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '08:25:00') 
            {
                $jeudi8H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '08:25:00') 
            {
                $vendredi8H25 = $timeTable;
            } 
        }

        ////////////
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '09:20:00') 
            {
                $lundi9H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '09:20:00') 
            {
                $mardi9H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '09:20:00') 
            {
                $mercredi9H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '09:20:00') 
            {
                $jeudi9H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '09:20:00') 
            {
                $vendredi9H20 = $timeTable;
            }
                
        }

        ////////////
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '10:30:00') 
            {
                $lundi10H30 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '10:30:00') 
            {
                $mardi10H30 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '10:30:00') 
            {
                $mercredi10H30 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '10:30:00') 
            {
                $jeudi10H30 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '10:30:00') 
            {
                $vendredi10H30 = $timeTable;
            }
                
        }

        ////////////
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '11:25:00') 
            {
                $lundi11H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '11:25:00') 
            {
                $mardi11H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '11:25:00') 
            {
                $mercredi11H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '11:25:00') 
            {
                $jeudi11H25 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '11:25:00') 
            {
                $vendredi11H25 = $timeTable;
            }
                
        }

        ////////////
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '12:20:00') 
            {
                $lundi12H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '12:20:00') 
            {
                $mardi12H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '12:20:00') 
            {
                $mercredi12H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '12:20:00') 
            {
                $jeudi12H20 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '12:20:00') 
            {
                $vendredi12H20 = $timeTable;
            }
                
        }

        ////////////
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '13:40:00') 
            {
                $lundi13H40 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '13:40:00') 
            {
                $mardi13H40 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '13:40:00') 
            {
                $mercredi13H40 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '13:40:00') 
            {
                $jeudi13H40 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '13:40:00') 
            {
                $vendredi13H40 = $timeTable;
            }
                
        }

        ////////////
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::LUNDI && $timeTable->getStartTime() == '14:35:00') 
            {
                $lundi14H35 = $timeTable;
            }
        }
        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MARDI && $timeTable->getStartTime() == '14:35:00') 
            {
                $mardi14H35 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::MERCREDI && $timeTable->getStartTime() == '14:35:00') 
            {
                $mercredi14H35 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::JEUDI && $timeTable->getStartTime() == '14:35:00') 
            {
                $jeudi14H35 = $timeTable;
            }
        }

        foreach ($timeTables as $timeTable) 
        {
            if ($timeTable->getDay()->getDay() == ConstantsClass::VENDREDI && $timeTable->getStartTime() == '14:35:00') 
            {
                $vendredi14H35 = $timeTable;
            }
                
        }


        $pdf->SetFont('Times', 'B', 9);
        $pdf->Cell(20, 5, utf8_decode(''), 0, 1, 'L');
        $pdf->Cell($largeur, $hauteur, utf8_decode('HORAIRES / SCHEDULE'),1, 0, 'C', true);
        $pdf->Cell($largeur, $hauteur, utf8_decode('LUNDI / MONDAY'),1, 0, 'C', true);
        $pdf->Cell($largeur, $hauteur, utf8_decode('MARDI / TUESDAY'),1, 0, 'C', true);
        $pdf->Cell($largeur, $hauteur, utf8_decode('MERCREDI / WEDNESDAY'),1, 0, 'C', true);
        $pdf->Cell($largeur, $hauteur, utf8_decode('JEUDI / THURSDAY'),1, 0, 'C', true);
        $pdf->Cell($largeur, $hauteur, utf8_decode('VENDREDI / FRIDAY'),1, 1, 'C', true);

        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('07h30-08h25'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);
        
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi7H30 ? $lundi7H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi7H30 ? $mardi7H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi7H30 ? $mercredi7H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi7H30 ? $jeudi7H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi7H30 ? $vendredi7H30->getSubject()->getSubject() : ""), 'R', 1, 'C');
        
        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi7H30 ? $lundi7H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi7H30 ? $mardi7H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi7H30 ? $mercredi7H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi7H30 ? $jeudi7H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi7H30 ? $vendredi7H30->getTeacher()->getFullName() : ""), 'R', 1, 'C');
                


        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('08h25-09h20'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);

        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi8H25 ? $lundi8H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi8H25 ? $mardi8H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi8H25 ? $mercredi8H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi8H25 ? $jeudi8H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi8H25 ? $vendredi8H25->getSubject()->getSubject() : ""), 'RT', 1, 'C');
        
        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi8H25 ? $lundi8H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi8H25 ? $mardi8H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi8H25 ? $mercredi8H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi8H25 ? $jeudi8H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi8H25 ? $vendredi8H25->getTeacher()->getFullName() : ""), 'R', 1, 'C');
        
        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('09h20-10h15'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);

        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi9H20 ? $lundi9H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi9H20 ? $mardi9H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi9H20 ? $mercredi9H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi9H20 ? $jeudi9H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi9H20 ? $vendredi9H20->getSubject()->getSubject() : ""), 'RT', 1, 'C');
        
        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi9H20 ? $lundi9H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi9H20 ? $mardi9H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi9H20 ? $mercredi9H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi9H20 ? $jeudi9H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi9H20 ? $vendredi9H20->getTeacher()->getFullName() : ""), 'R', 1, 'C');
        

        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('10h15-10h30'),1, 0, 'C', true);
        $pdf->Cell($largeur*5, $hauteur, utf8_decode('PETITE PAUSE / SMALL BRAK'),1, 1, 'C', true );

        ////////////
        $pdf->Cell($largeur, $hauteur, utf8_decode('10h30-11h25'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);

        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi10H30 ? $lundi10H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi10H30 ? $mardi10H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi10H30 ? $mercredi10H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi10H30 ? $jeudi10H30->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi10H30 ? $vendredi10H30->getSubject()->getSubject() : ""), 'R', 1, 'C');
       
        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi10H30 ? $lundi10H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi10H30 ? $mardi10H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi10H30 ? $mercredi10H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi10H30 ? $jeudi10H30->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi10H30 ? $vendredi10H30->getTeacher()->getFullName() : ""), 'R', 1, 'C');
       
        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('11h25-12h20'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);

        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi11H25 ? $lundi11H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi11H25 ? $mardi11H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi11H25 ? $mercredi11H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi11H25 ? $jeudi11H25->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi11H25 ? $vendredi11H25->getSubject()->getSubject() : ""), 'RT', 1, 'C');
        
        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi11H25 ? $lundi11H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi11H25 ? $mardi11H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi11H25 ? $mercredi11H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi11H25 ? $jeudi11H25->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi11H25 ? $vendredi11H25->getTeacher()->getFullName() : ""), 'R', 1, 'C');
        

        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('12h20-12h15'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);

        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi12H20 ? $lundi12H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi12H20 ? $mardi12H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi12H20 ? $mercredi12H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi12H20 ? $jeudi12H20->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi12H20 ? $vendredi12H20->getSubject()->getSubject() : ""), 'RT', 1, 'C');
        

        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi12H20 ? $lundi12H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi12H20 ? $mardi12H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi12H20 ? $mercredi12H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi12H20 ? $jeudi12H20->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi12H20 ? $vendredi12H20->getTeacher()->getFullName() : ""), 'R', 1, 'C');
        
        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('13h15-13h40'),1, 0, 'C', true);
        $pdf->Cell($largeur*5, $hauteur, utf8_decode('GRANDE PAUSE / BIG BREAK'),1, 1, 'C', true );

        ////////////
        $pdf->Cell($largeur, $hauteur, utf8_decode('13h40-14h35'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);

        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi13H40 ? $lundi13H40->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi13H40 ? $mardi13H40->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi13H40 ? $mercredi13H40->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi13H40 ? $jeudi13H40->getSubject()->getSubject() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi13H40 ? $vendredi13H40->getSubject()->getSubject() : ""), 'R', 1, 'C');
        
        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi13H40 ? $lundi13H40->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi13H40 ? $mardi13H40->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi13H40 ? $mercredi13H40->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi13H40 ? $jeudi13H40->getTeacher()->getFullName() : ""), 'R', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi13H40 ? $vendredi13H40->getTeacher()->getFullName() : ""), 'R', 1, 'C');
        

        ////////////
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell($largeur, $hauteur, utf8_decode('14h35-15h30'),1, 0, 'C', true);
        $pdf->SetFont('Times', 'B', 11);

        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi14H35 ? $lundi14H35->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi14H35 ? $mardi14H35->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi14H35 ? $mercredi14H35->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi14H35 ? $jeudi14H35->getSubject()->getSubject() : ""), 'RT', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi14H35 ? $vendredi14H35->getSubject()->getSubject() : ""), 'RT', 1, 'C');
        
        $pdf->SetX(55);
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($lundi14H35 ? $lundi14H35->getSubject()->getSubject() : ""), 'RB', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mardi14H35 ? $mardi14H35->getSubject()->getSubject() : ""), 'RB', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($mercredi14H35 ? $mercredi14H35->getSubject()->getSubject() : ""), 'RB', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($jeudi14H35 ? $jeudi14H35->getSubject()->getSubject() : ""), 'RB', 0, 'C');
        $pdf->Cell($largeur, $hauteur/2, utf8_decode($vendredi14H35 ? $vendredi14H35->getSubject()->getSubject() : ""), 'RBR', 1, 'C');
        


        $pdf->Cell(0, $hauteur-3, utf8_decode(''),0, 1, 'C');
        $pdf->SetFont('Times', '', 14);
        $pdf->Cell(190, $hauteur-3, utf8_decode(''),0, 0, 'C');
        $pdf->Cell($largeur+20, $hauteur-3, utf8_decode('Fait à Yaoundé, le ..................................'),0, 1, 'L');
        $pdf->Cell($largeur, $hauteur-3, utf8_decode(''),0, 1, 'C');
        $pdf->Cell(190, $hauteur-3, utf8_decode(''),0, 0, 'C');
        $pdf->Cell($largeur+20, $hauteur-3, utf8_decode('LE PROVISEUR'),0, 1, 'L');
        $pdf->Ln();


        return $pdf;
    }

    public function getHeaderTitle(PDF $pdf): PDF
    {
        $pdf->SetFont('Times', 'B', 20);
        $pdf->Cell(150, 7, utf8_decode('EMPLOI DU TEMPS / '), 0, 0, 'R');
        $pdf->SetFont('Times', 'BI', 20);
        $pdf->Cell(135, 7, utf8_decode('TIME TABLE'), 0, 1, 'L');

        return $pdf;
    }


}
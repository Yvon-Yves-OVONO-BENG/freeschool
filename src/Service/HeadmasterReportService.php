<?php

namespace App\Service;

use App\Entity\ConstantsClass;
use App\Entity\HeadmasterReport\StudentAgeRow;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\ReportElements\PDF;
use App\Entity\ReportElements\Pagination;
use App\Entity\SubSystem;
use App\Repository\StudentRepository;

class HeadmasterReportService
{
    public function __construct(
        protected GeneralService $generalService, 
        protected StudentRepository $studentRepository)
    {}

    public function printCountTeachers(array $departments, School $school, SchoolYear $schoolYear, SubSystem $subSystem): Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 7;

        $cellDepartment= 60;
        $cellEffectif= 30;

        $pdf = new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

        // entête de la fiche
        $pdf->SetFont('Times', 'B', $fontSize+2);

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('EFFECTIFS DES ENSEIGNANTS PAR DISCIPLINE'), 0, 1, 'C');
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode($school->getFrenchName()), 0, 1, 'C');

            // entête du tableau
            $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Départements'), 1, 0, 'C', true);
            $pdf->Cell($cellEffectif, $cellHeaderHeight2, utf8_decode('Effectifs'), 1, 0, 'C', true);
            $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Départements'), 1, 0, 'C', true);
            $pdf->Cell($cellEffectif, $cellHeaderHeight2, utf8_decode('Effectifs'), 1, 1, 'C', true);
        }else
        {
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('NUMBER OF TEACHERS BY DISCIPLINE'), 0, 1, 'C');
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode($school->getFrenchName()), 0, 1, 'C');

            // entête du tableau
            $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Departements'), 1, 0, 'C', true);
            $pdf->Cell($cellEffectif, $cellHeaderHeight2, utf8_decode('Effective'), 1, 0, 'C', true);
            $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Departements'), 1, 0, 'C', true);
            $pdf->Cell($cellEffectif, $cellHeaderHeight2, utf8_decode('Effective'), 1, 1, 'C', true);
        }
        

        $counter = 0;
        $effectif = 0;

        foreach($departments as $department)
        {   $effectif++;

            if (strlen($department->getDepartment()) > 23) 
            {
                $pdf->SetFont('Times', 'B', $fontSize-3);
            }
            else
            {
                $pdf->SetFont('Times', 'B', $fontSize+2);
            }

            $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode($department->getDepartment()), 1, 0, 'L');

            $pdf->Cell($cellEffectif, $cellHeaderHeight2, utf8_decode(count($department->getTeachers())), 1, 0, 'C');
            $counter++;
            if($counter%2 == 0)
            {
                $pdf->Ln();
            }
        }


        if($counter%2 == 0)
        {
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Effectif total'), 1, 0, 'L', true);
            }else
            {
                $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Effective total'), 1, 0, 'L', true);
            }
            $pdf->Cell($cellDepartment+$cellEffectif*2, $cellHeaderHeight2, utf8_decode($effectif), 1, 1, 'C', true);
            
        }else
        {
            $pdf->Cell($cellDepartment+$cellEffectif, $cellHeaderHeight2, '', 1, 1, 'C');

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Effectif total'), 1, 0, 'L', true);
            }else
            {
                $pdf->Cell($cellDepartment, $cellHeaderHeight2, utf8_decode('Effective total'), 1, 0, 'L', true);
            }

            $pdf->Cell($cellDepartment+$cellEffectif*2, $cellHeaderHeight2, utf8_decode($effectif), 1, 1, 'C', true);

        }

        $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);

        return $pdf;
    }

    /**
     * On compte les effectifs des élèves par tranche d'âge
     */
    public function getStudentsAge(array $classrooms, SchoolYear $schoolYear): array
    {
        $studentsAges = [];
        $students = [];
        $studentsAgesCycle1 = [];
        $studentsAgesCycle2 = [];

        foreach ($classrooms as $classroom) 
        {
            $studentAgeRow = new StudentAgeRow();
            $studentAgeRow->setClassroom($classroom);

            $students = $this->studentRepository->findBy([
                'classroom' => $classroom,
                'schoolYear' => $schoolYear
            ]);
            foreach ($students as $student) 
            {
                $studentAge = $this->generalService->getStudentAge($student);
                $sex = $student->getSex()->getSex();
                switch ($studentAge) 
                {
                    case 1:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setLessThanBoysAge10($studentAgeRow->getLessThanBoysAge10()+1);
                        }else
                        {
                            $studentAgeRow->setLessThanGirlsAge10($studentAgeRow->getLessThanGirlsAge10()+1);

                        }
                       
                        break;
                    
                    case 10:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge10($studentAgeRow->getBoysAge10()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge10($studentAgeRow->getGirlsAge10()+1);

                        }
                        break;
                    
                    case 11:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge11($studentAgeRow->getBoysAge11()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge11($studentAgeRow->getGirlsAge11()+1);

                        }
                        
                        break;
                    
                    case 12:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge12($studentAgeRow->getBoysAge12()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge12($studentAgeRow->getGirlsAge12()+1);

                        }
                        break;
            
                    case 13:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge13($studentAgeRow->getBoysAge13()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge13($studentAgeRow->getGirlsAge13()+1);

                        }
                        break;
                    
                    case 14:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge14($studentAgeRow->getBoysAge14()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge14($studentAgeRow->getGirlsAge14()+1);

                        }
                        break;
                    
                    case 15:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge15($studentAgeRow->getBoysAge15()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge15($studentAgeRow->getGirlsAge15()+1);

                        }
                        break;
                    
                    case 16:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge16($studentAgeRow->getBoysAge16()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge16($studentAgeRow->getGirlsAge16()+1);

                        }
                        break;

                    case 17:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge17($studentAgeRow->getBoysAge17()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge17($studentAgeRow->getGirlsAge17()+1);

                        }
                        break;
                    
                    case 18:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge18($studentAgeRow->getBoysAge18()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge18($studentAgeRow->getGirlsAge18()+1);

                        }
                        break;

                    case 19:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge19($studentAgeRow->getBoysAge19()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge19($studentAgeRow->getGirlsAge19()+1);

                        }
                        break;

                    case 20:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setBoysAge20($studentAgeRow->getBoysAge20()+1);
                        }else
                        {
                            $studentAgeRow->setGirlsAge20($studentAgeRow->getGirlsAge20()+1);

                        }
                        break;
                
                    case -1:
                        if($sex == 'M')
                        {
                            $studentAgeRow->setGreatherThanBoysAge20($studentAgeRow->getGreatherThanBoysAge20()+1);
                        }else
                        {
                            $studentAgeRow->setGreatherThanGirlsAge20($studentAgeRow->getGreatherThanGirlsAge20()+1);

                        }
                        break;
                }

            }

            if($classroom->getLevel()->getCycle()->getCycle() == 1)
            {
                $studentsAgesCycle1[] =  $studentAgeRow;
            }else
            {
                $studentsAgesCycle2[] =  $studentAgeRow;

            }
        }

        $studentsAges['cycle1'] = $studentsAgesCycle1;
        $studentsAges['cycle2'] = $studentsAgesCycle2;

        return $studentsAges;
    }

    public function printStudentsAge(array $studentsAges, School $school, SchoolYear $schoolYear, SubSystem $subSystem): Pagination
    {
        $fontSize = 10;

        $cellHeaderHeight2 = 8;
        $cellBodyHeight2 = 7;

        $cellClassroom = 30;
        $cellAge = 18;

        $pdf = new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService-> statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
        // Entête de la liste
        $pdf->SetFont('Times', 'B', $fontSize+2);
        $pdf->Cell(0, $cellHeaderHeight2, utf8_decode("EFFECTIFS DES ELEVES PAR TRANCHE D'ÂGES"), 0, 1, 'C');
        $pdf->Cell(0, $cellHeaderHeight2, utf8_decode($school->getFrenchName()), 0, 1, 'C');
        $pdf->Ln();
        $pdf->SetFont('Times', 'B', $fontSize);

        //  entete du tableau
        $pdf = $this->displayStudentAgeHeaderPagination($pdf, $cellClassroom, $cellBodyHeight2, $cellAge, $subSystem);
        

        // contenu du tableau cycle1
        $studentAgeRows1 = $studentsAges['cycle1'];
        $studentsAgeCycle1 = new StudentAgeRow();
        $studentsAgeCycle12 = new StudentAgeRow();
        foreach ($studentAgeRows1 as $studentAgeRow) 
        {
            $pdf = $this->displayAgeRowPagination($pdf, $cellClassroom, $cellBodyHeight2, $cellAge, $fontSize, $studentAgeRow);
            $studentsAgeCycle1 = $this->recapCycleAgeRow($studentsAgeCycle1, $studentAgeRow);
        }
        // recapitulatif cycle 1
        $pdf = $this->displayAgeRowPagination($pdf, $cellClassroom, $cellBodyHeight2, $cellAge, $fontSize, $studentsAgeCycle1, 'Totaux cycle 1', true);
        
        // Mise à jour total etablissemnt 
        $studentsAgeCycle12 = $this->recapCycleAgeRow($studentsAgeCycle12, $studentsAgeCycle1);
        
        // contenu du tableau cycle2
        $studentAgeRows2 = $studentsAges['cycle2'];
        $studentsAgeCycle2 = new StudentAgeRow();
        foreach ($studentAgeRows2 as $studentAgeRow) 
        {
            $pdf = $this->displayAgeRowPagination($pdf, $cellClassroom, $cellBodyHeight2, $cellAge, $fontSize, $studentAgeRow);
            $studentsAgeCycle2 = $this->recapCycleAgeRow($studentsAgeCycle2, $studentAgeRow);
        }
        // recapitulatif cycle 2
        $pdf = $this->displayAgeRowPagination($pdf, $cellClassroom, $cellBodyHeight2, $cellAge, $fontSize, $studentsAgeCycle2, 'Totaux cycle 2', true);

        // Mise à jour total etablissemnt 
        $studentsAgeCycle12 = $this->recapCycleAgeRow($studentsAgeCycle12, $studentsAgeCycle2);

        // total etablissement
        $pdf = $this->displayAgeRowPagination($pdf, $cellClassroom, $cellBodyHeight2, $cellAge, $fontSize, $studentsAgeCycle12, 'Totaux établissement', true);

        $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);

         return $pdf;
    }

    public function displayAgeRowPagination(Pagination $pdf, int $cellClassroom, int $cellBodyHeight2, int $cellAge, int $fontSize, StudentAgeRow $studentAgeRow, string $title = '', bool $fill = false): Pagination
    {
        if(!is_null($studentAgeRow->getClassroom()))
        {
            $classroomName = $studentAgeRow->getClassroom()->getClassroom();

            if(strlen($classroomName) <= 14)
            {
                $pdf->Cell($cellClassroom, $cellBodyHeight2, utf8_decode($classroomName), 1, 0, 'L', $fill);
            }elseif(strlen($classroomName) > 14  && strlen($classroomName) <= 16)
            {
                $pdf->SetFont('Times', 'B', $fontSize-1);
                $pdf->Cell($cellClassroom, $cellBodyHeight2, utf8_decode($classroomName), 1, 0, 'L', $fill);
                $pdf->SetFont('Times', 'B', $fontSize);

            }else
            {
                $pdf->SetFont('Times', 'B', $fontSize-3);
                $pdf->Cell($cellClassroom, $cellBodyHeight2, utf8_decode($classroomName), 1, 0, 'L', $fill);
                $pdf->SetFont('Times', 'B', $fontSize);
            }

        }else
        {
            if(strlen($title) > 17)
            {
                $pdf->SetFont('Times', 'B', $fontSize-1);
                $pdf->Cell($cellClassroom, $cellBodyHeight2, utf8_decode($title), 1, 0, 'L', $fill);
                $pdf->SetFont('Times', 'B', $fontSize);

            }else
            {
                $pdf->Cell($cellClassroom, $cellBodyHeight2, utf8_decode($title), 1, 0, 'L', $fill);

            }
        }


        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getLessThanBoysAge10()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getLessThanGirlsAge10()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getLessThanBoysAge10() + $studentAgeRow->getLessThanGirlsAge10()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge10()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge10()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge10() + $studentAgeRow->getGirlsAge10()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge11()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge11()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge11() + $studentAgeRow->getGirlsAge11()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge12()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge12()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge12() + $studentAgeRow->getGirlsAge12()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge13()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge13()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge13() + $studentAgeRow->getGirlsAge13()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge14()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge14()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge14() + $studentAgeRow->getGirlsAge14()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge15()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge15()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge15() + $studentAgeRow->getGirlsAge15()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge16()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge16()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge16() + $studentAgeRow->getGirlsAge16()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge17()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge17()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge17() + $studentAgeRow->getGirlsAge17()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge18()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge18()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge18() + $studentAgeRow->getGirlsAge18()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge19()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge19()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge19() + $studentAgeRow->getGirlsAge19()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge20()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGirlsAge20()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getBoysAge20() + $studentAgeRow->getGirlsAge20()), 1, 0, 'C', $fill);

        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGreatherThanBoysAge20()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGreatherThanGirlsAge20()), 1, 0, 'C', $fill);
        $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode($studentAgeRow->getGreatherThanBoysAge20() + $studentAgeRow->getGreatherThanGirlsAge20()), 1, 1, 'C', $fill);

        return $pdf;
    }

    public function recapCycleAgeRow(StudentAgeRow $studentsAgeCycle1, StudentAgeRow $studentAgeRow): StudentAgeRow
    {
        $studentsAgeCycle1->setLessThanBoysAge10($studentsAgeCycle1->getLessThanBoysAge10() +  $studentAgeRow->getLessThanBoysAge10());
        $studentsAgeCycle1->setBoysAge10($studentsAgeCycle1->getBoysAge10() +  $studentAgeRow->getBoysAge10());
        $studentsAgeCycle1->setBoysAge11($studentsAgeCycle1->getBoysAge11() +  $studentAgeRow->getBoysAge11());
        $studentsAgeCycle1->setBoysAge12($studentsAgeCycle1->getBoysAge12() +  $studentAgeRow->getBoysAge12());
        $studentsAgeCycle1->setBoysAge13($studentsAgeCycle1->getBoysAge13() +  $studentAgeRow->getBoysAge13());
        $studentsAgeCycle1->setBoysAge14($studentsAgeCycle1->getBoysAge14() +  $studentAgeRow->getBoysAge14());
        $studentsAgeCycle1->setBoysAge15($studentsAgeCycle1->getBoysAge15() +  $studentAgeRow->getBoysAge15());
        $studentsAgeCycle1->setBoysAge16($studentsAgeCycle1->getBoysAge16() +  $studentAgeRow->getBoysAge16());
        $studentsAgeCycle1->setBoysAge17($studentsAgeCycle1->getBoysAge17() +  $studentAgeRow->getBoysAge17());
        $studentsAgeCycle1->setBoysAge18($studentsAgeCycle1->getBoysAge18() +  $studentAgeRow->getBoysAge18());
        $studentsAgeCycle1->setBoysAge19($studentsAgeCycle1->getBoysAge19() +  $studentAgeRow->getBoysAge19());
        $studentsAgeCycle1->setGreatherThanBoysAge20($studentsAgeCycle1->getGreatherThanBoysAge20() +  $studentAgeRow->getGreatherThanBoysAge20());

        $studentsAgeCycle1->setLessThanGirlsAge10($studentsAgeCycle1->getLessThanGirlsAge10() +  $studentAgeRow->getLessThanGirlsAge10());
        $studentsAgeCycle1->setGirlsAge10($studentsAgeCycle1->getGirlsAge10() +  $studentAgeRow->getGirlsAge10());
        $studentsAgeCycle1->setGirlsAge11($studentsAgeCycle1->getGirlsAge11() +  $studentAgeRow->getGirlsAge11());
        $studentsAgeCycle1->setGirlsAge12($studentsAgeCycle1->getGirlsAge12() +  $studentAgeRow->getGirlsAge12());
        $studentsAgeCycle1->setGirlsAge13($studentsAgeCycle1->getGirlsAge13() +  $studentAgeRow->getGirlsAge13());
        $studentsAgeCycle1->setGirlsAge14($studentsAgeCycle1->getGirlsAge14() +  $studentAgeRow->getGirlsAge14());
        $studentsAgeCycle1->setGirlsAge15($studentsAgeCycle1->getGirlsAge15() +  $studentAgeRow->getGirlsAge15());
        $studentsAgeCycle1->setGirlsAge16($studentsAgeCycle1->getGirlsAge16() +  $studentAgeRow->getGirlsAge16());
        $studentsAgeCycle1->setGirlsAge17($studentsAgeCycle1->getGirlsAge17() +  $studentAgeRow->getGirlsAge17());
        $studentsAgeCycle1->setGirlsAge18($studentsAgeCycle1->getGirlsAge18() +  $studentAgeRow->getGirlsAge18());
        $studentsAgeCycle1->setGirlsAge19($studentsAgeCycle1->getGirlsAge19() +  $studentAgeRow->getGirlsAge19());
        $studentsAgeCycle1->setGreatherThanGirlsAge20($studentsAgeCycle1->getGreatherThanGirlsAge20() +  $studentAgeRow->getGreatherThanGirlsAge20());

        return $studentsAgeCycle1;
        
    }


    public function displayStudentAgeHeaderPagination(Pagination $pdf, int $cellClassroom, int $cellBodyHeight2, int $cellAge, SubSystem $subSystem): Pagination
    {
        $pdf->Cell($cellClassroom, $cellBodyHeight2*3, utf8_decode('Classes'), 1, 0, 'C', true);

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellAge*13, $cellBodyHeight2, utf8_decode('Âges (ans)'), 1, 1, 'C', true);
        }else
        {
            $pdf->Cell($cellAge*13, $cellBodyHeight2, utf8_decode('Ages (years)'), 1, 1, 'C', true);
        }
        

        $pdf->Cell($cellClassroom);
        $pdf->Cell($cellAge, $cellBodyHeight2, utf8_decode('< 10'), 1, 0, 'C', true);
        for ($i=10; $i <= 20; $i++) 
        { 
            $pdf->Cell($cellAge, $cellBodyHeight2, utf8_decode($i), 1, 0, 'C', true);
            
        }
        $pdf->Cell($cellAge, $cellBodyHeight2, utf8_decode('> 20'), 1, 1, 'C', true);

        $pdf->Cell($cellClassroom);
        for ($i=0; $i < 13; $i++) 
        { 
            $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode('G'), 1, 0, 'C', true);
            $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode('F'), 1, 0, 'C', true);
            $pdf->Cell($cellAge/3, $cellBodyHeight2, utf8_decode('T'), 1, 0, 'C', true);
        }
        $pdf->Ln();

        return $pdf;
    }
}
<?php

namespace App\Service;

use App\Entity\Duty;
use App\Entity\Lesson;
use App\Entity\School;
use App\Entity\Country;
use App\Entity\Teacher;
use App\Entity\Handicap;
use App\Entity\Movement;
use App\Entity\Classroom;
use App\Entity\SubSystem;
use App\Entity\Department;
use App\Entity\SchoolYear;
use App\Entity\EthnicGroup;
use App\Entity\HandicapType;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Entity\ReportElements\Pagination;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use App\Entity\ReportElements\StudentReport;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\RegisterAndListElements\MarkReportRow;
use App\Entity\RegisterAndListElements\AbsenceReportRow;
use App\Entity\RegisterAndListElements\MarkReportHeader;
use App\Entity\RegisterAndListElements\ResponsableStudent;
use App\Entity\RegisterAndListElements\AbsenceReportHeader;

class RegisterAndListService
{
    public function __construct(
        protected RequestStack $request,
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected DutyRepository $dutyRepository, 
        protected TeacherRepository $teacherRepository, 
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    /**
     * Retourne la liste des élèves par classe
     *
     * @param array $classrooms
     * @return array
     */
    public function getStudentList(array $classrooms, SchoolYear $schoolYear): array
    {
        $studentList = [];

        foreach($classrooms as $classroom)
        {
           
            $studentList[] = $this->studentRepository->findAllToDisplay($classroom, $schoolYear);
        }

        return $studentList;
    }


     /**
     * Retourne la liste des élèves de tout l'établissement du cycle 1
     *
     * @param School $school
     * @param SubSystem $subSystem
     * @return array
     */
    public function getAllStudentListCycle1(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        $studentListCycle1 = [];

        $studentListCycle1[] = $this->studentRepository->findToDisplayAllStudentCycle1($schoolYear, $subSystem);
        
        return $studentListCycle1;
    }

    /**
     * Retourne la liste des élèves de tout l'établissement du cycle 2
     *
     * @param School $school
     * @param SubSystem $subSystem
     * @return array
     */
    public function getAllStudentListCycle2(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        $studentListCycle2 = [];

        $studentListCycle2[] = $this->studentRepository->findToDisplayAllStudentCycle2($schoolYear, $subSystem);
        
        return $studentListCycle2;
    }


    /**
     * Imprime la liste des élèves par classe
     *
     * @param array $studentList
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    public function printStudentList(array $studentList, School $school, SchoolYear $schoolYear, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeaderHeight2 = 7;
            $cellBodyHeight2 = 6;

            $pdf = new Pagination();

            if(empty($studentList))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer la liste !"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("La classe sélectionné ne contient aucun élève."), 0, 1, 'C');

                return $pdf;
            }
            foreach($studentList as $students)
            {
                $numberOfStudents = count($students);

                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                
                // Entête de la liste
                $pdf = $this->getHeaderStudentListPagination($pdf, $school, $students[0]->getClassroom(), $subSystem);

                // entête du tableau
                $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                // contenu du tableau
                $pdf->SetFont('Times', '', $fontSize);
                $numero = 0;
                foreach($students as $student)
                {
                    $numero++;
                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(25, $cellBodyHeight2, $student->getRegistrationNumber(), 1, 0, 'L', true);
                    $pdf->SetFont('Times', '', $fontSize-1);
                    $pdf->Cell(80-5, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                    
                    if(strlen($student->getBirthplace())  > 17 )
                    {
                        $pdf->SetFont('Times', '', $fontSize-3);

                    }else
                    {
                        $pdf->SetFont('Times', '', $fontSize);
                    }

                    $pdf->Cell(57, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    
                    $pdf->SetFont('Times', '', $fontSize);

                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell(10, $cellBodyHeight2, $student->getSex()->getSex(), 1, 0, 'C', true);
                    if($student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $pdf->Cell(10, $cellBodyHeight2, "R", 1, 0, 'C', true);

                    }else
                    {
                        $pdf->Cell(10, $cellBodyHeight2, "N", 1, 0, 'C', true);
                    }
                    $pdf->Ln();

                    // if( ($numero % 30) == 0 && $numberOfStudents > 30) /*On passe à une nouvelle page après 30 lignes*/
                    // {
                        // On insère une page
                        // $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                        
                        // Administrative Header
                        // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                        // Entête de la liste
                        // $pdf = $this->getHeaderStudentListPagination($pdf, $school, $students[0]->getClassroom(), $subSystem);

                        // entête du tableau
                    //     $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                    //     $pdf->SetFont('Times', '', $fontSize);

                    // }
                }
                $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
                
            }
        }else
        {
            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeaderHeight2 = 7;
            $cellBodyHeight2 = 6;

            $pdf = new Pagination();

            if(empty($studentList))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Unable to print list !"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("The selected class does not contain any students."), 0, 1, 'C');

                return $pdf;
            }
            foreach($studentList as $students)
            {
                $numberOfStudents = count($students);

                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                
                // Entête de la liste
                $pdf = $this->getHeaderStudentListPagination($pdf, $school, $students[0]->getClassroom(), $subSystem);

                // entête du tableau
                $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                // contenu du tableau
                $pdf->SetFont('Times', '', $fontSize);
                $numero = 0;
                foreach($students as $student)
                {
                    $numero++;
                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(30, $cellBodyHeight2, $student->getRegistrationNumber(), 1, 0, 'L', true);
                    $pdf->SetFont('Times', '', $fontSize-1);
                    $pdf->Cell(80, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                    if(strlen($student->getBirthplace())  > 16 )
                    {
                        $pdf->SetFont('Times', '', $fontSize-3);

                    }else
                    {
                        $pdf->SetFont('Times', '', $fontSize-2);
                    }

                    $pdf->Cell(57, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    
                    
                    if($student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $sex = $student->getSex()->getSex().'**';

                    }else
                    {
                        $sex = $student->getSex()->getSex();
                    }
                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell(12, $cellBodyHeight2, $sex, 1, 0, 'C', true);
                    $pdf->Ln();

                    if( ($numero % 30) == 0 && $numberOfStudents > 30) /*On passe à une nouvelle page après 30 lignes*/
                    {
                        // On insère une page
                        $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                        
                        // Administrative Header
                        // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                        // Entête de la liste
                        // $pdf = $this->getHeaderStudentListPagination($pdf, $school, $students[0]->getClassroom(), $subSystem);

                        // entête du tableau
                        $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                        $pdf->SetFont('Times', '', $fontSize);

                    }
                }
                $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
            }
        }

        return $pdf;
    }


    /**
     * Imprime la liste des élèves de tout l'établissement
     *
     * @param array $studentListCycle1
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    public function printAllStudentList(array $studentListCycle1, array $studentListCycle2, School $school, SchoolYear $schoolYear, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeaderHeight2 = 7;
            $cellBodyHeight2 = 6;

            $pdf = new Pagination();
            
            if(empty($studentListCycle1))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer la liste !"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("Le cycle ne contient aucun élève."), 0, 1, 'C');

                return $pdf;
            }

            ///////VARIABLES DES ELEVES
            $nouveauxFilles1 = 0;
            $nouveauxGarcons1 = 0;

            $redoublantsFilles1 = 0;
            $redoublantsGarcons1 = 0;

            ///////VARIABLES DES ELEVES
            $nouveauxFilles2 = 0;
            $nouveauxGarcons2 = 0;

            $redoublantsFilles2 = 0;
            $redoublantsGarcons2 = 0;
            
            foreach($studentListCycle1 as $students)
            {
                $numberOfStudentsCycle1 = count($students);
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                
                foreach($students as $student)
                {
                    if($student->getSex()->getSex() == "F" && $student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $redoublantsFilles1 = $redoublantsFilles1 + 1 ;

                    }elseif($student->getSex()->getSex() == "F" && $student->getRepeater()->getRepeater() == 'Non')
                    {
                        $nouveauxFilles1 = $nouveauxFilles1 + 1;

                    }elseif($student->getSex()->getSex() == "M" && $student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $redoublantsGarcons1 = $redoublantsGarcons1 + 1 ;

                    }elseif($student->getSex()->getSex() == "M" && $student->getRepeater()->getRepeater() == 'Non')
                    {
                        $nouveauxGarcons1 = $nouveauxGarcons1 + 1;
                    }
                }

                // Entête de la liste
                $pdf->SetFont('Times', 'B', 14);
                $pdf->Cell(0, 5, "LISTE DES ELEVES DE L'ETABLISSEMENT", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(0, 5, "PREMIER CYCLE", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->SetFont('Times', 'BI', 12);
                $pdf->Cell(30, 5, "Profil du Cycle", 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Filles", 1, 0, 'C', true);
                $pdf->Cell(20, 5, utf8_decode("Garçons"), 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Total", 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Nouveaux", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles1, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxGarcons1, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxFilles1 + $nouveauxGarcons1, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Redoublants", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $redoublantsFilles1, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsGarcons1, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsFilles1 + $redoublantsGarcons1, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Effectif total", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles1 + $redoublantsFilles1 , 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxGarcons1 + $redoublantsGarcons1, 1, 0, 'C', true);
                $pdf->Cell(20, 5, $numberOfStudentsCycle1, 1, 1, 'C', true);

                $pdf->Ln(5);
                $pdf->SetFont('Times', 'B', 12);

                // entête du tableau
                $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                // contenu du tableau
                $pdf->SetFont('Times', '', $fontSize);
                $numero = 0;

                foreach($students as $student)
                {
                    $numero++;

                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else 
                    {
                        $pdf->SetFillColor(255,255,255);
                    }

                    $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(30, $cellBodyHeight2, $student->getRegistrationNumber(), 1, 0, 'L', true);
                    $pdf->Cell(80, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                    if(strlen($student->getBirthplace())  > 16 )
                    {
                        $pdf->SetFont('Times', '', $fontSize-3);

                    }else
                    {
                        $pdf->SetFont('Times', '', $fontSize);
                    }

                    $pdf->Cell(57, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    
                    if($student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $sex = $student->getSex()->getSex().'**';

                    }else
                    {
                        $sex = $student->getSex()->getSex();
                    }

                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell(12, $cellBodyHeight2, $sex, 1, 1, 'C', true);

                    
                    // $pdf->Ln();

                    // if( ($numero % 29) == 0) /*On passe à une nouvelle page après 30 lignes*/
                    // {
                    //     // On insère une page
                    //     $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                        
                    //     // Administrative Header
                    //     $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                    //     // Entête de la liste
                    //     // $pdf = $this->getHeaderStudentList($pdf, $school, $students[0]->getClassroom());

                    //     // entête du tableau
                    //     $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2, $subSystem);

                    //     $pdf->SetFont('Times', '', $fontSize);

                    // }
                }
                
                $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
            }

            if(empty($studentListCycle2))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer la liste !"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("Le cycle ne contient aucun élève."), 0, 1, 'C');

                return $pdf;
            }

            
            foreach($studentListCycle2 as $students)
            {
                $numberOfStudentsCycle2 = count($students);

                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                
                foreach($students as $student)
                {
                    if($student->getSex()->getSex() == "F" && $student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $redoublantsFilles2 = $redoublantsFilles2 + 1 ;

                    }elseif($student->getSex()->getSex() == "F" && $student->getRepeater()->getRepeater() == 'Non')
                    {
                        $nouveauxFilles2 = $nouveauxFilles2 + 1;

                    }elseif($student->getSex()->getSex() == "M" && $student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $redoublantsGarcons2 = $redoublantsGarcons2 + 1 ;

                    }elseif($student->getSex()->getSex() == "M" && $student->getRepeater()->getRepeater() == 'Non')
                    {
                        $nouveauxGarcons2 = $nouveauxGarcons2 + 1;
                    }
                }
                // Entête de la liste
                $pdf->SetFont('Times', 'B', 14);
                $pdf->Cell(0, 5, "LISTE DES ELEVES DE L'ETABLISSEMENT", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(0, 5, "SECOND CYCLE", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->SetFont('Times', 'BI', 12);
                $pdf->Cell(30, 5, "Profil du Cycle", 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Filles", 1, 0, 'C', true);
                $pdf->Cell(20, 5, utf8_decode("Garçons"), 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Total", 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Nouveaux", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles2, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxGarcons2, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxFilles2 + $nouveauxGarcons2, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Redoublants", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $redoublantsFilles2, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsGarcons2, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsFilles2 + $redoublantsGarcons2, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Effectif total", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles2 + $redoublantsFilles2 , 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxGarcons2 + $redoublantsGarcons2, 1, 0, 'C', true);
                $pdf->Cell(20, 5, $numberOfStudentsCycle2, 1, 1, 'C', true);
                $pdf->Ln(5);
                $pdf->SetFont('Times', 'B', 12);

                // entête du tableau
                $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                // contenu du tableau
                $pdf->SetFont('Times', '', $fontSize);
                $numero = 0;
                foreach($students as $student)
                {
                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $numero++;
                    $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(30, $cellBodyHeight2, $student->getRegistrationNumber(), 1, 0, 'L', true);
                    $pdf->Cell(80, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                    if(strlen($student->getBirthplace())  > 16 )
                    {
                        $pdf->SetFont('Times', '', $fontSize-3);

                    }else
                    {
                        $pdf->SetFont('Times', '', $fontSize);
                    }

                    $pdf->Cell(57, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    
                    if($student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $sex = $student->getSex()->getSex().'**';

                    }else
                    {
                        $sex = $student->getSex()->getSex();
                    }

                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell(12, $cellBodyHeight2, $sex, 1, 1, 'C', true);
                    
                    // $pdf->Ln();

                    // if( ($numero % 29) == 0 ) /*On passe à une nouvelle page après 30 lignes*/
                    // {
                    //     // On insère une page
                    //     $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                        
                    //     // Administrative Header
                    //     $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                    //     // Entête de la liste
                    //     // $pdf = $this->getHeaderStudentList($pdf, $school, $students[0]->getClassroom());

                    //     // entête du tableau
                    //     $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2, $subSystem);

                    //     $pdf->SetFont('Times', '', $fontSize);

                    // }
                }
                $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);

            }

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            

            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BI', 12);
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "Profil de l'Etablissement", 1, 0, 'C', true);
            $pdf->Cell(20, 5, "Filles", 1, 0, 'C', true);
            $pdf->Cell(20, 5, utf8_decode("Garçons"), 1, 0, 'C', true);
            $pdf->Cell(20, 5, "Total", 1, 1, 'C', true);

            ///////
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "Nouveaux", 1, 0, 'C', true);
            $pdf->Cell(20, 5, $nouveauxFilles1 + $nouveauxFilles2, 1, 0, 'C');
            $pdf->Cell(20, 5, $nouveauxGarcons1+ $nouveauxGarcons2, 1, 0, 'C');
            $pdf->Cell(20, 5, $nouveauxFilles1 + $nouveauxGarcons1 + $nouveauxFilles2 + $nouveauxGarcons2, 1, 1, 'C', true);

            ///////
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "Redoublants", 1, 0, 'C', true);
            $pdf->Cell(20, 5, $redoublantsFilles1 + $redoublantsFilles2, 1, 0, 'C');
            $pdf->Cell(20, 5, $redoublantsGarcons1 + $redoublantsGarcons2, 1, 0, 'C');
            $pdf->Cell(20, 5, $redoublantsFilles1 + $redoublantsGarcons1 + $redoublantsFilles2 + $redoublantsGarcons2, 1, 1, 'C', true);

            ///////
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "Effectif total", 1, 0, 'C', true);
            $pdf->Cell(20, 5, $nouveauxFilles1 + $redoublantsFilles1 + $nouveauxFilles2 + $redoublantsFilles2 , 1, 0, 'C', true);
            $pdf->Cell(20, 5, $nouveauxGarcons1 + $redoublantsGarcons1 + $nouveauxGarcons2 + $redoublantsGarcons2, 1, 0, 'C', true);
            $pdf->Cell(20, 5, $numberOfStudentsCycle1 + $numberOfStudentsCycle2, 1, 1, 'C', true);

            $pdf->Ln();
            $pdf->Ln();
            $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
        }else
        {
            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeaderHeight2 = 7;
            $cellBodyHeight2 = 6;

            $pdf = new Pagination();

            ///////VARIABLES DES ELEVES
            $nouveauxFilles1 = 0;
            $nouveauxGarcons1 = 0;

            $redoublantsFilles1 = 0;
            $redoublantsGarcons1 = 0;

            ///////VARIABLES DES ELEVES
            $nouveauxFilles2 = 0;
            $nouveauxGarcons2 = 0;

            $redoublantsFilles2 = 0;
            $redoublantsGarcons2 = 0;

            if(empty($studentListCycle1))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Unable to print list !"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("The cycle contains no students."), 0, 1, 'C');

                return $pdf;
            }

            
            foreach($studentListCycle1 as $students)
            {
                $numberOfStudentsCycle1 = count($students);
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                
                // Entête de la liste
                $pdf->SetFont('Times', 'B', 14);
                $pdf->Cell(0, 5, "LIST OF STUDENTS AT THE ESTABLISHMENT", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(0, 5, "FIRST CYCLE", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->SetFont('Times', 'BI', 12);
                $pdf->Cell(30, 5, "Profile of Cycle", 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Girls", 1, 0, 'C', true);
                $pdf->Cell(20, 5, utf8_decode("Boys"), 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Total", 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "News", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles1, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxGarcons1, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxFilles1 + $nouveauxGarcons1, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Repeaters", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $redoublantsFilles1, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsGarcons1, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsFilles1 + $redoublantsGarcons1, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Effective total", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles1 + $redoublantsFilles1 , 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxGarcons1 + $redoublantsGarcons1, 1, 0, 'C', true);
                $pdf->Cell(20, 5, $numberOfStudentsCycle1, 1, 1, 'C', true);
                $pdf->Ln(5);
                $pdf->SetFont('Times', 'B', 12);

                // entête du tableau
                $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                // contenu du tableau
                $pdf->SetFont('Times', '', $fontSize);
                $numero = 0;
                foreach($students as $student)
                {
                    $numero++;
                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(30, $cellBodyHeight2, $student->getRegistrationNumber(), 1, 0, 'L', true);
                    $pdf->Cell(80, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                    if(strlen($student->getBirthplace())  > 16 )
                    {
                        $pdf->SetFont('Times', '', $fontSize-3);

                    }else
                    {
                        $pdf->SetFont('Times', '', $fontSize);
                    }

                    $pdf->Cell(57, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    
                    if($student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $sex = $student->getSex()->getSex().'**';

                    }else
                    {
                        $sex = $student->getSex()->getSex();
                    }

                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell(12, $cellBodyHeight2, $sex, 1, 1, 'C', true);
                    
                    
                    $pdf->Ln();

                    // if( ($numero % 30) == 0) /*On passe à une nouvelle page après 30 lignes*/
                    // {
                        // On insère une page
                        // $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                        
                        // Administrative Header
                        // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                        // Entête de la liste
                        // $pdf = $this->getHeaderStudentList($pdf, $school, $students[0]->getClassroom());

                        // entête du tableau
                    //     $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                    //     $pdf->SetFont('Times', '', $fontSize);

                    // }
                }
                $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
            }

            if(empty($studentListCycle2))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Unable to print list!"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("The cycle contains no students."), 0, 1, 'C');

                return $pdf;
            }

            foreach($studentListCycle2 as $students)
            {
                $numberOfStudentsCycle2 = count($students);

                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                
                // Entête de la liste
                $pdf->SetFont('Times', 'B', 14);
                $pdf->Cell(0, 5, "LIST OF STUDENTS AT THE ESTABLISHMENT", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(0, 5, "SECOND CYCLE", 0, 1, 'C');
                $pdf->Ln(5);
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->SetFont('Times', 'BI', 12);
                $pdf->Cell(30, 5, "Profile of Cycle", 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Girls", 1, 0, 'C', true);
                $pdf->Cell(20, 5, utf8_decode("Boys"), 1, 0, 'C', true);
                $pdf->Cell(20, 5, "Total", 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "News", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles1, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxGarcons1, 1, 0, 'C');
                $pdf->Cell(20, 5, $nouveauxFilles1 + $nouveauxGarcons1, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Repeaters", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $redoublantsFilles1, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsGarcons1, 1, 0, 'C');
                $pdf->Cell(20, 5, $redoublantsFilles1 + $redoublantsGarcons1, 1, 1, 'C', true);

                ///////
                $pdf->Cell(50, 5, "", 0, 0, 'C');
                $pdf->Cell(30, 5, "Effective total", 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxFilles1 + $redoublantsFilles1 , 1, 0, 'C', true);
                $pdf->Cell(20, 5, $nouveauxGarcons1 + $redoublantsGarcons1, 1, 0, 'C', true);
                $pdf->Cell(20, 5, $numberOfStudentsCycle1, 1, 1, 'C', true);
                $pdf->Ln(5);
                $pdf->SetFont('Times', 'B', 12);

                // entête du tableau
                $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                // contenu du tableau
                $pdf->SetFont('Times', '', $fontSize);
                $numero = 0;
                foreach($students as $student)
                {
                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $numero++;
                    $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(30, $cellBodyHeight2, $student->getRegistrationNumber(), 1, 0, 'L', true);
                    $pdf->Cell(80, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                    if(strlen($student->getBirthplace())  > 16 )
                    {
                        $pdf->SetFont('Times', '', $fontSize-3);

                    }else
                    {
                        $pdf->SetFont('Times', '', $fontSize);
                    }

                    $pdf->Cell(57, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    
                    if($student->getRepeater()->getRepeater() == 'Oui')
                    {
                        $sex = $student->getSex()->getSex().'**';

                    }else
                    {
                        $sex = $student->getSex()->getSex();
                    }

                    $pdf->SetFont('Times', '', $fontSize);
                    $pdf->Cell(12, $cellBodyHeight2, $sex, 1, 1, 'C', true);
                    
                    $pdf->Ln();

                    // if( ($numero % 30) == 0 ) /*On passe à une nouvelle page après 30 lignes*/
                    // {
                        // On insère une page
                        // $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
                        
                        // Administrative Header
                        // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                        // Entête de la liste
                        // $pdf = $this->getHeaderStudentList($pdf, $school, $students[0]->getClassroom());

                        // entête du tableau
                    //     $pdf = $this->getTableHeaderStudentListPagination($pdf, $cellHeaderHeight2, $subSystem);

                    //     $pdf->SetFont('Times', '', $fontSize);

                    // }
                }
                $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
            }

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            

            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BI', 12);
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "Profil de l'Etablissement", 1, 0, 'C', true);
            $pdf->Cell(20, 5, "Filles", 1, 0, 'C', true);
            $pdf->Cell(20, 5, utf8_decode("Garçons"), 1, 0, 'C', true);
            $pdf->Cell(20, 5, "Total", 1, 1, 'C', true);

            ///////
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "News", 1, 0, 'C', true);
            $pdf->Cell(20, 5, $nouveauxFilles1 + $nouveauxFilles2, 1, 0, 'C');
            $pdf->Cell(20, 5, $nouveauxGarcons1+ $nouveauxGarcons2, 1, 0, 'C');
            $pdf->Cell(20, 5, $nouveauxFilles1 + $nouveauxGarcons1 + $nouveauxFilles2 + $nouveauxGarcons2, 1, 1, 'C', true);

            ///////
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "Repeaters", 1, 0, 'C', true);
            $pdf->Cell(20, 5, $redoublantsFilles1 + $redoublantsFilles2, 1, 0, 'C');
            $pdf->Cell(20, 5, $redoublantsGarcons1 + $redoublantsGarcons2, 1, 0, 'C');
            $pdf->Cell(20, 5, $redoublantsFilles1 + $redoublantsGarcons1 + $redoublantsFilles2 + $redoublantsGarcons2, 1, 1, 'C', true);

            ///////
            $pdf->Cell(40, 5, "", 0, 0, 'C');
            $pdf->Cell(50, 5, "Effective total", 1, 0, 'C', true);
            $pdf->Cell(20, 5, $nouveauxFilles1 + $redoublantsFilles1 + $nouveauxFilles2 + $redoublantsFilles2 , 1, 0, 'C', true);
            $pdf->Cell(20, 5, $nouveauxGarcons1 + $redoublantsGarcons1 + $nouveauxGarcons2 + $redoublantsGarcons2, 1, 0, 'C', true);
            $pdf->Cell(20, 5, $numberOfStudentsCycle1 + $numberOfStudentsCycle2, 1, 1, 'C', true);

            $pdf->Ln();
            $pdf->Ln();
            $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);

        }

        return $pdf;
    }

    /**
     * Entête de la fiche de la liste des élèves
     *
     * @param Pagination $pdf
     * @param School $school
     * @param Classroom $classroom
     * @return Pagination
     */
    public function getHeaderStudentListPagination(Pagination $pdf, School $school, Classroom $classroom, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'LISTE DES ELEVES', 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'C');
            $pdf->Cell(70, 5, 'Classe : '.utf8_decode($classroom->getClassroom()), 1, 0, 'C', true);
            $pdf->Cell(35, 5, '', 0, 0, 'C');

            $pdf->SetFont('Times', 'BI', 10);
            $pdf->Cell(35, 5, 'Profil de la classe ', 1, 0, 'C', true);
            $pdf->Cell(10, 5, "F", 1, 0, 'C', true);
            $pdf->Cell(10, 5, "G", 1, 0, 'C', true);
            $pdf->Cell(10, 5, "T", 1, 1, 'C', true);
            
            //$effectif = count($classroom->getStudents());
            $students = $classroom->getStudents();
            $effectif = 0;

            foreach($students as $student)
            {
                if($student->isSupprime() == 0)
                {
                    $effectif = $effectif + 1;
                }
            }


            $girls = 0;
            $boys = 0;

            $girlsRepeat = 0;
            $boysRepeat = 0;

            foreach($students as $student)
            {
                if($student->getSex()->getSex() == constantsClass::SEX_F && $student->isSupprime() == 0)
                {
                    $girls = $girls + 1;
                }
                elseif($student->getSex()->getSex() == constantsClass::SEX_M && $student->isSupprime() == 0)
                {
                    $boys = $boys + 1;
                }

                if($student->getSex()->getSex() == ConstantsClass::SEX_F && $student->getRepeater()->getRepeater() == ConstantsClass::REPEATER_YES && $student->isSupprime() == 0)
                {
                    $girlsRepeat = $girlsRepeat + 1;
                }
                elseif($student->getSex()->getSex() == ConstantsClass::SEX_M && $student->getRepeater()->getRepeater() == ConstantsClass::REPEATER_YES && $student->isSupprime() == 0)
                {
                    $boysRepeat = $boysRepeat + 1;
                }
            }

            $pdf->Cell(105, 5, '', 0, 0, 'C');
            $pdf->Cell(35, 5, 'Nouveaux', 1, 0, 'C', true);
            $pdf->Cell(10, 5, $girls - $girlsRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, $boys - $boysRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, ($girls - $girlsRepeat) + ($boys - $boysRepeat), 1, 1, 'C');

            $pdf->Cell(105, 5, '', 0, 0, 'C');
            $pdf->Cell(35, 5, 'Redoublants', 1, 0, 'C', true);
            $pdf->Cell(10, 5, $girlsRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, $boysRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, $girlsRepeat + $boysRepeat , 1, 1, 'C');

            $pdf->Cell(105, 5, '', 0, 0, 'C');
            $pdf->Cell(35, 5, 'Effectif total', 1, 0, 'C', true);
            $pdf->Cell(10, 5, $girls, 1, 0, 'C', true);
            $pdf->Cell(10, 5, $boys, 1, 0, 'C', true);
            $pdf->Cell(10, 5, $effectif, 1, 1, 'C', true);
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 12);
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'LIST OF STUDENTS', 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'C');
            $pdf->Cell(70, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 1, 0, 'C', true);
            $pdf->Cell(35, 5, '', 0, 0, 'C');

            $pdf->SetFont('Times', 'BI', 10);
            $pdf->Cell(35, 5, 'Class profile ', 1, 0, 'C', true);
            $pdf->Cell(10, 5, "G", 1, 0, 'C', true);
            $pdf->Cell(10, 5, "B", 1, 0, 'C', true);
            $pdf->Cell(10, 5, "T", 1, 1, 'C', true);
            
            //$effectif = count($classroom->getStudents());
            $students = $classroom->getStudents();

            $effectif = 0;

            foreach($students as $student)
            {
                if($student->isSupprime() == 0)
                {
                    $effectif = $effectif + 1;
                }
            }

            $girls = 0;
            $boys = 0;

            $girlsRepeat = 0;
            $boysRepeat = 0;

            foreach($students as $student)
            {
                if($student->getSex()->getSex() == constantsClass::SEX_F && $student->isSupprime() == 0)
                {
                    $girls = $girls + 1;
                }
                elseif($student->getSex()->getSex() == constantsClass::SEX_M && $student->isSupprime() == 0)
                {
                    $boys = $boys + 1;
                }

                if($student->getSex()->getSex() == ConstantsClass::SEX_F && $student->getRepeater()->getRepeater() == ConstantsClass::REPEATER_YES && $student->isSupprime() == 0)
                {
                    $girlsRepeat = $girlsRepeat + 1;
                }elseif($student->getSex()->getSex() == ConstantsClass::SEX_M && $student->getRepeater()->getRepeater() == ConstantsClass::REPEATER_YES && $student->isSupprime() == 0)
                {
                    $boysRepeat = $boysRepeat + 1;
                }
            }

            $pdf->Cell(105, 5, '', 0, 0, 'C');
            $pdf->Cell(35, 5, 'New', 1, 0, 'C', true);
            $pdf->Cell(10, 5, $girls - $girlsRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, $boys - $boysRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, ($girls - $girlsRepeat) + ($boys - $boysRepeat), 1, 1, 'C');

            $pdf->Cell(105, 5, '', 0, 0, 'C');
            $pdf->Cell(35, 5, 'Repeaters', 1, 0, 'C', true);
            $pdf->Cell(10, 5, $girlsRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, $boysRepeat, 1, 0, 'C');
            $pdf->Cell(10, 5, $girlsRepeat + $boysRepeat , 1, 1, 'C');

            $pdf->Cell(105, 5, '', 0, 0, 'C');
            $pdf->Cell(35, 5, 'Total workforce', 1, 0, 'C', true);
            $pdf->Cell(10, 5, $girls, 1, 0, 'C', true);
            $pdf->Cell(10, 5, $boys, 1, 0, 'C', true);
            $pdf->Cell(10, 5, $effectif, 1, 1, 'C', true);
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 12);
        }
        return $pdf;
    }


    /**
     * Entête du tableau de la liste des élèves
     *
     * @param Pagination $pdf
     * @param integer $cellHeaderHeight2
     * @return Pagination
     */
    public function getTableHeaderStudentListPagination(Pagination $pdf, int $cellHeaderHeight2, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, 'NIU', 1, 0, 'C', true);
            $pdf->Cell(80-5, $cellHeaderHeight2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Cell(57, $cellHeaderHeight2, 'Date et Lieu de Naissance', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeaderHeight2, 'Sexe', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeaderHeight2, 'Statut', 1, 0, 'C', true);
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, 'NIU', 1, 0, 'C', true);
            $pdf->Cell(80-5, $cellHeaderHeight2, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell(57, $cellHeaderHeight2, 'Date and place of birth', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeaderHeight2, 'Sex', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeaderHeight2, 'Status', 1, 0, 'C', true);
            $pdf->Ln();
        }
        return $pdf;
    }


    /**
     * Imprime le registre de reférence et le procès verbal des notes
     *
     * @param array $classroomReportsForRegister
     * @param School $school
     * @param SchoolYear $schoolYear
     * @param integer $numberOfLessons
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param array $sequence
     * @param integer $pv
     * @return Pagination
     */
    public function printReferenceRegister(array $classroomReportsForRegister, School $school, SchoolYear $schoolYear, string $firstPeriodLetter, int $idP, int $numberOfLessons = 0, int $pv = 0): Pagination
    {

        if($firstPeriodLetter === 't')
        {
            $termName = 'TRIMESTRE : '.$this->termRepository->find($idP)->getTerm();

        }elseif($firstPeriodLetter === 's')
        {
            $termName = 'EVALUATION : '.$this->sequenceRepository->find($idP)->getSequence();
        }else
        {
            $termName = 'ANNUEL';

        }

        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellHeaderHeight1 = 27;
        $cellDecisionWidth = 27;
        $cellStudentNameWidth = 60;
        $cellNumberWidth = 6;
        $cellSubjectWidth = $cellStudentNameWidth+$cellNumberWidth;

        $cellHeight2 = 5*18/30;

        $pdf = new Pagination();

        if(empty($classroomReportsForRegister))
        {
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            $pdf->SetFont('Times', '', 20);
            $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que chaque classe contienne au moins un élève'), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que toutes les notes soient saisies'), 0, 1, 'C');

            return $pdf;
        }


        foreach($classroomReportsForRegister as $reports)
        {
            $firstReport =  $reports[0];

            // on recupere le nombre de lessons
            $numberOfLessons = $firstReport->getNumberOfLessons();

            // on calcule la largeur des cellule
            if(!$pv)
            {
                //////Registre de reference
                $cellHeaderWidth1 = (292-($cellSubjectWidth+$cellDecisionWidth))/($numberOfLessons+3);

            }else
            {
                /////proces verbal
                $cellHeaderWidth1 = (282-$cellSubjectWidth)/$numberOfLessons;

            }

            // on fixe la taille de la police en fnction du nombre de lessons
            if($numberOfLessons >= 15)
            {
                $size = 9;

            }elseif($numberOfLessons < 15 && $numberOfLessons >= 12)
            {
                $size = 10;

            } else
            {
                $size = 11;

            }
                
            // Oninsère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            // Administrative zone
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);

            // Entête de la fiche
            $pdf = $this->getReferenceRegisterSlipHeaderPagination($pdf, $termName, $school,  $firstReport, $cellSubjectWidth, $cellHeaderHeight1, $cellHeaderWidth1, $pv,  $firstPeriodLetter, $cellDecisionWidth, $cellHeight2, $cellNumberWidth, $cellStudentNameWidth);

            if(!empty($firstReport->getReportBody()->getRowsGroup1()))
            {
                // contenu du tableau
                $numero = 0;
                $numberOfStudents = count($reports);
                $girls = 0;
                $boys = 0;
                $notesGirls = 0;
                $notesBoys = 0;

                foreach($reports as $report)
                {
                    $pdf->SetFont('Times', 'B', 6);
                    $pdf->Cell($cellNumberWidth, $cellHeight2, $numero+1, 1, 0, 'C');
                    $pdf->Cell($cellStudentNameWidth, $cellHeight2, utf8_decode(substr($report->getReportHeader()->getStudent()->getFullName(), 0, 28)), 1, 0, 'L');

                    $pdf->SetFont('Times', 'B', $size-1);

                    // note des matières du groupe 1
                    $pdf =  $this->displayAverageRowPagination($pdf, $report->getReportBody()->getRowsGroup1(), $cellHeaderWidth1, $cellHeight2);

                    // note des matières du groupe 2
                    $pdf =  $this->displayAverageRowPagination($pdf, $report->getReportBody()->getRowsGroup2(), $cellHeaderWidth1, $cellHeight2);

                    // note des matières du groupe 3
                    $pdf =  $this->displayAverageRowPagination($pdf, $report->getReportBody()->getRowsGroup3(), $cellHeaderWidth1, $cellHeight2);

                    // Totaux et decision
                    $pdf = $this->displayTotalAndDesicionPagination($pdf, $pv, $cellHeaderWidth1, $cellHeight2, $report, $cellDecisionWidth, $firstPeriodLetter);

                    $numero++;

                    // if(($numero % 30 == 0) && $numberOfStudents > 30)
                    // {
                        // On insère une page
                        // $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                        // Adminstrative zone
                        // $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school,  $schoolYear);

                        //    entête de la fiche
                        // $pdf = $this->getReferenceRegisterSlipHeaderPagination($pdf, $termName, $school,  $firstReport, $cellSubjectWidth, $cellHeaderHeight1, $cellHeaderWidth1, $pv,  $firstPeriodLetter, $cellDecisionWidth, $cellHeight2, $cellNumberWidth, $cellStudentNameWidth);
                    // }

                    ////////////////CALCUL DES EFFECTIFS
                    if ($report->getReportHeader()->getStudent()->getSex()->getSex() == "F") 
                    {
                       $girls = $girls + 1;
                    }
                    elseif($report->getReportHeader()->getStudent()->getSex()->getSex() == "M")
                    {
                        $boys = $boys + 1;
                    }

                    ////////////////NOTES DES ELEVES
                    if ($report->getReportHeader()->getStudent()->getSex()->getSex() == "F" && $report->getReportFooter()->getStudentResult()->getMoyenne() >= 10 ) 
                    {
                       $notesGirls = $notesGirls + 1;
                    }
                    elseif($report->getReportHeader()->getStudent()->getSex()->getSex() == "M" && $report->getReportFooter()->getStudentResult()->getMoyenne() >= 10)
                    {
                        $notesBoys = $notesBoys + 1;
                    }
                    
                }

                // Oninsère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                if($pv == 0)
                {
                    $mySession = $this->request->getSession();
                    if($mySession)
                    {
                        $subSystem = $mySession->get('subSystem');

                    }

                    $pdf->Ln(10);
                    $pdf->SetFont('Arial', 'BIU', 18);

                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell(0, $cellHeight2+5, "PROFIL DE LA CLASSE", 0, 1, 'C');
                    }else
                    {
                        $pdf->Cell(0, $cellHeight2+5, "CLASS PROFILE", 0, 1, 'C');
                    }
                    
                    $pdf->Ln(5);

                    $pdf->SetX(23);
                    $pdf->SetFont('Arial', 'B', 18);
                    $pdf->Cell(90, $cellHeight2+5, "", 0, 0, 'C');

                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell(50, $cellHeight2+5, "Filles", 1, 0, 'C', true);
                        $pdf->Cell(50, $cellHeight2+5, utf8_decode("Garçons"), 1, 0, 'C', true);
                    }else
                    {
                        $pdf->Cell(50, $cellHeight2+5, "Girls", 1, 0, 'C', true);
                        $pdf->Cell(50, $cellHeight2+5, utf8_decode("Boys"), 1, 0, 'C', true);
                    }
                    $pdf->Cell(50, $cellHeight2+5, "Total", 1, 0, 'C', true);

                    $pdf->Cell(50, $cellHeight2+5, "", 0, 1, 'C');

                    $pdf->SetX(23);
                    $pdf->SetFont('Arial', 'B', 18);

                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell(90, $cellHeight2+5, "EFFECTIF", 1, 0, 'C', true);
                    }else
                    {
                        $pdf->Cell(90, $cellHeight2+5, "EFFECTIVE", 1, 0, 'C', true);
                    }
                    $pdf->SetFont('Arial', '', 18);
                    $pdf->Cell(50, $cellHeight2+5, $girls, 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, $boys, 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, $girls + $boys, 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, "", 0, 1, 'C');

                    $pdf->SetX(23);
                    $pdf->SetFont('Arial', 'B', 18);
                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell(90, $cellHeight2+5, "Moyenne >= 10/20", 1, 0, 'C', true);
                    }else
                    {
                        $pdf->Cell(90, $cellHeight2+5, "Average >= 10/20", 1, 0, 'C', true);
                    }
                    $pdf->SetFont('Arial', '', 18);
                    $pdf->Cell(50, $cellHeight2+5, $notesGirls, 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, $notesBoys, 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, $notesGirls + $notesBoys, 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, "", 0, 1, 'C');

                    //////////////POURCENTAGE
                    if ($girls != 0) 
                    {
                        $percentGirls = ($notesGirls / $girls) * 100 ;
                    } else 
                    {
                        $percentGirls = 0 ;
                    }
                    
                    if ($boys != 0) 
                    {
                        $percentBoys = ($notesBoys / $boys) * 100;
                    } else 
                    {
                        $percentBoys = 0;
                    }

                    if ($girls != 0 && $boys != 0) 
                    {
                        $totalPercent = ($percentGirls + $percentBoys) / 2;
                    } elseif($girls != 0 && $boys == 0) 
                    {
                        $totalPercent = $percentGirls ;
                    }elseif($girls == 0 && $boys != 0) 
                    {
                        $totalPercent = $percentBoys ;
                    }
                    
                    $pdf->SetX(23);
                    $pdf->SetFont('Arial', 'B', 18);

                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell(90, $cellHeight2+5, utf8_decode("Taux de réussite"), 1, 0, 'C', true);
                    }else
                    {
                        $pdf->Cell(90, $cellHeight2+5, "Success rate", 1, 0, 'C', true);
                    }
                    $pdf->SetFont('Arial', '', 18);
                    $pdf->Cell(50, $cellHeight2+5, $this->generalService->formatMark($percentGirls)." %", 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, $this->generalService->formatMark($percentBoys)." %", 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, $this->generalService->formatMark($totalPercent)." %", 1, 0, 'C');
                    $pdf->Cell(50, $cellHeight2+5, "", 0, 1, 'C');
                    $pdf->Ln(10);

                    $pdf->SetX(23);
                    $pdf->SetFont('Arial', 'B', 18);
                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell(0, $cellHeight2+5, utf8_decode("Fait à ").utf8_decode($school->getPlace())." le, _ _ _ _ _ _ _ _ _  ", 0, 1, 'R');
                    }else
                    {
                        $pdf->Cell(0, $cellHeight2+5, utf8_decode("Done at ").utf8_decode($school->getPlace())." on, _ _ _ _ _ _ _ _ _  ", 0, 1, 'R');
                    }
                    $pdf->SetX(23);

                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        if($school->isPublic())
                        {
                            if($school->isLycee())
                            {
                                $pdf->Cell(130, $cellHeight2+5, "Le Professeur Principal", 0, 0, 'L');
                                $pdf->Cell(90, $cellHeight2+5, "Le Censeur", 0, 0, 'C');
                            }else
                            {
                                $pdf->Cell(130, $cellHeight2+5, "Le Professeur Principal", 0, 0, 'L');
                                $pdf->Cell(90, $cellHeight2+5, "Le Surveillant", 0, 0, 'C');
                            }
                        }else
                        {
                            $pdf->Cell(130, $cellHeight2+5, "Le Professeur Principal", 0, 0, 'L');
                            $pdf->Cell(90, $cellHeight2+5, utf8_decode("Le Préfet des études"), 0, 0, 'C');
                        }
                    }else
                    {
                        if($school->isPublic())
                    {
                        if($school->isLycee())
                        {
                            $pdf->Cell(130, $cellHeight2+5, "The Head Teacher", 0, 0, 'L');
                            $pdf->Cell(90, $cellHeight2+5, "The Censor", 0, 0, 'C');
                        }else
                        {
                            $pdf->Cell(130, $cellHeight2+5, "The Head Teacher", 0, 0, 'L');
                            $pdf->Cell(90, $cellHeight2+5, "The Supervisor", 0, 0, 'C');
                        }
                    }else
                    {
                        $pdf->Cell(130, $cellHeight2+5, "The Head Teacher", 0, 0, 'L');
                        $pdf->Cell(90, $cellHeight2+5, "The Prefect of Studies", 0, 0, 'C');
                    }
                    }
                }

            }else
            {
                $mySession = $this->request->getSession();
                if($mySession)
                {
                    $subSystem = $mySession->get('subSystem');

                }
                $pdf->Ln(5);
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    if($pv == 0)
                    {
                        $title = 'REGISTRE DE REFERENCE';
                    }else
                    {
                        $title = 'PROCES VERBAL DES NOTES';
                    }

                    $pdf->SetFont('Times', 'B', 15);
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "IMPOSSIBLE D'IMPRIMER LE ".$title." DE LA CLASSE DE ".utf8_decode($firstReport->getReportHeader()->getClassroom()->getClassroom()), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "CAR IL Y'A ENCORE DES NOTES NON SAISIES DANS CETTE CLASSE", 0, 0, 'C');
                }else
                {
                    if($pv == 0)
                    {
                        $title = 'REFERENCE REGISTER';
                    }else
                    {
                        $title = 'MINUTES OF NOTES';
                    }

                    $pdf->SetFont('Times', 'B', 15);
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, 'Unable to print the '.$title.' from the classe of '.utf8_decode($firstReport->getReportHeader()->getClassroom()->getClassroom()), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, 'BECAUSE THERE ARE STILL NOTES NOT ENTERED IN THIS CLASS', 0, 0, 'C');
                }

            }
                
        }

        return $pdf;
    }

    /**
     * Construit l'entete du registre de reférence
     *
     * @param Pagination $pdf
     * @param string $termName
     * @param School $school
     * @param StudentReport $firstReport
     * @param integer $cellSubjectWidth
     * @param integer $cellHeaderHeight1
     * @param integer $cellHeaderWidth1
     * @param integer $pv
     * @param string $firstPeriodLetter
     * @param integer $cellDecisionWidth
     * @param integer $cellHeight2
     * @param integer $cellNumberWidth
     * @param integer $cellStudentNameWidth
     * @return Pagination
     */
    public function getReferenceRegisterSlipHeaderPagination(Pagination $pdf, string $termName, School $school, StudentReport $firstReport, int $cellSubjectWidth, int $cellHeaderHeight1, int $cellHeaderWidth1, int $pv, string $firstPeriodLetter, int $cellDecisionWidth, int $cellHeight2, int $cellNumberWidth, int $cellStudentNameWidth): Pagination
    {
        $mySession = $this->request->getSession();
        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }
            
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($pv == 0)
            {
                $title = 'REGISTRE DE REFERENCE';
            }else
            {
                $title = 'PROCES VERBAL DES NOTES';
            }
        }else
        {
            if($pv == 0)
            {
                $title = 'REFERENCE REGISTER';
            }else
            {
                $title = 'MINUTES NOTES';
            }
        }
        
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf = $this->generalService->staisticSlipHeaderRegisterPagination($pdf, $title, $termName, $school, 'Classe',  $firstReport->getReportHeader()->getStudent()->getClassroom()->getClassroom());
        }else
        {
            $pdf = $this->generalService->staisticSlipHeaderRegisterPagination($pdf, $title, $termName, $school, 'Class',  $firstReport->getReportHeader()->getStudent()->getClassroom()->getClassroom());
        }
            // $pdf->Ln();

        if(!empty($firstReport->getReportBody()->getRowsGroup1()))
        {
            // entête du tableau
            $pdf->SetFont('Times', 'B', 9);
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellSubjectWidth, $cellHeaderHeight1, utf8_decode('Matières'), 1, 0, 'C', true);
            }
            else
            {
                $pdf->Cell($cellSubjectWidth, $cellHeaderHeight1, utf8_decode('Subjects'), 1, 0, 'C', true);
            }
            $pdf->SetFont('Times', 'B',6);

            $x = $cellSubjectWidth+($cellHeaderWidth1/2)+11; 
            $r = $cellHeaderWidth1; 
            $y = 87; 
            $numberOfSubjects = 0;

            // listes des matières de groupe 1 à l'entête
            foreach($firstReport->getReportBody()->getRowsGroup1() as $reportRow)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeaderHeight1, '', 1, 0, 'C', true);
                if(strlen($reportRow->getSubject()) <= 17)
                {
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    
                }elseif(strlen($reportRow->getSubject()) <= 20)
                {
                    $pdf->SetFont('Times', 'B',6);
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    $pdf->SetFont('Times', 'B',7);

                }else
                {
                    $pdf->SetFont('Times', 'B',5);
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    $pdf->SetFont('Times', 'B',7);
                }
                $x += $r;
                $numberOfSubjects++;
            }

            // listes des matières de groupe 2 à l'entête
            foreach($firstReport->getReportBody()->getRowsGroup2() as $reportRow)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeaderHeight1, '', 1, 0, 'C', true);
                if(strlen($reportRow->getSubject()) <= 17)
                {
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    
                }elseif(strlen($reportRow->getSubject()) <= 20)
                {
                    $pdf->SetFont('Times', 'B',6);
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    $pdf->SetFont('Times', 'B',7);

                }else
                {
                    $pdf->SetFont('Times', 'B',5);
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    $pdf->SetFont('Times', 'B',7);
                }
                $x += $r;
                $numberOfSubjects++;
            }

            // listes des matières de groupe 3 à l'entête
            foreach($firstReport->getReportBody()->getRowsGroup3() as $reportRow)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeaderHeight1, '', 1, 0, 'C', true);
                if(strlen($reportRow->getSubject()) <= 17)
                {
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    
                }elseif(strlen($reportRow->getSubject()) <= 20)
                {
                    $pdf->SetFont('Times', 'B',6);
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    $pdf->SetFont('Times', 'B',7);

                }else
                {
                    $pdf->SetFont('Times', 'B',5);
                    $pdf->RotatedText($x, $y, utf8_decode($reportRow->getSubject()), 90);
                    $pdf->SetFont('Times', 'B',7);
                }
                $x += $r;
                $numberOfSubjects++;
            }

            $pdf->SetFont('Times', 'B',9);
            
            if($pv)
            {
                $pdf->Ln();
            }

            // entête suite
            if(!$pv)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeaderHeight1, '', 1, 0, 'C', true);
                $pdf->RotatedText($x, $y, 'Total', 90);

                $x += $r;

                $pdf->Cell($cellHeaderWidth1, $cellHeaderHeight1, '', 'LTR', 0, 'C', true);
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->RotatedText($x, $y, 'Moyenne / 20', 90);
                }else
                {
                    $pdf->RotatedText($x, $y, 'Average / 20', 90);
                }

                $x += $r;

                $pdf->Cell($cellHeaderWidth1, $cellHeaderHeight1, '', 'LTR', 0, 'C', true);
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->RotatedText($x, $y, 'Rang', 90);
                }else
                {
                    $pdf->RotatedText($x, $y, 'Rank', 90);
                }

                if($firstPeriodLetter == 's')
                {
                    $pdf->Cell($cellDecisionWidth-7, $cellHeaderHeight1, utf8_decode('Observation'), 'LTR', 1, 'C', true);

                }else
                {
                    $pdf->SetFont('Times', 'B',7);
                    $x += $r;
                    $pdf->Cell($cellDecisionWidth-7, $cellHeaderHeight1, utf8_decode(''), 'LTR', 1, 'C', true);
                    if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                    {
                        
                        $pdf->RotatedText($x+3, $y, utf8_decode('Décision du Conseil'), 90);
                    }else
                    {
                        $pdf->RotatedText($x+3, $y, 'Council decision', 90);
                    }

                }
            }

            // entête suite
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellSubjectWidth, $cellHeight2, 'Coefficients', 1, 0, 'C', true);

            // Les coefficients des matières du groupe 1
            foreach($firstReport->getReportBody()->getRowsGroup1() as $reportRow)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, $reportRow->getCoefficient(), 1, 0, 'C', true);
            }

            // Les coefficients des matières du groupe 2
            foreach($firstReport->getReportBody()->getRowsGroup2() as $reportRow)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, $reportRow->getCoefficient(), 1, 0, 'C', true);
            }

            // Les coefficients des matières du groupe 3
            foreach($firstReport->getReportBody()->getRowsGroup3() as $reportRow)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, $reportRow->getCoefficient(), 1, 0, 'C', true);
            }

            if($pv)
            {
                $pdf->Ln();
            }

            if(!$pv)
            {
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, $firstReport->getReportFooter()->getStudentResult()->getTotalClassroomCoefficient(), 1, 0, 'C', true);
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, '', 'LR', 0, 'C', true);
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, '', 'LR', 0, 'C', true);
                $pdf->Cell($cellDecisionWidth-7, $cellHeight2, '', 'LR', 1, 'C', true);
            }

            // Entête suite
            $pdf->Cell($cellNumberWidth, $cellHeight2, 'No', 1, 0, 'C', true);
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellStudentNameWidth, $cellHeight2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            }else
            {
                $pdf->Cell($cellStudentNameWidth, $cellHeight2, utf8_decode('First and Last names'), 1, 0, 'C', true);
            }
            
            $pdf->SetFont('Times', 'B', 7);

            for($i = 0; $i < $numberOfSubjects; $i++)
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellHeaderWidth1, $cellHeight2, 'Note/20', 1, 0, 'C', true);
                }else
                {
                    $pdf->Cell($cellHeaderWidth1, $cellHeight2, 'Mark/20', 1, 0, 'C', true);
                }
            }
            
            $pdf->SetFont('Times', 'B', 5.5);

            if($pv)
            {
                $pdf->Ln();
            }

            if(!$pv)
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellHeaderWidth1, $cellHeight2, 'Note*Coef', 1, 0, 'C', true);
                }else
                {
                    $pdf->Cell($cellHeaderWidth1, $cellHeight2, 'Mark*Coef', 1, 0, 'C', true);
                }

                $pdf->SetFont('Times', 'B', 7);
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, '', 'LBR', 0, 'C', true);
                $pdf->Cell($cellHeaderWidth1, $cellHeight2, '', 'LBR', 0, 'C', true);
                $pdf->Cell($cellDecisionWidth-7, $cellHeight2, '', 'LBR', 1, 'C', true);
            }
        }   
        return $pdf;
    }

    /**
     * Affiche une ligne des notes des matières d'un groupe donné
     *
     * @param Pagination $pdf
     * @param array $reportGroup1
     * @param integer $cellHeaderWidth1
     * @param integer $cellHeight2
     * @return Pagination
     */
    public function displayAverageRowPagination(Pagination $pdf, array $reportGroup1, int $cellHeaderWidth1, int $cellHeight2): Pagination
    {
        foreach($reportGroup1 as $studentMark)
        {
            $pdf->Cell($cellHeaderWidth1, $cellHeight2, $this->generalService->formatMark($studentMark->getMoyenne()), 1, 0, 'C');
        }

        return $pdf;
    }

    public function displayTotalAndDesicionPagination(Pagination $pdf, int $pv, int $cellHeaderWidth1, int $cellHeight2, StudentReport $report, int $cellDecisionWidth, string $firstPeriodLetter): Pagination
    {
        if($pv)
        {
            $pdf->Ln();
        }

        if(!$pv)
        {
            $pdf->Cell($cellHeaderWidth1, $cellHeight2, $this->generalService->formatMark($report->getReportFooter()->getStudentResult()->getTotalMark()), 1, 0, 'C');

            $pdf->Cell($cellHeaderWidth1, $cellHeight2, $this->generalService->formatMark($report->getReportFooter()->getStudentResult()->getMoyenne()), 1, 0, 'C');

            $pdf->Cell($cellHeaderWidth1, $cellHeight2, utf8_decode( $this->generalService->formatRank($report->getReportFooter()->getStudentResult()->getRang(), $report->getReportHeader()->getStudent()->getSex()->getSex())), 1, 0, 'C');

            if($firstPeriodLetter == 's' || $firstPeriodLetter == 't')
            {
                $pdf->Cell($cellDecisionWidth-7, $cellHeight2,  utf8_decode($report->getReportFooter()->getWorkAppreciation()->getAppreciation()), 1, 1, 'L');

            }else
            {
                $pdf->Cell($cellDecisionWidth-7, $cellHeight2,  '', 1, 1, 'L');
            }
        }

        return $pdf;
    }


    /**
     * Fabrique et retourne les relevés de note
     *
     * @param array $allTeacherLessons
     * @return array
     */
    public function getMarkReports(array $allTeacherLessons): array
    {
        $markReports = [];
        $markReport = [];

        // pour chaque cours on construit le relevé de notes
        foreach($allTeacherLessons as $teacherLessons)
        {
            foreach($teacherLessons as $lesson)
            {
                $markReportHeader = new MarkReportHeader();

                // on contruit la partie relative aux informations sur le cours
                $markReportHeader->setLesson($lesson);

                // on contruit la partie "compétences visées"

                $skills = $lesson->getSkills();
                // if(!empty($skills))
                // {
                //     foreach($skills as $skill)
                //     {
                //         switch($skill->getTerm()->getTerm())
                //         {
                //             case 1:
                //                 $markReportHeader->setSkill1($skill->getSkill());
                //             break;

                //             case 2:
                //                 $markReportHeader->setSkill2($skill->getSkill());
                //             break;

                //             case 3:
                //                 $markReportHeader->setSkill3($skill->getSkill());
                //             break;
                //         }
                //     }
                // }

                // On contruit la partie contenant les evaluations
                $markReportRows = [];
                $numberOfSequences = 0;

                $classroom = $lesson->getClassroom();
                $students = $this->studentRepository->findBy([
                    'classroom' => $classroom
                ], [
                    'fullName' => 'ASC'
                ]);
                $numberOfStudents = count($students);

                // on recupères les évaluations liées au lesson
                $evaluations = $this->evaluationRepository->findLessonEvaluations($lesson);
                $numberOfEvaluations = count($evaluations);

                // on compte le nombre de séquences dont les notes sont déjà enregistrées
                if($numberOfStudents)
                {
                    $numberOfSequences = $numberOfEvaluations/$numberOfStudents;
                }

                // on set le MarkReportRow
                $markReportRow = new MarkReportRow();
                $counter = 0;

                if($numberOfEvaluations)
                {
                    // si les notes ont déjà été saisies, on les set selon la séquence
                    foreach($evaluations as $evaluation)
                    {
                        $markReportRow->setStudentName($evaluation->getStudent()->getFullName());
                        $markReportRow->setSexStudent($evaluation->getStudent()->getSex()->getSex());

                        switch($evaluation->getSequence()->getSequence())
                        {
                            case 1:
                                $markReportRow->setEvaluation1($evaluation->getMark());
                            break;

                            case 2:
                                $markReportRow->setEvaluation2($evaluation->getMark());
                            break;

                            case 3:
                                $markReportRow->setEvaluation3($evaluation->getMark());
                            break;

                            case 4:
                                $markReportRow->setEvaluation4($evaluation->getMark());
                            break;

                            case 5:
                                $markReportRow->setEvaluation5($evaluation->getMark());
                            break;

                            case 6:
                                $markReportRow->setEvaluation6($evaluation->getMark());
                            break;
                        }
                        $counter++;

                        if($counter == $numberOfSequences)
                        {
                            $markReportRows[] =  $markReportRow;
                            $markReportRow = new MarkReportRow();
                            $counter = 0;
                        }
                    }
                }else 
                {
                    // si aucune note n'est saisi dans la classe, on recupère juste les noms des elèves
                    if(!empty($students))
                    {
                        foreach($students as $student)
                        {
                            $markReportRow = new MarkReportRow();
                            $markReportRow->setStudentName($student->getFullName());
                            $markReportRow->setSexStudent($student->getSex()->getSex());
                            $markReportRows[] = $markReportRow;
                        }
                    }

                }
                $markReport['header'] = $markReportHeader;
                $markReport['body'] = $markReportRows;
                
                $markReports[] = $markReport;
            }
            
        }
        return $markReports;
        
    }

    /**
     * Fabrique et retourne les relevés de note
     *
     * @param array $allTeacherLessons
     * @return array
     */
    public function getMarkReportLesson(Lesson $lesson): array
    {
        $markReports = [];
        $markReport = [];

        // on construit le relevé de note de la leçon
    
        $markReportHeader = new MarkReportHeader();

        // on contruit la partie relative aux informations sur le cours
        $markReportHeader->setLesson($lesson);

        // on contruit la partie "compétences visées"
        $skills = $lesson->getSkills();
        if(!empty($skills))
        {
            foreach($skills as $skill)
            {
                switch($skill->getTerm()->getTerm())
                {
                    case 1:
                        $markReportHeader->setSkill1($skill->getSkill());
                    break;

                    case 2:
                        $markReportHeader->setSkill2($skill->getSkill());
                    break;

                    case 3:
                        $markReportHeader->setSkill3($skill->getSkill());
                    break;
                }
            }
        }

        // On contruit la partie contenant les evaluations
        $markReportRows = [];
        $numberOfSequences = 0;

        $classroom = $lesson->getClassroom();
        $students = $this->studentRepository->findBy([
            'classroom' => $classroom
        ], [
            'fullName' => 'ASC'
        ]);
        $numberOfStudents = count($students);

        // on recupères les évaluations liées au lesson
        $evaluations = $this->evaluationRepository->findLessonEvaluations($lesson);
        $numberOfEvaluations = count($evaluations);

        // on compte le nombre de séquences dont les notes sont déjà enregistrées
        if($numberOfStudents)
        {
            $numberOfSequences = $numberOfEvaluations/$numberOfStudents;
        }

        // on set le MarkReportRow
        $markReportRow = new MarkReportRow();
        $counter = 0;

        if($numberOfEvaluations)
        {
            // si les notes ont déjà été saisies, on les set selon la séquence
            foreach($evaluations as $evaluation)
            {
                $markReportRow->setStudentName($evaluation->getStudent()->getFullName());
                $markReportRow->setSexStudent($evaluation->getStudent()->getSex()->getSex());

                switch($evaluation->getSequence()->getSequence())
                {
                    case 1:
                        $markReportRow->setEvaluation1($evaluation->getMark());
                    break;

                    case 2:
                        $markReportRow->setEvaluation2($evaluation->getMark());
                    break;

                    case 3:
                        $markReportRow->setEvaluation3($evaluation->getMark());
                    break;

                    case 4:
                        $markReportRow->setEvaluation4($evaluation->getMark());
                    break;

                    case 5:
                        $markReportRow->setEvaluation5($evaluation->getMark());
                    break;

                    case 6:
                        $markReportRow->setEvaluation6($evaluation->getMark());
                    break;
                }
                $counter++;

                if($counter == $numberOfSequences)
                {
                    $markReportRows[] =  $markReportRow;
                    $markReportRow = new MarkReportRow();
                    $counter = 0;
                }
            }
        }else 
        {
            // si aucune note n'est saisi dans la classe, on recupère juste les noms des elèves
            if(!empty($students))
            {
                foreach($students as $student)
                {
                    $markReportRow = new MarkReportRow();
                    $markReportRow->setStudentName($student->getFullName());
                    $markReportRow->setSexStudent($student->getSex()->getSex());
                    $markReportRows[] = $markReportRow;
                }
            }

        }
        $markReport['header'] = $markReportHeader;
        $markReport['body'] = $markReportRows;
        
        $markReports[] = $markReport;
    
            
        
        return $markReports;
        
    }

    /**
     * Imprime les relevés de notes
     *
     * @param array $markReports
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return Pagination
     */
    public function printMarkReports(array $markReports, SchoolYear $schoolYear, School $school): Pagination
    {

        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new Pagination();

         if(empty($markReports))
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, 20);

            $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer les relevés de notes !"), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode("Assurez-vous que l'enseignant dispense au moins un cours."), 0, 1, 'C');
           
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BI', $fontSize-3);
            $pdf->Cell(0, 10, utf8_decode("Unable to print transcipt !"), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode("Make sure the teacher has at least one course."), 0, 1, 'C');

            return $pdf;
        }


        foreach($markReports as $markReport)
        {
            $markReportHeader = $markReport['header'];
            $lesson = $markReportHeader->getLesson();
            $numberOfStudents = count($lesson->getClassroom()->getStudents());

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // entête de la fiche
            $pdf = $this->displayReportMarkSlipHeaderPagination($pdf, $lesson);

            // entête du tableau
            $pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, $markReportHeader->getSkill1(), $markReportHeader->getSkill2(),  $markReportHeader->getSkill3(), $cellHeaderHeight2);

            // Contenu du tableau
            $numero = 0;
            foreach( $markReport['body'] as $reportMarkRow)
            {
                $numero++;
                if ($numero % 2 != 0) 
                {
                    $pdf->SetFillColor(219,238,243);
                }
                else 
                {
                    $pdf->SetFillColor(255,255,255);
                }

                $pdf->Cell(8, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                if(strlen($reportMarkRow->getStudentName()) > 34)
                {
                    $pdf->SetFont('Times', 'B', $fontSize-3);
                }
                else
                {
                    $pdf->SetFont('Times', 'B', $fontSize-3);
                }

                $pdf->Cell(66, $cellBodyHeight2, utf8_decode($reportMarkRow->getStudentName()), 1, 0, 'L', true);

                $pdf->SetFont('Times', 'B', $fontSize-3);
                $pdf->Cell(6, $cellBodyHeight2, utf8_decode($reportMarkRow->getSexStudent()), 1, 0, 'C', true);
                //dump($reportMarkRow);
                $pdf->Cell(19, $cellBodyHeight2, ($reportMarkRow->getEvaluation1() != ConstantsClass::UNRANKED_MARK) ? $reportMarkRow->getEvaluation1() : '/', 1, 0, 'C', true);
                $pdf->Cell(19, $cellBodyHeight2, ($reportMarkRow->getEvaluation2() != ConstantsClass::UNRANKED_MARK) ? $reportMarkRow->getEvaluation2() : '/', 1, 0, 'C', true);
                $pdf->Cell(19, $cellBodyHeight2, ($reportMarkRow->getEvaluation3() != ConstantsClass::UNRANKED_MARK) ? $reportMarkRow->getEvaluation3() : '/', 1, 0, 'C', true);
                $pdf->Cell(19, $cellBodyHeight2, ($reportMarkRow->getEvaluation4() != ConstantsClass::UNRANKED_MARK) ? $reportMarkRow->getEvaluation4() : '/', 1, 0, 'C', true);
                $pdf->Cell(19, $cellBodyHeight2, ($reportMarkRow->getEvaluation5() != ConstantsClass::UNRANKED_MARK) ? $reportMarkRow->getEvaluation5() : '/', 1, 0, 'C', true);
                $pdf->Cell(19, $cellBodyHeight2, ($reportMarkRow->getEvaluation6() != ConstantsClass::UNRANKED_MARK) ? $reportMarkRow->getEvaluation6() : '/', 1, 0, 'C', true);
                // $pdf = $this->displayRowOfReportMArk($pdf, $reportMarkRow->getEvaluation1(), $cellBodyHeight2);
                //$pdf = $this->displayRowOfReportMArk($pdf, $reportMarkRow->getEvaluation2(), $cellBodyHeight2);
                //$pdf = $this->displayRowOfReportMArk($pdf, $reportMarkRow->getEvaluation3(), $cellBodyHeight2);
                //$pdf = $this->displayRowOfReportMArk($pdf, $reportMarkRow->getEvaluation4(), $cellBodyHeight2);
                //$pdf = $this->displayRowOfReportMArk($pdf, $reportMarkRow->getEvaluation5(), $cellBodyHeight2);
                //$pdf = $this->displayRowOfReportMArk($pdf, $reportMarkRow->getEvaluation6(), $cellBodyHeight2);


                $pdf->Ln();

                // Après 30 lignes, on passe à une nouvelle page
                // if( ($numero % 30) == 0 && $numberOfStudents > 30 )
                // {
                    // On insère une page
                    // $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

                    // Administrative Header
                    // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

                    // entête de la fiche
                    // $pdf = $this->displayReportMarkSlipHeaderPagination($pdf, $lesson);

                    // entête du tableau
                //     $pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, $markReportHeader->getSkill1(), $markReportHeader->getSkill2(),  $markReportHeader->getSkill3(), $cellHeaderHeight2);
                // }
            }
            //dd('ok');
        }

        return $pdf;
    }

    /**
     * fonction qui imprime les fiches de reports de notes d'une classes donnée
     *
     * @param array $markReports
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return Pagination
     */
    public function printMarkReportsClassroom(array $markReports, SchoolYear $schoolYear, School $school): Pagination
    {

        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new Pagination();

         if(empty($markReports))
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, 20);

            $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer les relevés de notes !"), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode("Assurez-vous que cette classe a au moins un cours."), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BI', $fontSize-3);
            $pdf->Cell(0, 10, utf8_decode("Unable to print transcipt !"), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode("Make sure this class has at least one."), 0, 1, 'C');
            
            return $pdf;
        }


        foreach($markReports as $markReport)
        {
            // dd($markReport);
            // $markReportHeader = $markReport['header'];
            // $lesson = $markReportHeader->getLesson();
            $numberOfStudents = count($markReport->getClassroom()->getStudents());

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
            // entête de la fiche
            $pdf = $this->displayReportMarkSlipHeaderPagination($pdf, $markReport);

            // entête du tableau
            //$pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, $markReportHeader->getSkill1(), $markReportHeader->getSkill2(),  $markReportHeader->getSkill3(), $cellHeaderHeight2);
            $pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, "", "",  "", $cellHeaderHeight2);

            // Contenu du tableau
            $numero = 0;
            // $students = $markReport->getClassroom()->getStudents();

            $students = $this->studentRepository->findBy([
                'classroom' => $markReport->getClassroom()
            ]);
            #jz trie par ordre alphabétique
            usort($students, function($a, $b) 
            {
                return strcmp($a->getFullName(), $b->getFullName());
            });

            //dd($students);
            foreach($students as $student)
            {
                $numero++;
                if ($numero % 2 != 0) 
                {
                    $pdf->SetFillColor(219,238,243);
                }
                else 
                {
                    $pdf->SetFillColor(255,255,255);
                }

                $pdf->Cell(8, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                if(strlen($student->getFullName()) > 34)
                {
                    $pdf->SetFont('Times', 'B', $fontSize-3);
                }
                else
                {
                    $pdf->SetFont('Times', 'B', $fontSize-3);
                }

                $pdf->Cell(66, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                $pdf->SetFont('Times', 'B', $fontSize-3);
                $pdf->Cell(6, $cellBodyHeight2, utf8_decode($student->getSex()->getSex()), 1, 0, 'C', true);
                
                if(count($student->getEvaluations()) > 0)
                {
                    foreach($student->getEvaluations() as $evaluation)
                    {
                        if($markReport->getId() == $evaluation->getLesson()->getId())
                        {
                            switch($evaluation->getSequence()->getSequence())
                            {
                                case 1:
                                    $pdf->Cell(19, $cellBodyHeight2, (($evaluation->getMark() != ConstantsClass::UNRANKED_MARK) ? $evaluation->getMark() : '/') , 1, 0, 'C', true);
                                    break;
                                case 2:
                                    $pdf->Cell(19, $cellBodyHeight2, (($evaluation->getMark() != ConstantsClass::UNRANKED_MARK) ? $evaluation->getMark() : '/'), 1, 0, 'C', true);
                                    break;
                                case 3:
                                    $pdf->Cell(19, $cellBodyHeight2, (($evaluation->getMark() != ConstantsClass::UNRANKED_MARK) ? $evaluation->getMark() : '/'), 1, 0, 'C', true);
                                    break;
                                case 4:
                                    $pdf->Cell(19, $cellBodyHeight2, (($evaluation->getMark() != ConstantsClass::UNRANKED_MARK) ? $evaluation->getMark() : '/'), 1, 0, 'C', true);
                                    break;
                                case 5:
                                    $pdf->Cell(19, $cellBodyHeight2, (($evaluation->getMark() != ConstantsClass::UNRANKED_MARK) ? $evaluation->getMark() : '/'), 1, 0, 'C', true);
                                    break;
                                case 6:
                                    $pdf->Cell(19, $cellBodyHeight2, (($evaluation->getMark() != ConstantsClass::UNRANKED_MARK) ? $evaluation->getMark() : '/'), 1, 0, 'C', true);
                                    break;
                            }
                        }
                        
                    }
                }
                else
                {
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                }
                
                
                $pdf->Ln();

                // Après 30 lignes, on passe à une nouvelle page
                // if( ($numero % 30) == 0 && $numberOfStudents > 30 )
                // {
                    // On insère une page
                    // $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

                    // Administrative Header
                    // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

                    // entête de la fiche
                    // $pdf = $this->displayReportMarkSlipHeaderPagination($pdf, $lesson);

                    // entête du tableau
                //     $pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, $markReportHeader->getSkill1(), $markReportHeader->getSkill2(),  $markReportHeader->getSkill3(), $cellHeaderHeight2);
                // }
            }
        }

        return $pdf;
    }


    /**
     * fonction qui imprime les fiches de reports de notes de toutes les classes
     *
     * @param array $markReports
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return Pagination
     */
    public function printMarkReportsAllClassroom(array $markReports, SchoolYear $schoolYear, School $school): Pagination
    {

        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new Pagination();

         if(empty($markReports))
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, 20);

            $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer les relevés de notes !"), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode("Assurez-vous que cette classe a au moins un cours."), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BI', $fontSize-3);
            $pdf->Cell(0, 10, utf8_decode("Unable to print transcipt !"), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode("Make sure this class has at least one."), 0, 1, 'C');
            
            return $pdf;
        }


        foreach($markReports as $markReport)
        {
            // $markReportHeader = $markReport['header'];
            // $lesson = $markReportHeader->getLesson();
            $numberOfStudents = count($markReport->getClassroom()->getStudents());

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
            // entête de la fiche
            $pdf = $this->displayReportMarkSlipHeaderPagination($pdf, $markReport);

            // entête du tableau
            //$pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, $markReportHeader->getSkill1(), $markReportHeader->getSkill2(),  $markReportHeader->getSkill3(), $cellHeaderHeight2);
            $pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, "", "",  "", $cellHeaderHeight2);

            // Contenu du tableau
            $numero = 0;
            // $students = $markReport->getClassroom()->getStudents();

            $students = $this->studentRepository->findBy([
                'classroom' => $markReport->getClassroom()
            ]);
            
            #je trie par ordre alphabétique
            usort($students, function($a, $b) 
            {
                return strcmp($a->getFullName(), $b->getFullName());
            });

            //dd($students);
            foreach($students as $student)
            {
                $numero++;
                if ($numero % 2 != 0) 
                {
                    $pdf->SetFillColor(219,238,243);
                }
                else 
                {
                    $pdf->SetFillColor(255,255,255);
                }

                $pdf->Cell(8, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                if(strlen($student->getFullName()) > 34)
                {
                    $pdf->SetFont('Times', 'B', $fontSize-3);
                }
                else
                {
                    $pdf->SetFont('Times', 'B', $fontSize-3);
                }

                $pdf->Cell(66, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                $pdf->SetFont('Times', 'B', $fontSize-3);
                $pdf->Cell(6, $cellBodyHeight2, utf8_decode($student->getSex()->getSex()), 1, 0, 'C', true);
                
                if(count($student->getEvaluations()) > 0)
                {
                    foreach($student->getEvaluations() as $evaluation)
                    {
                        if($markReport->getId() == $evaluation->getLesson()->getId())
                        {
                            switch($evaluation->getSequence()->getSequence())
                            {
                                case 1:
                                    $pdf->Cell(19, $cellBodyHeight2, $evaluation->getMark() ? ($evaluation->getMark() == 0.1) : "", 1, 0, 'C', true);
                                    break;
                                case 2:
                                    $pdf->Cell(19, $cellBodyHeight2, $evaluation->getMark() ? $evaluation->getMark() : "", 1, 0, 'C', true);
                                    break;
                                case 3:
                                    $pdf->Cell(19, $cellBodyHeight2, $evaluation->getMark() ? $evaluation->getMark() : "", 1, 0, 'C', true);
                                    break;
                                case 4:
                                    $pdf->Cell(19, $cellBodyHeight2, $evaluation->getMark() ? $evaluation->getMark() : "", 1, 0, 'C', true);
                                    break;
                                case 5:
                                    $pdf->Cell(19, $cellBodyHeight2, $evaluation->getMark() ? $evaluation->getMark() : "", 1, 0, 'C', true);
                                    break;
                                case 6:
                                    $pdf->Cell(19, $cellBodyHeight2, $evaluation->getMark() ? $evaluation->getMark() : "", 1, 0, 'C', true);
                                    break;
                            }
                        }
                        
                    }
                }
                else
                {
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                    $pdf->Cell(19, $cellBodyHeight2, "", 1, 0, 'L', true);
                }
                
                
                $pdf->Ln();

                // Après 30 lignes, on passe à une nouvelle page
                // if( ($numero % 30) == 0 && $numberOfStudents > 30 )
                // {
                    // On insère une page
                    // $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

                    // Administrative Header
                    // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

                    // entête de la fiche
                    // $pdf = $this->displayReportMarkSlipHeaderPagination($pdf, $lesson);

                    // entête du tableau
                //     $pdf = $this->displayReportMarkSlipTableHeaderPagination($pdf, $markReportHeader->getSkill1(), $markReportHeader->getSkill2(),  $markReportHeader->getSkill3(), $cellHeaderHeight2);
                // }
            }
        }

        return $pdf;
    }


    /**
     * Affiche une ligne du relevé des notes
     *
     * @param Pagination $pdf
     * @param float $evaluation
     * @param integer $cellBodyHeight2
     * @return Pagination
     */
    public function displayRowOfReportMArk(Pagination $pdf, ?float $evaluation, int $cellBodyHeight2): Pagination
    {
        if($evaluation)
        {
            if($evaluation != ConstantsClass::UNRANKED_MARK)
                $pdf->Cell(19, $cellBodyHeight2, $evaluation, 1, 0, 'C', true);
            else
                $pdf->Cell(19, $cellBodyHeight2, '/', 1, 0, 'C', true);
        }else
        {
            $pdf->Cell(19, $cellBodyHeight2, '', 1, 0, 'C', true);
        }

        return $pdf;
    }


    /**
     * Affiche l'netête de la fiche des relevés de notes
     *
     * @param Pagination $pdf
     * @param Lesson $lesson
     * @return Pagination
     */
    public function displayReportMarkSlipHeaderPagination(Pagination $pdf, Lesson $lesson): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $students = $lesson->getClassroom()->getStudents();

        $numberOfStudents = count($students);

        $girls = 0;
        $boys = 0;

        foreach($students as $student)
        {
            if($student->getSex()->getSex() == ConstantsClass::SEX_F)
            {
                $girls = $girls + 1;
            }
            else
            {
                $boys = $boys + 1;
            }
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'RELEVE DES NOTES', 0, 1, 'C');
            $pdf->Cell(0, 3, '', 0, 1, 'C');
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(20, 5, 'Classe', 1, 0, 'C', true);
            $pdf->Cell(40, 5, 'Discipline', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Coefficient', 1, 0, 'C', true);
            $pdf->Cell(90, 5, 'Enseignant', 1, 0, 'C', true);
            $pdf->Cell(25, 5, 'Grade', 1, 1, 'C', true);


            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(20, 5, utf8_decode($lesson->getClassroom()->getClassroom()), 1, 0, 'C');
            $pdf->Cell(40, 5, utf8_decode($lesson->getSubject()->getSubject()), 1, 0, 'C');
            $pdf->Cell(20, 5, $lesson->getCoefficient(), 1, 0, 'C');
            
            $sex = "";
            if($lesson->getTeacher()->getSex()->getSex() == ConstantsClass::SEX_F)
            {
                $sex = "Mme ";
            }
            else
            {
                $sex = "M. ";
            }
            $pdf->Cell(90, 5, utf8_decode($sex.$lesson->getTeacher()->getFullName()), 1, 0, 'C');
            $pdf->Cell(25, 5, $lesson->getTeacher()->getGrade()->getGrade(), 1, 0, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln();
            
            $pdf->SetFont('Times', 'BI', 10);
            $pdf->Cell(40, 5, '', 0, 0, 'L');
            $pdf->Cell(30, 5, 'Profil de la classe ', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Filles ', 1, 0, 'C', true);
            $pdf->Cell(20, 5, utf8_decode('Garçons'), 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Total', 1, 1, 'C', true);
            
            $pdf->Cell(40, 5, '', 0, 0, 'L');
            $pdf->Cell(30, 5, '', 0, 0, 'L');
            $pdf->Cell(20, 5, $girls, 1, 0, 'C');
            $pdf->Cell(20, 5, $boys, 1, 0, 'C');
            $pdf->Cell(20, 5, $numberOfStudents, 1, 1, 'C');

            $pdf->Ln();

        }
        else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'TRANSCRIPT', 0, 0, 'C');
            $pdf->Cell(0, 3, '', 0, 1, 'C');
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(20, 5, 'Class', 1, 0, 'C', true);
            $pdf->Cell(40, 5, 'Subject', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Coefficient', 1, 0, 'C', true);
            $pdf->Cell(90, 5, 'Teacher', 1, 0, 'C', true);
            $pdf->Cell(25, 5, 'Rank', 1, 1, 'C', true);


            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(20, 5, utf8_decode($lesson->getClassroom()->getClassroom()), 1, 0, 'C');
            $pdf->Cell(40, 5, utf8_decode($lesson->getSubject()->getSubject()), 1, 0, 'C');
            $pdf->Cell(20, 5, $lesson->getCoefficient(), 1, 0, 'C');
            
            $sex = "";
            if($lesson->getTeacher()->getSex()->getSex() == ConstantsClass::SEX_F)
            {
                $sex = "Mme ";
            }
            else
            {
                $sex = "Mr. ";
            }
            $pdf->Cell(90, 5, utf8_decode($sex.$lesson->getTeacher()->getFullName()), 1, 0, 'C');
            $pdf->Cell(25, 5, $lesson->getTeacher()->getGrade()->getGrade(), 1, 0, 'C');
            $pdf->Ln();
            $pdf->Cell(0, 3, '', 0, 0, 'C');
            $pdf->Ln();

            
            $pdf->SetFont('Times', 'BI', 10);
            $pdf->Cell(40, 5, '', 0, 0, 'L');
            $pdf->Cell(30, 5, 'Profile classroom ', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Girls ', 1, 0, 'C', true);
            $pdf->Cell(20, 5, "Boys", 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Total', 1, 1, 'C', true);
            
            $pdf->Cell(40, 5, '', 0, 0, 'L');
            $pdf->Cell(30, 5, '', 0, 0, 'L');
            $pdf->Cell(20, 5, $girls, 1, 0, 'C');
            $pdf->Cell(20, 5, $boys, 1, 0, 'C');
            $pdf->Cell(20, 5, $numberOfStudents, 1, 1, 'C');

            $pdf->Ln();
        }

        return $pdf;
    }


    /**
     * Affiche l'entête du tablea de la fiche des relevés de notes
     *
     * @param Pagination $pdf
     * @param string $skill1
     * @param string $skill2
     * @param string $skill3
     * @param integer $cellHeaderHeight2
     * @return Pagination
     */
    public function displayReportMarkSlipTableHeaderPagination(Pagination $pdf, string $skill1, string $skill2, string $skill3, int $cellHeaderHeight2): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            // $pdf->SetFont('Times', 'B', 7);

            // $pdf->Cell(80, 3, '', 'LTR', 0, 'C', true);
            // $pdf->Cell(38, 3, 'Trimestre 1', 1, 0, 'C', true);
            // $pdf->Cell(38, 3, 'Trimestre 2', 1, 0, 'C', true);
            // $pdf->Cell(38, 3, 'Trimestre 3', 1, 0, 'C', true);
            // $pdf->Ln();

            $pdf->SetFont('Times', 'B', 8);

            //$pdf->Cell(80, 18, utf8_decode('Compétences Visées'), 'LBR', 0, 'C', true);
            
            // entête compétence trimestre 1
            //$pdf = $this->displayHeaderSkill($pdf, $skill1, true);
            //$pdf = $this->displayHeaderSkill($pdf, $skill2, true);
            //$pdf = $this->displayHeaderSkill($pdf, $skill3, false);
            
            $pdf->Ln();
            $pdf->Cell(8, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(66, $cellHeaderHeight2, 'NOMS ET PRENOMS', 1, 0, 'C', true);
            $pdf->Cell(6, $cellHeaderHeight2, 'S', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 1', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 2', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 3', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 4', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 5', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 6', 1, 0, 'C', true);
            $pdf->Ln();
        }else
        {
            // $pdf->SetFont('Times', 'B', 7);

            // $pdf->Cell(80, 3, '', 'LTR', 0, 'C', true);
            // $pdf->Cell(38, 3, 'Term 1', 1, 0, 'C', true);
            // $pdf->Cell(38, 3, 'Term 2', 1, 0, 'C', true);
            // $pdf->Cell(38, 3, 'Term 3', 1, 0, 'C', true);
            // $pdf->Ln();

            $pdf->SetFont('Times', 'B', 8);

            // $pdf->Cell(80, 18, utf8_decode('Target skill'), 'LBR', 0, 'C', true);
            
            // // entête compétence trimestre 1
            // $pdf = $this->displayHeaderSkill($pdf, $skill1, true);
            // $pdf = $this->displayHeaderSkill($pdf, $skill2, true);
            // $pdf = $this->displayHeaderSkill($pdf, $skill3, false);
            
            $pdf->Ln();
            $pdf->Cell(8, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(66, $cellHeaderHeight2, 'FIRST AND LAST NAMES', 1, 0, 'C', true);
            $pdf->Cell(6, $cellHeaderHeight2, 'S', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 1', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 2', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 3', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 4', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 5', 1, 0, 'C', true);
            $pdf->Cell(19, $cellHeaderHeight2, 'Evaluation 6', 1, 0, 'C', true);
            $pdf->Ln();
        }

        return $pdf;
    }


    /**
     * Affiche entête partie compétence trimestre
     *
     * @param Pagination $pdf
     * @param string $skill
     * @return Pagination
     */
    public function displayHeaderSkill(Pagination $pdf, string $skill, bool $setPosition): Pagination
    {
        $myTargetedSkill = strtolower(utf8_decode($skill));
        if(strlen($myTargetedSkill) <= 27)
        {
            $pdf->Cell(38, 18, $myTargetedSkill, 1, 0, 'C');

        }else
        {
            $myTargetedSkill1 = substr($myTargetedSkill, 0, 27);
            $myTargetedSkill2 = substr($myTargetedSkill, 27, 27);
            $myTargetedSkill3 = substr($myTargetedSkill, 54, 27);

            $pdf->Cell(38, 6, $myTargetedSkill1, 'LR', 0, 'L');
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Ln();
            $pdf->SetXY($x - 38, $y + 6);
            $pdf->Cell(38, 6, $myTargetedSkill2, 'LR', 0, 'L');
            $pdf->Ln();
            $pdf->SetXY($x - 38, $y + 12);
            $pdf->Cell(38, 6, $myTargetedSkill3, 'LBR', 0, 'L');

            if($setPosition)
            {
                $pdf->SetXY($x, $y);
            }

        }

        return $pdf;
    }


    /**
     * Construit les relevés d'absences
     *
     * @param array $allAbsences
     * @return array
     */
    public function getAbsenceReports(array $allAbsences): array
    {
        $absenceReports = [];
        $absenceReport = [];

        // pour chaque liste on construit le relevé d'absences'
        foreach($allAbsences as $studentAbsences)
        {
            $absenceReportHeader = new AbsenceReportHeader();
            $absenceReportRows = [];

            // on contruit la partie relative aux informations sur la classe
            $classroom = $studentAbsences['classroom'];
            $absenceReportHeader->setClassroom($classroom);

            $students = $this->studentRepository->findBy([
                'classroom' => $classroom,
            ], [
                'fullName' => 'ASC'
            ]);

            $numberOfStudents = count($students);

            $absences = $studentAbsences['absences'];
            if ($absences)
            {
                $numberOfAbsences = count($absences);
            }else
            {
                $numberOfAbsences = 0;
            }
            
            $numberOfTerms = ($numberOfStudents > 0) ?  ($numberOfAbsences/$numberOfStudents) : 0;

            if(!empty($absences))
            {
                // si les absences ont déjà été saisies, on les set selon le trimestre

                // On contruit la partie contenant les absences
                $absenceReportRow = new AbsenceReportRow();
                $counter = 0;

                foreach($absences as $absence)
                {
                    
                    $absenceReportRow->setStudentName($absence->getStudent()->getFullName());

                    switch($absence->getTerm()->getTerm())
                    {
                        case 1:
                            $absenceReportRow->setAbsence1($absence->getAbsence());
                        break;

                        case 2:
                            $absenceReportRow->setAbsence2($absence->getAbsence());
                        break;

                        case 3:
                            $absenceReportRow->setAbsence3($absence->getAbsence());
                        break;
                    }
                    $counter++;

                    if($counter == $numberOfTerms)
                    {
                        $absenceReportRows[] =  $absenceReportRow;
                        $absenceReportRow = new AbsenceReportRow();
                        $counter = 0;
                    }
                }

            }else 
            {
                // si aucune absence n'est saisie dans la classe, on recupère juste les noms des elèves
                foreach($students as $student)
                {
                    $absenceReportRow = new AbsenceReportRow();
                    $absenceReportRow->setStudentName($student->getFullName())->setSex($student->getSex()->getSex());
                    $absenceReportRows[] = $absenceReportRow;
                }

            }

            $absenceReport['header'] = $absenceReportHeader;
            $absenceReport['body'] = $absenceReportRows;
            
            $absenceReports[] = $absenceReport;

        }
        return $absenceReports;
    }

    /**
     * Imprime les relevés d'absences
     *
     * @param array $absencesReports
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return Pagination
     */
    public function printAbsenceReports(array $absencesReports, SchoolYear $schoolYear, School $school): Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellHeaderHeight3 = 7;
        $cellBodyHeight3 = 6;

        $pdf = new Pagination();

        if(empty($absencesReports))
        {
            $pdf->addPage();
            $pdf->setFont('Times', '', 20);
            $pdf->setTextColor(0, 0, 0);
            $pdf->SetLeftMargin(20);
            $pdf->SetFillColor(200);

            $pdf->Cell(0, 10, utf8_decode("Impression des relevés d'absence impossible !"), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode("- Assurez-vous que la classe contienne au moins un élève."), 0, 1, 'C');

            return $pdf;
        }

        foreach($absencesReports as $absencesReport)
        {
            $classroom = $absencesReport['header']->getClassroom();
            $numberOfStudents = count($classroom->getStudents());

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // entête de la fiche
            $pdf = $this->displayHeaderAbsenceReportSlip($pdf, $school, $classroom);
            
            // entête du tableau
            $pdf = $this->displayTableHeaderOfAbsenceReport($pdf, $cellHeaderHeight3);
            
            // Contenu du tableau
            $numero = 0; 
            
            foreach($absencesReport['body'] as $absence)
            {
                $numero++;
                if ($numero % 2 != 0) 
                {
                    $pdf->SetFillColor(219,238,243);
                }else {
                    $pdf->SetFillColor(255,255,255);
                }

                $pdf->SetFont('Times', 'B', 8);
                $pdf->Cell(10, $cellBodyHeight3, $numero, 1, 0, 'C', true);
                $pdf->Cell(90, $cellBodyHeight3, utf8_decode($absence->getStudentName()), 1, 0, 'L', true);
                $pdf->Cell(10, $cellBodyHeight3, utf8_decode($absence->getSex()), 1, 0, 'C', true);

                $pdf->Cell(27, $cellBodyHeight3, $absence->getAbsence1(), 1, 0, 'C', true);
                $pdf->Cell(27, $cellBodyHeight3, $absence->getAbsence2(), 1, 0, 'C', true);
                $pdf->Cell(27, $cellBodyHeight3, $absence->getAbsence3(), 1, 0, 'C', true);

                $pdf->Ln();

                // if(($numero % 30) == 0 && $numberOfStudents > 0)
                // {
                    // On insère une page
                    // $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

                    // Administrative Header
                    // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

                    // entête de la fiche
                    // $pdf = $this->displayHeaderAbsenceReportSlip($pdf, $school, $classroom);
                    
                    // entête du tableau
                //     $pdf = $this->displayTableHeaderOfAbsenceReport($pdf, $cellHeaderHeight3);
                // }
            }
        }
        return $pdf;
    }

    /**
     * Affiche l'entête de la fiche des relevés d'absence
     *
     * @param Pagination $pdf
     * @param School $school
     * @param Classroom $classroom
     * @return Pagination
     */
    public function displayHeaderAbsenceReportSlip(Pagination $pdf, School $school, Classroom $classroom): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'RELEVE DES ABSENCES', 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            
            $pdf->Cell(30, 5, 'Classe', 1, 0, 'C', true);
            $pdf->Cell(101, 5, 'Surveillant', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Filles', 1, 0, 'C', true);
            $pdf->Cell(20, 5, utf8_decode('Garçons'), 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Total', 1, 1, 'C', true);

            $supervisor = $classroom->getSupervisor();

            $students = $classroom->getStudents();
            $numberOfStudents = count($students);

            $girls = 0;
            $boys = 0;
            
            foreach($students as $student)
            {
                if($student->getSex()->getSex() == constantsClass::SEX_F)
                $girls = $girls + 1;
                else
                $boys = $boys + 1;
            }
            $pdf->Cell(30, 5, utf8_decode($classroom->getClassroom()), 1, 0, 'C');
            $pdf->Cell(101, 5, utf8_decode($this->generalService->getNameWithTitle($supervisor->getFullName(), $supervisor->getSex()->getSex())), 1, 0, 'C');
            $pdf->Cell(20, 5, $girls, 1, 0, 'C');
            $pdf->Cell(20, 5, $boys, 1, 0, 'C');
            $pdf->Cell(20, 5, $numberOfStudents, 1, 1, 'C');

            $pdf->Ln(3);
        }
        else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'HOURS OF ABSENCES', 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            
            $pdf->Cell(30, 5, 'Classroom', 1, 0, 'C', true);
            $pdf->Cell(101, 5, 'Supervisor', 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Girls', 1, 0, 'C', true);
            $pdf->Cell(20, 5, utf8_decode('Boys'), 1, 0, 'C', true);
            $pdf->Cell(20, 5, 'Total', 1, 1, 'C', true);

            $supervisor = $classroom->getSupervisor();

            $students = $classroom->getStudents();
            $numberOfStudents = count($students);

            $girls = 0;
            $boys = 0;
            
            foreach($students as $student)
            {
                if($student->getSex()->getSex() == constantsClass::SEX_F)
                $girls = $girls + 1;
                else
                $boys = $boys + 1;
            }
            $pdf->Cell(30, 5, utf8_decode($classroom->getClassroom()), 1, 0, 'C');
            $pdf->Cell(101, 5, utf8_decode($this->generalService->getNameWithTitle($supervisor->getFullName(), $supervisor->getSex()->getSex())), 1, 0, 'C');
            $pdf->Cell(20, 5, $girls, 1, 0, 'C');
            $pdf->Cell(20, 5, $boys, 1, 0, 'C');
            $pdf->Cell(20, 5, $numberOfStudents, 1, 1, 'C');

            $pdf->Ln(3);
        }
        return $pdf;
    }


    /**
     * Afffiche l'entête du tableau de la fiche des relevés d'absence
     *
     * @param Pagination $pdf
     * @param integer $cellHeaderHeight3
     * @return Pagination
     */
    public function displayTableHeaderOfAbsenceReport(Pagination $pdf, int $cellHeaderHeight3): Pagination
    {
        $pdf->SetFont('Times', 'B', 8);
        $pdf->Cell(10, $cellHeaderHeight3, 'No', 1, 0, 'C', true);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(90, $cellHeaderHeight3, 'NOMS ET PRENOMS', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeaderHeight3, 'Genre', 1, 0, 'C', true);
            $pdf->Cell(27, $cellHeaderHeight3, 'Trimestre 1', 1, 0, 'C', true);
            $pdf->Cell(27, $cellHeaderHeight3, 'Trimestre 2', 1, 0, 'C', true);
            $pdf->Cell(27, $cellHeaderHeight3, 'Trimestre 3', 1, 0, 'C', true);
        }
        else
        {
            $pdf->Cell(90, $cellHeaderHeight3, 'FIRST AND LAST NAMES', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeaderHeight3, 'Gender', 1, 0, 'C', true);
            $pdf->Cell(27, $cellHeaderHeight3, 'Term 1', 1, 0, 'C', true);
            $pdf->Cell(27, $cellHeaderHeight3, 'Term 2', 1, 0, 'C', true);
            $pdf->Cell(27, $cellHeaderHeight3, 'Term 3', 1, 0, 'C', true);
        }
        $pdf->Ln();

        return $pdf;
    }


    /**
     * Imprime la liste des animateurs pédagogiques
     * @param array $departments
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    public function printEducationalFacilitatorList(array $departments, School $school, SchoolYear $schoolYear, SubSystem $subSystem):Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 8;
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf = new Pagination();

            if(empty($departments))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode(' AUCUN DEPARTEMENT ENREGISTRE '), 0, 1, 'C');

                return $pdf;
            }

            $numberOfDepartments = count($departments);

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
            
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
            // Entête de la liste
            $pdf = $this->getHeaderEducationalFacilitatorListPagination($pdf, $schoolYear, $cellHeaderHeight2, $school, $subSystem);

            // entête du tableau
            $pdf = $this->getTableHeaderEducationalFacilitatorListPagination($pdf, $cellHeaderHeight2, $fontSize, $subSystem);

            // contenu du tableau
            $numero = 0;
            foreach($departments as $department)
            {
                $numberOfTeachers = count($department->getTeachers());
                $facilitator = $department->getEducationalFacilitator();
                
                $numero++;
                
                $pdf = $this->getEducationalFacilitatorRowPagination($pdf, $cellHeaderHeight2, $numero, $department, $fontSize, $facilitator, $schoolYear, $numberOfTeachers);

                if( ($numero % 30) == 0 && $numberOfDepartments > 30) /*On passe à une nouvelle page après 30 lignes*/
                {
                    // On insère une page
                    $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
                    
                    // Administrative Header
                    $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                    
                    // Entête de la liste
                    $pdf = $this->getHeaderEducationalFacilitatorListPagination($pdf, $schoolYear, $cellHeaderHeight2, $school, $subSystem);

                    // entête du tableau
                    $pdf = $this->getTableHeaderEducationalFacilitatorListPagination($pdf, $cellHeaderHeight2, $fontSize, $subSystem);

                }
            }

            $pdf->Ln();

            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Fait à '.$school->getPlace().' le _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');

            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(136, $cellHeaderHeight2, utf8_decode("Le Proviseur"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(136, $cellHeaderHeight2, utf8_decode("Le Directeur"), 0, 1, 'R');
                }
            }else
            {
                $pdf->Cell(136, $cellHeaderHeight2, utf8_decode("Le Principal"), 0, 1, 'R');
            }
        }else
        {
            $cellHeaderHeight = 3;

            $cellHeaderHeight2 = 8;

            $pdf = new Pagination();

            if(empty($departments))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode(' NO REGISTERED DEPARTMENT '), 0, 1, 'C');

                return $pdf;
            }

            $numberOfDepartments = count($departments);

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
            
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
            // Entête de la liste
            $pdf = $this->getHeaderEducationalFacilitatorListPagination($pdf, $schoolYear, $cellHeaderHeight2, $school, $subSystem);

            // entête du tableau
            $pdf = $this->getTableHeaderEducationalFacilitatorListPagination($pdf, $cellHeaderHeight2, $fontSize, $subSystem);

            // contenu du tableau
            $numero = 0;
            foreach($departments as $department)
            {
                $numberOfTeachers = count($department->getTeachers());
                $facilitator = $department->getEducationalFacilitator();
                
                $numero++;
                
                $pdf = $this->getEducationalFacilitatorRowPagination($pdf, $cellHeaderHeight2, $numero, $department, $fontSize, $facilitator, $schoolYear, $numberOfTeachers);

                if( ($numero % 30) == 0 && $numberOfDepartments > 30) /*On passe à une nouvelle page après 30 lignes*/
                {
                    // On insère une page
                    $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
                    
                    // Administrative Header
                    $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                    
                    // Entête de la liste
                    $pdf = $this->getHeaderEducationalFacilitatorListPagination($pdf, $schoolYear, $cellHeaderHeight2, $school, $subSystem);

                    // entête du tableau
                    $pdf = $this->getTableHeaderEducationalFacilitatorListPagination($pdf, $cellHeaderHeight2, $fontSize, $subSystem);

                }
            }

            $pdf->Ln();

            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Done at '.$school->getPlace().' on _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');

            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(136, $cellHeaderHeight2, utf8_decode("The Principal"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(136, $cellHeaderHeight2, utf8_decode("The Director"), 0, 1, 'R');
                }
            }else
            {
                $pdf->Cell(136, $cellHeaderHeight2, utf8_decode("The Directore"), 0, 1, 'R');
            }
        }
        
        return $pdf;

    }

    /**
     * Entête de la liste des animateurs pédagogiques
     * @param Pagination $pdf
     * @param SchoolYear $schoolYear
     * @param integer $cellHeaderHeight2
     * @return Pagination
     */
    public function getHeaderEducationalFacilitatorListPagination(Pagination $pdf, SchoolYear $schoolYear, int  $cellHeaderHeight2, School $school, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE )
        {
            $pdf->Cell(00, $cellHeaderHeight2, utf8_decode('NOTE DE SERVICE N° ___________/AP/'.$school->getServiceNote()), 0, 1, 'C');
            $pdf->Cell(00, $cellHeaderHeight2, utf8_decode("Portant proposition des animateurs pédagogiques pour le compte de l'année scolaire ".$schoolYear->getSchoolYear()), 0, 1, 'C');
            $pdf->Ln();
        }else
        {
            $pdf->Cell(00, $cellHeaderHeight2, utf8_decode('SERVICE NOTE  N° ___________/EF/'.$school->getServiceNote()), 0, 1, 'C');
            $pdf->Cell(00, $cellHeaderHeight2, utf8_decode("Proposal from educational facilitators on behalf of the school year ".$schoolYear->getSchoolYear()), 0, 1, 'C');
            $pdf->Ln();
        }
        

        return $pdf;
    }


    /**
     * Entête du tableau de la liste des animateurs pédagogiques
     * @param Pagination $pdf
     * @param integer $cellHeaderHeight2
     * @param integer $fontSize
     * @return Pagination
     */
    public function getTableHeaderEducationalFacilitatorListPagination(Pagination $pdf, int $cellHeaderHeight2, int $fontSize, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(8, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Département'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell(17, $cellHeaderHeight2, utf8_decode('Grade'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Matricule'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Ancienneté'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Téléphone'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize-1);
            $pdf->Cell(20, $cellHeaderHeight2/2, utf8_decode('Nombre'), 'LTR', 2, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2/2, utf8_decode("d'enseignants"), 'LBR', 1, 'C', true);
            $pdf->SetFont('Times', '', $fontSize);
        }else
        {
            $pdf->Cell(8, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Departement'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell(17, $cellHeaderHeight2, utf8_decode('Rank'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Reg. Num.'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Seniority'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Phone'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize-1);
            $pdf->Cell(20, $cellHeaderHeight2/2, utf8_decode('Number'), 'LTR', 2, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2/2, utf8_decode("of teachers"), 'LBR', 1, 'C', true);
            $pdf->SetFont('Times', '', $fontSize);
        }
        

        return $pdf;
    }

    public function getEducationalFacilitatorRowPagination(Pagination $pdf, int $cellHeaderHeight2, int $numero, Department $department, int $fontSize, ?Teacher $facilitator, SchoolYear $schoolYear, int $numberOfTeachers): Pagination
    {
        $pdf->Cell(8, $cellHeaderHeight2, utf8_decode($numero), 1, 0, 'C');

        if(strlen($department->getDepartment()) > 10)
        {
            $pdf->SetFont('Times', '', $fontSize-5);
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode($department->getDepartment()), 1, 0, 'L');
            $pdf->SetFont('Times', '', $fontSize);
            
        }else
        {
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode($department->getDepartment()), 1, 0, 'L');

        }

        if(!is_null($facilitator))
        {
            if(is_null($facilitator->getIntegrationDate()))
            {
                $anciennete = '//';
            }else
            {
                $anciennete = (int)explode('-', $schoolYear->getSchoolYear())[1] - (int)date_parse($facilitator->getIntegrationDate()->format('Y-m-d'))['year'];

                $anciennete = str_pad($anciennete, 2, '0', STR_PAD_LEFT);

                $anciennete .= ' ans';
            }

            if(strlen($facilitator->getFullName()) > 25)
            {
                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($facilitator->getFullName()), 1, 0, 'L');
                $pdf->SetFont('Times', '', $fontSize);
                
            }else
            {
                $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($facilitator->getFullName()), 1, 0, 'L');

            }
            
            $pdf->Cell(17, $cellHeaderHeight2, utf8_decode($facilitator->getGrade()->getGrade()), 1, 0, 'C');
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode($facilitator->getAdministrativeNumber()), 1, 0, 'C');
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode($anciennete), 1, 0, 'C');
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode($facilitator->getPhoneNumber()), 1, 0, 'C');

        }else
        {
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
            $pdf->Cell(17, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
        }

        $pdf->Cell(20, $cellHeaderHeight2, utf8_decode($numberOfTeachers), 1, 1, 'C');

        return $pdf;
    }

    public function getResponsableStudents(array $classrooms, SchoolYear $schoolYear)
    {
        $responsablestudents = [];

        foreach ($classrooms as $classroom) 
        {
            $responsabilityStudent = new ResponsableStudent();

            $responsabilityStudent->setClassroom($classroom);
        
            $students = $this->studentRepository->findResponsableStudents($classroom, $schoolYear);

            if(!empty($students))
            {
                
                foreach ($students as $student) 
                {
                    $responsability = $student->getResponsability()->getResponsability();
                    switch ($responsability) 
                    {
                        case ConstantsClass::RESPONSABILITY_KING_1:
                            $responsabilityStudent->setKing1($student);
                            break;
                            
                        case ConstantsClass::RESPONSABILITY_KING_2:
                            $responsabilityStudent->setKing2($student);
                        break;

                        case ConstantsClass::RESPONSABILITY_DELEGATE_1:
                            $responsabilityStudent->setDelegate1($student);
                        break;

                        case ConstantsClass::RESPONSABILITY_DELEGATE_2:
                            $responsabilityStudent->setDelegate2($student);
                        break;
                    }
                }

                $responsablestudents[] = $responsabilityStudent;
            }

        }

        return  $responsablestudents;
    }

    public function printResponsableStudents(array $responsableStudents, School $school, SchoolYear $schoolYear, SubSystem $subSystem):Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $fontSize = 10;
            $cellHeaderHeight2 = 8;
            $pdf = new Pagination();

            if(empty($responsableStudents))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Ln();
                $pdf->Cell(0, $cellHeaderHeight2, utf8_decode(' AUCUN RESPONSABLE DE CLASSE ENREGISTRE '), 0, 1, 'C');

                return $pdf;
            }

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService-> statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
            // Entête de la liste
            $pdf->SetFont('Times', 'B', 16);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('LISTE DES CHEFS ET DELEGUES DES CLASSES'), 0, 1, 'C');
            $pdf->Ln();

            // entête du tableau
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->Cell(40, $cellHeaderHeight2, utf8_decode('Classes'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Chefs'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Sous chefs'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Délégué N°1'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Délégué N°2'), 1, 1, 'C', true);

            $pdf->SetFont('Times', 'B', $fontSize-3);
            foreach ($responsableStudents as $responsableStudent) 
            {
                $pdf->SetFont('Times', 'B', $fontSize);
                $pdf->Cell(40, $cellHeaderHeight2, utf8_decode($responsableStudent->getClassroom()->getClassroom()), 1, 0, 'C');
                

                if(!is_null($responsableStudent->getKing1()))
                {
                    if(strlen($responsableStudent->getKing1()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing1()->getFullName()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing1()->getFullName()), 1, 0, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
                }

                if(!is_null($responsableStudent->getKing2()))
                {
                    if(strlen($responsableStudent->getKing2()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing2()->getFullName()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing2()->getFullName()), 1, 0, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
                }

                if(!is_null($responsableStudent->getDelegate1()))
                {
                    if(strlen($responsableStudent->getDelegate1()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate1()->getFullName()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate1()->getFullName()), 1, 0, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
                }
                

                if(!is_null($responsableStudent->getDelegate2()))
                {
                    if(strlen($responsableStudent->getDelegate2()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate2()->getFullName()), 1, 1, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate2()->getFullName()), 1, 1, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 1, 'C');
                }
            }

            $pdf->Ln();
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Fait à '.$school->getPlace().' le _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');
            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(223, $cellHeaderHeight2, utf8_decode('Le Proviseur'), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(223, $cellHeaderHeight2, utf8_decode('Le Directeur'), 0, 1, 'R');
                }
                
            }else
            {
                $pdf->Cell(223, $cellHeaderHeight2, utf8_decode('Le Principal'), 0, 1, 'R');
            }
            
        }else
        {
            $fontSize = 10;
            $cellHeaderHeight2 = 8;
            $pdf = new Pagination();

            if(empty($responsableStudents))
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                $pdf->SetFont('Times', '', 20);
                $pdf->Ln();
                $pdf->Cell(0, $cellHeaderHeight2, utf8_decode(' AUCUN RESPONSABLE DE CLASSE ENREGISTRE '), 0, 1, 'C');

                return $pdf;
            }

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService-> statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
            // Entête de la liste
            $pdf->SetFont('Times', 'B', 16);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('LIST OF CLASS HEADS AND DELEGATES'), 0, 1, 'C');
            $pdf->Ln();

            // entête du tableau
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->Cell(40, $cellHeaderHeight2, utf8_decode('Class'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Chiefs'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Sous chefs'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Delegate N°1'), 1, 0, 'C', true);
            $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('Delegate N°2'), 1, 1, 'C', true);

            $pdf->SetFont('Times', 'B', $fontSize-3);
            foreach ($responsableStudents as $responsableStudent) 
            {
                $pdf->SetFont('Times', 'B', $fontSize);
                $pdf->Cell(40, $cellHeaderHeight2, utf8_decode($responsableStudent->getClassroom()->getClassroom()), 1, 0, 'C');
                

                if(!is_null($responsableStudent->getKing1()))
                {
                    if(strlen($responsableStudent->getKing1()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing1()->getFullName()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing1()->getFullName()), 1, 0, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
                }

                if(!is_null($responsableStudent->getKing2()))
                {
                    if(strlen($responsableStudent->getKing2()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing2()->getFullName()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getKing2()->getFullName()), 1, 0, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
                }

                if(!is_null($responsableStudent->getDelegate1()))
                {
                    if(strlen($responsableStudent->getDelegate1()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate1()->getFullName()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate1()->getFullName()), 1, 0, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 0, 'C');
                }
                

                if(!is_null($responsableStudent->getDelegate2()))
                {
                    if(strlen($responsableStudent->getDelegate2()->getFullName()) > 23)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-3);
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate2()->getFullName()), 1, 1, 'L');
                        $pdf->SetFont('Times', 'B', $fontSize);
                    }else
                    {
                        $pdf->Cell(60, $cellHeaderHeight2, utf8_decode($responsableStudent->getDelegate2()->getFullName()), 1, 1, 'L');

                    }

                }else
                {
                    $pdf->Cell(60, $cellHeaderHeight2, utf8_decode('/'), 1, 1, 'C');
                }
            }

            $pdf->Ln();
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Done at '.$school->getPlace().' on _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');
            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(223, $cellHeaderHeight2, utf8_decode('The Principal'), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(223, $cellHeaderHeight2, utf8_decode('The Director'), 0, 1, 'R');
                }
                
            }else
            {
                $pdf->Cell(223, $cellHeaderHeight2, utf8_decode('The Principal'), 0, 1, 'R');
            }
        }

        return $pdf;

    }

    public function printSchoolStructure(?Teacher $headmaster, ?array $censors, ?array $supervisors, ?array $counsellors, ?Teacher $chiefOrientaion, ?Teacher $sportService, ?Teacher $chiefOfwork, ?Teacher $apps, ?Teacher $treasurer, ?Teacher $econome, ?array $teachersByDepartments, ?array $otherTeachers, School $school, SchoolYear $schoolYear, SubSystem $subSystem): Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 7;

        $cellNumber = 10;
        $cellDuty = 45;
        $cellFullName = 90;
        $cellPhone = 40;

        $cellNumber2 = 10;
        $cellDuty2 = 30;
        $cellFullName2 = 85;
        $cellPhone2 = 30;
        $cellDepartment2 = 30;

        $cellNumber3 = 10;
        $cellDuty3 = 30;
        $cellFullName3 = 85;
        $cellPhone3 = 30;
        $cellDepartment3 = 30;

        $pdf = new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
            
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
        
        // Entête de la liste
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode("STRUCTURE DE L'ETABLISSEMENT"), 0, 1, 'C');
            $pdf->Cell(100, $cellHeaderHeight2, utf8_decode($school->getFrenchName()), 0, 0, 'C');
            $pdf->Cell(90, $cellHeaderHeight2, utf8_decode('ANNEE SCOLAIRE '.$schoolYear->getSchoolYear()), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('I. Personnel administratif'), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty, $cellHeaderHeight2, utf8_decode('Fonctions'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName, $cellHeaderHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone, $cellHeaderHeight2, utf8_decode('Téléphone'), 1, 1, 'C', true);
        }
        else
        {
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode("STRUCTURE OF THE ESTABLISHMENT"), 0, 1, 'C');
            $pdf->Cell(100, $cellHeaderHeight2, utf8_decode($school->getFrenchName()), 0, 0, 'C');
            $pdf->Cell(90, $cellHeaderHeight2, utf8_decode('SCHOOL YEAR '.$schoolYear->getSchoolYear()), 0, 1, 'C');
            $pdf->Ln();
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('I. Administrative staff'), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty, $cellHeaderHeight2, utf8_decode('Functions'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName, $cellHeaderHeight2, utf8_decode('Names and surnames'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone, $cellHeaderHeight2, utf8_decode('Phone'), 1, 1, 'C', true);
        }
        

        $number = 1;

        // le proviseur
        $pdf = $this->displaySchoolStructureRowPagination($pdf, $headmaster, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);

        // les censeurs
        foreach($censors as $censor)
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $censor, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        

        // les surveillants
        foreach($supervisors as $supervisor)
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $supervisor, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        // chef service orientation
        if(!is_null($chiefOrientaion))
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $chiefOrientaion, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        // chef service des sports
        if(!is_null($sportService))
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $sportService, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        // chef des travaux
        if(!is_null($chiefOfwork))
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $chiefOfwork, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        // responsable APPS
        if(!is_null($apps))
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $apps, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        // intendant
        if(!is_null($treasurer))
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $treasurer, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        // économe
        if(!is_null($econome))
        {
            $number++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $econome, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $number);
        }

        // Le service orientation
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('II. Service orientation'), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty, $cellHeaderHeight2, utf8_decode('Fonctions'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName, $cellHeaderHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone, $cellHeaderHeight2, utf8_decode('Téléphone'), 1, 1, 'C', true);
        }
        else
        {
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('II. Guidance service'), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty, $cellHeaderHeight2, utf8_decode('Functions'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName, $cellHeaderHeight2, utf8_decode('Names and surnames'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone, $cellHeaderHeight2, utf8_decode('Phone'), 1, 1, 'C', true);
        }
        $i = 0;
        foreach($counsellors as $counsellor)
        {   
            $i++;
            $pdf = $this->displaySchoolStructureRowPagination($pdf, $counsellor, $cellNumber, $cellDuty, $cellFullName, $cellPhone, $cellHeaderHeight2, $i);
        }


        // Le personnel enseignant
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('III. Personnel enseignant'), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber2, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDepartment2, $cellHeaderHeight2, utf8_decode('Département'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName2, $cellHeaderHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty2, $cellHeaderHeight2, utf8_decode('Fonctions'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone2, $cellHeaderHeight2, utf8_decode('Téléphone'), 1, 1, 'C', true);
        }
        else
        {
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('III. Personnel enseignant'), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber2, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDepartment2, $cellHeaderHeight2, utf8_decode('Department'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName2, $cellHeaderHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty2, $cellHeaderHeight2, utf8_decode('Functions'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone2, $cellHeaderHeight2, utf8_decode('Phone'), 1, 1, 'C', true);
        }
        $number = 0;
        foreach($teachersByDepartments as $teachersByDepartment)
        {
            $department = $teachersByDepartment['department'];
            $teachers = $teachersByDepartment['teachers'];

            $numberOfTeachers = count($teachers);

            if($numberOfTeachers)
            {
                foreach($teachers as $teacher)
                {   
                    $number++;
                    $pdf = $this->displaySchoolStructureRowTeacherPagination($pdf, $teacher, $cellDuty2, $cellFullName2, $cellPhone2, $cellHeaderHeight2, $cellNumber2, $number,  $cellDepartment2, $fontSize, $department);
                }

            }

        }

        // Le personnel d'appui
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode("III. Personnel d'appui"), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber3, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDepartment3, $cellHeaderHeight2, utf8_decode('Départements'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName3, $cellHeaderHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty3, $cellHeaderHeight2, utf8_decode('Fonctions'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone3, $cellHeaderHeight2, utf8_decode('Téléphone'), 1, 1, 'C', true);
        }
        else
        {
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode("III. Support staff"), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);

            // entête du tableau
            $pdf->Cell($cellNumber3, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellDepartment3, $cellHeaderHeight2, utf8_decode('Departments'), 1, 0, 'C', true);
            $pdf->Cell($cellFullName3, $cellHeaderHeight2, utf8_decode('Names and surnames'), 1, 0, 'C', true);
            $pdf->Cell($cellDuty3, $cellHeaderHeight2, utf8_decode('Functions'), 1, 0, 'C', true);
            $pdf->Cell($cellPhone3, $cellHeaderHeight2, utf8_decode('Phone'), 1, 1, 'C', true);
        }

        $number = 0;
        foreach($otherTeachers as $otherTeacher)
        {  
            $department = $otherTeacher->getDepartment();
            $number++;
            $pdf = $this->displaySchoolStructureRowTeacherPagination($pdf, $otherTeacher, $cellDuty3, $cellFullName3, $cellPhone3, $cellHeaderHeight2, $cellNumber3, $number,  $cellDepartment3, $fontSize, $department);

        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
             
            $pdf->Ln();
            $pdf->Cell(100, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
            $pdf->Cell(50, $cellHeaderHeight2, utf8_decode('Fait à '.$school->getPlace().' le _ _ _ _ _ _ _ _ _'), 0, 1, 'L');

            $pdf->Cell(100, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
            $pdf->Cell(50, $cellHeaderHeight2, utf8_decode('Le '.$school->getHeadmaster()->getDuty()->getDuty()), 0, 1, 'L');
        }
        else
        {
            $pdf->Ln();
            $pdf->Cell(100, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
            $pdf->Cell(50, $cellHeaderHeight2, utf8_decode('Done at '.$school->getPlace().' le _ _ _ _ _ _ _ _ _'), 0, 1, 'L');

            $pdf->Cell(100, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
            switch($school->getHeadmaster()->getDuty())
            {
                case ConstantsClass::HEADMASTER_DUTY :
                    $pdf->Cell(50, $cellHeaderHeight2, utf8_decode('The PRINCIPAL'), 0, 1, 'L');
                    break;

                case ConstantsClass::DIRECTOR_DUTY :
                    $pdf->Cell(50, $cellHeaderHeight2, utf8_decode('The DIRECTOR'), 0, 1, 'L');
                    break;
            }
            
        }
           

        

        return $pdf;
    }

    /**
     * Affiche une ligne de la structure de l'établissement personnel administratif
     */
    public function displaySchoolStructureRowPagination(Pagination $pdf, Teacher $teacher, int $cellNumber, int $cellDuty, int $cellFullName, int $cellPhone, int $cellHeaderHeight2, int $number): Pagination
    {
        $pdf->Cell($cellNumber, $cellHeaderHeight2, utf8_decode($number), 1, 0, 'C');
        switch($teacher->getDuty()->getDuty())
        {
            case ConstantsClass::SPORT_SERVICE_DUTY: 
                $pdf->Cell($cellDuty, $cellHeaderHeight2, utf8_decode("CSSS"), 1, 0, 'L');
                break;

            case ConstantsClass::CHIEF_ORIENTATION_DUTY: 
                $pdf->Cell($cellDuty, $cellHeaderHeight2, utf8_decode("CSO"), 1, 0, 'L');
                break;

            default: 
                $pdf->Cell($cellDuty, $cellHeaderHeight2, utf8_decode($teacher->getDuty()->getDuty()), 1, 0, 'L');
                break;
        }
        $pdf->Cell($cellFullName, $cellHeaderHeight2, utf8_decode($teacher->getFullName()), 1, 0, 'L');
        $pdf->Cell($cellPhone, $cellHeaderHeight2, utf8_decode($teacher->getPhoneNumber()), 1, 1, 'L');

        return $pdf;
    }

    /**
     * Affiche une ligne de la structure de l'établissement personnel enseignant
     */
    public function displaySchoolStructureRowTeacherPagination(Pagination $pdf, Teacher $teacher, int $cellDuty2, int $cellFullName2, int $cellPhone2, int $cellHeaderHeight2, int $cellNumber2, int $number, int $cellDepartment2, int $fontSize, Department $department = null): Pagination
    {
        $pdf->Cell($cellNumber2, $cellHeaderHeight2, utf8_decode($number), 1, 0, 'C');

        if(!is_null($department))
        {
            $pdf->SetFont('Times', 'B', $fontSize-2);
            $pdf->Cell($cellDepartment2, $cellHeaderHeight2, utf8_decode($department->getDepartment()), 1, 0, 'C');
            $pdf->SetFont('Times', 'B', $fontSize);
        }

        $pdf->Cell($cellFullName2, $cellHeaderHeight2, utf8_decode($teacher->getFullName()), 1, 0, 'L');
        switch($teacher->getDuty()->getDuty())
        {
            case ConstantsClass::AP_DUTY:
                $pdf->Cell($cellDuty2, $cellHeaderHeight2, utf8_decode("Ani. Pédag."), 1, 0, 'L');
                break;
            
                default:
                $pdf->Cell($cellDuty2, $cellHeaderHeight2, utf8_decode($teacher->getDuty()->getDuty()), 1, 0, 'L');
                break;
                
        }

        $pdf->Cell($cellPhone2, $cellHeaderHeight2, utf8_decode($teacher->getPhoneNumber()), 1, 1, 'L');

        return $pdf;
    }

    /**
     * Liste des enseignants par département
     */
    public function getTeachersByDepartment(array $departments, SchoolYear $schoolYear, ?Duty $duty): array
    {
        $teachersByDepartment = [];

        foreach ($departments as $department) 
        {
           $teachers = $this->teacherRepository->findBy([
               'department' => $department,
               'schoolYear' => $schoolYear,
               'duty' => $duty
           ], [
               'fullName' => 'ASC'
           ]);

           $teachersByDepartment[] = ['department' => $department, 'teachers' => $teachers];
        }

        return $teachersByDepartment;
    }

    public function printStaffList(?Teacher $headmaster, ?array $censors, ?array $supervisors, ?array $counsellors, ?Teacher $chiefOrientaion, ?Teacher $sportSercive, ?Teacher $chiefOfwork, ?Teacher $apps, ?Teacher $treasurer, ?Teacher $econome, ?array $ap, ?array $teachers, ?School $school, ?schoolYear $schoolYear, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $fontSize = 10;

            $cellHeaderHeight2 = 8;

            $pdf = new Pagination();

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService-> statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
            // Entête de la liste
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('FICHIER DU PERSONNEL EN SERVICE AU '.$school->getFrenchName()), 0, 1, 'C');
            $pdf->Ln();

            $x = 0;
            $r = 0; 
            $y = 70;

            $cell10 = 10;
            $cell22 = 22;

            $pdf->SetFont('Times', 'B', $fontSize-2);
            //  entête du tableau
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cell10+5, $cellHeaderHeight2, utf8_decode('Matricule'), 1, 0, 'C', true);
            $pdf->Cell($cell10*6, $cellHeaderHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Date naissance'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Lieu Naissance'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Sexe'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('SM'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Statut'), 1, 0, 'C', true);
            $pdf->Cell($cell10+5, $cellHeaderHeight2, utf8_decode('Dipôme'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Discipline'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Grade'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Fonction'), 1, 0, 'C', true);
            $pdf->Cell($cell22, $cellHeaderHeight2/2, utf8_decode('Ancienneté'), 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x-$cell22, $y+$cellHeaderHeight2/2);
            $pdf->Cell($cell22/2, $cellHeaderHeight2/2, utf8_decode('Au poste'), 1, 0, 'C', true);
            $pdf->Cell($cell22/2, $cellHeaderHeight2/2,  utf8_decode('A la FP'), 1, 0, 'C', true);
            $pdf->SetXY($x, $y);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($cell10, $cellHeaderHeight2/2, utf8_decode('Heures'), 'LTR', 2, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2/2, utf8_decode('/Sem'), 'LBR', 0, 'C', true);
            $pdf->SetXY($x+$cell10, $y);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Région'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Téléphone'), 1, 1, 'C', true);

            $number = 0;
            // Le proviseur
            if(!is_null($headmaster))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $headmaster,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }

            // les censeurs
            foreach ($censors as $censor) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $censor,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);
                
            }

            // les surveillants
            foreach ($supervisors as $supervisor) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $supervisor,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);
                
            }

            // Le chef service orientation
            if(!is_null($chiefOrientaion))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $chiefOrientaion,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }

            // Le chef service des sports
            if(!is_null($sportSercive))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $sportSercive,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }
            
            // Le responsable apps
            if(!is_null($apps))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $apps,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }
            
            // L'intendant
            if(!is_null($treasurer))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $treasurer,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }
            
            // L'econome
            if(!is_null($econome))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $econome,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }

            // les conseillers
            foreach ($counsellors as $counsellor) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $counsellor,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);
                
            }

            // les ap
            foreach ($ap as $a) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $a,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize); 
            }

            // les enseignants
            foreach ($teachers as $teacher) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $teacher,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize); 
            }

            $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
        }else
        {
            $fontSize = 10;

            $cellHeaderHeight2 = 8;

            $pdf = new Pagination();

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService-> statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
            // Entête de la liste
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('FILE OF PERSONNEL IN SERVICE AT '.$school->getFrenchName()), 0, 1, 'C');
            $pdf->Ln();

            $x = 0;
            $r = 0; 
            $y = 70;

            $cell10 = 10;
            $cell22 = 22;

            $pdf->SetFont('Times', 'B', $fontSize-2);
            //  entête du tableau
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cell10+5, $cellHeaderHeight2, utf8_decode('Reg. Num.'), 1, 0, 'C', true);
            $pdf->Cell($cell10*6, $cellHeaderHeight2, utf8_decode('Names'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Date of birth'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Birth Place'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Sex'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('MS'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Status'), 1, 0, 'C', true);
            $pdf->Cell($cell10+5, $cellHeaderHeight2, utf8_decode('Diploma'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Discipline'), 1, 0, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Grade'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Fonction'), 1, 0, 'C', true);
            $pdf->Cell($cell22, $cellHeaderHeight2/2, utf8_decode('Seniority'), 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x-$cell22, $y+$cellHeaderHeight2/2);
            $pdf->Cell($cell22/2, $cellHeaderHeight2/2, utf8_decode('At office'), 1, 0, 'C', true);
            $pdf->Cell($cell22/2, $cellHeaderHeight2/2,  utf8_decode('In the CS'), 1, 0, 'C', true);
            $pdf->SetXY($x, $y);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($cell10, $cellHeaderHeight2/2, utf8_decode('Hours'), 'LTR', 2, 'C', true);
            $pdf->Cell($cell10, $cellHeaderHeight2/2, utf8_decode('/Week'), 'LBR', 0, 'C', true);
            $pdf->SetXY($x+$cell10, $y);
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode('Region'), 1, 0, 'C', true);
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode('Phone'), 1, 1, 'C', true);

            $number = 0;
            // Le proviseur
            if(!is_null($headmaster))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $headmaster,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }

            // les censeurs
            foreach ($censors as $censor) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $censor,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);
                
            }

            // les surveillants
            foreach ($supervisors as $supervisor) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $supervisor,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);
                
            }

            // Le chef service orientation
            if(!is_null($chiefOrientaion))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $chiefOrientaion,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }

            // Le chef service des sports
            if(!is_null($sportSercive))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $sportSercive,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }
            
            // Le responsable apps
            if(!is_null($apps))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $apps,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }
            
            // L'intendant
            if(!is_null($treasurer))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $treasurer,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }
            
            // L'econome
            if(!is_null($econome))
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $econome,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);

            }

            // les conseillers
            foreach ($counsellors as $counsellor) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $counsellor,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize);
                
            }

            // les enseignants
            foreach ($teachers as $teacher) 
            {
                $number++;
                $pdf = $this->displayStaffRowPagination($pdf, $teacher,  $cell10, $cell22, $cellHeaderHeight2, $number, $schoolYear, $fontSize); 
            }

            $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);

        }
        return $pdf;

    }

    /**
     * Affiche une ligne dans le tableau du personnel
     */
    public function displayStaffRowPagination(Pagination $pdf, Teacher $teacher, int $cell10, int $cell22, int $cellHeaderHeight2, int $number, SchoolYear $schoolYear, int $fontSize): Pagination
    {
        if(!is_null($teacher->getintegrationDate()))
        {
            $ancienneteFP = (int)explode('-', $schoolYear->getSchoolYear())[1] - (int)date_parse($teacher->getintegrationDate()->format('Y-m-d'))['year'];
            $ancienneteFP = str_pad($ancienneteFP, 2, '0', STR_PAD_LEFT);

        }else
        {
            $ancienneteFP = '//';
        }

        if(!is_null($teacher->getAffectationDate()))
        {
            $anciennetePoste = (int)explode('-', $schoolYear->getSchoolYear())[1] - (int)date_parse($teacher->getAffectationDate()->format('Y-m-d'))['year'];
            $anciennetePoste = str_pad($anciennetePoste, 2, '0', STR_PAD_LEFT);

        }else
        {
            $anciennetePoste = '//';
        }


        $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode($number), 1, 0, 'C');
        $pdf->Cell($cell10+5, $cellHeaderHeight2, utf8_decode($teacher->getAdministrativeNumber()), 1, 0, 'C');
        $pdf->Cell($cell10*6, $cellHeaderHeight2, utf8_decode($teacher->getFullName()), 1, 0, 'L');

        if(is_null($teacher->getBirthday()))
        {
            $pdf->Cell($cell10*2, $cellHeaderHeight2, '//', 1, 0, 'C');

        }else
        {
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode($teacher->getBirthday()->format('d-m-Y')), 1, 0, 'C');

        }
        if(is_null($teacher->getBirthplace()))
        {
            $pdf->Cell($cell10*2, $cellHeaderHeight2, '//', 1, 0, 'C');

        }else
        {
            $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode($teacher->getBirthplace()), 1, 0, 'C');

        }

        $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode($teacher->getSex() ? $teacher->getSex()->getSex():""), 1, 0, 'C');

        if(is_null($teacher->getMatrimonialStatus()))
        {
            $pdf->Cell($cell10, $cellHeaderHeight2, '//', 1, 0, 'C');

        }else
        {
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode($teacher->getMatrimonialStatus()->getMatrimonialStatus()), 1, 0, 'C');

        }

        if(is_null($teacher->getStatus()))
        {
            $pdf->Cell($cell10, $cellHeaderHeight2, '//', 1, 0, 'C');

        }else
        {
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode(substr($teacher->getStatus()->getStatus(), 0, 4) ), 1, 0, 'C');

        }

        if(is_null($teacher->getDiploma()))
        {
            $pdf->Cell($cell10+5, $cellHeaderHeight2, '//', 1, 0, 'C');

        }else
        {
            $pdf->Cell($cell10+5, $cellHeaderHeight2, utf8_decode($teacher->getDiploma()->getDiploma()), 1, 0, 'C');

        }

        if(is_null($teacher->getDepartment()))
        {
            $pdf->Cell($cell10*2, $cellHeaderHeight2, '//', 1, 0, 'C');

        }else
        {
            $teacherDepartment = $teacher->getDepartment()->getDepartment();
            if(strlen($teacherDepartment) > 9)
            {
                $pdf->SetFont('Times', 'B', $fontSize-5);
                $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode($teacher->getDepartment()->getDepartment()), 1, 0, 'C');
                $pdf->SetFont('Times', 'B', $fontSize-2);

            }else
            {
                $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode($teacher->getDepartment()->getDepartment()), 1, 0, 'C');

            }

        }
        $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode($teacher->getGrade() ? $teacher->getGrade()->getGrade():""), 1, 0, 'C');
        $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode($teacher->getDuty()->getDuty()), 1, 0, 'C');
        $pdf->Cell($cell22/2, $cellHeaderHeight2, utf8_decode($anciennetePoste), 1, 0, 'C');
        $pdf->Cell($cell22/2, $cellHeaderHeight2, utf8_decode($ancienneteFP), 1, 0, 'C');

        $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode($this->getHoursPerWeek($teacher)), 1, 0, 'C');

        if(is_null($teacher->getRegion()))
        {
            $pdf->Cell($cell10, $cellHeaderHeight2, '//', 1, 0, 'C');

        }else
        {
            $pdf->Cell($cell10, $cellHeaderHeight2, utf8_decode($teacher->getRegion()->getRegion()), 1, 0, 'C');

        }
        $pdf->Cell($cell10*2, $cellHeaderHeight2, utf8_decode($teacher->getPhoneNumber()), 1, 1, 'C');

        return $pdf;
    }

    /**
     * Compte le nombre d'heures par semaine d'un enseignant
     */
    public function getHoursPerWeek(Teacher $teacher): int
    {
        $numberOfHours = 0;
        $lessons = $teacher->getLessons();

        foreach($lessons as $lesson)
        {
            $numberOfHours += $lesson->getWeekHours();
        }

        return $numberOfHours;

    }


    /**
     * On imprime les élèves particuliers
     */
    public function printParticularStudent(array $allStudents, School $school, SchoolYear $schoolYear, EthnicGroup $ethnicGroup = null, Movement $movement = null, Handicap $handicap = null, HandicapType $handicapType = null, Country $country = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 8;
        $cellBodyHeight2 = 7;

        $pdf = new PDF();
        if(!empty($allStudents))
        {
            foreach ($allStudents as $students) 
            {
                if(!empty($students))
                {
                    $classroom = $students[0]->getClassroom();
                    // On insère une page
                    $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                       
                   // Administrative Header
                   $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                   
                   $pdf->SetFont('Times', 'B', $fontSize+3);

                   $title = $this->getTitleParticularStudent($ethnicGroup, $movement, $handicap, $handicapType, $country);

                    // Entête de la fiche   
                   $pdf->Ln();
                   $pdf->Cell(0, 10, utf8_decode($title), 0, 1, 'C'); 
                   $pdf->Cell(100, 10, utf8_decode($school->getFrenchName()), 0, 0, 'C'); 
                   $pdf->Cell(90, 10, utf8_decode('Classe : '.$classroom->getClassroom()), 0, 1, 'C');
                   $pdf->Ln();
                    //    entête du tableau
                    $pdf->SetFont('Times', 'B', $fontSize);
                    $pdf->Cell(100, $cellBodyHeight2, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
                    $pdf->Cell(70, $cellBodyHeight2, utf8_decode('Date et lieu de naissance'), 1, 0, 'C', true);
                    $pdf->Cell(20, $cellBodyHeight2, utf8_decode('Sexe'), 1, 1, 'C', true);

                    // contenu du tableau 
                    foreach ($students as $student) 
                    {
                        $pdf->Cell(100, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L');
                        $pdf->Cell(70, $cellBodyHeight2, utf8_decode($student->getBirthday()->format('d-m-Y').' à '.$student->getBirthplace()), 1, 0, 'L');
                        $pdf->Cell(20, $cellBodyHeight2, utf8_decode($student->getSex()->getSex()), 1, 1, 'C');
                    }

                    $pdf = $this->generalService->doAt($pdf, $school, $cellHeaderHeight2);
    
                }
            }

        }else
        {
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                       
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
            $pdf->SetFont('Times', 'B', $fontSize+3);
            $title = $this->getTitleParticularStudent($ethnicGroup, $movement, $handicap, $handicapType, $country);

            $pdf->Ln();
            $pdf->Cell(0, 10, utf8_decode($title), 0, 1, 'C'); 
            $pdf->Cell(0, 10, utf8_decode($school->getFrenchName()), 0, 1, 'C');
            $pdf->Cell(0, 10, utf8_decode('AUCUN ELEVE ENRERISTRE REMPLISSANT CES CRITERES'), 0, 1, 'C');

            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf = $this->generalService->doAt($pdf, $school, $cellHeaderHeight2);
        }

        

        return $pdf;
    }

    public function getTitleParticularStudent(EthnicGroup $ethnicGroup = null, Movement $movement = null, Handicap $handicap = null, HandicapType $handicapType = null, Country $country = null): string
    {
        $title = 'Liste des élèves';

        if($ethnicGroup)
        {
            $title .= ' '.$ethnicGroup->getEthnicGroup();
        }

        if($movement)
        {
            $title .= ' '.$movement->getMovement();
        }

        if($handicap)
        {
            $title .= ' '.$handicap->getHandicap();
        }

        if($handicapType)
        {
            $title .= ' '.$handicapType->getHandicapType();
        }

        if($country)
        {
            $title .= " pays d'origine ".$country->getCountry();
        }

        return $title;
    }

    
}
<?php

namespace App\Service;
use App\Entity\School;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class RepertoryClassroomService
{
    public function __construct(
        protected RequestStack $request,
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected DutyRepository $dutyRepository, 
        protected StudentRepository $studentRepository, 
        protected TeacherRepository $teacherRepository, 
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
     * Imprime le repertoire de la classe
     *
     * @param School $school
     * @return PDF
     */
    public function printRepertoryClassroom(School $school, SchoolYear $schoolYear, Classroom $classroom): PDF
    {
        
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new PDF();
        $studentList = $classroom->getStudents();

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if(empty($studentList))
        {
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer le répertoire !"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("La classe sélectionné ne contient aucun élève."), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Unable to print contact list!"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("The class contains no students."), 0, 1, 'C');
            }

            return $pdf;
        }

        
        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
        
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
        
        // Entête de la liste
        $pdf = $this->getHeaderStudentList($pdf, $classroom);

        // entête du tableau
        $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2);

        // contenu du tableau
        $pdf->SetFont('Times', '', $fontSize);
        $numero = 0;
        $numberOfStudents = count($studentList);
        foreach($studentList as $student)
        {
            if ($student->isSupprime() == 0) 
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
                $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->Cell(80, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);

                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(94.5, $cellBodyHeight2, ($student->getTelephonePere() ? utf8_decode($student->getTelephonePere())." - "  : " - ").($student->getTelephoneMere() ? utf8_decode($student->getTelephoneMere())." - " : " - " ).($student->getTelephoneTuteur() ? utf8_decode($student->getTelephoneTuteur())." - " :" - ").($student->getTelephonePersonneEnCasUrgence() ? utf8_decode($student->getTelephonePersonneEnCasUrgence()." - ") :" - "), 1, 0, 'C', true);
                
                $pdf->Ln();

                // if( ($numero % 30) == 0 && $numberOfStudents > 30) /*On passe à une nouvelle page après 30 lignes*/
                // {
                //     // On insère une page
                //     $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                    
                //     // Administrative Header
                //     $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
        
                //     // Entête de la liste
                //     $pdf = $this->getHeaderStudentList($pdf, $classroom);

                //     // entête du tableau
                //     $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2);

                //     $pdf->SetFont('Times', '', $fontSize);

                // }
            }
            
        }
        $pdf = $this->generalService->doAt($pdf, $school, $cellHeaderHeight2);
        

        return $pdf;
    }

    /**
     * Imprime le repertoire des parents de la classe
     *
     * @param School $school
     * @return PDF
     */
    public function printRepertoryParentClassroom(School $school, SchoolYear $schoolYear, Classroom $classroom): PDF
    {
        
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new PDF();
        $studentList = $classroom->getStudents();

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if(empty($studentList))
        {
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Impossible d'imprimer le répertoire des parents!"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("La classe sélectionné ne contient aucun élève."), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode("Unable to print contact list of parents!"), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode("The class contains no students."), 0, 1, 'C');
            }

            return $pdf;
        }

        
        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
        
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
        
        // Entête de la liste
        $pdf = $this->getHeaderParentList($pdf, $classroom);

        // entête du tableau
        $pdf = $this->getTableHeaderParentList($pdf, $cellHeaderHeight2);

        // contenu du tableau
        $pdf->SetFont('Times', '', $fontSize);
        $numero = 0;
        $numberOfStudents = count($studentList);
        foreach($studentList as $student)
        {
            if ($student->isSupprime() == 0) 
            {
                $numero++;
                // if ($numero % 2 != 0) 
                // {
                //     $pdf->SetFillColor(219,238,243);
                // }
                // else 
                // {
                //     $pdf->SetFillColor(255,255,255);
                // }
                $pdf->Cell(10, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->Cell(0, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 1, 'C', true);

                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell(29, $cellBodyHeight2, utf8_decode(""), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode("Père/Father"), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode("Mère/Mother"), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode("Tuteur/Tutor"), 1, 1, 'C');

                $pdf->Cell(29, $cellBodyHeight2, utf8_decode("Nom / Name"), 1, 0, 'C');
                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getFatherName() ? utf8_decode($student->getFatherName())  : " - "), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getMotherName() ? utf8_decode($student->getMotherName())  : " - "), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getTuteur() ? utf8_decode($student->getTuteur())  : " - "), 1, 1, 'C');

                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell(29, $cellBodyHeight2, utf8_decode("Profession"), 1, 0, 'C');
                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getProfessionPere() ? utf8_decode($student->getProfessionPere())  : " - "), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getProfessionMere() ? utf8_decode($student->getProfessionMere())  : " - "), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getProfessionTuteur() ? utf8_decode($student->getProfessionTuteur())  : " - "), 1, 1, 'C');

                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell(29, $cellBodyHeight2, utf8_decode("Contact"), 1, 0, 'C');
                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getTelephonePere() ? utf8_decode($student->getTelephonePere())  : " - "), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getTelephoneMere() ? utf8_decode($student->getTelephoneMere()) : " - "), 1, 0, 'C');
                $pdf->Cell(52, $cellBodyHeight2, utf8_decode($student->getTelephoneTuteur() ? utf8_decode($student->getTelephoneTuteur()) :" - "), 1, 1, 'C');

                

                // $pdf->Cell(94.5, $cellBodyHeight2, ($student->getTelephonePere() ? utf8_decode($student->getTelephonePere())." - "  : " - ").($student->getTelephoneMere() ? utf8_decode($student->getTelephoneMere())." - " : " - " ).($student->getTelephoneTuteur() ? utf8_decode($student->getTelephoneTuteur())." - " :" - ").($student->getTelephonePersonneEnCasUrgence() ? utf8_decode($student->getTelephonePersonneEnCasUrgence()." - ") :" - "), 1, 0, 'C', true);
                

                // if( ($numero % 30) == 0 && $numberOfStudents > 30) /*On passe à une nouvelle page après 30 lignes*/
                // {
                //     // On insère une page
                //     $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                    
                //     // Administrative Header
                //     $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
        
                //     // Entête de la liste
                //     $pdf = $this->getHeaderStudentList($pdf, $classroom);

                //     // entête du tableau
                //     $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2);

                //     $pdf->SetFont('Times', '', $fontSize);

                // }
            }
            
        }
        $pdf = $this->generalService->doAt($pdf, $school, $cellHeaderHeight2);
        

        return $pdf;
    }



    /**
     * Entête de la fiche de la liste des élèves
     *
     * @param PDF $pdf
     * @param School $school
     * @return PDF
     */
    public function getHeaderStudentList(PDF $pdf, Classroom $classroom): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Times', 'B', 14);
        
        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->Cell(0, 5, 'REPERTOIRE DES ELEVES', 0, 2, 'C');
        } else 
        {
            $pdf->Cell(0, 5, "STUDENT'S CONTACTS", 0, 2, 'C');
        }

        
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'C');
        $pdf->Cell(0, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 1, 1, 'C', true);
        

        $pdf->Cell(35, 5, '', 0, 0, 'C');
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        return $pdf;
    }

    /**
     * Entête de la fiche du repertoire des parents d'une classe
     *
     * @param PDF $pdf
     * @param Classroom $classroom
     * @return PDF
     */
    public function getHeaderParentList(PDF $pdf, Classroom $classroom): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Times', 'B', 14);
        
        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->Cell(0, 5, 'REPERTOIRE DES PARENTS DES ELEVES', 0, 2, 'C');
        } else 
        {
            $pdf->Cell(0, 5, "PARENTS'S ADRESS", 0, 2, 'C');
        }

        
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'C');
        $pdf->Cell(0, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 1, 1, 'C', true);
        

        $pdf->Cell(35, 5, '', 0, 0, 'C');
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        return $pdf;
    }


    /**
     * Entête du tableau de la liste des élèves
     *
     * @param PDF $pdf
     * @param integer $cellHeaderHeight2
     * @return PDF
     */
    public function getTableHeaderStudentList(PDF $pdf, int $cellHeaderHeight2): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeaderHeight2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Cell(94.5, $cellHeaderHeight2, utf8_decode('Contact :  du Père / de la Mère / du Tuteur'), 1, 0, 'C', true);
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeaderHeight2, utf8_decode("First and last names"), 1, 0, 'C', true);
            $pdf->Cell(94.5, $cellHeaderHeight2, utf8_decode("Contact : Father / Mother / Tutor"), 1, 0, 'C', true);
            $pdf->Ln();
        }

        return $pdf;
    }


    /**
     * Entête du tableau de la liste des élèves
     *
     * @param PDF $pdf
     * @param integer $cellHeaderHeight2
     * @return PDF
     */
    public function getTableHeaderParentList(PDF $pdf, int $cellHeaderHeight2): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode("First and last names"), 1, 0, 'C', true);
            $pdf->Ln();
        }

        return $pdf;
    }


    
}
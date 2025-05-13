<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\School;
use App\Entity\Teacher;
use App\Entity\Sequence;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Entity\ReportElements\PDF;
use App\Repository\TermRepository;
use App\Repository\LevelRepository;
use App\Repository\LessonRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\EvaluationRepository;
use App\Entity\ReportElements\Pagination;
use Symfony\Component\HttpFoundation\RequestStack;

class TeacherService 
{
    public function __construct(
        protected RequestStack $request,
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected LevelRepository $levelRepository, 
        protected LessonRepository $lessonRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected SequenceRepository $sequenceRepository,
        protected EvaluationRepository $evaluationRepository, 
        )
    {}

    /**
     * Recherche et retourne la liste des enseignants qui n'ont pas encore saisi de notes
     *
     * @param integer $termId
     * @param integer $levelId
     * @param integer $classroomId
     * @return array
     */
    public function getUnrecordedMark(int $termId, int $levelId = 0, int $classroomId = 0): array
    {  
        $evaluations = [];

        $mySession = $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
            $schoolYear = $mySession->get('schoolYear');
        }

        $selectedTerm = $this->termRepository->find($termId);
        $selectedLevel = $this->levelRepository->find($levelId);
        $selectedClassroom = $this->classroomRepository->find($classroomId);

        // toutes les séquences du trimestre
        $sequences = $selectedTerm->getSequences();

        if($selectedLevel) // Les lessons du niveau sélectionné
        {
            $allLessons = $this->lessonRepository->findAllForLevel($selectedLevel, $schoolYear, $subSystem);
        }
        elseif($selectedClassroom) // Les lessons de la classe sélectionnée
        {
            $allLessons = $this->lessonRepository->findAllToDisplay($selectedClassroom, $subSystem, true);
        }
        else // Les lessons de tous les niveax
        {
            $allLessons = $this->lessonRepository->findAllLessonsOfSchoolYear($schoolYear, $subSystem);
        }
        
        foreach ($allLessons as $lesson) 
        {
            foreach ($sequences as $sequence) 
            {
                $recordedEvaluation = $this->evaluationRepository->findOneBy([
                    'lesson' => $lesson, 
                    'sequence' => $sequence
                ]);
              
                if($recordedEvaluation == null)
                {
                    $evaluations[] = ['lesson' => $lesson, 'sequence' => $sequence];
                }
            }
        }
        
        return $evaluations;
    }


    /**
     * Enseignants dans le retard des saisies par séquence
     *
     * @param integer $sequenceId
     * @param integer $levelId
     * @param integer $classroomId
     * @return array
     */
    public function displayTeachersLaters(int $sequenceId, int $levelId = 0, int $classroomId = 0): array
    {  
        $evaluations = [];

        $mySession = $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
            $schoolYear = $mySession->get('schoolYear');
        }

        // $selectedTerm = $this->termRepository->find($termId);
        $sequence = $this->sequenceRepository->find($sequenceId);
        $selectedLevel = $this->levelRepository->find($levelId);
        $selectedClassroom = $this->classroomRepository->find($classroomId);

        // toutes les séquences du trimestre
        // $sequences = $selectedTerm->getSequences();

        if($selectedLevel) // Les lessons du niveau sélectionné
        {
            $allLessons = $this->lessonRepository->findAllForLevel($selectedLevel, $schoolYear, $subSystem);
        }
        elseif($selectedClassroom) // Les lessons de la classe sélectionnée
        {
            $allLessons = $this->lessonRepository->findAllToDisplay($selectedClassroom, $subSystem, true);
        }
        else // Les lessons de tous les niveax
        {
            $allLessons = $this->lessonRepository->findAllLessonsOfSchoolYear($schoolYear, $subSystem);
        }
        
        foreach ($allLessons as $lesson) 
        {
            // foreach ($sequences as $sequence) 
            // {
                $recordedEvaluation = $this->evaluationRepository->findOneBy([
                    'lesson' => $lesson, 
                    'sequence' => $sequence
                ]);
              
                if($recordedEvaluation == null)
                {
                    $evaluations[] = ['lesson' => $lesson, 'sequence' => $sequence];
                }
            // }
        }
        
        return $evaluations;
        
    }

    /**
     * Imprime la liste des enseignants retardataires dans les saisies
     * @param array $evaluations, 
     * @param School $school, 
     * @param SchoolYear $schoolYear, 
     * @param Term $term
     */

    public function printLaters(array $evaluations, School $school, SchoolYear $schoolYear, Sequence $sequence):Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight1 = 4;
        $cellHeaderHeight = 6;

        $numberWith = 10;
        $fullNameWith = 70;
        $classroomWith = 25;
        $subjectWith = 60;

        $pdf = new Pagination();

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
            
        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);
         
        // Entête de la liste
        $pdf = $this-> getHeaderLatersList($pdf, $school, $sequence);

        //  entête du tableau 
        $pdf = $this->getTableHeaderLatersList($pdf, $cellHeaderHeight, $numberWith, $fullNameWith, $classroomWith, $subjectWith);

        $number = 0;
        foreach ($evaluations as $evaluation) 
        {
            $lesson = $evaluation['lesson'];
            $sequence = $evaluation['sequence'];

            $number++;
            $pdf->SetFont('Times', '', 9);
            $pdf->Cell($numberWith, $cellHeaderHeight, $number, 1, 0, 'C');
            $pdf->Cell($fullNameWith+10, $cellHeaderHeight, utf8_decode($lesson->getTeacher()->getFullName()), 1, 0, 'L');
            // $pdf->Cell($classroomWith, $cellHeaderHeight, utf8_decode($sequence->getSequence()), 1, 0, 'C');
            $pdf->Cell($classroomWith+10, $cellHeaderHeight, utf8_decode($lesson->getClassroom()->getClassroom()), 1, 0, 'C');
            $pdf->Cell($subjectWith+5, $cellHeaderHeight, utf8_decode($lesson->getSubject()->getSubject()), 1, 0, 'C');
            $pdf->Ln();
        }

        return $pdf;
    }

    /**
     * Undocumented function
     *
     * @param Pagination $pdf
     * @param School $school
     * @param Sequence $sequence
     * @return Pagination
     */
    public function getHeaderLatersList(Pagination $pdf, School $school, Sequence $sequence): Pagination
    {
        $mySession = $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'LISTE DES NOTES ENCORE ATTENDUES', 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'L');
            $pdf->Cell(0, 5, 'EVALUATION : '.$sequence->getSequence(), 0, 2, 'C');
            $pdf->Ln();
        } else 
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, 'LIST OF NOTES STILL AWAITED', 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'L');
            $pdf->Cell(0, 5, 'EVALUATION : '.$sequence->getSequence(), 0, 2, 'C');
            $pdf->Ln();
        }
        
        return $pdf;
    }


    public function getTableHeaderLatersList(Pagination $pdf, int $cellHeaderHeight, int $numberWith, int $fullNameWith, int $classroomWith, int $subjectWith): Pagination
    {
        $mySession = $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell($numberWith, $cellHeaderHeight, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith+10, $cellHeaderHeight, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            // $pdf->Cell($classroomWith, $cellHeaderHeight, 'Evaluation', 1, 0, 'C', true);
            $pdf->Cell($classroomWith+10, $cellHeaderHeight, 'Classes', 1, 0, 'C', true);
            $pdf->Cell($subjectWith+5, $cellHeaderHeight, utf8_decode('Matières'), 'LTR', 0, 'C', true);
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell($numberWith, $cellHeaderHeight, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith+10, $cellHeaderHeight, utf8_decode('First and last names'), 1, 0, 'C', true);
            // $pdf->Cell($classroomWith, $cellHeaderHeight, 'Evaluation', 1, 0, 'C', true);
            $pdf->Cell($classroomWith+10, $cellHeaderHeight, 'Classes', 1, 0, 'C', true);
            $pdf->Cell($subjectWith+5, $cellHeaderHeight, utf8_decode('Subjects'), 'LTR', 0, 'C', true);
            $pdf->Ln();
        }

        return $pdf;
    }


    public function printAssumedDuty(School $school, SchoolYear $schoolYear, array $teachers, int $asd = 0, int $pe = 0): PDF
    {
        $fontSize = 10;
        $fontSize11 = $fontSize+1;
        $fontSize12 = $fontSize+2;
        $fontSize13 = $fontSize+3;
        $fontSize14 = $fontSize+4;
        $fontSize15 = $fontSize+5;
        $cellHeaderHeight = 3;

        $cellHeaderHeight7 = 7;
        $cellHeaderHeight5 = 4;
        $cellHeaderHeight10 = $cellHeaderHeight5*2;
        $cellBodyHeight7 = 7;

        $cell1 = 10;
        $cell2 = $cell1*2;
        $cell3 = $cell1*3;
        $cell4 = $cell1*4;
        $cell5 = $cell1*5;
        $cell6 = $cell1*6;
        $cell7 = $cell1*7;
        $cell8 = $cell1*8;
        $cell9 = $cell1*9;
        $cell10 = $cell1*10;
        $space = 2;
        $space2 = 4;

        $pdf = new PDF();

        foreach ($teachers as $teacher) 
        {
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
            // Impression de la prise/reprise de service
            if($asd == 1)
            {
                // entête de la fiche
                $pdf->Ln($cellHeaderHeight5);
                $pdf->SetFont('Times', 'B', $fontSize);
                $pdf->Cell(0, $cellHeaderHeight5, utf8_decode('N°__________/'.$schoolYear->getSchoolYear().'/'.$school->getServiceNote()), 0, 1, 'C');
                $pdf->Ln();
                $pdf->SetFont('Times', 'B', $fontSize15);
                $pdf->Cell(0, $cellHeaderHeight7, utf8_decode('CERTIFICAT DE PRISE/REPRISE DE SERVICE'), 0, 1, 'C');
                $pdf->SetFont('Times', 'BI', $fontSize15);
                $pdf->Cell(0, $cellHeaderHeight7, utf8_decode('CERTIFICATE OF ASSUMPTION/RESUMPTION'), 0, 1, 'C');
                $pdf->Ln();
    
                // contenu
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Je soussigné'), 0, 0, 'L');
                $pdf->Cell($cell5, $cellHeaderHeight10, utf8_decode('_ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode($school->getHeadmaster()->getDuty()->getDuty().' du '.$school->getFrenchName()), 0, 1, 'L');
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('I undersigned'), 0, 0, 'L'); 
                $pdf->Cell($cell5, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Principal of '.$school->getEnglishName()), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell2, $cellHeaderHeight5, utf8_decode('Certifie que '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell1, $cellHeaderHeight5, utf8_decode($this->getFrenchCivility($teacher)), 0, 0, 'L');

                $pdf->Cell($cell7+$cell8, $cellHeaderHeight10, utf8_decode($teacher->getFullName()), 0, 0, 'C');
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell2, $cellHeaderHeight5, utf8_decode('Testify that '), 0, 0, 'L');
                $pdf->SetFont('Times', 'IB', $fontSize11);
                $pdf->Cell($cell1, $cellHeaderHeight5, utf8_decode($this->getEnglishCivility($teacher)), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode($this->getBorn($teacher)), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getBirthday()))
                {
                    $pdf->Cell($cell3, $cellHeaderHeight5*2, '//', 0, 0, 'C');
                }else
                {
                    $pdf->Cell($cell3, $cellHeaderHeight5*2, utf8_decode($teacher->getBirthday()->format('d-m-Y')), 0, 0, 'C');

                }

                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode(' à '), 0, 0, 'C');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getBirthplace()))
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getBirthplace()), 0, 0, 'C');
                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Born on '), 0, 0, 'L');
                $pdf->Cell($cell3, $cellHeaderHeight5, '', 0, 0, 'C');
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode(' at '), 0, 1, 'C');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Grade '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getGrade()->getGrade()), 0, 0, 'C');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Matricule '), 0, 0, 'C');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getAdministrativeNumber()), 0, 0, 'C');
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Rank '), 0, 0, 'L');
                $pdf->Cell($cell3, $cellHeaderHeight5, '', 0, 0, 'C');
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Matricule '), 0, 1, 'C');
                $pdf->Ln($space);
    
                $pdf->Cell($cell4, $cellHeaderHeight5, utf8_decode("Région d'origine"), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if (is_null($teacher->getRegion())) 
                {
                    $pdf->Cell($cell5, $cellHeaderHeight10, '//', 0, 0, 'C');
                    
                }else
                {
                    $pdf->Cell($cell5, $cellHeaderHeight10, utf8_decode($teacher->getRegion()->getRegion()), 0, 0, 'C');

                }

                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell4, $cellHeaderHeight5, utf8_decode("Département d'origine "), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getDivision()))
                {
                    $pdf->Cell($cell5, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell5, $cellHeaderHeight10, utf8_decode($teacher->getDivision()->getDivision()), 0, 0, 'C');

                }

                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell4, $cellHeaderHeight5, utf8_decode('Région of origin'), 0, 0, 'L');
                $pdf->Cell($cell5, $cellHeaderHeight5, '', 0, 0, 'C');
                $pdf->Cell($cell4, $cellHeaderHeight5, utf8_decode('Division of origin '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell5, $cellHeaderHeight5, utf8_decode("Arrondissement d'origine"), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getSubDivision()))
                {
                    $pdf->Cell($cell5, $cellHeaderHeight10, '//', 0, 0, 'L');

                }else
                {
                    $pdf->Cell($cell5, $cellHeaderHeight10, utf8_decode($teacher->getSubDivision()->getSubdivision()), 0, 0, 'L');

                }

                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell5, $cellHeaderHeight5, utf8_decode('Statut matrimonial '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($this->getMatrimonialStatusOnSex($teacher)), 0, 0, 'L');
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell5, $cellHeaderHeight5, utf8_decode('Subdivision of origin'), 0, 0, 'L');
                $pdf->Cell($cell5, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell5, $cellHeaderHeight5, utf8_decode('Family status '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode($this->getAffectationTitle($teacher).' par Arrêté/Décision, note de service N° '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getAffectationNote()))
                {
                    $pdf->Cell($cell10, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell10, $cellHeaderHeight10, utf8_decode($teacher->getAffectationNote()), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Posted/Appointed/Transfered by Order/Decision service note N° '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Du'), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getAffectationDate()))
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getAffectationDate()->format('d-m-Y')), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Of'), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('A effectivment pris/repris service dans mon établissement le '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getTakeFunctiondate()))
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, utf8_decode($teacher->getTakeFunctiondate()->format('d-m-Y')), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('Has effectively assumed/resumed duty in my school on  '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('En qualité de '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getDuty()->getDuty()), 0, 0, 'C');
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('As  '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Diplôme  '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getDiploma()))
                {
                    $pdf->Cell($cell4, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell4, $cellHeaderHeight10, utf8_decode($teacher->getDiploma()->getDiploma()), 0, 0, 'C');

                }
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell4, $cellHeaderHeight5, utf8_decode('Spécialité '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getSpeciality()))
                {
                    $pdf->Cell($cell4, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell4, $cellHeaderHeight10, utf8_decode($teacher->getSpeciality()->getSubject()), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Diploma  '), 0, 0, 'L');
                $pdf->Cell($cell4, $cellHeaderHeight5, '', 0, 0, 'C');
                $pdf->Cell($cell4, $cellHeaderHeight5, utf8_decode('Specialisation '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode("Date de première prise de service dans l'administration "), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getFirstDateFunction()))
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, utf8_decode($teacher->getFirstDateFunction()->format('d-m-Y')), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('First date of assumption of duty in the public service '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
    
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('Date de première prise de service au poste actuel '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getFirstDateActualFunction()))
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, utf8_decode($teacher->getFirstDateActualFunction()->format('d-m-Y')), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('First date of assumption of duty in the school '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
    
    
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('Matière effectivement enseignée '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getTeachingSubject()))
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell8, $cellHeaderHeight10, utf8_decode($teacher->getTeachingSubject()->getSubject()), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('Subject taught '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space);
                $pdf->Ln($cellHeaderHeight5);
    
                
                $pdf->Cell($cell8+$cell10, $cellHeaderHeight5, utf8_decode('Le présent certificat de prise/reprise de service a été délivré pour servir et valoir ce que de droit '), 0, 1, 'L');
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell8+$cell10, $cellHeaderHeight5, utf8_decode('In testimonial where of this certificate of assumption/resumption of duty has been issued to duty service its purpose '), 0, 1, 'L');
                $pdf->Ln();
                $pdf->Ln();
    
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Fait à '.$school->getPlace().' le __________'), 0, 1, 'L');
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Done in '.$school->getPlace().' on '), 0, 1, 'L');
                $pdf->Ln($space);
    
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Le '.$school->getHeadmaster()->getDuty()->getDuty()), 0, 1, 'R');
                
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('The principal '), 0, 1, 'R');
                $pdf->Ln($space);
    
    
    
            }
    
            // Impression de la présence effective au poste
            if($pe == 1)
            {
                $cellHeaderHeight5 = 5;
                $cellHeaderHeight10 = $cellHeaderHeight5*2;
                $fontSize11 = $fontSize+2;
    
                // entête de la fiche
                $pdf->Ln($cellHeaderHeight5);
                $pdf->SetFont('Times', 'B', $fontSize);
                $pdf->Cell(0, $cellHeaderHeight5, utf8_decode('N°__________/'.$schoolYear->getSchoolYear().'/'.ConstantsClass::SERVICE_NOTE), 0, 1, 'C');
                $pdf->Ln();
                $pdf->SetFont('Times', 'B', $fontSize15);
                $pdf->Cell(0, $cellHeaderHeight7, utf8_decode('ATTESTATION DE PRESENCE EFFECTIVE'), 0, 1, 'C');
                $pdf->SetFont('Times', 'BI', $fontSize15);
                $pdf->Cell(0, $cellHeaderHeight7, utf8_decode('ATTESTATION OF EFFECTIVE PRESENCE'), 0, 1, 'C');
                $pdf->Ln();
    
                // Contenu
    
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Je soussigné'), 0, 0, 'L');
                $pdf->Cell($cell7, $cellHeaderHeight10, utf8_decode('_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 0, 'L');
                $pdf->Ln($cellHeaderHeight5);
                
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('I undersigned'), 0, 1, 'L'); 
                $pdf->Ln($space2);
                $pdf->SetFont('Times', '', $fontSize11);
    
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode($school->getHeadmaster()->getDuty()->getDuty().' du '.$school->getFrenchName()), 0, 1, 'L');
                
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Principal of '.$school->getEnglishName()), 0, 1, 'L');
                $pdf->Ln($space2);
    
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Certifie que '.$this->getFrenchCivility($teacher)), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell7+$cell8, $cellHeaderHeight10, utf8_decode($teacher->getFullName()), 0, 0, 'C');
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Testify that '.$this->getEnglishCivility($teacher)), 0, 1, 'L');
                $pdf->Ln($space2);
    
                $pdf->SetFont('Times', '', $fontSize11);
                
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Matricule '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell4, $cellHeaderHeight10, utf8_decode($teacher->getAdministrativeNumber()), 0, 0, 'C');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Grade '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getGrade()->getGrade()), 0, 0, 'C');
                
                $pdf->Ln($cellHeaderHeight5);
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Matricule '), 0, 0, 'L');
                $pdf->Cell($cell4, $cellHeaderHeight5, '', 0, 0, 'C');
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Rank '), 0, 1, 'L');
                $pdf->Ln($space2);
    
                $pdf->SetFont('Times', '', $fontSize+1);
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode($this->getAffectationTitle($teacher).' par Arrêté/Décision, note de service N° '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getAffectationNote()))
                {
                    $pdf->Cell($cell10, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell10, $cellHeaderHeight10, utf8_decode($teacher->getAffectationNote()), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize+1);
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Posted/Appointed/Transfered by Order/Decision service note N° '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space2);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Du'), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getAffectationDate()))
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getAffectationDate()->format('d-m-Y')), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Of'), 0, 1, 'L');
                $pdf->Ln($space2);
                
                $pdf->SetFont('Times', '', $fontSize14);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('est effectivement en service dans mon établissement'), 0, 1, 'L');
    
                $pdf->SetFont('Times', 'I', $fontSize14);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('Has effectively been presence in this institution  '), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space2);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('En qualité de '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getDuty()->getDuty()), 0, 0, 'C');
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('As'), 0, 1, 'L');
                $pdf->Ln($space2);
    
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('Depuis le '), 0, 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize11);

                if(is_null($teacher->getFirstDateActualFunction()))
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, '//', 0, 0, 'C');

                }else
                {
                    $pdf->Cell($cell3, $cellHeaderHeight10, utf8_decode($teacher->getFirstDateActualFunction()->format('d-m-Y')), 0, 0, 'C');

                }
                $pdf->Ln($cellHeaderHeight5);
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell3, $cellHeaderHeight5, utf8_decode('From'), 0, 1, 'L');
                $pdf->Ln($space2);
                
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('En foi de quoi la présente attestation est établie et délivrée pour servir et valoir ce que de droit.'), 0, 1, 'L');
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell9, $cellHeaderHeight5, utf8_decode('In testimonial hither to, this present attestation is established to serve the purpose for which it is intended.'), 0, 1, 'L');
                $pdf->SetFont('Times', '', $fontSize11);
                $pdf->Ln($space2);
    
                $pdf->Ln();
                $pdf->Ln();
    
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Fait à '.$school->getPlace().' le __________'), 0, 1, 'L');
    
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Done in '.$school->getPlace().' on '), 0, 1, 'L');
                $pdf->Ln($space);
    
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('Le '.$school->getHeadmaster()->getDuty()->getDuty()), 0, 1, 'R');
                
                $pdf->SetFont('Times', 'I', $fontSize11);
                $pdf->Cell($cell10, $cellHeaderHeight5, '', 0, 0, 'L');
                $pdf->Cell($cell8, $cellHeaderHeight5, utf8_decode('The principal '), 0, 1, 'R');
                $pdf->Ln($space);
    
    
    
            }
            
        }
      
        return $pdf;
    }

    public function getFrenchCivility(Teacher $teacher): string
    {
        $sex = $teacher->getSex()->getSex();

        if(is_null($teacher->getMatrimonialStatus()))
        {
            return '//';
        }

        $matrimonial = $teacher->getMatrimonialStatus()->getMatrimonialStatus();

        if($sex == 'F')
        {
            if($matrimonial == 'M' || $matrimonial == 'V')
            {
                return 'Madame';
            }else
            {
                return 'Madémoiselle';
            }
        }

        return 'Monsieur';

    }

    public function getEnglishCivility(Teacher $teacher): string
    {
        $sex = $teacher->getSex()->getSex();

        if(is_null($teacher->getMatrimonialStatus()))
        {
            return '//';
        }

        $matrimonial = $teacher->getMatrimonialStatus()->getMatrimonialStatus();

        if($sex == 'F')
        {
            if($matrimonial == 'M' || $matrimonial == 'V')
            {
                return 'Madam';
            }else
            {
                return 'Miss';
            }
        }

        return 'Mister';

    }

    public function getBorn(Teacher $teacher): string
    {
        $sex = $teacher->getSex()->getSex();
        if($sex == 'F')
        {
            return 'Née le ';
        }

        return 'Né le';
    }


    public function getAffectationTitle(Teacher $teacher): string
    {
        $sex = $teacher->getSex()->getSex();

        if($sex == 'F')
        {
            return 'Affectée/Nommée';
        }

        return 'Affecté/Nommé';
    }

    
    public function getMatrimonialStatusOnSex(Teacher $teacher)
    {
        $sex = $teacher->getSex()->getSex();

        if(is_null($teacher->getMatrimonialStatus()))
        {
            return '//';
        }
        
        $mySession = $this->request->getSession();
        
        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
        {
            switch ($teacher->getMatrimonialStatus()->getMatrimonialStatus()) 
            {
                case ConstantsClass::MARRIED:
                    if ($sex == 'F') 
                    {
                        return 'Mariée';
                    }
                    return 'Marié';
                    break;
                case ConstantsClass::SINGLE:
                    return 'Célibataire';
                    break;
                case ConstantsClass::WIDOW:
                    if ($sex == 'F') 
                    {
                        return 'Veuve';
                    }
                    return 'Veuf';
                    break;
                case ConstantsClass::DIVORCED:
                    if ($sex == 'F') 
                    {
                        return 'Divorcée';
                    }
                    return 'Divorcé';
                    break;
            }
        }else
        {
            switch ($teacher->getMatrimonialStatus()->getMatrimonialStatus()) 
            {
                case ConstantsClass::MARRIED:
                    if ($sex == 'F') 
                    {
                        return 'Married';
                    }
                    return 'Married';
                    break;
                case ConstantsClass::SINGLE:
                    return 'Single';
                    break;
                case ConstantsClass::WIDOW:
                    if ($sex == 'F') 
                    {
                        return 'Widow';
                    }
                    return 'Widow';
                    break;
                case ConstantsClass::DIVORCED:
                    if ($sex == 'F') 
                    {
                        return 'Divorced';
                    }
                    return 'Divorced';
                    break;
            }
        }
    }

}
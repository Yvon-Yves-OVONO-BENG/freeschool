<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\School;
use App\Entity\Classroom;
use App\Entity\SubSystem;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Repository\SexRepository;
use App\Entity\ReportElements\Pagination;
use App\Repository\TermRepository;
use App\Repository\SkillRepository;
use App\Repository\LessonRepository;
use App\Repository\ReportRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\StatisticElements\ClassroomStatisticSlipRow;

class StatisticService 
{
    public function __construct(
        protected RequestStack $request,
        protected SexRepository $sexRepository, 
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected SkillRepository $skillRepository, 
        protected LessonRepository $lessonRepository, 
        protected ReportRepository $reportRepository, 
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        )
    {}


    /**
     * Construit les éléments de la fiche statistique par classe
     *
     * @param array $rankPerLessons
     * @return array
     */
    public function getStatisticSlipPerClass(array $rankPerLessons): array
    {
        $classroomStatisticSlip = [];
        
        if(!empty($rankPerLessons))
        {
            foreach($rankPerLessons as $rankPerLesson)
            {
                if(!empty($rankPerLesson))
                {
                    
                    $classroomStatisticSlipRows = [];

                    foreach($rankPerLesson as $evaluationInformation)
                    {
                        $totalMark = 0;
                        $totalMarkBoys = 0;
                        $totalMarkGirls = 0;
                        $lastMark = 20;
                        $firstMark = 0;
                        $composedBoys = 0;
                        $composedGirls = 0;
                        $passedBoys = 0;
                        $passedGirls = 0;

                        $classroomStatisticSlipRow =  new ClassroomStatisticSlipRow();

                        $classroomStatisticSlipRow->setSubject($evaluationInformation['lessonName'])
                                ->setRegisteredBoys($evaluationInformation['boys'])
                                ->setRegisteredGirls($evaluationInformation['girls'])
                        ;

                        foreach($evaluationInformation['lessonMark'] as $markAndSex)
                        {

                            if($markAndSex['mark'] != ConstantsClass::UNRANKED_MARK)
                            {
                                $totalMark += $markAndSex['mark'];
                                
                                if($markAndSex['sex'] == 'M')
                                {
                                    $composedBoys++;
                                    $totalMarkBoys += $markAndSex['mark'];
                                }
                                else
                                {
                                    $composedGirls++;
                                    $totalMarkGirls += $markAndSex['mark'];
                                }
                                
                                if($markAndSex['mark'] >= 10)
                                {
                                    if($markAndSex['sex'] == 'M')
                                    {
                                        $passedBoys++;
                                        
                                    }
                                    else
                                    {
                                        $passedGirls++;
                                        
                                    }
                                }
                                
                                if($markAndSex['mark'] < $lastMark)
                                {
                                    $lastMark = $markAndSex['mark'];
                                }
                                
                                if($markAndSex['mark'] > $firstMark)
                                {
                                    $firstMark = $markAndSex['mark'];
                                }
                            }
                        }

                        $generalAverage = $this->generalService->getRatio($totalMark, ($composedBoys + $composedGirls));

                        $generalAverageBoys = $this->generalService->getRatio($totalMarkBoys, $composedBoys);

                        $generalAverageGirls = $this->generalService->getRatio($totalMarkGirls, $composedGirls);
                        
                        $classroomStatisticSlipRow->setComposedBoys($composedBoys)
                        ->setComposedGirls($composedGirls)
                        ->setPassedBoys($passedBoys)
                        ->setPassedGirls($passedGirls)
                        ->setGeneralAverage($generalAverage)
                        ->setGeneralAverageBoys($generalAverageBoys)
                        ->setGeneralAverageGirls($generalAverageGirls)
                        ->setFirstMark($firstMark)
                        ->setLastMark($lastMark)
                        ->setAppreciation($this->generalService->getApoAppreciation($generalAverage))
                        ;
                        
                        $classroomStatisticSlipRows[] = $classroomStatisticSlipRow;
                    }
                
                    // $classroomStatisticSlipRows['classroom'] = $rankPerLesson['classroom'];
                    $classroomStatisticSlipRowsAndClassroom['rows'] = $classroomStatisticSlipRows;
                    $classroomStatisticSlipRowsAndClassroom['classroom'] = $rankPerLesson[0]['classroom'];

                    $classroomStatisticSlip[] = $classroomStatisticSlipRowsAndClassroom;
                }

                unset( $classroomStatisticSlipRowsAndClassroom);
                unset($classroomStatisticSlipRows);

            }
        }
        
        return  $classroomStatisticSlip;
    }

    /**
     * Retourne les notes par lesson classées
     *
     * @param array $studentMarkTerm
     * @param Classroom $classroom
     * @return array
     */
    public function getRankPerLesson(array $studentMarkTerm, Classroom $classroom): array
    {
        //Effectif de la classe
        $numberOfStudents = count($classroom->getStudents());
        
        // Effectif garçons
        $numberOfBoys = count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'M'])
        ]));
        // Effectif filles
        $numberOfGirls = count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'F'])
        ]));
        // nombre de lessons
        $numberOfLessons = count($classroom->getLessons());

        $numberOfEvaluations = $numberOfLessons * $numberOfStudents;

        if(($numberOfStudents == 0) || (count($studentMarkTerm) != $numberOfEvaluations))
        {
            $noValidated = [];
            if(!$numberOfStudents)
            {
                $noValidated['lessonName'] = ConstantsClass::NO_STUDENT; 
            }else
            {
                $noValidated['lessonName'] = ConstantsClass::MISSED_MARK; 
            }

            $noValidated['lessonMark'] = [];
            $noValidated['boys'] = $numberOfBoys;
            $noValidated['girls'] = $numberOfGirls;
            $noValidated['classroom'] = $classroom->getClassroom();

           return ['0' => $noValidated];

        }

        $rankPerLesson = [];
    
            for($i = 0; $i < $numberOfLessons; $i++)
            {
                $lessonMarkAndSkill = [];
                $lesson = $studentMarkTerm[$i]->getLesson();
                $subject = $lesson->getSubject()->getSubject();
                $lessonMArk = [];
                for($j = $i; $j < $numberOfEvaluations; $j += $numberOfLessons)
                {
                    //On construit le tableau des notes par matière
                    $markAndSex['mark'] = $studentMarkTerm[$j]->getMark();
                    $markAndSex['sex'] = $studentMarkTerm[$j]->getStudent()->getSex()->getSex();
                    $lessonMArk[] =  $markAndSex;
                }
                    // On classe par ordre de mérite, c-à-d par ordre décroissant des notes(studentMark)
                    rsort( $lessonMArk, SORT_NUMERIC);
    
                // $lessonMarkAndSkill['Skill'] = $Skill;
                $lessonMarkAndSkill['lessonName'] = $subject; 
                $lessonMarkAndSkill['lessonMark'] = $lessonMArk;

                $lessonMarkAndSkill['boys'] = $numberOfBoys;
                $lessonMarkAndSkill['girls'] = $numberOfGirls;

                $lessonMarkAndSkill['classroom'] = $classroom->getClassroom();

    
                $rankPerLesson[$lesson->getId()] =  $lessonMarkAndSkill;
            }
        
        // On classe par ordre de mérite, c-à-d par ordre décroissant des moyennes(average)
        $lessonNameColum = array_column($rankPerLesson, 'lessonName');
        array_multisort($lessonNameColum, SORT_ASC, $rankPerLesson);
        
        return  $rankPerLesson;
    }


    /**
     * Imprime la fiche des statistique par classe
     *
     * @param array $statisticSlipPerClass
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    public function printStatisticSlipPerClass(array $statisticSlipPerClass, string $firstPeriodLetter, int $idP, School $school, SchoolYear $schoolYear): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }
        }else
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }
        }

        $pdf = new Pagination();

        foreach($statisticSlipPerClass as $oneStatisticSlip)
        {
            // On insère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            // on rempli l'entête administrative
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();  

            // Logo de l'établissement
            $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900);  
            $pdf->setXY($x, $y);

            // Entête de la fiche
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'FICHE STATISTIQUE PAR CLASSE', $termName, $school, 'Classe', $oneStatisticSlip['classroom']);
            }else
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET BY CLASS', $termName, $school, 'Classe', $oneStatisticSlip['classroom']);
            }

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Disciplines');
            
            $mySubject = $oneStatisticSlip['rows'][0]->getSubject();
            
            if( $mySubject == ConstantsClass::MISSED_MARK)
            {
                $pdf->SetFont('Times', 'B', 15);
                $pdf->Ln(10);
                $mySession =  $this->request->getSession();

                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell(0, 6, "IMPOSSIBLE D'IMPRIMER LA FICHE DE LA CLASSE DE ".utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "CAR IL Y'A ENCORE DES NOTES NON SAISIES DANS CETTE CLASSE", 0, 0, 'C');
                }else
                {
                    $pdf->Cell(0, 6, 'UNABLE TO PRINT THE CLASS SHEET OF '.utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "BECAUSE THERE ARE STILL UNENTERED NOTES IN THIS CLASS ", 0, 0, 'C');
                }

            }elseif($mySubject == ConstantsClass::NO_STUDENT)
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->SetFont('Times', 'B', 15);
                    // $pdf->Ln(10);
                    $pdf->Cell(0, 6, "IMPOSSIBLE D'IMPRIMER LA FICHE DE LA CLASSE DE ".utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "CAR AUCUN ELEVE N'EST INSCRIT DANS CETTE CLASSE", 0, 0, 'C');
                }else
                {
                    $pdf->SetFont('Times', 'B', 15);
                    // $pdf->Ln(10);
                    $pdf->Cell(0, 6, 'UNABLE TO PRINT THE CLASS SHEET OF '.utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, 'BECAUSE NO STUDENT IS REGISTERED INTHIS CLASS', 0, 0, 'C');
                }
            }else
            {
                 // Contenu du tableau
                foreach($oneStatisticSlip['rows'] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    
                }

                $pdf->Ln(3);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(36, 6, '', 0, 0, 'C');
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    if($school->isPublic())
                    {
                        if($school->isLycee())
                        {
                            $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                        }else
                        {
                            $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                        }
                        
                    }else
                    {
                        $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                    }
                    
                }else
                {
                    if($school->isPublic())
                    {
                        if($school->isLycee())
                        {
                            $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                        }else
                        {
                            $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                        }
                        
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }
                }

            }
        }

        return $pdf;

    }

     /**
     * Imprime la fiche des statistique par classe
     *
     * @param array $statisticSlipPerClass
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    public function printDataPerClass(array $statisticSlipPerClass, string $firstPeriodLetter, int $idP, School $school, SchoolYear $schoolYear): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }
        }else
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }
        }

        $pdf = new Pagination();

        foreach($statisticSlipPerClass as $oneStatisticSlip)
        {
            // On insère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            // on rempli l'entête administrative
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();  

            // Logo de l'établissement
            $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900);  
            $pdf->setXY($x, $y);

            // Entête de la fiche
            $mySession =  $this->request->getSession();

            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'FICHE STATISTIQUE PAR CLASSE', $termName, $school, 'Classe', $oneStatisticSlip['classroom']);
            }else
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET BY CLASS', $termName, $school, 'Class', $oneStatisticSlip['classroom']);
            }

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Disciplines');
            
            $mySubject = $oneStatisticSlip['rows'][0]->getSubject();
            
            if( $mySubject == ConstantsClass::MISSED_MARK)
            {
                $pdf->SetFont('Times', 'B', 15);
                $pdf->Ln(10);
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell(0, 6, "IMPOSSIBLE D'IMPRIMER LA FICHE DE LA CLASSE DE ".utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "CAR IL Y'A ENCORE DES NOTES NON SAISIES DANS CETTE CLASSE", 0, 0, 'C');
                }else
                {
                    $pdf->Cell(0, 6, "UNABLE TO PRINT THE CLASS SHEET OF ".utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "BECAUSE THERE ARE STILL UNENTERED NOTES IN THIS CLASS ", 0, 0, 'C');
                }

            }elseif($mySubject == ConstantsClass::NO_STUDENT)
            {
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->SetFont('Times', 'B', 15);
                    // $pdf->Ln(10);
                    $pdf->Cell(0, 6, "IMPOSSIBLE D'IMPRIMER LA FICHE DE LA CLASSE DE ".utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, "CAR AUCUN ELEVE N'EST INSCRIT DANS CETTE CLASSE", 0, 0, 'C');
                }else
                {
                    $pdf->SetFont('Times', 'B', 15);
                    // $pdf->Ln(10);
                    $pdf->Cell(0, 6, 'UNABLE TO PRINT THE CLASS SHEET OF '.utf8_decode($oneStatisticSlip['classroom']), 0, 0, 'C');
                    $pdf->Ln(10);
                    $pdf->Cell(0, 6, 'BECAUSE NO STUDENT IS REGISTERED INTHIS CLASS', 0, 0, 'C');
                }
            }else
            {
                 // Contenu du tableau
                foreach($oneStatisticSlip['rows'] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    
                }

                $pdf->Ln(3);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(36, 6, '', 0, 0, 'C');
                
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    if($school->isPublic())
                    {
                        if($school->isLycee())
                        {
                            $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                        }else
                        {
                            $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                        }
                        
                    }else
                    {
                        $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                    }
                    
                }else
                {
                    if($school->isPublic())
                    {
                        if($school->isLycee())
                        {
                            $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                        }else
                        {
                            $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                            $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                        }
                        
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }
                }

            }
        }

        return $pdf;

    }


    /**
     * Entête du tablea de la fiche etatistique
     *
     * @param Pagination $pdf
     * @param string $firstColum
     * @return Pagination
     */
    public function statisticTableHeaderPagination(Pagination $pdf, string $firstColum): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 30;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        $pdf->Ln();
        $pdf->SetFont('Times', 'B', 10);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($firstColum), 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, 'Inscrits', 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('Ayant Composé'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('Absents'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode("Taux d'Assiduité (%)"), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, 'Nb de Moyennes', 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('Taux Réussite (%)'), 1, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 8);

            $pdf->Cell($cellWidth1, $cellHeigh0, 'Moy Gen', 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'Moy', 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'Moy', 'LTR', 0, 'C', true);

            $pdf->SetFont('Times', 'B', 8);

            $pdf->Cell($cellWidth3, $cellHeigh0, utf8_decode('Appréciation'), 'LTR', 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Ln();

            $pdf->Cell($cellWidth0, $cellHeigh0, '', 'LBR', 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 7);

            // $pdf->Cell($cellWidth2, $cellHeigh0, 'Gen', 'LBR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'Premier', 'LBR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'Dernier', 'LBR', 0, 'C', true);
            $pdf->Cell($cellWidth3, $cellHeigh0, '', 'LBR', 0, 'C', true);
        }else
        {
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($firstColum), 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, 'Regist.', 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('Having Comp.'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('Absent'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode("Attendance rate (%)"), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, 'Nb of avg', 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('Success rate (%)'), 1, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 8);

            $pdf->Cell($cellWidth1, $cellHeigh0, 'Gen Avg', 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'Avg', 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'Avg', 'LTR', 0, 'C', true);

            $pdf->SetFont('Times', 'B', 8);

            $pdf->Cell($cellWidth3, $cellHeigh0, utf8_decode('Appréciation'), 'LTR', 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Ln();

            $pdf->Cell($cellWidth0, $cellHeigh0, '', 'LBR', 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell($cellWidth2, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 7);

            // $pdf->Cell($cellWidth2, $cellHeigh0, 'Gen', 'LBR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'First', 'LBR', 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeigh0, 'Last', 'LBR', 0, 'C', true);
            $pdf->Cell($cellWidth3, $cellHeigh0, '', 'LBR', 0, 'C', true);
        }

        $pdf->Ln();
        $pdf->SetFont('Times', 'B', 9);

        return $pdf;
    }

    /**
     * entete Fiche Synthese De La Couverture Des Heures Et Programmes Enseignement Par Matiere
     *
     * @param Pagination $pdf
     * @param string $firstColum
     * @return Pagination
     */
    public function enteteFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere(Pagination $pdf, string $firstColum): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 100;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        $pdf->Ln();
        $pdf->SetFont('Times', 'B', 10);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode(""), 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, 'Taux de couverture des programmes', 1, 0, 'C', true);
            $pdf->Cell($cellWidth1-30, $cellHeigh0, utf8_decode('Taux de couverture des '), "LTR", 0, 'C', true);
            $pdf->Cell($cellWidth1-25, $cellHeigh0, utf8_decode('Taux de réussite des élèves'), "LTR", 1, 'C', true);
            
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($firstColum), 'LR', 0, 'C', true);
            $pdf->Cell($cellWidth1/2, $cellHeigh0, utf8_decode('Enseignements théoriques'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1/2, $cellHeigh0, utf8_decode('Travaux pratiques'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1-30, $cellHeigh0, utf8_decode("heures d'enseignement"), "LBR", 0, 'C', true);
            $pdf->Cell($cellWidth1-25, $cellHeigh0, utf8_decode(""), "LBR", 1, 'C', true);

            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Trimestre'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Année'), 1, 0, 'C', true);

            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Trimestre'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Année'), 1, 0, 'C', true);

            
            $pdf->Cell(($cellWidth1-30)/2, $cellHeigh0, utf8_decode('Trimestre'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1-30)/2, $cellHeigh0, utf8_decode('Année'), 1, 0, 'C', true);

            $pdf->Cell(($cellWidth1-25)/3, $cellHeigh0, utf8_decode("Effectifs"), "LBR", 0, 'C', true);
            $pdf->Cell(($cellWidth1-25)/3, $cellHeigh0, utf8_decode("M >= 0"), "LBR", 0, 'C', true);
            $pdf->Cell(($cellWidth1-25)/3, $cellHeigh0, utf8_decode("Taux de réussite"), "LBR", 1, 'C', true);
            
            
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell($cellWidth0, $cellHeigh0, '', 'LBR', 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LF', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LF', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LF', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LF', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'HP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'HF', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'HP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'HF', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'F', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'T', 1, 1, 'C', true);

        }
        else
        {
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode(""), 'LTR', 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, 'Program coverage rate', 1, 0, 'C', true);
            $pdf->Cell($cellWidth1-30, $cellHeigh0, utf8_decode('Coverage rate'), "LTR", 0, 'C', true);
            $pdf->Cell($cellWidth1-25, $cellHeigh0, utf8_decode('Student success rate'), "LTR", 1, 'C', true);
            
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($firstColum), 'LR', 0, 'C', true);
            $pdf->Cell($cellWidth1/2, $cellHeigh0, utf8_decode('Theoretical training'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1/2, $cellHeigh0, utf8_decode('Pratical work'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1-30, $cellHeigh0, utf8_decode("teaching hours"), "LBR", 0, 'C', true);
            $pdf->Cell($cellWidth1-25, $cellHeigh0, utf8_decode(""), "LBR", 1, 'C', true);

            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Trimester'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Year'), 1, 0, 'C', true);

            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Trimester'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1/2)/2, $cellHeigh0, utf8_decode('Yerar'), 1, 0, 'C', true);

            
            $pdf->Cell(($cellWidth1-30)/2, $cellHeigh0, utf8_decode('Trimester'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1-30)/2, $cellHeigh0, utf8_decode('Year'), 1, 0, 'C', true);

            $pdf->Cell(($cellWidth1-25)/3, $cellHeigh0, utf8_decode("Effective"), "LBR", 0, 'C', true);
            $pdf->Cell(($cellWidth1-25)/3, $cellHeigh0, utf8_decode("Avg >= 0"), "LBR", 0, 'C', true);
            $pdf->Cell(($cellWidth1-25)/3, $cellHeigh0, utf8_decode("Success rate"), "LBR", 1, 'C', true);
            
            
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell($cellWidth0, $cellHeigh0, '', 'LBR', 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LD', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LD', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LD', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LP', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, 'LD', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'SH', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'HW', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'SH', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, 'HW', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, '%', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'T', 1, 0, 'C', true);

            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'B', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'G', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, 'T', 1, 1, 'C', true);
        }

        return $pdf;
    }

    public function enteteFicheSyntheseDePerformanceParClasse(Pagination $pdf, string $firstColum): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 30;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        $pdf->Ln();
        $pdf->SetFont('Times', 'B', 10);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0+3, utf8_decode(''), 'LTR', 0, 'C', true);

            $pdf->Cell(($cellWidth1*3)+28, $cellHeigh0+3, utf8_decode('Taux de participation'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1*3)+28, $cellHeigh0+3, utf8_decode('Taux de réussite des élèves'), 1, 1, 'C', true);
            
            $pdf->Cell($cellWidth0, $cellHeigh0+3, utf8_decode($firstColum), 'LR', 0, 'C', true);
            $pdf->Cell((($cellWidth1*3)+28)/3, $cellHeigh0+3, 'INSCRITS', 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1*3)+28)/3, $cellHeigh0+3, utf8_decode('PRESENTS'), 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1*3)+28)/3, $cellHeigh0+3, utf8_decode('% DE PARTICIPATION'), 1, 0, 'C', true);
            
            $pdf->Cell((($cellWidth1*3)+28)/2, $cellHeigh0+3, utf8_decode('MOYENNE >= 10/20'), 1, 0, 'C', true);
            $pdf->Cell((($cellWidth1*3)+28)/2, $cellHeigh0+3, utf8_decode('% DE REUSSITE'), 1, 0, 'C', true);

        }else
        {
            $pdf->Cell($cellWidth0, $cellHeigh0+3, utf8_decode(''), 'LTR', 0, 'C', true);
            $pdf->Cell(($cellWidth1*3)+28, $cellHeigh0+3, utf8_decode('Participation rates'), 1, 0, 'C', true);
            $pdf->Cell(($cellWidth1*3)+28, $cellHeigh0+3, utf8_decode('Student success rate'), 1, 1, 'C', true);
            
            $pdf->Cell($cellWidth0, $cellHeigh0+3, utf8_decode($firstColum), 'LR', 0, 'C', true);
            
            $pdf->Cell($cellWidth1, $cellHeigh0, 'REGISTERED', 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('PRESENTS'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('% OF PARTICIPATION'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('AVERAGE  ≥ 10/20'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth1, $cellHeigh0, utf8_decode('SUCCESS RATE(%)'), 1, 0, 'C', true);

            
        }

        $pdf->SetFont('Times', 'B', 10);
        $pdf->Ln();

        $pdf->Cell($cellWidth0, $cellHeigh0+3, '', 'LBR', 0, 'C', true);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'G', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'F', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'T', 1, 0, 'C', true);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'G', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'F', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'T', 1, 0, 'C', true);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'G', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'F', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+3, 'T', 1, 0, 'C', true);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+3, 'G', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+3, 'F', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+3, 'T', 1, 0, 'C', true);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+3, 'G', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+3, 'F', 1, 0, 'C', true);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+3, 'T', 1, 0, 'C', true);

        $pdf->Ln();
        $pdf->SetFont('Times', 'B', 9);

        return $pdf;
    }

    /**
     * Entête du tablea de la fiche de collecte de données
     *
     * @param Pagination $pdf
     * @param string $firstColum
     * @return Pagination
     */
    public function dataCollectionTableHeaderPagination(Pagination $pdf, School $school): Pagination
    {
        $couvertureProgramme = 85;
        $couvertureHeure = 80;
        $tauxAssoduite = 50;
        $tauxReussite = 40;

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        // $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell($couvertureProgramme-27, 5*5, utf8_decode("Classes / "), 1, 0, 'C', true);
            // $pdf->Cell(30, 5*2.5, utf8_decode("Nom de"), 1, 0, 'C', true);
            $pdf->Cell($couvertureProgramme-5, 5, utf8_decode("TAUX DE COUVERTURE DES PROGRAMMES PAR"), 'LTR', 0, 'C', true);
            $pdf->Cell($couvertureHeure, 5, utf8_decode("TAUX DE COUVERTURE DES HEURES"), 'LTR', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("TAUX"), 'LTR', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("TAUX"), 'LTR', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+58, $y);
            $pdf->Cell($couvertureProgramme-5, 5, utf8_decode("RAPPORT A L'ANNEE"), 'LRB', 0, 'C', true);
            $pdf->Cell($couvertureHeure, 5, utf8_decode("D'ENSEIGNEMENT PAR RAPPORT A L'ANNEE"), 'LRB', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("D'ASSIDUITE"), 'LRB', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("DE REUSSITE"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+58, $y);
            // $pdf->Cell(30, 5*2.5, utf8_decode("de l'enseignant"), 1, 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("Leçons"), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("Leçons"), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell(($couvertureHeure/3), 5*2, utf8_decode("Heures dues"), 'LRB', 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3), 5*2, utf8_decode("Heures Faites"), 'LRB', 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell((25/2), 5*3, utf8_decode("Ens"), 'LRB', 0, 'C', true);
            $pdf->Cell((25/2), 5*3, utf8_decode("Elev"), 'LRB', 0, 'C', true);

            $pdf->Cell((40/3), 5*3, utf8_decode("Eff Eval"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("M>=10"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("%"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x, $y-10);
            $pdf->Cell($couvertureProgramme-27, 5, utf8_decode("Enseignant"), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("prévues"), 'LRB', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("faites"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+58, $y);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Prat"), 1, 0, 'C', true);

            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Théo"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Prat"), 1, 1, 'C', true);
        }else
        { 
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell($couvertureProgramme-27, 5*5, utf8_decode("Classes / "), 1, 0, 'C', true);
            // $pdf->Cell(30, 5*2.5, utf8_decode("Nom de"), 1, 0, 'C', true);
            $pdf->Cell($couvertureProgramme-5, 5, utf8_decode("PROGRAM COVERAGE RATE BY"), 'LTR', 0, 'C', true);
            $pdf->Cell($couvertureHeure, 5, utf8_decode("PROGRAM COVERAGE RATE"), 'LTR', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("ATTENDANCE"), 'LTR', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("SUCCESS"), 'LTR', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+58, $y);
            $pdf->Cell($couvertureProgramme-5, 5, utf8_decode("SCHOOL YEAR"), 'LRB', 0, 'C', true);
            $pdf->Cell($couvertureHeure, 5, utf8_decode("TEACHING BY SCHOOL YEAR"), 'LRB', 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode("RATE"), 'LRB', 0, 'C', true);
            $pdf->Cell(40, 5, utf8_decode("RATE"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+58, $y);
            // $pdf->Cell(30, 5*2.5, utf8_decode("de l'enseignant"), 1, 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("Lessons"), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("Lessons"), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell(($couvertureHeure/3), 5*2, utf8_decode("Hours due"), 'LRB', 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3), 5*2, utf8_decode("Hours done"), 'LRB', 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3), 5*2, utf8_decode("%"), 'LRB', 0, 'C', true);

            $pdf->Cell((25/2), 5*3, utf8_decode("Teac"), 'LRB', 0, 'C', true);
            $pdf->Cell((25/2), 5*3, utf8_decode("Stu"), 'LRB', 0, 'C', true);

            $pdf->Cell((40/3), 5*3, utf8_decode("Eff Eval"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("M>=10"), 'LRB', 0, 'C', true);
            $pdf->Cell((40/3), 5*3, utf8_decode("%"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x, $y-10);
            $pdf->Cell($couvertureProgramme-27, 5, utf8_decode("Teachers"), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("planned"), 'LRB', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode("done"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+58, $y);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-5)/3)/2), 5, utf8_decode("Prat"), 1, 0, 'C', true);

            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureHeure/3)/2, 5, utf8_decode("Prat"), 1, 1, 'C', true);
        }
        return $pdf;
    }


    public function enteteTableauFicheDeCollecteTauxDeCouvertureDesProgrammesEtHeuresEnseignements(Pagination $pdf, School $school): Pagination
    {
        $couvertureProgramme = 150;
        $couvertureHeure = 120;

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        // $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell($couvertureProgramme-110, 5*2, utf8_decode(""), 'LTR', 0, 'C', true);
            // $pdf->Cell(30, 5*2.5, utf8_decode("Nom de"), 1, 0, 'C', true);
            $pdf->Cell($couvertureProgramme, 5, utf8_decode("Taux de couverture des programmes"), 'LTR', 0, 'C', true);
            $pdf->Cell($couvertureProgramme-60, 5*2, utf8_decode("Taux de couverture des heures d'enseignement"), 'LTR', 1, 'C', true);
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+40, $y-5);
            $pdf->Cell(($couvertureProgramme)/2, 5, utf8_decode("Enseignements théoriques"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureProgramme)/2, 5, utf8_decode("Travaux pratiques"), 1, 1, 'C', true);
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+115, $y);
            // $pdf->Cell(30, 5*2.5, utf8_decode("de l'enseignant"), 1, 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Trimestre"), 'LRB', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Année"), 'LRB', 0, 'C', true);

            $pdf->Cell((($couvertureProgramme-60)/2), 5, utf8_decode("Trimestre"), 'LRT', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-60)/2), 5, utf8_decode("Année"), 'LRT', 0, 'C', true);

            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode(""), 'LR', 0, 'C');
            $pdf->Cell((($couvertureProgramme-5)/3), 5*2, utf8_decode(""), 'LRB', 1, 'C');

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x, $y-10);
            $pdf->Cell($couvertureProgramme-110, 5*3, utf8_decode("Classes"), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Trimestre"), 'LRB', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Année"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+40, $y);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LF"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LF"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LF"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LF"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("HP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("HF"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("HP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("HF"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("%"), 'LTR', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+40, $y);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("ARD"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("SRD"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);

            #############"
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("ARD"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("SRD"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            
            #############"
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("ARD"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("SRD"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);

            #############"
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("ARD"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("SRD"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);

            ####################
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);

            ####################
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 1, 'C', true);
            
        }else
        { 
            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell($couvertureProgramme-110, 5*2, utf8_decode("Classes"), 'LTR', 0, 'C', true);
            // $pdf->Cell(30, 5*2.5, utf8_decode("Nom de"), 1, 0, 'C', true);
            $pdf->Cell($couvertureProgramme, 5, utf8_decode("Program coverage rate"), 'LTR', 0, 'C', true);
            $pdf->Cell($couvertureProgramme-60, 5*2, utf8_decode("Rate of coverage of teaching hours"), 'LTR', 1, 'C', true);
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+40, $y-5);
            $pdf->Cell(($couvertureProgramme)/2, 5, utf8_decode("Theorical lessons"), 1, 0, 'C', true);
            $pdf->Cell(($couvertureProgramme)/2, 5, utf8_decode("Pratical lessons"), 1, 1, 'C', true);
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+115, $y);
            // $pdf->Cell(30, 5*2.5, utf8_decode("de l'enseignant"), 1, 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Trimester"), 'LRB', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Year"), 'LRB', 0, 'C', true);

            $pdf->Cell((($couvertureProgramme-60)/2), 5, utf8_decode("Trimester"), 'LRT', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme-60)/2), 5, utf8_decode("Year"), 'LRT', 0, 'C', true);

            $pdf->Cell((($couvertureProgramme-5)/3), 5, utf8_decode(""), 'LR', 0, 'C');
            $pdf->Cell((($couvertureProgramme-5)/3), 5*2, utf8_decode(""), 'LRB', 1, 'C');

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x, $y-10);
            $pdf->Cell($couvertureProgramme-110, 5*3, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Trimester"), 'LRB', 0, 'C', true);
            $pdf->Cell((($couvertureProgramme)/2)/2, 5, utf8_decode("Year"), 'LRB', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+40, $y);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LD"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LD"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LD"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LP"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("LD"), 1, 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("SH"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("SW"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("%"), 'LTR', 0, 'C', true);

            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("SH"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("SW"), 'LTR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode("%"), 'LTR', 1, 'C', true);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->SetXY($x+40, $y);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WDR"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WtDR"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);

            #############"
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WDR"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WtDR"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            
            #############"
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WDR"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WtDR"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);

            #############"
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);
            $pdf->SetFont('ARIAL', 'B', 7);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WDR"), 1, 0, 'C', true);
            $pdf->Cell((((($couvertureProgramme)/2)/2)/3)/2, 5, utf8_decode("WtDR"), 1, 0, 'C', true);

            $pdf->SetFont('ARIAL', 'B', 9);
            $pdf->Cell(((($couvertureProgramme)/2)/2)/3, 5, utf8_decode(""), 'LBR', 0, 'C', true);

            ####################
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);

            ####################
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 0, 'C', true);
            $pdf->Cell(((($couvertureProgramme-60)/2))/3, 5, utf8_decode(""), 'LR', 1, 'C', true);
        }

        return $pdf;
    }

    /**
     * Affiche un ligne de la fiche statistique
     *
     * @param Pagination $pdf
     * @param ClassroomStatisticSlipRow $row
     * @param boolean $fill
     * @return Pagination
     */
    public function statisticTableRowPagination(Pagination $pdf, ClassroomStatisticSlipRow $row, bool $fill = false): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 30;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        
        if(strlen($row->getSubject()) <= 15)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);

        }elseif(strlen($row->getSubject()) <= 20)
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }else
        {
            $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }


        $pdf->SetFont('Times', 'B', 9);

        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getRegisteredBoys(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getRegisteredGirls(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getRegisteredBoys() + $row->getRegisteredGirls(), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getComposedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getComposedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getComposedBoys() + $row->getComposedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getRegisteredBoys() - $row->getComposedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getRegisteredGirls() - $row->getComposedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, ($row->getRegisteredBoys() + $row->getRegisteredGirls()) - ($row->getComposedBoys() + $row->getComposedGirls()), 1, 0, 'C', $fill);

        
        $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getComposedBoys()*100, $row->getRegisteredBoys())), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0,  $this->generalService->formatMark($this->generalService->getRatio($row->getComposedGirls()*100, $row->getRegisteredGirls())), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls()))), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getPassedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getPassedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell($cellWidth2, $cellHeigh0, $row->getPassedBoys() + $row->getPassedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedBoys()*100, $row->getComposedBoys())), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedGirls()*100, $row->getComposedGirls())), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($row->getGeneralAverageBoys()), 1, 0, 'C', $fill);

        $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($row->getGeneralAverageGirls()), 1, 0, 'C', $fill);
        
        $pdf->Cell($cellWidth2, $cellHeigh0,  $this->generalService->formatMark($row->getGeneralAverage()), 1, 0, 'C', $fill);

        if(($row->getFirstMark() == 0) && ($row->getLastMark() != 0))
        {
            $pdf->Cell($cellWidth2, $cellHeigh0, '/', 1, 0, 'C', $fill);
            $pdf->Cell($cellWidth2, $cellHeigh0, '/', 1, 0, 'C', $fill);
        }else
        {
            $pdf->Cell($cellWidth2, $cellHeigh0,$this->generalService->formatMark($row->getFirstMark()), 1, 0, 'C', $fill);
            $pdf->Cell($cellWidth2, $cellHeigh0, $this->generalService->formatMark($row->getLastMark()), 1, 0, 'C', $fill); 
        }
        $pdf->Cell($cellWidth3, $cellHeigh0, utf8_decode($row->getAppreciation()), 1, 0, 'L', $fill);
        $pdf->Ln(); 
        
        return $pdf;
    }


    public function ligneTableauFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere(Pagination $pdf, ClassroomStatisticSlipRow $row, array $lessonData, int $term, bool $fill = false): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 100;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        
        if(strlen($row->getSubject()) <= 15)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);

        }elseif(strlen($row->getSubject()) <= 20)
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }else
        {
            $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($cellWidth0, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }

        
        $pdf->SetFont('Times', 'B', 9);

        switch($term)
        {
            case 1 :
                foreach($lessonData as $lesson)
                {
                    if($row->getSubject() == $lesson['matiere']->getSubject())
                    {
                        ##### theorique
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);
                        
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format(($lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite']/$lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }

                        ##### Pratiques
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format(($lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite']/$lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }

                        
                    }
                }
                break;
            
            case 2 :
                foreach($lessonData as $lesson)
                {
                    if($row->getSubject() == $lesson['matiere']->getSubject())
                    {
                        ##### theorique
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre2']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre2']['nbreLessonTheoriqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['trimestre2']['nbreLessonTheoriquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre2']['nbreLessonTheoriqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['trimestre2']['nbreLessonTheoriquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonTheoriqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonTheoriqueFaite'])/$lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }

                        ##### Pratiques
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre2']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre2']['nbreLessonPratiqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['trimestre2']['nbreLessonPratiquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['Trimestre2']['nbreLessonPratiqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['trimestre2']['nbreLessonPratiquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonPratiqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonPratiqueFaite'])/$lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }

                        
                    }
                }
                break;

            case 3 :
                foreach($lessonData as $lesson)
                {
                    if($row->getSubject() == $lesson['matiere']->getSubject())
                    {
                        ##### theorique
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre3']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre3']['nbreLessonTheoriqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['trimestre3']['nbreLessonTheoriquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre3']['nbreLessonTheoriqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['trimestre3']['nbreLessonTheoriquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonTheoriqueFaite'] + $lesson['lessonsByTrimester']['Trimestre3']['nbreLessonTheoriqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre1']['nbreLessonTheoriqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonTheoriqueFaite'] + $lesson['lessonsByTrimester']['Trimestre3']['nbreLessonTheoriqueFaite'])/$lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }

                        ##### Pratiques
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre3']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre3']['nbreLessonPratiqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['trimestre3']['nbreLessonPratiquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['Trimestre3']['nbreLessonPratiqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['trimestre3']['nbreLessonPratiquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonPratiqueFaite'] + $lesson['lessonsByTrimester']['Trimestre3']['nbreLessonPratiqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['trimestre1']['nbreLessonPratiqueFaite'] + $lesson['lessonsByTrimester']['Trimestre2']['nbreLessonPratiqueFaite'] + $lesson['lessonsByTrimester']['Trimestre3']['nbreLessonPratiqueFaite'])/$lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }
    
                    }
                }
                break;

            case 5 :
                foreach($lessonData as $lesson)
                {
                    if($row->getSubject() == $lesson['matiere']->getSubject())
                    {
                        ##### theorique
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriqueFaite'])/$lesson['lessonsByTrimester']['annuel']['nbreLessonTheoriquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }

                        ##### Pratiques
                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonPratiqueFaite'], 
                                                                        1, 0, 'C', $fill);

                        if(($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue']) == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiqueFaite'])/
                                                                        ($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue']))*100, 2), 
                                                                        1, 0, 'C', $fill);
                        }


                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'], 
                                                                        1, 0, 'C', $fill);

                        $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, $lesson['lessonsByTrimester']['annuel']['nbreLessonPratiqueFaite'], 
                                                                                1, 0, 'C', $fill);

                        if($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'] == 0)
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($cellWidth1/2)/2)/3, $cellHeigh0, number_format((($lesson['lessonsByTrimester']['annuel']['nbreLessonPratiqueFaite'])/$lesson['lessonsByTrimester']['annuel']['nbreLessonPratiquePrevue'])*100, 2), 
                                                                                1, 0, 'C', $fill);
                        }
    
                    }
                }
                break;
        }


        $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, "", 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, "", 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, "", 1, 0, 'C', $fill);

        $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, "", 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, "", 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-30)/2)/3, $cellHeigh0, "", 1, 0, 'C', $fill);

        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $row->getComposedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $row->getComposedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $row->getComposedBoys() + $row->getComposedGirls(), 1, 0, 'C', $fill);
        
        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $row->getPassedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $row->getPassedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $row->getPassedBoys() + $row->getPassedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedBoys()*100, $row->getComposedBoys())), 1, 0, 'C', $fill);

        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedGirls()*100, $row->getComposedGirls())), 1, 0, 'C', $fill);

        $pdf->Cell((($cellWidth1-25)/3)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 1, 'C', $fill);
        
        return $pdf;
    }

    public function ligneFicheSyntheseDePerformanceDesElevesParMatiere(Pagination $pdf, ClassroomStatisticSlipRow $row, bool $fill = false): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 30;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        
        if(strlen($row->getSubject()) <= 15)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);

        }elseif(strlen($row->getSubject()) <= 20)
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }else
        {
            $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }


        $pdf->SetFont('Times', 'B', 9);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredBoys() + $row->getRegisteredGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedBoys() + $row->getComposedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getComposedBoys()*100, $row->getRegisteredBoys())), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1,  $this->generalService->formatMark($this->generalService->getRatio($row->getComposedGirls()*100, $row->getRegisteredGirls())), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls()))), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedBoys() + $row->getPassedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedBoys()*100, $row->getComposedBoys())), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedGirls()*100, $row->getComposedGirls())), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 0, 'C', $fill);

        // $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($row->getGeneralAverageBoys()), 1, 0, 'C', $fill);
        // $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($row->getGeneralAverageGirls()), 1, 0, 'C', $fill);
        // $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1,  $this->generalService->formatMark($row->getGeneralAverage()), 1, 0, 'C', $fill);

        
        $pdf->Ln(); 
        
        return $pdf;
    }

    public function totalEtablissementFicheSyntheseDePerformanceDesElevesParClasse(Pagination $pdf, ClassroomStatisticSlipRow $row, bool $fill = false): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 30;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        
        if(strlen($row->getSubject()) <= 15)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);

        }elseif(strlen($row->getSubject()) <= 20)
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }else
        {
            $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }


        $pdf->SetFont('Times', 'B', 9);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredBoys() + $row->getRegisteredGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedBoys() + $row->getComposedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getComposedBoys()*100, $row->getRegisteredBoys())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1,  $this->generalService->formatMark($this->generalService->getRatio($row->getComposedGirls()*100, $row->getRegisteredGirls())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls()))), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedBoys() + $row->getPassedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedBoys()*100, $row->getComposedBoys())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedGirls()*100, $row->getComposedGirls())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 0, 'C', $fill);

        $pdf->Ln(); 
        
        return $pdf;
    }

    public function ligneFicheSyntheseDePerformanceDesElevesParClasse(Pagination $pdf, ClassroomStatisticSlipRow $row, bool $fill = false): Pagination
    {
        $cellWidth0 = 36;
        $cellWidth1 = 30;
        $cellWidth2 = 10;
        $cellWidth3 = 18;
        $cellHeigh0 = 4;
        
        if(strlen($row->getSubject()) <= 15)
        {
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);

        }elseif(strlen($row->getSubject()) <= 20)
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }else
        {
            $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($cellWidth0, $cellHeigh0+1, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }


        $pdf->SetFont('Times', 'B', 9);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getRegisteredBoys() + $row->getRegisteredGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $row->getComposedBoys() + $row->getComposedGirls(), 1, 0, 'C', $fill);
        
        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getComposedBoys()*100, $row->getRegisteredBoys())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1,  $this->generalService->formatMark($this->generalService->getRatio($row->getComposedGirls()*100, $row->getRegisteredGirls())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/3)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls()))), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedBoys(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedGirls(), 1, 0, 'C', $fill);
        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $row->getPassedBoys() + $row->getPassedGirls(), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedBoys()*100, $row->getComposedBoys())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedGirls()*100, $row->getComposedGirls())), 1, 0, 'C', $fill);

        $pdf->Cell(((($cellWidth1*3)+28)/2)/3, $cellHeigh0+1, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 0, 'C', $fill);

        $pdf->Ln(); 
        
        return $pdf;
    }



    public function dataCollectionTableRowPagination(Pagination $pdf, ClassroomStatisticSlipRow $row, array $lessons, bool $fill = false): Pagination
    {
        $cellWidth0 = 55;
        $cellHeigh0 = 4;
        $couvertureProgramme = 80;
        $couvertureHeure =80;
        $tauxAssiduite = 25;
        $tauxReussite = 40;

        $nbreLeconTheoPrevue = 0;
        $nbreClasse = 0;
        $pdf->SetFont('Times', 'B', 9);
        foreach($lessons as $lesson)
        {
            if(($lesson->getClassroom()->getClassroom() == $row->getSubject()) && ($lesson->getSubject()->getSubject() == $row->getTitle()) )
            {
                $pdf->Cell(($cellWidth0/2)+30.5, $cellHeigh0, utf8_decode($row->getSubject()), "LT", 1, 'L', $fill);
                $pdf->SetFont('Times', '', 6);

                if($lesson->getTeacher()->getSex()->getSex() == "F")
                {
                    $pdf->Cell(($cellWidth0/2)+30.5, $cellHeigh0, utf8_decode("(Mme. ".$lesson->getTeacher()->getFullName().")"), "LRB", 0, 'L', $fill);

                }else
                {
                    $pdf->Cell(($cellWidth0/2)+30.5, $cellHeigh0, utf8_decode("(M. ".$lesson->getTeacher()->getFullName().")"), "LRB", 0, 'L', $fill);

                }

                $pdf->SetFont('Times', 'B', 9);
                ////LECONS PREVUES
                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->setXY($x, $y-$cellHeigh0);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()), 1, 0, 'C', $fill);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()), 1, 0, 'C', $fill);

                ////LECONS FAITES
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()), 1, 0, 'C', $fill);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()), 1, 0, 'C', $fill);

                ////POURCENTAGE LECONS
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                    number_format((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                    ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100,2):"00", 1, 0, 'C', $fill);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                    number_format((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                    ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100,2):"00", 1, 0, 'C', $fill);


                    ///HEURES DUES
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode(($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours() ), 1, 0, 'C', $fill);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode(($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ), 1, 0, 'C', $fill);

                    ////HEURES FAITES
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode(($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() ), 1, 0, 'C', $fill);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode(($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ), 1, 0, 'C', $fill);


                    ////POURCENTAGE HEURES FAITES THEORIQUE
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ? utf8_decode(
                        number_format((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100,2)
                    ):"00", 1, 0, 'C', $fill);
                    
                    ////POURCENTAGE HEURES FAITES PRATIQUE
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ? utf8_decode(
                        number_format((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100,2)
                    ):"00", 1, 0, 'C', $fill);


                    //////ASSIDUITE DES ENSEIGNANTS ////////
                    $pdf->Cell(($tauxAssiduite/2), $cellHeigh0*2, ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ? utf8_decode(
                        number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                        (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100,2)

                    ):"00", 1, 0, 'C', $fill);
                    
            }

        }


        //////ASSIDUITE DES ELEVES ////////
        $pdf->Cell(($tauxAssiduite/2), $cellHeigh0*2, $this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls()))), 1, 0, 'C', $fill);
    
        //////////ayant composés
        $pdf->Cell(($tauxReussite/3), $cellHeigh0*2, $row->getComposedBoys() + $row->getComposedGirls(), 1, 0, 'C', $fill);

        ///////NOMBRE DE MOYENNE
        $pdf->Cell(($tauxReussite/3), $cellHeigh0*2, $row->getPassedBoys() + $row->getPassedGirls(), 1, 0, 'C', $fill);

        /////POUCENTAGE DE REUSSITE
        $pdf->Cell(($tauxReussite/3), $cellHeigh0*2, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 1, 'C', $fill);
        
        return $pdf;
    }


    public function ligneFicheDeCollecteDesTauxDeCouverturesDesProgrammesEtHeuresEnseignements(Pagination $pdf, ClassroomStatisticSlipRow $row, array $lessons, int $term, bool $fill = false): Pagination
    {
        $cellWidth0 = 55;
        $cellHeigh0 = 4;
        $couvertureProgramme = 150;
        $couvertureHeure =80;
        $tauxAssiduite = 25;
        $tauxReussite = 40;

        $nbreLeconTheoPrevue = 0;
        $nbreClasse = 0;
        $pdf->SetFont('Times', 'B', 9);
        foreach($lessons as $lesson)
        {
            if(($lesson->getClassroom()->getClassroom() == $row->getSubject()) && ($lesson->getSubject()->getSubject() == $row->getTitle()) )
            {
                $pdf->Cell($couvertureProgramme-110, $cellHeigh0+1, utf8_decode($row->getSubject()), "LT", 0, 'L', $fill);
                // $pdf->SetFont('Times', '', 6);

                // if($lesson->getTeacher()->getSex()->getSex() == "F")
                // {
                //     $pdf->Cell(($cellWidth0/2)+30.5, $cellHeigh0, utf8_decode("(Mme. ".$lesson->getTeacher()->getFullName().")"), "LRB", 0, 'L', $fill);

                // }else
                // {
                //     $pdf->Cell(($cellWidth0/2)+30.5, $cellHeigh0, utf8_decode("(M. ".$lesson->getTeacher()->getFullName().")"), "LRB", 0, 'L', $fill);

                // }

                $pdf->SetFont('Times', 'B', 9);

                ////LECONS PREVUES THEORIQUES
                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->setXY($x, $y);
                
                switch($term)
                {
                    case 1 :
                        #leçon theo prevu trimestre
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq2() 
                        ), 1, 0, 'C', $fill);

                        #leçon theo faite avec ressource trimestre
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() 
                        ), 1, 0, 'C', $fill);

                        #leçon theo faite sans ressource trimestre
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2() 
                        ), 1, 0, 'C', $fill);

                        #pourcentage
                        $nbrLessonPrevues = ($lesson->getNbreLessonTheoriquePrevueSeq1() + 
                        $lesson->getNbreLessonTheoriquePrevueSeq2());

                        if($nbrLessonPrevues == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format(((
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq2()
                                )/($nbrLessonPrevues))*100 , 2)                                                          
                            ), 1, 0, 'C', $fill);
                        }
                        break;

                    case 2 :
                        #leçon theo prevu trimestre
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq4() 
                        ), 1, 0, 'C', $fill);

                        #leçon theo faite avec ressource trimestre
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() 
                        ), 1, 0, 'C', $fill);

                        #leçon theo faite sans ressource trimestre
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteSeq3() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq4() 
                        ), 1, 0, 'C', $fill);

                        #pourcentage
                        $nbrLessonPrevues = ($lesson->getNbreLessonTheoriquePrevueSeq3() + 
                        $lesson->getNbreLessonTheoriquePrevueSeq4());

                        if($nbrLessonPrevues == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq3() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq4() )/($nbrLessonPrevues))*100 , 0, '.', ' ')                                                          
                            ), 1, 0, 'C', $fill);
                        }

                        break;
                    case 3 :
                        #leçon theo prevu trimestre
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq6()
                        ), 1, 0, 'C', $fill);

                        #leçon theo faite avec ressource trimestre
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() 
                        ), 1, 0, 'C', $fill);

                        #leçon theo faite sans ressource trimestre
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteSeq5() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                        ), 1, 0, 'C', $fill);

                        #pourcentage
                        $nbrLessonPrevues = ($lesson->getNbreLessonTheoriquePrevueSeq5() + 
                        $lesson->getNbreLessonTheoriquePrevueSeq6());

                        if($nbrLessonPrevues == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq5() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq6() )/($nbrLessonPrevues))*100 , 0, '.', ' ')                                                          
                            ), 1, 0, 'C', $fill);
                        }
                        break;
                }

                ////LECONS THEORIQUES PREUVES ANNEE 
                $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                                    $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq6()
                                ), 1, 0, 'C', $fill);
                
                $nbreLessonTheoriquePrevueAnnuel = 
                $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                $lesson->getNbreLessonTheoriquePrevueSeq6();

                switch($term)
                {
                    case 1:
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2()
                        ), 1, 0, 'C', $fill);

                        if($nbreLessonTheoriquePrevueAnnuel == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq2() )/($nbreLessonTheoriquePrevueAnnuel))*100 , 2)                                                          
                            ), 1, 0, 'C', $fill);
                        }


                        break;
                    
                    case 2:
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() 
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq4() 
                        ), 1, 0, 'C', $fill);


                        if($nbreLessonTheoriquePrevueAnnuel == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format(((
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq4() 
                                )/($nbreLessonTheoriquePrevueAnnuel))*100 , 2)                                                          
                            ), 1, 0, 'C', $fill);
                        }

                        break;

                    case 3:
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                        ), 1, 0, 'C', $fill);

                        if($nbreLessonTheoriquePrevueAnnuel == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format(((
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                                )/($nbreLessonTheoriquePrevueAnnuel))*100 , 2)                                                          
                            ), 1, 0, 'C', $fill);
                        }
                        break;
                }

                #####LECONS PRATIQUES 
                switch($term)
                {
                    case 1 :
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiquePrevueSeq1() + 
                            $lesson->getNbreLessonPratiquePrevueSeq2() 
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() 
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2() 
                        ), 1, 0, 'C', $fill);

                        $nbrLessonPrevues = ($lesson->getNbreLessonPratiquePrevueSeq1() + 
                        $lesson->getNbreLessonPratiquePrevueSeq2());

                        if($nbrLessonPrevues == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq2() )/($nbrLessonPrevues))*100 , 0, '.', ' ')                                                          
                            ), 1, 0, 'C', $fill);
                        }
                        break;

                    case 2 :
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiquePrevueSeq3() + 
                            $lesson->getNbreLessonPratiquePrevueSeq4() 
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteSeq3() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq4() 
                        ), 1, 0, 'C', $fill);

                        $nbrLessonPrevues = ($lesson->getNbreLessonPratiquePrevueSeq3() + 
                        $lesson->getNbreLessonPratiquePrevueSeq4());

                        if($nbrLessonPrevues == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq3() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq4() )/($nbrLessonPrevues))*100 , 0, '.', ' ')                                                          
                            ), 1, 0, 'C', $fill);
                        }

                        break;
                    case 3 :
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiquePrevueSeq5() + 
                            $lesson->getNbreLessonPratiquePrevueSeq6()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteSeq5() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq6() 
                        ), 1, 0, 'C', $fill);

                        $nbrLessonPrevues = ($lesson->getNbreLessonPratiquePrevueSeq5() + 
                        $lesson->getNbreLessonPratiquePrevueSeq6());

                        if($nbrLessonPrevues == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq5() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq6() )/($nbrLessonPrevues))*100 , 0, '.', ' ')                                                          
                            ), 1, 0, 'C', $fill);
                        }
                        break;
                }

                ////LECONS PRATIQUES PREVUES
                $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0+1, utf8_decode(
                                    $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq6()
                                ), 1, 0, 'C', $fill);
                
                $nbreLessonPratiquePrevueAnnuel = 
                $lesson->getNbreLessonPratiquePrevueSeq1() + 
                $lesson->getNbreLessonPratiquePrevueSeq2() + 
                $lesson->getNbreLessonPratiquePrevueSeq3() + 
                $lesson->getNbreLessonPratiquePrevueSeq4() + 
                $lesson->getNbreLessonPratiquePrevueSeq5() + 
                $lesson->getNbreLessonPratiquePrevueSeq6();

                switch($term)
                {
                    case 1:
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2()
                        ), 1, 0, 'C', $fill);

                        if($nbreLessonPratiquePrevueAnnuel == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq2() )/($nbreLessonPratiquePrevueAnnuel))*100 , 2)                                                          
                            ), 1, 0, 'C', $fill);
                        }


                        break;
                    
                    case 2:
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2() +
                            $lesson->getNbreLessonPratiqueFaiteSeq3() +
                            $lesson->getNbreLessonPratiqueFaiteSeq4() 
                        ), 1, 0, 'C', $fill);


                        if($nbreLessonPratiquePrevueAnnuel == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                $lesson->getNbreLessonPratiqueFaiteSeq4() 
                                )/($nbreLessonPratiquePrevueAnnuel))*100 , 2)                                                          
                            ), 1, 0, 'C', $fill);
                        }

                        break;

                    case 3:
                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0+1, utf8_decode(
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2() +
                            $lesson->getNbreLessonPratiqueFaiteSeq3() +
                            $lesson->getNbreLessonPratiqueFaiteSeq4() +
                            $lesson->getNbreLessonPratiqueFaiteSeq5() +
                            $lesson->getNbreLessonPratiqueFaiteSeq6() 
                        ), 1, 0, 'C', $fill);

                        if($nbreLessonPratiquePrevueAnnuel == 0)
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode('00'), 1, 0, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell((($couvertureProgramme/2)/3)/2, $cellHeigh0+1, utf8_decode(
                                number_format((($lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                $lesson->getNbreLessonPratiqueFaiteSeq4() +
                                $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                $lesson->getNbreLessonPratiqueFaiteSeq6() 
                                )/($nbreLessonPratiquePrevueAnnuel))*100 , 2)                                                          
                            ), 1, 0, 'C', $fill);
                        }
                        break;
                }

                //////////////////////////////
                ////POURCENTAGE HEURE
                $nbreHeuresPrevues = $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                $lesson->getNbreLessonTheoriquePrevueSeq2() +
                $lesson->getNbreLessonTheoriquePrevueSeq3() +
                $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                $lesson->getNbreLessonTheoriquePrevueSeq5() +
                $lesson->getNbreLessonTheoriquePrevueSeq6() +
                $lesson->getNbreLessonPratiquePrevueSeq1() + 
                $lesson->getNbreLessonPratiquePrevueSeq2() +
                $lesson->getNbreLessonPratiquePrevueSeq3() +
                $lesson->getNbreLessonPratiquePrevueSeq4() + 
                $lesson->getNbreLessonPratiquePrevueSeq5() +
                $lesson->getNbreLessonPratiquePrevueSeq6();

                switch($term)
                {
                    case 1:
                        $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                            (
                                $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                $lesson->getNbreLessonTheoriquePrevueSeq2() +
                                $lesson->getNbreLessonPratiquePrevueSeq1() +
                                $lesson->getNbreLessonPratiquePrevueSeq2() 
                            )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                            (
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() 
                            )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);


                        if($nbreHeuresPrevues == 0)
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode("00"), 1, 0, 'C', $fill);
                        }
                        else
                        {

                            $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                                (
                                    number_format((((
                                    $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                    $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                    $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() 
                                ))/( $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                $lesson->getNbreLessonTheoriquePrevueSeq2() +
                                $lesson->getNbreLessonPratiquePrevueSeq1() +
                                $lesson->getNbreLessonPratiquePrevueSeq2()))*100, 2))
    
                                ), 1, 0, 'C', $fill);
                        }


                        break;

                    case 2 :
                        $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                            (
                                $lesson->getNbreLessonTheoriquePrevueSeq3() +
                                $lesson->getNbreLessonTheoriquePrevueSeq4() +
                                $lesson->getNbreLessonPratiquePrevueSeq3() +
                                $lesson->getNbreLessonPratiquePrevueSeq4() 
                            )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                            (
                            
                            $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                            $lesson->getNbreLessonPratiqueFaiteSeq3() +
                            $lesson->getNbreLessonPratiqueFaiteSeq4() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                            
                            )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);


                        if($nbreHeuresPrevues == 0)
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode("00"), 1, 0, 'C', $fill);
                        }
                        else
                        {


                            $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                                (
                                    number_format(((( 
                                    
                                    $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                    $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq4() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                                ))/( 
                                $lesson->getNbreLessonTheoriquePrevueSeq3() +
                                $lesson->getNbreLessonPratiquePrevueSeq3() +
                                $lesson->getNbreLessonTheoriquePrevueSeq4() +
                                $lesson->getNbreLessonPratiquePrevueSeq4()
                                ))*100, 2))
    
                                ), 1, 0, 'C', $fill);
                        }
                        break;

                    case 3 :
                        $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                            (
                             
                            $lesson->getNbreLessonTheoriquePrevueSeq5() +
                            $lesson->getNbreLessonTheoriquePrevueSeq6() +
                            $lesson->getNbreLessonPratiquePrevueSeq5() +
                            $lesson->getNbreLessonPratiquePrevueSeq6()
                            
                            )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);

                        $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                            (
                            $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                            $lesson->getNbreLessonTheoriqueFaiteSeq6() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                            $lesson->getNbreLessonPratiqueFaiteSeq5() +
                            $lesson->getNbreLessonPratiqueFaiteSeq6() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                            )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);


                        if($nbreHeuresPrevues == 0)
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode("00"), 1, 0, 'C', $fill);
                        }
                        else
                        {

                            $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                                (
                                    number_format((((
                                    $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                    $lesson->getNbreLessonTheoriqueFaiteSeq6() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq6() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                ))/( 
                                $lesson->getNbreLessonTheoriquePrevueSeq5() +
                                $lesson->getNbreLessonPratiquePrevueSeq5() +
                                $lesson->getNbreLessonTheoriquePrevueSeq6() +
                                $lesson->getNbreLessonPratiquePrevueSeq6()
                                ))*100, 2))
    
                                ), 1, 0, 'C', $fill);
                        }
                        break;
                }

                        
                ////POURCENTAGE HEURE ANNEE
                $pdf->Cell(((($couvertureProgramme-60)/2))/3, $cellHeigh0+1, utf8_decode(
                                    ($lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                    $lesson->getNbreLessonTheoriquePrevueSeq6() +

                                    $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                    $lesson->getNbreLessonPratiquePrevueSeq6()
                                    
                                    )*$lesson->getWeekHours()
                                ), 1, 0, 'C', $fill);
                
                switch($term)
                {
                    case 1:
                        $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode(
                            (
                                $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() 
                                )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);

                        if($nbreHeuresPrevues == 0)
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode("00"
                            ), 1, 1, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode(
                                number_format((((
                                    $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() 

                                    ))/($nbreHeuresPrevues))*100, 2)
                            ), 1, 1, 'C', $fill);
                        }

                        break;

                    case 2:
                        $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode(
                            (
                                $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                                $lesson->getNbreLessonPratiqueFaiteSeq4() +
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                                )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);

                        if($nbreHeuresPrevues == 0)
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode("00"
                            ), 1, 1, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode(
                                number_format((((
                                    $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                    $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                    $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                                    $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                    $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq4() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                                    ))/($nbreHeuresPrevues))*100, 2)
                            ), 1, 1, 'C', $fill);
                        }
                        break;

                    case 3:
                        $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode(
                            (
                                $lesson->getNbreLessonTheoriqueFaiteSeq5() + 
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                                $lesson->getNbreLessonPratiqueFaiteSeq5() + 
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() + 
                                $lesson->getNbreLessonTheoriqueFaiteSeq6() +
                                $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                                $lesson->getNbreLessonPratiqueFaiteSeq6() +
                                $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                )*$lesson->getWeekHours()
                        ), 1, 0, 'C', $fill);

                        if($nbreHeuresPrevues == 0)
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode("00"
                            ), 1, 1, 'C', $fill);
                        }
                        else
                        {
                            $pdf->Cell(((($couvertureProgramme-60)/2)/3), $cellHeigh0+1, utf8_decode(
                                number_format((((
                                    
                                    $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                    $lesson->getNbreLessonTheoriqueFaiteSeq6() +
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() +
                                    $lesson->getNbreLessonPratiqueFaiteSeq6() +
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                    ))/($nbreHeuresPrevues))*100, 2)
                            ), 1, 1, 'C', $fill);
                        }
                        break;
                }

                    
            }

        }

        return $pdf;
    }

    // /**
    //  * Contruit les élément de la fiche de collecte de données
    //  *
    //  * @param [type] $studentMarkTermsSubjects
    //  * @return array
    //  */
    // public function getDataCollection($studentMarkTermsSubjects): array
    // {
    //     $classroomStatisticSlipPerSubjects = [];

    //     if(!empty($studentMarkTermsSubjects))
    //     {
    //         foreach($studentMarkTermsSubjects as $MarkTermsSubjects)
    //         {
    //             $classroomStatisticSlipPerSubject = [];
    //             $classroomStatisticSlipPerSubjectCycle = [];
    //             $classroomStatisticSlipPerSubjectCycle2 = [];

    //             foreach($MarkTermsSubjects as $subjectEvaluations)
    //             {
    //                 $firstSubjectEvaluations = $subjectEvaluations[0];

    //                 $classroomStatisticSlipRow =  new ClassroomStatisticSlipRow();
    //                 $classroom = $firstSubjectEvaluations->getStudent()->getClassroom();
    //                 $cycle = $classroom->getLevel()->getCycle()->getCycle();

    //                 $numberOfBoys = count($this->studentRepository->findBy([
    //                     'classroom' => $classroom,
    //                     'sex' => $this->sexRepository->findOneBy(['sex' => 'M'])
    //                 ]));

    //                 $numberOfGirls = count($this->studentRepository->findBy([
    //                     'classroom' => $classroom,
    //                     'sex' => $this->sexRepository->findOneBy(['sex' => 'F'])
    //                 ]));

    //                 $classroomStatisticSlipRow->setSubject($classroom->getClassroom())
    //                         ->setRegisteredBoys($numberOfBoys)
    //                         ->setRegisteredGirls( $numberOfGirls)
    //                         ->setTitle($firstSubjectEvaluations->getLesson()->getSubject()->getSubject())
    //                 ;

    //                 $totalMark = 0;
    //                 $totalMarkBoys = 0;
    //                 $totalMarkGirls = 0;
    //                 $lastMark = 20;
    //                 $firstMark = 0;
    //                 $composedBoys = 0;
    //                 $composedGirls = 0;
    //                 $passedBoys = 0;
    //                 $passedGirls = 0;

    //                 foreach($subjectEvaluations as $evaluation)
    //                 {
    //                     $mark = $evaluation->getMark();
    //                     $sex =$evaluation->getStudent()->getSex()->getSex();

    //                     if($mark != ConstantsClass::UNRANKED_MARK)
    //                     {
    //                         $totalMark += $mark;
                                
    //                         if($sex == 'M')
    //                         {
    //                             $composedBoys++;
    //                             $totalMarkBoys += $mark;
    //                         }else
    //                         {
    //                             $composedGirls++;
    //                             $totalMarkGirls += $mark;
    //                         }
                                
    //                         if($mark >= 10)
    //                         {
    //                             if($sex == 'M')
    //                             {
    //                                 $passedBoys++;
    //                             }else
    //                             {
    //                                 $passedGirls++;
    //                             }
    //                         }
                                
    //                             if($mark < $lastMark)
    //                                 $lastMark = $mark;
                                
    //                             if($mark > $firstMark)
    //                                 $firstMark = $mark;
    //                     }
    //                 }

    //                 $generalAverage = $this->generalService->getRatio($totalMark, $composedBoys + $composedGirls);

    //                 $generalAverageBoys = $this->generalService->getRatio($totalMarkBoys, $composedBoys);
                    
    //                 $generalAverageGirls = $this->generalService->getRatio($totalMarkGirls, $composedGirls);
                        
    //                 $classroomStatisticSlipRow->setComposedBoys($composedBoys)
    //                 ->setComposedGirls($composedGirls)
    //                 ->setPassedBoys($passedBoys)
    //                 ->setPassedGirls($passedGirls)
    //                 ->setGeneralAverageBoys($generalAverageBoys)
    //                 ->setGeneralAverageGirls($generalAverageGirls)
    //                 ->setGeneralAverage($generalAverage)
    //                 ->setFirstMark($firstMark)
    //                 ->setLastMark($lastMark)
    //                 ->setAppreciation($this->generalService->getApoAppreciation($generalAverage))
    //                 ;
                    
    //                 if($cycle == 1)
    //                     $classroomStatisticSlipPerSubjectCycle[] = $classroomStatisticSlipRow;
    //                 else
    //                     $classroomStatisticSlipPerSubjectCycle2[] = $classroomStatisticSlipRow;
    //             }
                
    //             $classroomStatisticSlipPerSubject[] = $classroomStatisticSlipPerSubjectCycle;
    //             $classroomStatisticSlipPerSubject[] = $classroomStatisticSlipPerSubjectCycle2;
    //             $classroomStatisticSlipPerSubjects[] = $classroomStatisticSlipPerSubject;

    //             unset($classroomStatisticSlipPerSubject);
    //             unset($classroomStatisticSlipPerSubjectCycle);
    //             unset($classroomStatisticSlipPerSubjectCycle2);
    //         }
    //     }

    //     return  $classroomStatisticSlipPerSubjects;
    // }


    /**
     * Imprime la fiche de collecte de données
     *
     * @param array $classroomStatisticSlipPerSubjects
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param School $school
     * @param SchoolYear $schoolYear
     * @param array $nbreClasseParCycles
     * @return Pagination
     */
    public function printDataCollection(array $classroomStatisticSlipPerSubjects, string $firstPeriodLetter, int $idP, School $school, SchoolYear $schoolYear, array $lessons, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $cellHeigh0 = 4;
            $couvertureProgramme = 80;
            $couvertureHeure =80;
            $tauxAssiduite = 25;

            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }

            $pdf = new Pagination();

            if(empty($classroomStatisticSlipPerSubjects))
            {
                // Oninsère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');
                $pdf->Ln();

                return $pdf;
            }

            foreach($classroomStatisticSlipPerSubjects as $statistics)
            {
                $pageCounter = 0;
                $rowCounter = 0;

                // On insère une page
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pageCounter++;

                // on rempli l'entête administrative
                $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
                $pdf->Ln(2);
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // Logo de l'établissement
                // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
                $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900);  
                $pdf->setXY($x, $y);

                // Entête de la fiche
                if(!empty($statistics[0]))
                    $discipline = $statistics[0][0]->getTitle();
                elseif(!empty($statistics[1]))
                    $discipline = $statistics[1][0]->getTitle();
                else
                    $discipline = "";

                // Entête de la fiche
                // $pdf = $this->generalService->staisticSlipHeader($pdf, 'FICHE DE COLLECTE DE DONNEES', $termName, $school,  'Discipline', $discipline);
                $pdf->SetFont('Times', 'B', 15);
                $pdf->Cell(0, 6, utf8_decode("FICHE  SYNTHESE DE LA COUVERTURE DES HEURES"), 0, 1, 'C');
                $pdf->Cell(0, 6, utf8_decode("ET PROGRAMMES D’ENSEIGNEMENT PAR CLASSE"), 0, 1, 'C');
                // $pdf->Cell(0, 6, utf8_decode("ET DES TAUX D'ASSIDUITE ET DE REUSSITE"), 0, 1, 'C');
                $pdf->Cell(150, 6, utf8_decode($termName), 0, 0, 'C');
                $pdf->Cell(90, 6, utf8_decode('Discipline : '.$discipline), 0, 1, 'C');
                $pdf->Ln();

                // Entête du tableau
                // $pdf = $this->statisticTableHeader($pdf, 'Classes');
                $pdf = $this->dataCollectionTableHeaderPagination($pdf, $school);

                // Contenu du tableau
                $rowSchool = new ClassroomStatisticSlipRow();
                $rowSchool->setSubject('Totaux Etablissement');
                $counterSchool = 0;

                // Cycle 1
                if(!empty($statistics[0]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totaux Cycle 1');
                    $counter = 0;

                    foreach($statistics[0] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->dataCollectionTableRowPagination($pdf, $row, $lessons, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                        // On recupère les tataux du cycle 1
                        if($row->getLastMark() < $rowCycle->getLastMark())
                            $rowCycle->setLastMark($row->getLastMark());
                        if($row->getFirstMark() > $rowCycle->getFirstMark())
                            $rowCycle->setFirstMark($row->getFirstMark());
                        
                        $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                                ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                                ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                                ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                                ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                                ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                                ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                                ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                                ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                        ;
                        $counter++;
                    }
                    $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                    $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                    $generalMark = $rowCycle->getGeneralAverage();

                    if($counter)
                    {
                        $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                        $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                        $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                    }

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // on met à jour les totaux de l'établissement
                    if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                            $rowSchool->setLastMark($rowCycle->getLastMark());
                        if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                            $rowSchool->setFirstMark($rowCycle->getFirstMark());
                        
                        $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                                ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                                ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                                ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                                ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                                ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                                ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                                ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                                ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                        ;

                    // Sous total Cycle 1
                    // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                    $pdf->Cell(58, 4*2, 'Totaux 1er cycle', 1, 0, 'L', true);

                    $nbreLeconTheoPrevue1 = 0;
                    $nbreLeconPratPrevue1 = 0;
                    $nbreLeconTheoFaite1 = 0;
                    $nbreLeconPratFaite1 = 0;

                    $pourcentageLeconTheo1 = 0;
                    $pourcentageLeconPrat1 = 0;

                    $nbreHeureDueTheo1 = 0;
                    $nbreHeureDuePrat1 = 0;
                    $nbreHeureFaiteTheo1 = 0;
                    $nbreHeureFaitePrat1 = 0;

                    $pourcentageHeureDue1 = 0;
                    $pourcentageHeureFaite1 = 0;

                    $pourcentageAssiduiteEnseignant1 = 0;

                    $nbreClasse1erCycle = 0;
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 1 || $nbreClasseParCycle['level'] == 2 || $nbreClasseParCycle['level'] == 3 || $nbreClasseParCycle['level'] == 4 )
                        {
                            $nbreClasse1erCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        if ($lesson->getClassroom()->getLevel()->getLevel() == 1 || $lesson->getClassroom()->getLevel()->getLevel() == 2 || $lesson->getClassroom()->getLevel()->getLevel() == 3 || $lesson->getClassroom()->getLevel()->getLevel() == 4) 
                        { 
                            $nbreLeconTheoPrevue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                            $nbreLeconPratPrevue1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                            $nbreLeconTheoFaite1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                            $nbreLeconPratFaite1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                            //////////////////////////
                            $pourcentageLeconTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                            (number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                            ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";

                            //////////////////////////
                            $pourcentageLeconTheo1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            (number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                            ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";


                            ///////somme heures dues théoriques
                            $nbreHeureDueTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                            //////somme heures dues pratique
                            $nbreHeureDuePrat1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                            ///////somme heures faites théoriques
                            $nbreHeureFaiteTheo1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                            //////somme heures faites pratique
                            $nbreHeureFaitePrat1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                            //////pourcentage heures théorique
                            $pourcentageHeureDue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100
                            )/ $nbreClasse1erCycle),2):"00";


                            ////////////pourcentage des heures pratiques
                            $pourcentageHeureFaite1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse1erCycle),2):"00";


                            /////////ASSIDUITE DES ENSEIGNANTS
                            $pourcentageAssiduiteEnseignant1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                                (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours())*100)/ $nbreClasse1erCycle),2):"00";
                            
                        }
                    }

                    // dd($nbreClasse1erCycle);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevue1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconPrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureDue1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureFaite1), 1, 0, 'C', true);

                    $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, utf8_decode($pourcentageAssiduiteEnseignant1), 1, 0, 'C', true);



                    $pdf = $this->dataCollectionTableRowPagination($pdf, $rowCycle, $lessons, true);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                    
                }
                
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);
                $pdf = $this->dataCollectionTableHeaderPagination($pdf, $school);

                $nbreLeconTheoPrevue1 = 0;
                $nbreLeconPratPrevue1 = 0;
                $nbreLeconTheoFaite1 = 0;
                $nbreLeconPratFaite1 = 0;

                $pourcentageLeconTheo1 = 0;
                $pourcentageLeconPrat1 = 0;

                $nbreHeureDueTheo1 = 0;
                $nbreHeureDuePrat1 = 0;
                $nbreHeureFaiteTheo1 = 0;
                $nbreHeureFaitePrat1 = 0;

                $pourcentageHeureDue1 = 0;
                $pourcentageHeureFaite1 = 0;

                $pourcentageAssiduiteEnseignant1 = 0;
                // Cycle 2
                if(!empty($statistics[1]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totaux Cycle 2');
                    $counter = 0;

                    foreach($statistics[1] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->dataCollectionTableRowPagination($pdf, $row, $lessons, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                        // on met à jour les totaux du cycle 2
                        if($row->getLastMark() < $rowCycle->getLastMark())
                            $rowCycle->setLastMark($row->getLastMark());
                        if($row->getFirstMark() > $rowCycle->getFirstMark())
                            $rowCycle->setFirstMark($row->getFirstMark());
                        
                        $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                                ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                                ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                                ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                                ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                                ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                                ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                                ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                                ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                        ;
                        $counter++;
                    }
                    $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                    $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                    $generalMark = $rowCycle->getGeneralAverage();

                    if($counter)
                    {
                        $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                        $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                        $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                    }

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // on met à jour les totaux de l'établissement
                    if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                            $rowSchool->setLastMark($rowCycle->getLastMark());
                        if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                            $rowSchool->setFirstMark($rowCycle->getFirstMark());
                        
                        $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                                ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                                ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                                ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                                ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                                ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                                ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                                ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                                ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                        ;

                        if($counterSchool)
                        {
                            $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                            $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                            $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                        }

                            $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));

                    // Sous total Cycle 2
                    // $pdf = $this->statisticTableRow($pdf, $rowCycle, true);
                    $pdf->Cell(58, 4*2, 'Totaux 2nd cycle', 1, 0, 'L', true);
                    
                    $nbreLeconTheoPrevue = 0;
                    $nbreLeconPratPrevue = 0;
                    $nbreLeconTheoFaite = 0;
                    $nbreLeconPratFaite = 0;

                    $pourcentageLeconTheo = 0;
                    $pourcentageLeconPrat = 0;

                    $nbreHeureDueTheo = 0;
                    $nbreHeureDuePrat = 0;
                    $nbreHeureFaiteTheo = 0;
                    $nbreHeureFaitePrat = 0;

                    $pourcentageHeureDue = 0;
                    $pourcentageHeureFaite = 0;

                    $pourcentageAssiduiteEnseignant = 0;

                    $nbreClasse2ndCycle = 0;
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 5 || $nbreClasseParCycle['level'] == 6 || $nbreClasseParCycle['level'] == 7 )
                        {
                            $nbreClasse2ndCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        if ($lesson->getClassroom()->getLevel()->getLevel() == 5 || $lesson->getClassroom()->getLevel()->getLevel() == 6 || $lesson->getClassroom()->getLevel()->getLevel() == 7 ) 
                        { 
                            $nbreLeconTheoPrevue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                            $nbreLeconPratPrevue += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                            $nbreLeconTheoFaite += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                            $nbreLeconPratFaite += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                            //////////////////////////POURCENTAGE LECON THEO
                            $pourcentageLeconTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                            number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                            ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100/ $nbreClasse2ndCycle),2) :"00";

                            //////////////////////////
                            $pourcentageLeconPrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                            ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse2ndCycle),2):"00";


                            ///////somme heures dues théoriques
                            $nbreHeureDueTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                            //////somme heures dues pratique
                            $nbreHeureDuePrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                            ///////somme heures faites théoriques
                            $nbreHeureFaiteTheo += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                            //////somme heures faites pratique
                            $nbreHeureFaitePrat += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                            //////pourcentage heures théorique
                            $pourcentageHeureDue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ? 
                                number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle,2):"00";


                            ////////////pourcentage des heures pratiques
                            $pourcentageHeureFaite += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format(((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100
                            )/ $nbreClasse2ndCycle,2):"00";


                            /////////ASSIDUITE DES PARENTS
                            $pourcentageAssiduiteEnseignant += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                                (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle ,2):"00";
                            
                        }
                    }

                    // dd($nbreClasse1erCycle);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevue), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconPrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureDue), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureFaite), 1, 0, 'C', true);

                    $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, utf8_decode($pourcentageAssiduiteEnseignant), 1, 0, 'C', true);

                    $pdf = $this->dataCollectionTableRowPagination($pdf, $rowCycle, $lessons, true);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                }else
                {
                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                    $rowSchool->setAppreciation($this->generalService->getApoAppreciation($rowSchool->getGeneralAverage()));
                }

                
                // Total Etablissement
                // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                $pdf->Cell(58, 4*2, 'Totaux Etablissement', 1, 0, 'L', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode(($nbreLeconTheoPrevue1 ? $nbreLeconTheoPrevue1 : 0) + ($nbreLeconTheoPrevue ? $nbreLeconTheoPrevue : 0)), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue1 + $nbreLeconPratPrevue), 1, 0, 'C', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite1 + $nbreLeconTheoFaite), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite1 + $nbreLeconPratFaite), 1, 0, 'C', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, number_format(($pourcentageLeconTheo1 + $pourcentageLeconTheo)/2,2), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, number_format(($pourcentageLeconPrat1 + $pourcentageLeconPrat)/2,2), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo1 + $nbreHeureDueTheo), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat1 + $nbreHeureDuePrat), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo1 + $nbreHeureFaiteTheo), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat1 + $nbreHeureFaitePrat), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, number_format(($pourcentageHeureDue1 + $pourcentageHeureDue)/2,2), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, number_format(($pourcentageHeureFaite1 + $pourcentageHeureFaite)/2,2), 1, 0, 'C', true);

                $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, number_format(($pourcentageAssiduiteEnseignant1 + $pourcentageAssiduiteEnseignant)/2, 2), 1, 0, 'C', true);


                $pdf = $this->dataCollectionTableRowPagination($pdf, $rowSchool, $lessons, true);

                // $pdf->Ln(3);
                // $pdf->SetFont('Times', 'B', 12);
                // $pdf->Cell(36, 6, '', 0, 0, 'C');
                // $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                // $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');

                //////////////NOUVELLE PAGE
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);
                $pdf->SetFont('Times', 'B', 15);
                $pdf->Cell(0, 6, utf8_decode("OBSERVATIONS GENERALES"), 1, 1, 'C', true);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(277/4, 6, utf8_decode("TAUX DE COUVERTURE DES "), "LR", 0, 'C');
                $pdf->Cell(277/4, 6, utf8_decode("TAUX DE COUVERTURE DES "), "LR", 0, 'C');
                $pdf->Cell(277/4, 6*2, utf8_decode("TAUX D'ASSIDUITE"), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*2, utf8_decode("TAUX DE REUSSITE"), "LRB", 1, 'C');

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->SetXY($x, $y-6);
                $pdf->Cell(277/4, 6, utf8_decode("PROGRAMMES"), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6, utf8_decode("HEURES D'ENSEIGNEMENT"), "LRB", 1, 'C');
                // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 0, 'C');
                // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 1, 'C');

                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 1, 'C');

                $pdf->SetFont('Times', 'BU', 12);
                $pdf->Cell(277, 6*2, utf8_decode("SUGGESTIONS"), "LR", 1, 'L');
                $pdf->Cell(277, 6*10, utf8_decode(""), "LRB", 1, 'L');
                $pdf->Ln(6);

                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(250, 6, utf8_decode("LE CHEF D'ETABLISSEMENT"), 0, 1, 'R');

            }
        }else
        {
            $cellHeigh0 = 4;
            $couvertureProgramme = 80;
            $couvertureHeure =80;
            $tauxAssiduite = 25;

            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }

            $pdf = new Pagination();

            if(empty($classroomStatisticSlipPerSubjects))
            {
                // Oninsère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the material is taught in at least one class'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the grades for this subject are entered'), 0, 1, 'C');
                $pdf->Ln();

                return $pdf;
            }

            foreach($classroomStatisticSlipPerSubjects as $statistics)
            {
                
                $pageCounter = 0;
                $rowCounter = 0;

                // Oninsère une page
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pageCounter++;

                // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900); 
                // on rempli l'entête administrative
                $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
                $pdf->Ln(2);
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // Logo de l'établissement
                // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
                 
                $pdf->setXY($x, $y);

                // Entête de la fiche
                if(!empty($statistics[0]))
                    $discipline = $statistics[0][0]->getTitle();
                elseif(!empty($statistics[1]))
                    $discipline = $statistics[1][0]->getTitle();
                else
                    $discipline = "";

                // Entête de la fiche
                // $pdf = $this->generalService->staisticSlipHeader($pdf, 'FICHE DE COLLECTE DE DONNEES', $termName, $school,  'Discipline', $discipline);
                $pdf->SetFont('Times', 'B', 15);
                $pdf->Cell(0, 6, utf8_decode("TEACHING HOURS AND PROGRAMME COVERAGE "), 0, 1, 'C');
                $pdf->Cell(0, 6, utf8_decode("SYNTHESIS FORM PER CLASS"), 0, 1, 'C');
                // $pdf->Cell(0, 6, utf8_decode("AND ATTENDANCE AND SUCCESS RATES"), 0, 1, 'C');
                $pdf->Cell(117, 6, utf8_decode($termName), 0, 0, 'R');
                $pdf->Cell(70, 6, utf8_decode('Discipline : '.$discipline), 0, 1, 'L');
                $pdf->Ln();

                // Entête du tableau
                // $pdf = $this->statisticTableHeader($pdf, 'Classes');
                $pdf = $this->dataCollectionTableHeaderPagination($pdf, $school);

                // Contenu du tableau
                $rowSchool = new ClassroomStatisticSlipRow();
                $rowSchool->setSubject('Totals Etablissement');
                $counterSchool = 0;

                $nbreLeconTheoPrevue = 0;
                $nbreLeconPratPrevue = 0;
                $nbreLeconTheoFaite = 0;
                $nbreLeconPratFaite = 0;

                $pourcentageLeconTheo = 0;
                $pourcentageLeconPrat = 0;

                $nbreHeureDueTheo = 0;
                $nbreHeureDuePrat = 0;
                $nbreHeureFaiteTheo = 0;
                $nbreHeureFaitePrat = 0;

                $pourcentageHeureDue = 0;
                $pourcentageHeureFaite = 0;

                $pourcentageAssiduiteEnseignant = 0;

                $nbreClasse2ndCycle = 0;

                
                // Cycle 1
                if(!empty($statistics[0]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totals Cycle 1');
                    $counter = 0;

                    foreach($statistics[0] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->dataCollectionTableRowPagination($pdf, $row, $lessons, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                        // On recupère les tataux du cycle 1
                        if($row->getLastMark() < $rowCycle->getLastMark())
                            $rowCycle->setLastMark($row->getLastMark());
                        if($row->getFirstMark() > $rowCycle->getFirstMark())
                            $rowCycle->setFirstMark($row->getFirstMark());
                        
                        $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                                ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                                ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                                ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                                ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                                ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                                ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                                ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                                ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                        ;
                        $counter++;
                    }
                    $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                    $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                    $generalMark = $rowCycle->getGeneralAverage();

                    if($counter)
                    {
                        $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                        $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                        $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                    }

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // on met à jour les totaux de l'établissement
                    if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                            $rowSchool->setLastMark($rowCycle->getLastMark());
                        if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                            $rowSchool->setFirstMark($rowCycle->getFirstMark());
                        
                        $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                                ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                                ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                                ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                                ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                                ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                                ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                                ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                                ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                        ;

                    // Sous total Cycle 1
                    // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                    $pdf->Cell(58, 4*2, 'Totals 1st cycle', 1, 0, 'L', true);

                    $nbreLeconTheoPrevue1 = 0;
                    $nbreLeconPratPrevue1 = 0;
                    $nbreLeconTheoFaite1 = 0;
                    $nbreLeconPratFaite1 = 0;

                    $pourcentageLeconTheo1 = 0;
                    $pourcentageLeconPrat1 = 0;

                    $nbreHeureDueTheo1 = 0;
                    $nbreHeureDuePrat1 = 0;
                    $nbreHeureFaiteTheo1 = 0;
                    $nbreHeureFaitePrat1 = 0;

                    $pourcentageHeureDue1 = 0;
                    $pourcentageHeureFaite1 = 0;

                    $pourcentageAssiduiteEnseignant1 = 0;

                    $nbreClasse1erCycle = 0;
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 1 || $nbreClasseParCycle['level'] == 2 || $nbreClasseParCycle['level'] == 3 || $nbreClasseParCycle['level'] == 4 )
                        {
                            $nbreClasse1erCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        if ($lesson->getClassroom()->getLevel()->getLevel() == 1 || $lesson->getClassroom()->getLevel()->getLevel() == 2 || $lesson->getClassroom()->getLevel()->getLevel() == 3 || $lesson->getClassroom()->getLevel()->getLevel() == 4) 
                        { 
                            $nbreLeconTheoPrevue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                            $nbreLeconPratPrevue1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                            $nbreLeconTheoFaite1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                            $nbreLeconPratFaite1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                            //////////////////////////
                            $pourcentageLeconTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                            (number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                            ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";

                            //////////////////////////
                            $pourcentageLeconTheo1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            (number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                            ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";


                            ///////somme heures dues théoriques
                            $nbreHeureDueTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                            //////somme heures dues pratique
                            $nbreHeureDuePrat1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                            ///////somme heures faites théoriques
                            $nbreHeureFaiteTheo1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                            //////somme heures faites pratique
                            $nbreHeureFaitePrat1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                            //////pourcentage heures théorique
                            $pourcentageHeureDue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100
                            )/ $nbreClasse1erCycle),2):"00";


                            ////////////pourcentage des heures pratiques
                            $pourcentageHeureFaite1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse1erCycle),2):"00";


                            /////////ASSIDUITE DES ENSEIGNANTS
                            $pourcentageAssiduiteEnseignant1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                                (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours())*100)/ $nbreClasse1erCycle),2):"00";
                            
                        }
                    }

                    // dd($nbreClasse1erCycle);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevue1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconPrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureDue1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureFaite1), 1, 0, 'C', true);

                    $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, utf8_decode($pourcentageAssiduiteEnseignant1), 1, 0, 'C', true);



                    $pdf = $this->dataCollectionTableRowPagination($pdf, $rowCycle, $lessons, true);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                    
                }

                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);
                $pdf = $this->dataCollectionTableHeaderPagination($pdf, $school);

                // Cycle 2
                if(!empty($statistics[1]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totals 2nd Cycle');
                    $counter = 0;

                    foreach($statistics[1] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->dataCollectionTableRowPagination($pdf, $row, $lessons, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                        // on met à jour les totaux du cycle 2
                        if($row->getLastMark() < $rowCycle->getLastMark())
                            $rowCycle->setLastMark($row->getLastMark());
                        if($row->getFirstMark() > $rowCycle->getFirstMark())
                            $rowCycle->setFirstMark($row->getFirstMark());
                        
                        $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                                ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                                ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                                ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                                ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                                ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                                ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                                ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                                ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                        ;
                        $counter++;
                    }
                    $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                    $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                    $generalMark = $rowCycle->getGeneralAverage();

                    if($counter)
                    {
                        $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                        $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                        $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                    }

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // on met à jour les totaux de l'établissement
                    if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                            $rowSchool->setLastMark($rowCycle->getLastMark());
                        if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                            $rowSchool->setFirstMark($rowCycle->getFirstMark());
                        
                        $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                                ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                                ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                                ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                                ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                                ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                                ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                                ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                                ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                        ;

                        if($counterSchool)
                        {
                            $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                            $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                            $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                        }

                            $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));

                    // Sous total Cycle 2
                    // $pdf = $this->statisticTableRow($pdf, $rowCycle, true);
                    $pdf->Cell(58, 4*2, 'Totals 2nd cycle', 1, 0, 'L', true);
                    
                    
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 5 || $nbreClasseParCycle['level'] == 6 || $nbreClasseParCycle['level'] == 7 )
                        {
                            $nbreClasse2ndCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        if ($lesson->getClassroom()->getLevel()->getLevel() == 5 || $lesson->getClassroom()->getLevel()->getLevel() == 6 || $lesson->getClassroom()->getLevel()->getLevel() == 7 ) 
                        { 
                            $nbreLeconTheoPrevue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                            $nbreLeconPratPrevue += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                            $nbreLeconTheoFaite += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                            $nbreLeconPratFaite += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                            //////////////////////////POURCENTAGE LECON THEO
                            $pourcentageLeconTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                            number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                            ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100/ $nbreClasse2ndCycle),2) :"00";

                            //////////////////////////
                            $pourcentageLeconPrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                            ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse2ndCycle),2):"00";


                            ///////somme heures dues théoriques
                            $nbreHeureDueTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                            //////somme heures dues pratique
                            $nbreHeureDuePrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                            ///////somme heures faites théoriques
                            $nbreHeureFaiteTheo += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                            //////somme heures faites pratique
                            $nbreHeureFaitePrat += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                            //////pourcentage heures théorique
                            $pourcentageHeureDue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ? 
                                number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle,2):"00";


                            ////////////pourcentage des heures pratiques
                            $pourcentageHeureFaite += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format(((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100
                            )/ $nbreClasse2ndCycle,2):"00";


                            /////////ASSIDUITE DES PARENTS
                            $pourcentageAssiduiteEnseignant += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                                (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle ,2):"00";
                            
                        }
                    }
                    
                    // dd($nbreClasse1erCycle);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevue), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconPrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureDue), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureFaite), 1, 0, 'C', true);

                    $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, utf8_decode($pourcentageAssiduiteEnseignant), 1, 0, 'C', true);

                    $pdf = $this->dataCollectionTableRowPagination($pdf, $rowCycle, $lessons, true);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                }else
                {
                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                    $rowSchool->setAppreciation($this->generalService->getApoAppreciation($rowSchool->getGeneralAverage()));
                }

                
                // Total Etablissement
                // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                $pdf->Cell(58, 4*2, 'Totals Etablissement', 1, 0, 'L', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode(($nbreLeconTheoPrevue1 ? $nbreLeconTheoPrevue1 : 0) + ($nbreLeconTheoPrevue ? $nbreLeconTheoPrevue : 0)), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue1 + $nbreLeconPratPrevue), 1, 0, 'C', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite1 + $nbreLeconTheoFaite), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite1 + $nbreLeconPratFaite), 1, 0, 'C', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, number_format(($pourcentageLeconTheo1 + $pourcentageLeconTheo)/2,2), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, number_format(($pourcentageLeconPrat1 + $pourcentageLeconPrat)/2,2), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo1 + $nbreHeureDueTheo), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat1 + $nbreHeureDuePrat), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo1 + $nbreHeureFaiteTheo), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat1 + $nbreHeureFaitePrat), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, number_format(($pourcentageHeureDue1 + $pourcentageHeureDue)/2,2), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, number_format(($pourcentageHeureFaite1 + $pourcentageHeureFaite)/2,2), 1, 0, 'C', true);

                $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, number_format(($pourcentageAssiduiteEnseignant1 + $pourcentageAssiduiteEnseignant)/2, 2), 1, 0, 'C', true);


                $pdf = $this->dataCollectionTableRowPagination($pdf, $rowSchool, $lessons, true);

                // $pdf->Ln(3);
                // $pdf->SetFont('Times', 'B', 12);
                // $pdf->Cell(36, 6, '', 0, 0, 'C');
                // $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                // $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');

                //////////////NOUVELLE PAGE
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 50, -90);  

                $pdf->SetFont('Times', 'B', 15);
                $pdf->Cell(0, 6, utf8_decode("GENERAL OBSERVATIONS"), 1, 1, 'C', true);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(277/4, 6, utf8_decode("COVERAGE RATE OF "), "LR", 0, 'C');
                $pdf->Cell(277/4, 6, utf8_decode("COVERAGE RATE OF "), "LR", 0, 'C');
                $pdf->Cell(277/4, 6*2, utf8_decode("ATTENDANCE RATE"), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*2, utf8_decode("SUCCESS RATE"), "LRB", 1, 'C');

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->SetXY($x, $y-6);
                $pdf->Cell(277/4, 6, utf8_decode("PROGRAMS"), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6, utf8_decode("TEACHING HOURS"), "LRB", 1, 'C');
                // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 0, 'C');
                // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 1, 'C');

                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 1, 'C');

                $pdf->SetFont('Times', 'BU', 12);
                $pdf->Cell(277, 6*2, utf8_decode("SUGGESTIONS"), "LR", 1, 'L');
                $pdf->Cell(277, 6*10, utf8_decode(""), "LRB", 1, 'L');
                $pdf->Ln(6);

                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(250, 6, utf8_decode("THE HEAD TEACHER"), 0, 1, 'R');

            }
        }
        return $pdf;
    }

    public function ficheDeCollecteDesTauxDeCouvertureDesProgrammesEtHeuresEnseignements(array $classroomStatisticSlipPerSubjects, string $firstPeriodLetter, int $idP, School $school, SchoolYear $schoolYear, array $lessons, SubSystem $subSystem, int $term): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $cellHeigh0 = 4;
            $couvertureProgramme = 150;
            $couvertureHeure =80;
            $tauxAssiduite = 25;

            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }

            $pdf = new Pagination();

            if(empty($classroomStatisticSlipPerSubjects))
            {
                // Oninsère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');
                $pdf->Ln();
                return $pdf;
            }

            foreach($classroomStatisticSlipPerSubjects as $statistics)
            {
                $pageCounter = 0;
                $rowCounter = 0;

                // On insère une page
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pageCounter++;

                // on rempli l'entête administrative
                $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
                $pdf->Ln(2);
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // Logo de l'établissement
                // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
                $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900);  
                $pdf->setXY($x, $y);

                // Entête de la fiche
                if(!empty($statistics[0]))
                    $discipline = $statistics[0][0]->getTitle();
                elseif(!empty($statistics[1]))
                    $discipline = $statistics[1][0]->getTitle();
                else
                    $discipline = "";

                // Entête de la fiche
                // $pdf = $this->generalService->staisticSlipHeader($pdf, 'FICHE DE COLLECTE DE DONNEES', $termName, $school,  'Discipline', $discipline);
                $pdf->SetFont('Times', 'B', 14);
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell(0, 6, utf8_decode("FICHE DE COLLECTE DES TAUX DE COUVERTURE DES PROGRAMMES ET DES HEURES D'ENSEIGNEMENTS"), 0, 1, 'C');
                }
                else
                {
                    $pdf->Cell(0, 6, utf8_decode("DATA COLLECTION FORM FOR PROGRAM COVERAGE RATES AND TEACHING HOURS"), 0, 1, 'C');
                }
                
                $pdf->Cell(150, 6, utf8_decode($termName), 0, 0, 'C');
                $pdf->Cell(90, 6, utf8_decode('Discipline : '.$discipline), 0, 1, 'C');
                $pdf->Ln();

                // Entête du tableau
                $pdf = $this->enteteTableauFicheDeCollecteTauxDeCouvertureDesProgrammesEtHeuresEnseignements($pdf, $school);

                // Contenu du tableau
                $rowSchool = new ClassroomStatisticSlipRow();
                $rowSchool->setSubject('Totaux Etablissement');
                $counterSchool = 0;

                // Cycle 1
                if(!empty($statistics[0]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totaux Cycle 1');
                    $counter = 0;

                    foreach($statistics[0] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->ligneFicheDeCollecteDesTauxDeCouverturesDesProgrammesEtHeuresEnseignements($pdf, $row, $lessons, $term, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                    }
                    

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // on met à jour les totaux de l'établissement
                    
                    // Sous total Cycle 1
                    // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                    $pdf->Cell($couvertureProgramme-110, 4*2, 'Totaux 1er cycle', 1, 0, 'L', true);

                    $nbreLeconTheoPrevueTrimestreCycle1 = 0;
                    $nbreLeconTheoPrevueAnneeCycle1 = 0;
                    $nbreLeconTheoFaiteAvecRessourceAnneeCycle1 = 0;
                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 = 0;
                    $nbreLeconTheoFaiteSansRessourceAnneeCycle1 = 0;
                    $nbreLeconTheoFaiteTrimestreCycle1 = 0;

                    $nbreLeconPratPrevueTrimestreCycle1 = 0;
                    $nbreLeconPratPrevueAnneeCycle1 = 0;
                    $nbreLeconPratFaiteAvecRessourceAnneeCycle1 = 0;
                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle1 = 0;
                    $nbreLeconPratFaiteSansRessourceAnneeCycle1 = 0;
                    $nbreLeconPratFaiteTrimestreCycle1 = 0;

                    $heureParSemaine = 0;

                    $nbreClasse1erCycle = 0;
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 1 || 
                        $nbreClasseParCycle['level'] == 2 || 
                        $nbreClasseParCycle['level'] == 3 || 
                        $nbreClasseParCycle['level'] == 4 )
                        {
                            $nbreClasse1erCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        $heureParSemaine = $lesson->getWeekHours();

                        if ($lesson->getClassroom()->getLevel()->getLevel() == 1 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 2 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 3 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 4) 
                        { 
                            $nbreLeconTheoPrevueAnneeCycle1 += 
                            $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq6();

                            $nbreLeconTheoFaiteAvecRessourceAnneeCycle1 += 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() ;
                            
                            $nbreLeconTheoFaiteSansRessourceAnneeCycle1 += 
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq3() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq4() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq5() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq6() ;

                            ###########
                            $nbreLeconPratPrevueAnneeCycle1 += 
                                $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                $lesson->getNbreLessonPratiquePrevueSeq6();

                            $nbreLeconPratFaiteAvecRessourceAnneeCycle1 += 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() ;
                            
                            

                            $nbreLeconPratFaiteSansRessourceAnneeCycle1 += 
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq3() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq4() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq5() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq6() ;

                            switch($term)
                            {
                                case 1 :
                                    $nbreLeconTheoPrevueTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq2()
                                                    );

                                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2()
                                                    );

                                    $nbreLeconTheoFaiteTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq2()
                                                    );

                                    ###########
                                    $nbreLeconPratPrevueTrimestreCycle1 += (
                                        $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq2()
                                    );

                                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle1 += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2()
                                                    );

                                    $nbreLeconPratFaiteTrimestreCycle1 += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                                        $lesson->getNbreLessonPratiqueFaiteSeq2()
                                                    );
                                    break;

                                case 2 :
                                    $nbreLeconTheoPrevueTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq4() 
                                                    );

                                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() 
                                                    );

                                    $nbreLeconTheoFaiteTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq4() 
                                                    );

                                    ################
                                    $nbreLeconPratPrevueTrimestreCycle1 += (
                                        $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq4() 
                                    );

                                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle1 += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                                                    );

                                    $nbreLeconPratFaiteTrimestreCycle1 += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                                        $lesson->getNbreLessonPratiqueFaiteSeq4() 
                                                    );
                                    break;

                                case 3 :
                                    $nbreLeconTheoPrevueTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq6()
                                                    );
                                        
                                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() 
                                                    );

                                    $nbreLeconTheoFaiteTrimestreCycle1 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                                                    );


                                    #########################
                                    $nbreLeconPratPrevueTrimestreCycle1 += (
                                        $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq6()
                                    );
                        
                                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle1 += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                                    );

                                    $nbreLeconPratFaiteTrimestreCycle1 += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                                        $lesson->getNbreLessonPratiqueFaiteSeq6() 
                                                    );
                                    break;


                                    case 0 :
                                        $nbreLeconTheoPrevueTrimestreCycle1 += (
                                                            $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq6()
                                                        );
                                            
                                        $nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 += (
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() 
                                                        );
    
                                        $nbreLeconTheoFaiteTrimestreCycle1 += (
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq1() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                                                        );
    
    
                                        #########################
                                        $nbreLeconPratPrevueTrimestreCycle1 += (
                                            $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq6()
                                        );
                            
                                        $nbreLeconPratFaiteAvecRessourceTrimestreCycle1 += (
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                                        );
    
                                        $nbreLeconPratFaiteTrimestreCycle1 += (
                                                            $lesson->getNbreLessonPratiqueFaiteSeq1() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq4() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq6() 
                                                        );
                                        break;
                            }
                            
                        }
                    }

                    // leçons théoriques trimestre
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevueTrimestreCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteAvecRessourceTrimestreCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteTrimestreCycle1), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueTrimestreCycle1 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 + 
                            $nbreLeconTheoFaiteTrimestreCycle1)/($nbreLeconTheoPrevueTrimestreCycle1))*100, 2)
                            ), 1, 0, 'C', true);
                    }
                    
                        
                    #leçons théoriques prévues l'année
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevueAnneeCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteAvecRessourceAnneeCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteSansRessourceAnneeCycle1), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueAnneeCycle1 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconTheoFaiteAvecRessourceAnneeCycle1 + $nbreLeconTheoFaiteSansRessourceAnneeCycle1)
                            /($nbreLeconTheoPrevueAnneeCycle1))*100,2)
                        ), 1, 0, 'C', true);
                    }

                    ##################
                    // leçons pratiques trimestre
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconPratPrevueTrimestreCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteAvecRessourceTrimestreCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteTrimestreCycle1), 1, 0, 'C', true);
                    
                    if($nbreLeconPratPrevueTrimestreCycle1 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"
                            ), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconPratFaiteAvecRessourceTrimestreCycle1 + 
                            $nbreLeconPratFaiteTrimestreCycle1)/($nbreLeconPratPrevueTrimestreCycle1))*100, 2)
                            ), 1, 0, 'C', true);
                    }
                    
                    #leçons pratiques prévues l'année
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconPratPrevueAnneeCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteAvecRessourceAnneeCycle1), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteSansRessourceAnneeCycle1), 1, 0, 'C', true);
                    
                    if($nbreLeconPratPrevueAnneeCycle1 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"
                        ), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconPratFaiteAvecRessourceAnneeCycle1 + $nbreLeconPratFaiteSansRessourceAnneeCycle1)
                            /($nbreLeconPratPrevueAnneeCycle1))*100,2)
                        ), 1, 0, 'C', true);
                    }
                    

                    ####### heures trimestre
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        ($nbreLeconTheoPrevueTrimestreCycle1 + $nbreLeconPratPrevueTrimestreCycle1)*$heureParSemaine
                    ), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        (
                            $nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 + 
                            $nbreLeconTheoFaiteTrimestreCycle1 +
                            $nbreLeconPratFaiteAvecRessourceTrimestreCycle1 + 
                            $nbreLeconPratFaiteTrimestreCycle1 
                        )*$heureParSemaine
                    ), 1, 0, 'C', true);

                    if($nbreLeconTheoPrevueTrimestreCycle1 + $nbreLeconPratPrevueTrimestreCycle1 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format(((
                                 $nbreLeconTheoFaiteAvecRessourceTrimestreCycle1 + 
                                 $nbreLeconTheoFaiteTrimestreCycle1 +
                                 $nbreLeconPratFaiteAvecRessourceTrimestreCycle1 + 
                                 $nbreLeconPratFaiteTrimestreCycle1 
                            )
                             /($nbreLeconTheoPrevueTrimestreCycle1 + $nbreLeconPratPrevueTrimestreCycle1))*100, 2)
                         ), 1, 0, 'C', true);
                    }
                    

                    ####### heures annuelles
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        ($nbreLeconTheoPrevueAnneeCycle1 + $nbreLeconPratPrevueAnneeCycle1)*$heureParSemaine), 1, 0, 'C', true);
                    
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        (
                            $nbreLeconTheoFaiteAvecRessourceAnneeCycle1 + 
                            $nbreLeconTheoFaiteSansRessourceAnneeCycle1 +
                            $nbreLeconPratFaiteAvecRessourceAnneeCycle1 + 
                            $nbreLeconPratFaiteSansRessourceAnneeCycle1
                        )*$heureParSemaine), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueAnneeCycle1 + $nbreLeconPratPrevueAnneeCycle1 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 1, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format(((
                                $nbreLeconTheoFaiteAvecRessourceAnneeCycle1 + 
                                $nbreLeconTheoFaiteSansRessourceAnneeCycle1 +
                                $nbreLeconPratFaiteAvecRessourceAnneeCycle1 + 
                                $nbreLeconPratFaiteSansRessourceAnneeCycle1
                                )
                            /($nbreLeconTheoPrevueAnneeCycle1 + $nbreLeconPratPrevueAnneeCycle1))*100, 2)
                        ), 1, 1, 'C', true);
                    }
                    

                    // $pdf = $this->dataCollectionTableRowPagination($pdf, $rowCycle, $lessons, true);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                    
                }
                
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);
                $pdf = $this->enteteTableauFicheDeCollecteTauxDeCouvertureDesProgrammesEtHeuresEnseignements($pdf, $school);

                
                // Cycle 2
                if(!empty($statistics[1]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totaux Cycle 2');
                    $counter = 0;

                    foreach($statistics[1] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->ligneFicheDeCollecteDesTauxDeCouverturesDesProgrammesEtHeuresEnseignements($pdf, $row, $lessons, $term, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                        // on met à jour les totaux du cycle 2
                        if($row->getLastMark() < $rowCycle->getLastMark())
                            $rowCycle->setLastMark($row->getLastMark());
                        if($row->getFirstMark() > $rowCycle->getFirstMark())
                            $rowCycle->setFirstMark($row->getFirstMark());
                        
                        $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                                ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                                ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                                ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                                ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                                ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                                ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                                ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                                ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                        ;
                        $counter++;
                    }
                    $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                    $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                    $generalMark = $rowCycle->getGeneralAverage();

                    if($counter)
                    {
                        $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                        $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                        $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                    }

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // Sous total Cycle 2
                    // $pdf = $this->statisticTableRow($pdf, $rowCycle, true);
                    $pdf->Cell($couvertureProgramme-110, 4*2, 'Totaux 2nd cycle', 1, 0, 'L', true);
                    
                    $nbreLeconTheoPrevue2 = 0;

                    $nbreLeconTheoPrevueTrimestreCycle2 = 0;
                    $nbreLeconTheoPrevueAnneeCycle2 = 0;
                    $nbreLeconTheoFaiteAvecRessourceAnneeCycle2 = 0;
                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 = 0;
                    $nbreLeconTheoFaiteSansRessourceAnneeCycle2 = 0;
                    $nbreLeconTheoFaiteTrimestreCycle2 = 0;

                    $nbreLeconPratPrevueTrimestreCycle2 = 0;
                    $nbreLeconPratPrevueAnneeCycle2 = 0;
                    $nbreLeconPratFaiteAvecRessourceAnneeCycle2 = 0;
                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle2 = 0;
                    $nbreLeconPratFaiteSansRessourceAnneeCycle2 = 0;
                    $nbreLeconPratFaiteTrimestreCycle2 = 0;

                    $heureParSemaine = 0;


                    $nbreLeconPratPrevue1 = 0;
                    $nbreLeconTheoFaite1 = 0;
                    $nbreLeconPratFaite1 = 0;

                    $pourcentageLeconTheo1 = 0;
                    $pourcentageLeconPrat1 = 0;

                    $nbreHeureDueTheo1 = 0;
                    $nbreHeureDuePrat1 = 0;
                    $nbreHeureFaiteTheo1 = 0;
                    $nbreHeureFaitePrat1 = 0;

                    $pourcentageHeureDue1 = 0;
                    $pourcentageHeureFaite1 = 0;

                    $pourcentageAssiduiteEnseignant1 = 0;

                    $nbreClasse1erCycle = 0;
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 5 || 
                        $nbreClasseParCycle['level'] == 6 || 
                        $nbreClasseParCycle['level'] == 7  )
                        {
                            $nbreClasse1erCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        $heureParSemaine = $lesson->getWeekHours();

                        if ($lesson->getClassroom()->getLevel()->getLevel() == 5 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 6 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 7
                            ) 
                        { 
                            $nbreLeconTheoPrevueAnneeCycle2 += 
                            $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq6();

                            $nbreLeconTheoFaiteAvecRessourceAnneeCycle2 += 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() ;
                            
                            $nbreLeconTheoFaiteSansRessourceAnneeCycle2 += 
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq3() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq4() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq5() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq6() ;

                            ###########
                            $nbreLeconPratPrevueAnneeCycle2 += 
                                $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                $lesson->getNbreLessonPratiquePrevueSeq6();

                            $nbreLeconPratFaiteAvecRessourceAnneeCycle2 += 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() ;
                            
                            $nbreLeconPratFaiteSansRessourceAnneeCycle2 += 
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq3() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq4() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq5() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq6() ;

                            switch($term)
                            {
                                case 1 :
                                    $nbreLeconTheoPrevueTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq2()
                                                    );

                                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2()
                                                    );

                                    $nbreLeconTheoFaiteTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq2()
                                                    );

                                    ###########
                                    $nbreLeconPratPrevueTrimestreCycle2 += (
                                        $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq2()
                                    );

                                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle2 += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2()
                                                    );

                                    $nbreLeconPratFaiteTrimestreCycle2 += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                                        $lesson->getNbreLessonPratiqueFaiteSeq2()
                                                    );
                                    break;

                                case 2 :
                                    $nbreLeconTheoPrevueTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq4() 
                                                    );

                                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() 
                                                    );

                                    $nbreLeconTheoFaiteTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq4() 
                                                    );

                                    ################
                                    $nbreLeconPratPrevueTrimestreCycle2 += (
                                        $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq4() 
                                    );

                                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle2 += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                                                    );

                                    $nbreLeconPratFaiteTrimestreCycle2 += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                                        $lesson->getNbreLessonPratiqueFaiteSeq4() 
                                                    );
                                    break;

                                case 3 :
                                    $nbreLeconTheoPrevueTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq6()
                                                    );
                                        
                                    $nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() 
                                                    );

                                    $nbreLeconTheoFaiteTrimestreCycle2 += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                                                    );


                                    #########################
                                    $nbreLeconPratPrevueTrimestreCycle2 += (
                                        $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq6()
                                    );
                        
                                    $nbreLeconPratFaiteAvecRessourceTrimestreCycle2 += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                                    );

                                    $nbreLeconPratFaiteTrimestreCycle2 += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                                        $lesson->getNbreLessonPratiqueFaiteSeq6() 
                                                    );
                                    break;

                                    case 0 :
                                        $nbreLeconTheoPrevueTrimestreCycle2 += (
                                                            $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq6()
                                                        );
                                            
                                        $nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 += (
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() 
                                                        );
    
                                        $nbreLeconTheoFaiteTrimestreCycle2 += (
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq1() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                                                        );
    
    
                                        #########################
                                        $nbreLeconPratPrevueTrimestreCycle2 += (
                                            $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq6()
                                        );
                            
                                        $nbreLeconPratFaiteAvecRessourceTrimestreCycle2 += (
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                                        );
    
                                        $nbreLeconPratFaiteTrimestreCycle2 += (
                                                            $lesson->getNbreLessonPratiqueFaiteSeq1() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq4() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq6() 
                                                        );
                                        break;
                            }
                            
                        }
                    }

                    // leçons théoriques trimestre
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevueTrimestreCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteAvecRessourceTrimestreCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteTrimestreCycle2), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueTrimestreCycle2 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 + 
                            $nbreLeconTheoFaiteTrimestreCycle2)/($nbreLeconTheoPrevueTrimestreCycle2))*100, 2)
                            ), 1, 0, 'C', true);
                    }
                   
                    
                    #leçons théoriques prévues l'année
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevueAnneeCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteAvecRessourceAnneeCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteSansRessourceAnneeCycle2), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueAnneeCycle2 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconTheoFaiteAvecRessourceAnneeCycle2 + $nbreLeconTheoFaiteSansRessourceAnneeCycle2)
                            /($nbreLeconTheoPrevueAnneeCycle2))*100,2)
                        ), 1, 0, 'C', true);
                    }
                    

                    ##################
                    // leçons pratiques trimestre
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconPratPrevueTrimestreCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteAvecRessourceTrimestreCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteTrimestreCycle2), 1, 0, 'C', true);
                    
                    if($nbreLeconPratPrevueTrimestreCycle2 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"
                            ), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconPratFaiteAvecRessourceTrimestreCycle2 + 
                            $nbreLeconPratFaiteTrimestreCycle2)/($nbreLeconPratPrevueTrimestreCycle2))*100, 2)
                            ), 1, 0, 'C', true);
                    }
                    
                    #leçons pratiques prévues l'année
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconPratPrevueAnneeCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteAvecRessourceAnneeCycle2), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteSansRessourceAnneeCycle2), 1, 0, 'C', true);
                    
                    if($nbreLeconPratPrevueAnneeCycle2 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"
                        ), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconPratFaiteAvecRessourceAnneeCycle2 + $nbreLeconPratFaiteSansRessourceAnneeCycle2)
                            /($nbreLeconPratPrevueAnneeCycle2))*100,2)
                        ), 1, 0, 'C', true);
                    }
                    

                    ####### heures trimestre
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        ($nbreLeconTheoPrevueTrimestreCycle2 + $nbreLeconPratPrevueTrimestreCycle2)*$heureParSemaine
                    ), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        (
                            $nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 + 
                            $nbreLeconTheoFaiteTrimestreCycle2 +
                            $nbreLeconPratFaiteAvecRessourceTrimestreCycle2 + 
                            $nbreLeconPratFaiteTrimestreCycle2 
                        )*$heureParSemaine
                    ), 1, 0, 'C', true);

                    if($nbreLeconTheoPrevueTrimestreCycle2 + $nbreLeconPratPrevueTrimestreCycle2 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format(((
                                 $nbreLeconTheoFaiteAvecRessourceTrimestreCycle2 + 
                                 $nbreLeconTheoFaiteTrimestreCycle2 +
                                 $nbreLeconPratFaiteAvecRessourceTrimestreCycle2 + 
                                 $nbreLeconPratFaiteTrimestreCycle2 
                            )
                             /($nbreLeconTheoPrevueTrimestreCycle2 + $nbreLeconPratPrevueTrimestreCycle2))*100, 2)
                         ), 1, 0, 'C', true);
                    }
                    

                    ####### heures annuelles
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        ($nbreLeconTheoPrevueAnneeCycle2 + $nbreLeconPratPrevueAnneeCycle2)*$heureParSemaine), 1, 0, 'C', true);
                    
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        (
                            $nbreLeconTheoFaiteAvecRessourceAnneeCycle2 + 
                            $nbreLeconTheoFaiteSansRessourceAnneeCycle2 +
                            $nbreLeconPratFaiteAvecRessourceAnneeCycle2 + 
                            $nbreLeconPratFaiteSansRessourceAnneeCycle2
                        )*$heureParSemaine), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueAnneeCycle2 + $nbreLeconPratPrevueAnneeCycle2 == 0)
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 1, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format(((
                                $nbreLeconTheoFaiteAvecRessourceAnneeCycle2 + 
                                $nbreLeconTheoFaiteSansRessourceAnneeCycle2 +
                                $nbreLeconPratFaiteAvecRessourceAnneeCycle2 + 
                                $nbreLeconPratFaiteSansRessourceAnneeCycle2
                                )
                            /($nbreLeconTheoPrevueAnneeCycle2 + $nbreLeconPratPrevueAnneeCycle2))*100, 2)
                        ), 1, 1, 'C', true);
                    }
                    
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                }else
                {
                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                    $rowSchool->setAppreciation($this->generalService->getApoAppreciation($rowSchool->getGeneralAverage()));
                }

                
                // Total Etablissement
                // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                $pdf->Cell($couvertureProgramme-110, 4*2, 'Totaux Etablissement', 1, 0, 'L', true);

                $nbreLeconTheoPrevue2 = 0;

                    $nbreLeconTheoPrevueTrimestreEtab = 0;
                    $nbreLeconTheoPrevueAnneeEtab = 0;
                    $nbreLeconTheoFaiteAvecRessourceAnneeEtab = 0;
                    $nbreLeconTheoFaiteAvecRessourceTrimestreEtab = 0;
                    $nbreLeconTheoFaiteSansRessourceAnneeEtab = 0;
                    $nbreLeconTheoFaiteTrimestreEtab = 0;

                    $nbreLeconPratPrevueTrimestreEtab = 0;
                    $nbreLeconPratPrevueAnneeEtab = 0;
                    $nbreLeconPratFaiteAvecRessourceAnneeEtab = 0;
                    $nbreLeconPratFaiteAvecRessourceTrimestreEtab = 0;
                    $nbreLeconPratFaiteSansRessourceAnneeEtab = 0;
                    $nbreLeconPratFaiteTrimestreEtab = 0;

                    $heureParSemaine = 0;


                    $nbreLeconPratPrevue1 = 0;
                    $nbreLeconTheoFaite1 = 0;
                    $nbreLeconPratFaite1 = 0;

                    $pourcentageLeconTheo1 = 0;
                    $pourcentageLeconPrat1 = 0;

                    $nbreHeureDueTheo1 = 0;
                    $nbreHeureDuePrat1 = 0;
                    $nbreHeureFaiteTheo1 = 0;
                    $nbreHeureFaitePrat1 = 0;

                    $pourcentageHeureDue1 = 0;
                    $pourcentageHeureFaite1 = 0;

                    $pourcentageAssiduiteEnseignant1 = 0;

                    $nbreClasse1erCycle = 0;
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 1 || $nbreClasseParCycle['level'] == 2 || $nbreClasseParCycle['level'] == 3 || $nbreClasseParCycle['level'] == 4 )
                        {
                            $nbreClasse1erCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        $heureParSemaine = $lesson->getWeekHours();

                        if (
                            $lesson->getClassroom()->getLevel()->getLevel() == 1 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 2 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 3 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 4 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 5 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 6 || 
                            $lesson->getClassroom()->getLevel()->getLevel() == 7
                            ) 
                        { 
                            $nbreLeconTheoPrevueAnneeEtab += 
                            $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                            $lesson->getNbreLessonTheoriquePrevueSeq6();

                            $nbreLeconTheoFaiteAvecRessourceAnneeEtab += 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() + 
                                    $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() ;
                            
                            $nbreLeconTheoFaiteSansRessourceAnneeEtab += 
                            $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq2() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq3() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq4() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq5() + 
                            $lesson->getNbreLessonTheoriqueFaiteSeq6() ;

                            ###########
                            $nbreLeconPratPrevueAnneeEtab += 
                                $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                $lesson->getNbreLessonPratiquePrevueSeq6();

                            $nbreLeconPratFaiteAvecRessourceAnneeEtab += 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() + 
                                    $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() ;
                            
                            

                            $nbreLeconPratFaiteSansRessourceAnneeEtab += 
                            $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq2() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq3() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq4() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq5() + 
                            $lesson->getNbreLessonPratiqueFaiteSeq6() ;

                            switch($term)
                            {
                                case 1 :
                                    $nbreLeconTheoPrevueTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq2()
                                                    );

                                    $nbreLeconTheoFaiteAvecRessourceTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() + 
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2()
                                                    );

                                    $nbreLeconTheoFaiteTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq1() + 
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq2()
                                                    );

                                    ###########
                                    $nbreLeconPratPrevueTrimestreEtab += (
                                        $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq2()
                                    );

                                    $nbreLeconPratFaiteAvecRessourceTrimestreEtab += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() + 
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2()
                                                    );

                                    $nbreLeconPratFaiteTrimestreEtab += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq1() + 
                                                        $lesson->getNbreLessonPratiqueFaiteSeq2()
                                                    );
                                    break;

                                case 2 :
                                    $nbreLeconTheoPrevueTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq4() 
                                                    );

                                    $nbreLeconTheoFaiteAvecRessourceTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() 
                                                    );

                                    $nbreLeconTheoFaiteTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq4() 
                                                    );

                                    ################
                                    $nbreLeconPratPrevueTrimestreEtab += (
                                        $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq4() 
                                    );

                                    $nbreLeconPratFaiteAvecRessourceTrimestreEtab += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() 
                                                    );

                                    $nbreLeconPratFaiteTrimestreEtab += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                                        $lesson->getNbreLessonPratiqueFaiteSeq4() 
                                                    );
                                    break;

                                case 3 :
                                    $nbreLeconTheoPrevueTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                                        $lesson->getNbreLessonTheoriquePrevueSeq6()
                                                    );
                                        
                                    $nbreLeconTheoFaiteAvecRessourceTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                                        $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() 
                                                    );

                                    $nbreLeconTheoFaiteTrimestreEtab += (
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                                        $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                                                    );


                                    #########################
                                    $nbreLeconPratPrevueTrimestreEtab += (
                                        $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                        $lesson->getNbreLessonPratiquePrevueSeq6()
                                    );
                        
                                    $nbreLeconPratFaiteAvecRessourceTrimestreEtab += (
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                                        $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                                    );

                                    $nbreLeconPratFaiteTrimestreEtab += (
                                                        $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                                        $lesson->getNbreLessonPratiqueFaiteSeq6() 
                                                    );
                                    break;

                                    case 0 :
                                        $nbreLeconTheoPrevueTrimestreEtab += (
                                                            $lesson->getNbreLessonTheoriquePrevueSeq1() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq2() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq3() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq4() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq5() + 
                                                            $lesson->getNbreLessonTheoriquePrevueSeq6()
                                                        );
                                            
                                        $nbreLeconTheoFaiteAvecRessourceTrimestreEtab += (
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq1() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq2() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq3() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq4() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq5() +
                                                            $lesson->getNbreLessonTheoriqueFaiteAvecRessourceSeq6() 
                                                        );
    
                                        $nbreLeconTheoFaiteTrimestreEtab += (
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq1() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq2() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq3() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq4() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq5() +
                                                            $lesson->getNbreLessonTheoriqueFaiteSeq6() 
                                                        );
    
    
                                        #########################
                                        $nbreLeconPratPrevueTrimestreEtab += (
                                            $lesson->getNbreLessonPratiquePrevueSeq1() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq2() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq3() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq4() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq5() + 
                                            $lesson->getNbreLessonPratiquePrevueSeq6()
                                        );
                            
                                        $nbreLeconPratFaiteAvecRessourceTrimestreEtab += (
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq1() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq2() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq3() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq4() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq5() +
                                                            $lesson->getNbreLessonPratiqueFaiteAvecRessourceSeq6() 
                                                        );
    
                                        $nbreLeconPratFaiteTrimestreEtab += (
                                                            $lesson->getNbreLessonPratiqueFaiteSeq1() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq2() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq3() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq4() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq5() +
                                                            $lesson->getNbreLessonPratiqueFaiteSeq6() 
                                                        );
                                        break;
                            }
                            
                        }
                    }

                    // leçons théoriques trimestre
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevueTrimestreEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteAvecRessourceTrimestreEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteTrimestreEtab), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueTrimestreEtab == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconTheoFaiteAvecRessourceTrimestreEtab + 
                            $nbreLeconTheoFaiteTrimestreEtab)/($nbreLeconTheoPrevueTrimestreEtab))*100, 2)
                            ), 1, 0, 'C', true);
                    }
                    
                    

                    #leçons théoriques prévues l'année
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevueAnneeEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteAvecRessourceAnneeEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconTheoFaiteSansRessourceAnneeEtab), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueAnneeEtab == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconTheoFaiteAvecRessourceAnneeEtab + $nbreLeconTheoFaiteSansRessourceAnneeEtab)
                            /($nbreLeconTheoPrevueAnneeEtab))*100,2)
                        ), 1, 0, 'C', true);
                    }
                    

                    ##################
                    // leçons pratiques trimestre
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconPratPrevueTrimestreEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteAvecRessourceTrimestreEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteTrimestreEtab), 1, 0, 'C', true);
                    
                    if($nbreLeconPratPrevueTrimestreEtab == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"
                            ), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconPratFaiteAvecRessourceTrimestreEtab + 
                            $nbreLeconPratFaiteTrimestreEtab)/($nbreLeconPratPrevueTrimestreEtab))*100, 2)
                            ), 1, 0, 'C', true);
                    }
                    
                    #leçons pratiques prévues l'année
                    $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode($nbreLeconPratPrevueAnneeEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteAvecRessourceAnneeEtab), 1, 0, 'C', true);
                    $pdf->Cell(((($couvertureProgramme/2)/2)/3)/2, $cellHeigh0*2, utf8_decode($nbreLeconPratFaiteSansRessourceAnneeEtab), 1, 0, 'C', true);
                    
                    if($nbreLeconPratPrevueAnneeEtab == 0)
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode("00"
                        ), 1, 0, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme/2)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format((($nbreLeconPratFaiteAvecRessourceAnneeEtab + $nbreLeconPratFaiteSansRessourceAnneeEtab)
                            /($nbreLeconPratPrevueAnneeEtab))*100,2)
                        ), 1, 0, 'C', true);
                    }
                    

                    ####### heures trimestre
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        ($nbreLeconTheoPrevueTrimestreEtab + $nbreLeconPratPrevueTrimestreEtab)*$heureParSemaine
                    ), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        (
                            $nbreLeconTheoFaiteAvecRessourceTrimestreEtab + 
                            $nbreLeconTheoFaiteTrimestreEtab +
                            $nbreLeconPratFaiteAvecRessourceTrimestreEtab + 
                            $nbreLeconPratFaiteTrimestreEtab 
                        )*$heureParSemaine
                    ), 1, 0, 'C', true);

                    if($nbreLeconTheoPrevueTrimestreEtab + $nbreLeconPratPrevueTrimestreEtab == 0)
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 0, 'C', true);
     
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format(((
                                 $nbreLeconTheoFaiteAvecRessourceTrimestreEtab + 
                                 $nbreLeconTheoFaiteTrimestreEtab +
                                 $nbreLeconPratFaiteAvecRessourceTrimestreEtab + 
                                 $nbreLeconPratFaiteTrimestreEtab 
                            )
                             /($nbreLeconTheoPrevueTrimestreEtab + $nbreLeconPratPrevueTrimestreEtab))*100, 2)
                         ), 1, 0, 'C', true);
     
                    }
                    
                    ####### heures annuelles
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        ($nbreLeconTheoPrevueAnneeEtab + $nbreLeconPratPrevueAnneeEtab)*$heureParSemaine), 1, 0, 'C', true);
                    
                    $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                        (
                            $nbreLeconTheoFaiteAvecRessourceAnneeEtab + 
                            $nbreLeconTheoFaiteSansRessourceAnneeEtab +
                            $nbreLeconPratFaiteAvecRessourceAnneeEtab + 
                            $nbreLeconPratFaiteSansRessourceAnneeEtab
                        )*$heureParSemaine), 1, 0, 'C', true);
                    
                    if($nbreLeconTheoPrevueAnneeEtab + $nbreLeconPratPrevueAnneeEtab == 0)
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode("00"), 1, 1, 'C', true);
                    }
                    else
                    {
                        $pdf->Cell((($couvertureProgramme-60)/2)/3, $cellHeigh0*2, utf8_decode(
                            number_format(((
                                $nbreLeconTheoFaiteAvecRessourceAnneeEtab + 
                                $nbreLeconTheoFaiteSansRessourceAnneeEtab +
                                $nbreLeconPratFaiteAvecRessourceAnneeEtab + 
                                $nbreLeconPratFaiteSansRessourceAnneeEtab
                                )
                            /($nbreLeconTheoPrevueAnneeEtab + $nbreLeconPratPrevueAnneeEtab))*100, 2)
                        ), 1, 1, 'C', true);
                    }

                
                $pdf->SetFont('Times', 'B', 11);
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->Cell(0, $cellHeigh0*2, utf8_decode("LP = Leçons prévues; LF = Leçons faites; HP = Heures prévues; HF = Heures faites; ARD = avec ressource digitalisée; SRD = sans ressource digitalisée."), 0, 1, 'C');
                    $pdf->Ln(3);
                    $pdf->Cell(270/4, $cellHeigh0*2, utf8_decode("TAUX D'ASSIDUITE DES ELEVES :"), 0, 0, 'C');
                    $pdf->Cell(270/4, $cellHeigh0*2, utf8_decode($this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls())))." %"), 0, 0, 'L');
                    $pdf->Cell((270/4)+15, $cellHeigh0*2, utf8_decode("TAUX D'ASSIDUITE DES ENSEIGNANTS  :"), 0, 0, 'C');
                    $pdf->Cell(270/4, $cellHeigh0*2, utf8_decode(
                        number_format(((
                                $nbreLeconTheoFaiteAvecRessourceTrimestreEtab + 
                                $nbreLeconTheoFaiteTrimestreEtab +
                                $nbreLeconPratFaiteAvecRessourceTrimestreEtab + 
                                $nbreLeconPratFaiteTrimestreEtab
                                )
                            /($nbreLeconTheoPrevueTrimestreEtab + $nbreLeconPratPrevueTrimestreEtab))*100, 2)
                        ." %"), 0, 1, 'L');
                
                
                }
                else
                {
                    $pdf->Cell(0, $cellHeigh0*2, utf8_decode("LP = Lesson planned; LD = Lesson done; SH = Scheduled hours; HW= Hours worker; WDR = With digitized resource; WtDR = Without digitized resource."), 0, 1, 'C');
                    $pdf->Ln(3);
                    $pdf->Cell(270/4, $cellHeigh0*2, utf8_decode("STUDENT ATTENDANCE RATE :"), 1, 0, 'C');
                    $pdf->Cell(270/4, $cellHeigh0*2, utf8_decode($this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls())))." %"), 0, 0, 'L');
                    
                    $pdf->Cell(270/4, $cellHeigh0*2, utf8_decode("TEACHER ATTENDANCE RATE :"), 1, 0, 'C');
                    $pdf->Cell(270/4, $cellHeigh0*2, utf8_decode(
                        number_format(((
                                $nbreLeconTheoFaiteAvecRessourceAnneeEtab + 
                                $nbreLeconTheoFaiteSansRessourceAnneeEtab +
                                $nbreLeconPratFaiteAvecRessourceAnneeEtab + 
                                $nbreLeconPratFaiteSansRessourceAnneeEtab
                                )
                            /($nbreLeconTheoPrevueAnneeEtab + $nbreLeconPratPrevueAnneeEtab))*100, 2)
                        ." %"), 0, 1, 'L');
                }

                // $pdf->Ln(3);
                // $pdf->SetFont('Times', 'B', 12);
                // $pdf->Cell(36, 6, '', 0, 0, 'C');
                // $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                // $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');

                //////////////NOUVELLE PAGE
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);
                $pdf->SetFont('Times', 'B', 14);
                $pdf->Cell(50, 6, utf8_decode(""), 1, 0, 'C', true);
                $pdf->Cell(234/2, 6, utf8_decode("ANALYSE ET COMMENTAIRE CRITIQUES"), 1, 0, 'C', true);
                $pdf->Cell(220/2, 6, utf8_decode("DEFINITION DES NOUVELLES STRATEGIES"), 1, 1, 'C', true);
                $pdf->SetFont('Times', 'B', 12);

                ///ENSEIGNEMENTS THEORIQUES
                //1
                $pdf->Cell(50, 6, utf8_decode("Taux de "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //2
                $pdf->Cell(50, 6, utf8_decode("couverture des "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //3
                $pdf->Cell(50, 6, utf8_decode("enseignements "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //4
                $pdf->Cell(50, 6, utf8_decode("théoriques"), "LBR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LBR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LBR", 1, 'C');

                ///ENSEIGNEMENTS PRATIQUES
                //1
                $pdf->Cell(50, 6, utf8_decode("Taux de "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //2
                $pdf->Cell(50, 6, utf8_decode("couverture des "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //3
                $pdf->Cell(50, 6, utf8_decode("enseignements "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //4
                $pdf->Cell(50, 6, utf8_decode("pratiques"), "LBR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LBR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LBR", 1, 'C');


                ///HEURES D'ENSEIGNEMENTS
                //1
                $pdf->Cell(50, 6, utf8_decode("Taux de "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //2
                $pdf->Cell(50, 6, utf8_decode("couverture des "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //3
                $pdf->Cell(50, 6, utf8_decode("heures "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //4
                $pdf->Cell(50, 6, utf8_decode("d'enseignements"), "LBR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LBR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LBR", 1, 'C');


                ///TAUX DE REUSSITE
                //1
                $pdf->Cell(50, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //2
                $pdf->Cell(50, 6, utf8_decode("Taux de "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //3
                $pdf->Cell(50, 6, utf8_decode("réussite "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //4
                $pdf->Cell(50, 6, utf8_decode(""), "LBR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LBR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LBR", 1, 'C');

                ///TAUX DE REUSSITE
                //1
                $pdf->Cell(50, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //2
                $pdf->Cell(50, 6, utf8_decode("Taux de "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //3
                $pdf->Cell(50, 6, utf8_decode("d'assiduité "), "LR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LR", 1, 'C');

                //4
                $pdf->Cell(50, 6, utf8_decode(""), "LBR", 0, 'C');
                $pdf->Cell(234/2, 6, utf8_decode(""), "LBR", 0, 'C');
                $pdf->Cell(220/2, 6, utf8_decode(""), "LBR", 1, 'C');
                
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(277, 6*2, utf8_decode("NB : Le renseignement de cette page n'est pas facultatif. Les informations de type RAS sont proscrites"), 0, 1, 'C');
                
                $pdf->Ln(6);

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(250/2, 6, utf8_decode(""), 0, 0, 'R');
                $pdf->Cell(250/2, 10, utf8_decode("Fait à ".$school->getPlace()." le _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'R');

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(250/2, 6, utf8_decode("L'Animateur Pédagogique"), 0, 0, 'L');
                $pdf->Cell(250/2, 6, utf8_decode("Le Chef d'Etablissement                        "), 0, 1, 'R');

            }
        }else
        {
            $cellHeigh0 = 4;
            $couvertureProgramme = 80;
            $couvertureHeure =80;
            $tauxAssiduite = 25;

            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }

            $pdf = new Pagination();

            if(empty($classroomStatisticSlipPerSubjects))
            {
                // Oninsère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the material is taught in at least one class'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the grades for this subject are entered'), 0, 1, 'C');
                $pdf->Ln();

                return $pdf;
            }

            foreach($classroomStatisticSlipPerSubjects as $statistics)
            {
                
                $pageCounter = 0;
                $rowCounter = 0;

                // Oninsère une page
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pageCounter++;

                // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900); 
                // on rempli l'entête administrative
                $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
                
                $pdf->Ln(2);
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // Logo de l'établissement
                // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
                 
                $pdf->setXY($x, $y);

                // Entête de la fiche
                if(!empty($statistics[0]))
                    $discipline = $statistics[0][0]->getTitle();
                elseif(!empty($statistics[1]))
                    $discipline = $statistics[1][0]->getTitle();
                else
                    $discipline = "";

                // Entête de la fiche
                // $pdf = $this->generalService->staisticSlipHeader($pdf, 'FICHE DE COLLECTE DE DONNEES', $termName, $school,  'Discipline', $discipline);
                $pdf->SetFont('Times', 'B', 15);
                $pdf->Cell(0, 6, utf8_decode("STATISTICAL DATA COLLECTION SHEET RELATING TO EVALUATIONS"), 0, 1, 'C');
                $pdf->Cell(0, 6, utf8_decode("COVERAGE RATE OF PROGRAMS, TEACHING HOURS"), 0, 1, 'C');
                $pdf->Cell(0, 6, utf8_decode("AND ATTENDANCE AND SUCCESS RATES"), 0, 1, 'C');
                $pdf->Cell(117, 6, utf8_decode($termName), 0, 0, 'R');
                $pdf->Cell(70, 6, utf8_decode('Discipline : '.$discipline), 0, 1, 'L');
                $pdf->Ln();

                // Entête du tableau
                // $pdf = $this->statisticTableHeader($pdf, 'Classes');
                $pdf = $this->dataCollectionTableHeaderPagination($pdf, $school);

                // Contenu du tableau
                $rowSchool = new ClassroomStatisticSlipRow();
                $rowSchool->setSubject('Totals Etablissement');
                $counterSchool = 0;

                $nbreLeconTheoPrevue = 0;
                $nbreLeconPratPrevue = 0;
                $nbreLeconTheoFaite = 0;
                $nbreLeconPratFaite = 0;

                $pourcentageLeconTheo = 0;
                $pourcentageLeconPrat = 0;

                $nbreHeureDueTheo = 0;
                $nbreHeureDuePrat = 0;
                $nbreHeureFaiteTheo = 0;
                $nbreHeureFaitePrat = 0;

                $pourcentageHeureDue = 0;
                $pourcentageHeureFaite = 0;

                $pourcentageAssiduiteEnseignant = 0;

                $nbreClasse2ndCycle = 0;

                
                // Cycle 1
                if(!empty($statistics[0]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totals Cycle 1');
                    $counter = 0;

                    foreach($statistics[0] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->dataCollectionTableRowPagination($pdf, $row, $lessons, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                        // On recupère les tataux du cycle 1
                        if($row->getLastMark() < $rowCycle->getLastMark())
                            $rowCycle->setLastMark($row->getLastMark());
                        if($row->getFirstMark() > $rowCycle->getFirstMark())
                            $rowCycle->setFirstMark($row->getFirstMark());
                        
                        $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                                ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                                ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                                ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                                ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                                ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                                ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                                ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                                ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                        ;
                        $counter++;
                    }
                    $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                    $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                    $generalMark = $rowCycle->getGeneralAverage();

                    if($counter)
                    {
                        $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                        $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                        $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                    }

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // on met à jour les totaux de l'établissement
                    if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                            $rowSchool->setLastMark($rowCycle->getLastMark());
                        if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                            $rowSchool->setFirstMark($rowCycle->getFirstMark());
                        
                        $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                                ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                                ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                                ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                                ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                                ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                                ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                                ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                                ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                        ;

                    // Sous total Cycle 1
                    // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                    $pdf->Cell(58, 4*2, 'Totals 1st cycle', 1, 0, 'L', true);

                    $nbreLeconTheoPrevue1 = 0;
                    $nbreLeconPratPrevue1 = 0;
                    $nbreLeconTheoFaite1 = 0;
                    $nbreLeconPratFaite1 = 0;

                    $pourcentageLeconTheo1 = 0;
                    $pourcentageLeconPrat1 = 0;

                    $nbreHeureDueTheo1 = 0;
                    $nbreHeureDuePrat1 = 0;
                    $nbreHeureFaiteTheo1 = 0;
                    $nbreHeureFaitePrat1 = 0;

                    $pourcentageHeureDue1 = 0;
                    $pourcentageHeureFaite1 = 0;

                    $pourcentageAssiduiteEnseignant1 = 0;

                    $nbreClasse1erCycle = 0;
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 1 || $nbreClasseParCycle['level'] == 2 || $nbreClasseParCycle['level'] == 3 || $nbreClasseParCycle['level'] == 4 )
                        {
                            $nbreClasse1erCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        if ($lesson->getClassroom()->getLevel()->getLevel() == 1 || $lesson->getClassroom()->getLevel()->getLevel() == 2 || $lesson->getClassroom()->getLevel()->getLevel() == 3 || $lesson->getClassroom()->getLevel()->getLevel() == 4) 
                        { 
                            $nbreLeconTheoPrevue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                            $nbreLeconPratPrevue1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                            $nbreLeconTheoFaite1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                            $nbreLeconPratFaite1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                            //////////////////////////
                            $pourcentageLeconTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                            (number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                            ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";

                            //////////////////////////
                            $pourcentageLeconTheo1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            (number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                            ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";


                            ///////somme heures dues théoriques
                            $nbreHeureDueTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                            //////somme heures dues pratique
                            $nbreHeureDuePrat1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                            ///////somme heures faites théoriques
                            $nbreHeureFaiteTheo1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                            //////somme heures faites pratique
                            $nbreHeureFaitePrat1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                            //////pourcentage heures théorique
                            $pourcentageHeureDue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100
                            )/ $nbreClasse1erCycle),2):"00";


                            ////////////pourcentage des heures pratiques
                            $pourcentageHeureFaite1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse1erCycle),2):"00";


                            /////////ASSIDUITE DES ENSEIGNANTS
                            $pourcentageAssiduiteEnseignant1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                                (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours())*100)/ $nbreClasse1erCycle),2):"00";
                            
                        }
                    }

                    // dd($nbreClasse1erCycle);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevue1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconPrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat1), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureDue1), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureFaite1), 1, 0, 'C', true);

                    $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, utf8_decode($pourcentageAssiduiteEnseignant1), 1, 0, 'C', true);



                    $pdf = $this->dataCollectionTableRowPagination($pdf, $rowCycle, $lessons, true);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                    
                }

                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);
                $pdf = $this->dataCollectionTableHeaderPagination($pdf, $school);

                // Cycle 2
                if(!empty($statistics[1]))
                {
                    $rowCycle = new ClassroomStatisticSlipRow();
                    $rowCycle->setSubject('Totals 2nd Cycle');
                    $counter = 0;

                    foreach($statistics[1] as $row)
                    {
                        // une ligne du tableau
                        $pdf = $this->dataCollectionTableRowPagination($pdf, $row, $lessons, false);
                        $rowCounter++;

                        // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                        // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                        // on met à jour les totaux du cycle 2
                        if($row->getLastMark() < $rowCycle->getLastMark())
                            $rowCycle->setLastMark($row->getLastMark());
                        if($row->getFirstMark() > $rowCycle->getFirstMark())
                            $rowCycle->setFirstMark($row->getFirstMark());
                        
                        $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                                ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                                ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                                ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                                ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                                ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                                ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                                ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                                ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                        ;
                        $counter++;
                    }
                    $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                    $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                    $generalMark = $rowCycle->getGeneralAverage();

                    if($counter)
                    {
                        $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                        $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                        $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                    }

                    $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                    $counterSchool += $counter;

                    // on met à jour les totaux de l'établissement
                    if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                            $rowSchool->setLastMark($rowCycle->getLastMark());
                        if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                            $rowSchool->setFirstMark($rowCycle->getFirstMark());
                        
                        $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                                ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                                ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                                ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                                ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                                ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                                ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                                ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                                ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                        ;

                        if($counterSchool)
                        {
                            $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                            $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                            $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                        }

                            $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));

                    // Sous total Cycle 2
                    // $pdf = $this->statisticTableRow($pdf, $rowCycle, true);
                    $pdf->Cell(58, 4*2, 'Totals 2nd cycle', 1, 0, 'L', true);
                    
                    
                    $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                    foreach($nbreClasseParCycles as $nbreClasseParCycle)
                    {
                        if ($nbreClasseParCycle['level'] == 5 || $nbreClasseParCycle['level'] == 6 || $nbreClasseParCycle['level'] == 7 )
                        {
                            $nbreClasse2ndCycle++;
                        }
                    }


                    foreach ($lessons as $lesson) 
                    {
                        if ($lesson->getClassroom()->getLevel()->getLevel() == 5 || $lesson->getClassroom()->getLevel()->getLevel() == 6 || $lesson->getClassroom()->getLevel()->getLevel() == 7 ) 
                        { 
                            $nbreLeconTheoPrevue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                            $nbreLeconPratPrevue += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                            $nbreLeconTheoFaite += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                            $nbreLeconPratFaite += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                            //////////////////////////POURCENTAGE LECON THEO
                            $pourcentageLeconTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                            number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                            ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100/ $nbreClasse2ndCycle),2) :"00";

                            //////////////////////////
                            $pourcentageLeconPrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                            ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse2ndCycle),2):"00";


                            ///////somme heures dues théoriques
                            $nbreHeureDueTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                            //////somme heures dues pratique
                            $nbreHeureDuePrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                            ///////somme heures faites théoriques
                            $nbreHeureFaiteTheo += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                            //////somme heures faites pratique
                            $nbreHeureFaitePrat += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                            //////pourcentage heures théorique
                            $pourcentageHeureDue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ? 
                                number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle,2):"00";


                            ////////////pourcentage des heures pratiques
                            $pourcentageHeureFaite += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format(((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100
                            )/ $nbreClasse2ndCycle,2):"00";


                            /////////ASSIDUITE DES PARENTS
                            $pourcentageAssiduiteEnseignant += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                                number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                                (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle ,2):"00";
                            
                        }
                    }
                    
                    // dd($nbreClasse1erCycle);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoPrevue), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($pourcentageLeconPrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat), 1, 0, 'C', true);

                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureDue), 1, 0, 'C', true);
                    $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($pourcentageHeureFaite), 1, 0, 'C', true);

                    $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, utf8_decode($pourcentageAssiduiteEnseignant), 1, 0, 'C', true);

                    $pdf = $this->dataCollectionTableRowPagination($pdf, $rowCycle, $lessons, true);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                }else
                {
                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                    $rowSchool->setAppreciation($this->generalService->getApoAppreciation($rowSchool->getGeneralAverage()));
                }

                
                // Total Etablissement
                // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                $pdf->Cell(58, 4*2, 'Totals Etablissement', 1, 0, 'L', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode(($nbreLeconTheoPrevue1 ? $nbreLeconTheoPrevue1 : 0) + ($nbreLeconTheoPrevue ? $nbreLeconTheoPrevue : 0)), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratPrevue1 + $nbreLeconPratPrevue), 1, 0, 'C', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconTheoFaite1 + $nbreLeconTheoFaite), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, utf8_decode($nbreLeconPratFaite1 + $nbreLeconPratFaite), 1, 0, 'C', true);

                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, number_format(($pourcentageLeconTheo1 + $pourcentageLeconTheo)/2,2), 1, 0, 'C', true);
                $pdf->Cell((($couvertureProgramme/3)/2), $cellHeigh0*2, number_format(($pourcentageLeconPrat1 + $pourcentageLeconPrat)/2,2), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDueTheo1 + $nbreHeureDueTheo), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureDuePrat1 + $nbreHeureDuePrat), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaiteTheo1 + $nbreHeureFaiteTheo), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, utf8_decode($nbreHeureFaitePrat1 + $nbreHeureFaitePrat), 1, 0, 'C', true);

                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, number_format(($pourcentageHeureDue1 + $pourcentageHeureDue)/2,2), 1, 0, 'C', true);
                $pdf->Cell((($couvertureHeure/3)/2), $cellHeigh0*2, number_format(($pourcentageHeureFaite1 + $pourcentageHeureFaite)/2,2), 1, 0, 'C', true);

                $pdf->Cell((($tauxAssiduite)/2), $cellHeigh0*2, number_format(($pourcentageAssiduiteEnseignant1 + $pourcentageAssiduiteEnseignant)/2, 2), 1, 0, 'C', true);


                $pdf = $this->dataCollectionTableRowPagination($pdf, $rowSchool, $lessons, true);

                // $pdf->Ln(3);
                // $pdf->SetFont('Times', 'B', 12);
                // $pdf->Cell(36, 6, '', 0, 0, 'C');
                // $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                // $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');

                //////////////NOUVELLE PAGE
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pdf->Image('images/school/'.$school->getFiligree(), 90, 50, -90);  

                $pdf->SetFont('Times', 'B', 15);
                $pdf->Cell(0, 6, utf8_decode("GENERAL OBSERVATIONS"), 1, 1, 'C', true);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(277/4, 6, utf8_decode("COVERAGE RATE OF "), "LR", 0, 'C');
                $pdf->Cell(277/4, 6, utf8_decode("COVERAGE RATE OF "), "LR", 0, 'C');
                $pdf->Cell(277/4, 6*2, utf8_decode("ATTENDANCE RATE"), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*2, utf8_decode("SUCCESS RATE"), "LRB", 1, 'C');

                $x = $pdf->GetX();
                $y = $pdf->GetY();

                $pdf->SetXY($x, $y-6);
                $pdf->Cell(277/4, 6, utf8_decode("PROGRAMS"), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6, utf8_decode("TEACHING HOURS"), "LRB", 1, 'C');
                // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 0, 'C');
                // $pdf->Cell(277/4, 6, utf8_decode(""), "LRB", 1, 'C');

                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 0, 'C');
                $pdf->Cell(277/4, 6*10, utf8_decode(""), "LRB", 1, 'C');

                $pdf->SetFont('Times', 'BU', 12);
                $pdf->Cell(277, 6*2, utf8_decode("SUGGESTIONS"), "LR", 1, 'L');
                $pdf->Cell(277, 6*10, utf8_decode(""), "LRB", 1, 'L');
                $pdf->Ln(6);

                $pdf->SetFont('Arial', 'B', 12);
                $pdf->Cell(250, 6, utf8_decode("THE HEAD TEACHER"), 0, 1, 'R');

            }
        }
        return $pdf;
    }

    /**
     * Contruit les élément de la fiche statistique par discipline
     *
     * @param [type] $studentMarkTermsSubjects
     * @return array
     */
    public function getStatisticSlipPerSubject($studentMarkTermsSubjects): array
    {
        $classroomStatisticSlipPerSubjects = [];

        if(!empty($studentMarkTermsSubjects))
        {
            foreach($studentMarkTermsSubjects as $MarkTermsSubjects)
            {
                $classroomStatisticSlipPerSubject = [];
                $classroomStatisticSlipPerSubjectCycle = [];
                $classroomStatisticSlipPerSubjectCycle2 = [];

                foreach($MarkTermsSubjects as $subjectEvaluations)
                {
                    $firstSubjectEvaluations = $subjectEvaluations[0];

                    $classroomStatisticSlipRow =  new ClassroomStatisticSlipRow();
                    $classroom = $firstSubjectEvaluations->getStudent()->getClassroom();
                    $cycle = $classroom->getLevel()->getCycle()->getCycle();

                    $numberOfBoys = count($this->studentRepository->findBy([
                        'classroom' => $classroom,
                        'sex' => $this->sexRepository->findOneBy(['sex' => 'M'])
                    ]));

                    $numberOfGirls = count($this->studentRepository->findBy([
                        'classroom' => $classroom,
                        'sex' => $this->sexRepository->findOneBy(['sex' => 'F'])
                    ]));

                    $classroomStatisticSlipRow->setSubject($classroom->getClassroom())
                            ->setRegisteredBoys($numberOfBoys)
                            ->setRegisteredGirls( $numberOfGirls)
                            ->setTitle($firstSubjectEvaluations->getLesson()->getSubject()->getSubject())
                    ;

                    $totalMark = 0;
                    $totalMarkBoys = 0;
                    $totalMarkGirls = 0;
                    $lastMark = 20;
                    $firstMark = 0;
                    $composedBoys = 0;
                    $composedGirls = 0;
                    $passedBoys = 0;
                    $passedGirls = 0;

                    foreach($subjectEvaluations as $evaluation)
                    {
                        $mark = $evaluation->getMark();
                        $sex =$evaluation->getStudent()->getSex()->getSex();

                        if($mark != ConstantsClass::UNRANKED_MARK)
                        {
                            $totalMark += $mark;
                                
                            if($sex == 'M')
                            {
                                $composedBoys++;
                                $totalMarkBoys += $mark;
                            }else
                            {
                                $composedGirls++;
                                $totalMarkGirls += $mark;
                            }
                                
                            if($mark >= 10)
                            {
                                if($sex == 'M')
                                {
                                    $passedBoys++;
                                }else
                                {
                                    $passedGirls++;
                                }
                            }
                                
                                if($mark < $lastMark)
                                    $lastMark = $mark;
                                
                                if($mark > $firstMark)
                                    $firstMark = $mark;
                        }
                    }

                    $generalAverage = $this->generalService->getRatio($totalMark, $composedBoys + $composedGirls);

                    $generalAverageBoys = $this->generalService->getRatio($totalMarkBoys, $composedBoys);
                    
                    $generalAverageGirls = $this->generalService->getRatio($totalMarkGirls, $composedGirls);
                        
                    $classroomStatisticSlipRow->setComposedBoys($composedBoys)
                    ->setComposedGirls($composedGirls)
                    ->setPassedBoys($passedBoys)
                    ->setPassedGirls($passedGirls)
                    ->setGeneralAverageBoys($generalAverageBoys)
                    ->setGeneralAverageGirls($generalAverageGirls)
                    ->setGeneralAverage($generalAverage)
                    ->setFirstMark($firstMark)
                    ->setLastMark($lastMark)
                    ->setAppreciation($this->generalService->getApoAppreciation($generalAverage))
                    ;
                    
                    if($cycle == 1)
                        $classroomStatisticSlipPerSubjectCycle[] = $classroomStatisticSlipRow;
                    else
                        $classroomStatisticSlipPerSubjectCycle2[] = $classroomStatisticSlipRow;
                }
                
                $classroomStatisticSlipPerSubject[] = $classroomStatisticSlipPerSubjectCycle;
                $classroomStatisticSlipPerSubject[] = $classroomStatisticSlipPerSubjectCycle2;
                $classroomStatisticSlipPerSubjects[] = $classroomStatisticSlipPerSubject;

                unset($classroomStatisticSlipPerSubject);
                unset($classroomStatisticSlipPerSubjectCycle);
                unset($classroomStatisticSlipPerSubjectCycle2);
            }
        }

        return  $classroomStatisticSlipPerSubjects;
    }


    /**
     * Imprime la fiche statistique par discipline
     *
     * @param array $classroomStatisticSlipPerSubjects
     * @param string $firstPeriodLetter
     * @param string $idP
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    
    public function printStatisticSlipPerSubject(array $classroomStatisticSlipPerSubjects, string $firstPeriodLetter, string $idP, School $school, SchoolYear $schoolYear, int $resume): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }
        }else
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }
        }

        $pdf = new Pagination();
        $resumePdf = new Pagination();

        // si c'est la synthèse qui est demandée, on remplit l'entête d la page de synthèse
        if($resume == 1)
        {
            // On insère une page
            $resumePdf = $resumePdf = $this->generalService->newPagePagination($resumePdf, 'L', 10, 7);

            // on remplit l'entête administrative
            $resumePdf = $this->generalService->statisticAdministrativeHeaderPagination($resumePdf, $school, $schoolYear);

            $resumePdf->Ln(1);
            $x = $resumePdf->GetX();
            $y = $resumePdf->GetY();
 
            //  // Logo de l'établissement
            //  $resumePdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            //  $resumePdf->Image('build/custom/images/logoFiligranen.jpg', 80, 49, -200);  
            $resumePdf->setXY($x, $y);

             // Entête de la fiche
             if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $resumePdf = $this->generalService->staisticSlipHeaderPagination($resumePdf, 'FICHE DE SYNTHESE GENERALE DES PERFORMANCES PAR DISCIPLINE', $termName, $school,  '', '');
            }else
            {
                $resumePdf = $this->generalService->staisticSlipHeaderPagination($resumePdf, 'GENERAL SUMMARY SHEET OF PERFORMANCES BY DISCIPLINE', $termName, $school,  '', '');
            }
            
            // en-tête du tableau
            $resumePdf = $this->statisticTableHeaderPagination($resumePdf, 'Disciplines');

            // return $resumePdf;
        }

        if(empty($classroomStatisticSlipPerSubjects))
        {
            // Oninsère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');

            }else
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the subject is taught in at least one class'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the notes for this subject are entered'), 0, 1, 'C');
            }
            
            $pdf->Ln();
            return $pdf;
        }

        // On declare le tableau qui va contenir la synthèse par matière
        $resumeSlipPerSubject = [];
        
        foreach($classroomStatisticSlipPerSubjects as $statistics)
        {
            $pageCounter = 0;
            $rowCounter = 0;

            // Oninsère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on remplit l'entête administrative
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // // Logo de l'établissement
            // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -200);  
            $pdf->setXY($x, $y);

            // Entête de la fiche
            if(!empty($statistics[0]))
                $discipline = $statistics[0][0]->getTitle();
            elseif(!empty($statistics[1]))
                $discipline = $statistics[1][0]->getTitle();
            else
                $discipline = "";

            // Entête de la fiche
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'FICHE STATISTIQUE PAR DISCIPLINE', $termName, $school,  'Discipline', $discipline);
            }else
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET BY DISCIPLINE', $termName, $school,  'Discipline', $discipline);
            }

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $rowSchool->setSubject('Totaux Etablissement');
            }else
            {
                $rowSchool->setSubject('Totals Establihsment');
            }
            $counterSchool = 0;

            // Cycle 1
            if(!empty($statistics[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();

                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $rowCycle->setSubject('Totaux Cycle 1');
                }else
                {
                    $rowCycle->setSubject('Totals Cycle 1');
                }
                $counter = 0;

                foreach($statistics[0] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                    if($pageCounter == 1)
                    {
                        if($rowCounter == 20)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insère une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }else
                    {
                        if($rowCounter == 28)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insere une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                             // on réinialise le compteur de ligne
                             $rowCounter = 0;
                        }
                    }

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec en-tête de tableau si c'est nécessaire
                if($pageCounter == 1)
                {
                    if($rowCounter == 20)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insère une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }else
                {
                    if($rowCounter == 28)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insere une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }
                
            }
            // Cycle 2
            if(!empty($statistics[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $rowCycle->setSubject('Totaux Cycle 2');
                }else
                {
                    $rowCycle->setSubject('Totals Cycle 2');
                }
                $counter = 0;

                foreach($statistics[1] as $row)
                {
                    // une ligne du tableau
                     $pdf = $this->statisticTableRowPagination($pdf, $row);
                     $rowCounter++;

                    // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                    if($pageCounter == 1)
                    {
                        if($rowCounter == 20)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insère une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }else
                    {
                        if($rowCounter == 28)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insere une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                        $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));



                // Sous total Cycle 2
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                if($pageCounter == 1)
                {
                    if($rowCounter == 20)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insère une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }else
                {
                    if($rowCounter == 28)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insere une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }

            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

                $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));
            }

            // Total Etablissement
            $pdf = $this->statisticTableRowPagination($pdf, $rowSchool, true);

            // on set le subject concerné et on sauvegarde la ligne de la synthèse
            $rowSchool->setSubject($discipline);
            $resumeSlipPerSubject[] = $rowSchool;

            $pdf->Ln(3);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(36, 6, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }

        }

        if($resume == 0)
        {
            return $pdf;

        }else
        {
            foreach ($resumeSlipPerSubject as $row) 
            {
                $resumePdf = $this->statisticTableRowPagination($resumePdf, $row);
            }

            $resumePdf->Ln(2);
            $resumePdf->SetFont('Times', 'B', 10);
            $resumePdf->Cell(36, 5, '', 0, 0, 'C');
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }

            return $resumePdf;
        }

    }


    public function ficheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere(array $classroomStatisticSlipPerSubjects, string $firstPeriodLetter, string $idP, School $school, SchoolYear $schoolYear, array $lessonData, int $term, int $resume): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }
        }else
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }
        }

        $pdf = new Pagination();
        $resumePdf = new Pagination();

        // si c'est la synthèse qui est demandée, on remplit l'entête d la page de synthèse
        if($resume == 1)
        {
            // On insère une page
            $resumePdf = $resumePdf = $this->generalService->newPagePagination($resumePdf, 'L', 10, 7);

            // on remplit l'entête administrative
            $resumePdf = $this->generalService->statisticAdministrativeHeaderPagination($resumePdf, $school, $schoolYear);

            $resumePdf->Ln(1);
            $x = $resumePdf->GetX();
            $y = $resumePdf->GetY();
 
            //  // Logo de l'établissement
            //  $resumePdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            //  $resumePdf->Image('build/custom/images/logoFiligranen.jpg', 80, 49, -200);  
            $resumePdf->setXY($x, $y);

             // Entête de la fiche
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $resumePdf = $this->generalService->titreFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($resumePdf, "FICHE SYNTHESE DE LA COUVERTURE DES HEURES ET PROGRAMMES D'ENSEIGNEMENT PAR MATIERE", $termName, $school,  '', '');
            }else
            {
                $resumePdf = $this->generalService->titreFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($resumePdf, 'TEACHING HOURS AND PROGRAMME COVERAGE SYNTHESIS FORM PER SUBJECT', $termName, $school,  '', '');
            }
            
            // en-tête du tableau
            $resumePdf = $this->enteteFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($resumePdf, 'Disciplines');

            // return $resumePdf;
        }

        if(empty($classroomStatisticSlipPerSubjects))
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');

            }else
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the subject is taught in at least one class'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the notes for this subject are entered'), 0, 1, 'C');
            }
            
            $pdf->Ln();
            return $pdf;
        }

        // On declare le tableau qui va contenir la synthèse par matière
        $resumeSlipPerSubject = [];
        
        foreach($classroomStatisticSlipPerSubjects as $statistics)
        {
            $pageCounter = 0;
            $rowCounter = 0;

            // On insère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on remplit l'entête administrative
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // // Logo de l'établissement
            // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -200);  
            $pdf->setXY($x, $y);

            // Entête de la fiche
            if(!empty($statistics[0]))
                $discipline = $statistics[0][0]->getTitle();
            elseif(!empty($statistics[1]))
                $discipline = $statistics[1][0]->getTitle();
            else
                $discipline = "";

            // Entête de la fiche
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'FICHE STATISTIQUE PAR DISCIPLINE', $termName, $school,  'Discipline', $discipline);
            }else
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET BY DISCIPLINE', $termName, $school,  'Discipline', $discipline);
            }

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $rowSchool->setSubject('Totaux Etablissement');
            }else
            {
                $rowSchool->setSubject('Totals Establihsment');
            }
            $counterSchool = 0;
            
            // Cycle 1
            if(!empty($statistics[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();

                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $rowCycle->setSubject('Totaux Cycle 1');
                }else
                {
                    $rowCycle->setSubject('Totals Cycle 1');
                }
                $counter = 0;

                foreach($statistics[0] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->ligneTableauFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($pdf, $row, $lessonData, $term);
                    $rowCounter++;

                    // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                    if($pageCounter == 1)
                    {
                        if($rowCounter == 20)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insère une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }else
                    {
                        if($rowCounter == 28)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insere une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                             // on réinialise le compteur de ligne
                             $rowCounter = 0;
                        }
                    }

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                $pdf = $this->ligneTableauFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($pdf, $rowCycle, $lessonData, $term, true);
                $rowCounter++;

                // on insère une nouvelle page avec en-tête de tableau si c'est nécessaire
                if($pageCounter == 1)
                {
                    if($rowCounter == 20)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insère une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }else
                {
                    if($rowCounter == 28)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insere une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }
                
            }
            // Cycle 2
            if(!empty($statistics[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $rowCycle->setSubject('Totaux Cycle 2');
                }else
                {
                    $rowCycle->setSubject('Totals Cycle 2');
                }
                $counter = 0;

                foreach($statistics[1] as $row)
                {
                    // une ligne du tableau
                     $pdf = $this->ligneTableauFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($pdf, $row, $lessonData, $term);
                     $rowCounter++;

                    // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                    if($pageCounter == 1)
                    {
                        if($rowCounter == 20)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insère une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }else
                    {
                        if($rowCounter == 28)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insere une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                        $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));



                // Sous total Cycle 2
                $pdf = $this->ligneTableauFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($pdf, $rowCycle, $lessonData, $term, true);
                $rowCounter++;

                // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                if($pageCounter == 1)
                {
                    if($rowCounter == 20)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insère une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }else
                {
                    if($rowCounter == 28)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insere une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }

            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

                $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));
            }

            // Total Etablissement
            $pdf = $this->ligneTableauFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($pdf, $rowSchool, $lessonData, $term, true);

            // on set le subject concerné et on sauvegarde la ligne de la synthèse
            $rowSchool->setSubject($discipline);
            $resumeSlipPerSubject[] = $rowSchool;

            $pdf->Ln(3);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(36, 6, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }

        }

        if($resume == 0)
        {
            return $pdf;

        }else
        {
            foreach ($resumeSlipPerSubject as $row) 
            {
                $resumePdf = $this->ligneTableauFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere($resumePdf, $row, $lessonData, $term);
            }

            $resumePdf->Ln(2);
            $resumePdf->SetFont('Times', 'B', 10);
            $resumePdf->Cell(36, 5, '', 0, 0, 'C');
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $resumePdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $resumePdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $resumePdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $resumePdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $resumePdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $resumePdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $resumePdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $resumePdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }

            return $resumePdf;
        }

    }


    /**
     * fiche De Synthese De Performance Des Eleves Par Matiere
     *
     * @param array $classroomStatisticSlipPerSubjects
     * @param string $firstPeriodLetter
     * @param string $idP
     * @param School $school
     * @param SchoolYear $schoolYear
     * @param integer $resume
     * @return Pagination
     */
    public function ficheDeSyntheseDePerformanceDesElevesParMatiere(array $classroomStatisticSlipPerSubjects, string $firstPeriodLetter, string $idP, School $school, SchoolYear $schoolYear, int $resume): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }
        }else
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }
        }

        $pdf = new Pagination();
        $resumePdf = new Pagination();

        // si c'est la synthèse qui est demandée, on remplit l'entête d la page de synthèse
        if($resume == 1)
        {
            // On insère une page
            $resumePdf = $resumePdf = $this->generalService->newPagePagination($resumePdf, 'L', 10, 7);

            // on remplit l'entête administrative
            $resumePdf = $this->generalService->statisticAdministrativeHeaderPagination($resumePdf, $school, $schoolYear);

            $resumePdf->Ln(1);
            $x = $resumePdf->GetX();
            $y = $resumePdf->GetY();
 
            //  // Logo de l'établissement
            //  $resumePdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            //  $resumePdf->Image('build/custom/images/logoFiligranen.jpg', 80, 49, -200);  
            $resumePdf->setXY($x, $y);

             // Entête de la fiche
             if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $resumePdf = $this->generalService->staisticSlipHeaderPagination($resumePdf, 'FICHE SYNTHESE DE PERFORMANCE DES ELEVES PAR MATIERE ', $termName, $school,  '', '');
            }else
            {
                $resumePdf = $this->generalService->staisticSlipHeaderPagination($resumePdf, 'STUDENT PERFORMANCE SUMMARY SHEET BY SUBJECT', $termName, $school,  '', '');
            }
            
            // en-tête du tableau
            $resumePdf = $this->enteteFicheSyntheseDePerformanceParClasse($resumePdf, 'Disciplines');

            // return $resumePdf;
        }

        if(empty($classroomStatisticSlipPerSubjects))
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');

            }else
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the subject is taught in at least one class'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the notes for this subject are entered'), 0, 1, 'C');
            }
            
            $pdf->Ln();
            return $pdf;
        }

        // On declare le tableau qui va contenir la synthèse par matière
        $resumeSlipPerSubject = [];
        
        foreach($classroomStatisticSlipPerSubjects as $statistics)
        {
            $pageCounter = 0;
            $rowCounter = 0;

            // Oninsère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on remplit l'entête administrative
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // // Logo de l'établissement
            // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -200);  
            $pdf->setXY($x, $y);

            // Entête de la fiche
            if(!empty($statistics[0]))
                $discipline = $statistics[0][0]->getTitle();
            elseif(!empty($statistics[1]))
                $discipline = $statistics[1][0]->getTitle();
            else
                $discipline = "";

            // Entête de la fiche
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'FICHE STATISTIQUE PAR DISCIPLINE', $termName, $school,  'Discipline', $discipline);
            }else
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET BY DISCIPLINE', $termName, $school,  'Discipline', $discipline);
            }

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $rowSchool->setSubject('Totaux Etablissement');
            }else
            {
                $rowSchool->setSubject('Totals Establihsment');
            }
            $counterSchool = 0;

            // Cycle 1
            if(!empty($statistics[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();

                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $rowCycle->setSubject('Totaux Cycle 1');
                }else
                {
                    $rowCycle->setSubject('Totals Cycle 1');
                }
                $counter = 0;

                foreach($statistics[0] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->ligneFicheSyntheseDePerformanceDesElevesParMatiere($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                    if($pageCounter == 1)
                    {
                        if($rowCounter == 20)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insère une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }else
                    {
                        if($rowCounter == 28)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insere une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                             // on réinialise le compteur de ligne
                             $rowCounter = 0;
                        }
                    }

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec en-tête de tableau si c'est nécessaire
                if($pageCounter == 1)
                {
                    if($rowCounter == 20)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insère une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }else
                {
                    if($rowCounter == 28)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insere une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }
                
            }
            // Cycle 2
            if(!empty($statistics[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $rowCycle->setSubject('Totaux Cycle 2');
                }else
                {
                    $rowCycle->setSubject('Totals Cycle 2');
                }
                $counter = 0;

                foreach($statistics[1] as $row)
                {
                    // une ligne du tableau
                     $pdf = $this->statisticTableRowPagination($pdf, $row);
                     $rowCounter++;

                    // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                    if($pageCounter == 1)
                    {
                        if($rowCounter == 20)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insère une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }else
                    {
                        if($rowCounter == 28)
                        {
                            // on insère une nouvelle page
                            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                            $pageCounter++;

                            // on insere une entête du tableau
                            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                            // on réinialise le compteur de ligne
                            $rowCounter = 0;
                        }
                    }

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                        $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));



                // Sous total Cycle 2
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec enête de tableau si c'est nécessaire
                if($pageCounter == 1)
                {
                    if($rowCounter == 20)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insère une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }else
                {
                    if($rowCounter == 28)
                    {
                        // on insère une nouvelle page
                        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                        $pageCounter++;

                        // on insere une entête du tableau
                        $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                        // on réinialise le compteur de ligne
                        $rowCounter = 0;
                    }
                }

            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

                $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));
            }

            // Total Etablissement
            $pdf = $this->statisticTableRowPagination($pdf, $rowSchool, true);

            // on set le subject concerné et on sauvegarde la ligne de la synthèse
            $rowSchool->setSubject($discipline);
            $resumeSlipPerSubject[] = $rowSchool;

            $pdf->Ln(3);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(36, 6, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }

        }

        if($resume == 0)
        {
            return $pdf;

        }else
        {
            foreach ($resumeSlipPerSubject as $row) 
            {
                $resumePdf = $this->ligneFicheSyntheseDePerformanceDesElevesParMatiere($resumePdf, $row);
            }

            $resumePdf->Ln(2);
            $resumePdf->SetFont('Times', 'BU', 10);
            $resumePdf->Cell(36, 5, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $resumePdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $resumePdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $resumePdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $resumePdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $resumePdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $resumePdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $resumePdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $resumePdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $resumePdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }

            return $resumePdf;
        }

    }


    public function addNewPageSlipPerSubjectPagination(Pagination $pdf, int &$pageCounter, int &$rowCounter): Pagination
    {
        if($pageCounter == 1)
        {
            if($rowCounter == 30)
            {
                // on insère une nouvelle page
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pageCounter++;

                // on insère une entête du tableau
                $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                // on réinialise le compteur de ligne
                $rowCounter = 0;
            }
        }else
        {
            if($rowCounter == 40)
            {
                // on insère une nouvelle page
                $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
                $pageCounter++;

                // on insere une entête du tableau
                $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

                    // on réinialise le compteur de ligne
                    $rowCounter = 0;
            }
        }

        return $pdf;
    }


    /**
     * Construit la fiche du taux de réussite par classe
     *
     * @param array $rankedStudentsPerClass
     * @return array
     */
    public function getRateOfSuccessPerClass(array $rankedStudentsPerClass): array
    {
        if(empty($rankedStudentsPerClass))
        {
            return [];

        }

        $rateOfSuccessPerClass = [];
        $rateOfSuccessPerClassCycle1 = [];
        $rateOfSuccessPerClassCycle2 = [];
            
        foreach($rankedStudentsPerClass as $students)
        {
            $row = new ClassroomStatisticSlipRow();
           
            $totalMark = 0;
            $totalMarkBoys = 0;
            $totalMarkGirls = 0;

            if(!empty($students))
            {
                $cycle = $students[0]['student']->getClassroom()->getLevel()->getCycle()->getCycle();
                $row->setSubject( $students[0]['student']->getClassroom()->getClassroom());

                    foreach($students as $student)
                    {
                        if($student['sex'] == 'M')
                        {
                            $row->setRegisteredBoys($row->getRegisteredBoys()+1);
                            $mark = $student['moyenne'];
                            
        
                            if($mark != ConstantsClass::UNRANKED_AVERAGE)
                            {
                                $totalMark += $mark;
                                $totalMarkBoys += $mark;
                                $row->setComposedBoys($row->getComposedBoys()+1);
                            
                                if($mark >= 10)
                                    $row->setPassedBoys($row->getPassedBoys()+1);
        
                                if($mark < $row->getLastMark())
                                    $row->setLastMark($mark);
                                
                                if($mark > $row->getFirstMark())
                                    $row->setFirstMark($mark);
                            }
                        }else
                        {
                            $row->setRegisteredGirls($row->getRegisteredGirls()+1);
                            $mark = $student['moyenne'];
        
                            if($mark != ConstantsClass::UNRANKED_AVERAGE)
                            {
                                $totalMark += $mark;
                                $totalMarkGirls += $mark;
                                $row->setComposedGirls($row->getComposedGirls()+1);
                            
                                if($mark >= 10)
                                    $row->setPassedGirls($row->getPassedGirls()+1);
        
                                if($mark < $row->getLastMark())
                                    $row->setLastMark($mark);
                                
                                if($mark > $row->getFirstMark())
                                    $row->setFirstMark($mark);
                            }
                        }
                        
                        $row->setGeneralAverage($this->generalService->getRatio($totalMark, ($row->getComposedBoys()+$row->getComposedGirls())));
                        
                        $row->setGeneralAverageBoys($this->generalService->getRatio($totalMarkBoys, $row->getComposedBoys()));

                        $row->setGeneralAverageGirls($this->generalService->getRatio($totalMarkGirls, $row->getComposedGirls()));
                        
                        $row->setAppreciation($this->generalService->getApoAppreciation($row->getGeneralAverage()));
        
                    }

                if($cycle == 1)
                {
                    $rateOfSuccessPerClassCycle1[] = $row;

                }else
                {
                    $rateOfSuccessPerClassCycle2[] = $row;

                }
            }
            
        }
        $rateOfSuccessPerClass[] = $rateOfSuccessPerClassCycle1;
        $rateOfSuccessPerClass[] = $rateOfSuccessPerClassCycle2;

        return $rateOfSuccessPerClass;
    }


    /**
     * Imprime le taux de réussite par classe
     *
     * @param array $rateOfSuccessPerClass
     * @param SchoolYear $schoolYear
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param School $school
     * @return Pagination
     */
    public function printRateOfSuccessPerClass(array $rateOfSuccessPerClass, SchoolYear $schoolYear, string $firstPeriodLetter, int $idP, School $school, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE )
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }

            $pdf = new Pagination();

            if(empty($rateOfSuccessPerClass))
            {
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->SetFont('Times', '', 20);
                    $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');

                }else
                {
                    $pdf->SetFont('Times', '', 20);
                    $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Make sure the subject is taught in at least one class'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Make sure the notes for this subject are entered'), 0, 1, 'C');
                }

                return $pdf;
            }

            $pageCounter = 0;
            $rowCounter = 0;

            // Oninsère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on rempli l'entête administraive
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // Logo de l'établissement
            // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900); 
            
            $pdf->setXY($x, $y);

            // Enteête de la fiche
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'FICHE STATISTIQUE RELATIVE AUX TAUX DE REUSSITE', $termName, $school);
            }else
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET RELATING TO SUCCESS RATES', $termName, $school);
            }
            $pdf->Ln();

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Classes');

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $rowSchool->setSubject('Totaux Etablissement');
            }else
            {
                $rowSchool->setSubject('Totals Establihsment');
            }
            $counterSchool = 0;

            // Cycle 1
            if(!empty($rateOfSuccessPerClass[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totaux Cycle 1');
                $counter = 0;

                foreach($rateOfSuccessPerClass[0] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                
            }
            // Cycle 2
            if(!empty($rateOfSuccessPerClass[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totaux Cycle 2');
                $counter = 0;
                foreach($rateOfSuccessPerClass[1] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys(($rowSchool->getGeneralAverageBoys()/$counterSchool));
                        $rowSchool->setGeneralAverageGirls(($rowSchool->getGeneralAverageGirls()/$counterSchool));
                        $rowSchool->setGeneralAverage(($rowSchool->getGeneralAverage()/$counterSchool));
                    }

                    $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));

                // Sous total Cycle 2
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;
                
                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

                $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));
            }

            // Total Etablissement
            $pdf = $this->statisticTableRowPagination($pdf, $rowSchool, true);

            $pdf->Ln(3);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(36, 6, '', 0, 0, 'C');
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }
        }else
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }

            $pdf = new Pagination();

            if(empty($rateOfSuccessPerClass))
            {
                    $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the class contains at least one student'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure all notes are entered'), 0, 1, 'C');

                return $pdf;
            }

            $pageCounter = 0;
            $rowCounter = 0;

            // Oninsère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on rempli l'entête administraive
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // Logo de l'établissement
            // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900); 
            
            $pdf->setXY($x, $y);

            // Enteête de la fiche
            $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET RELATING TO SUCCESS RATES', $termName, $school);
            $pdf->Ln();

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Class');

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            $rowSchool->setSubject('Establishment Totals');
            $counterSchool = 0;

            // Cycle 1
            if(!empty($rateOfSuccessPerClass[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totals Cycle 1');
                $counter = 0;

                foreach($rateOfSuccessPerClass[0] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                
            }
            // Cycle 2
            if(!empty($rateOfSuccessPerClass[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totals Cycle 2');
                $counter = 0;
                foreach($rateOfSuccessPerClass[1] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys(($rowSchool->getGeneralAverageBoys()/$counterSchool));
                        $rowSchool->setGeneralAverageGirls(($rowSchool->getGeneralAverageGirls()/$counterSchool));
                        $rowSchool->setGeneralAverage(($rowSchool->getGeneralAverage()/$counterSchool));
                    }

                    $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));

                // Sous total Cycle 2
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;
                
                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

                $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));
            }

            // Total Etablissement
            $pdf = $this->statisticTableRowPagination($pdf, $rowSchool, true);

            $pdf->Ln(3);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(36, 6, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }
            
        }

        return $pdf;
    }

    /**
     * Fiche Synthese De Performance Par Classe
     *
     * @param array $rateOfSuccessPerClass
     * @param SchoolYear $schoolYear
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param School $school
     * @param SubSystem $subSystem
     * @return Pagination
     */
    public function printFicheSyntheseDePerformanceParClasse(array $rateOfSuccessPerClass, SchoolYear $schoolYear, string $firstPeriodLetter, int $idP, School $school, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE )
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUEL';

            }

            $pdf = new Pagination();

            if(empty($rateOfSuccessPerClass))
            {
                $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                {
                    $pdf->SetFont('Times', '', 20);
                    $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');

                }else
                {
                    $pdf->SetFont('Times', '', 20);
                    $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Make sure the subject is taught in at least one class'), 0, 1, 'C');
                    $pdf->Ln();
                    $pdf->Cell(0, 10, utf8_decode('- Make sure the notes for this subject are entered'), 0, 1, 'C');
                }

                return $pdf;
            }

            $pageCounter = 0;
            $rowCounter = 0;

            // Oninsère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on rempli l'entête administraive
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // Logo de l'établissement
            // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900); 
            
            $pdf->setXY($x, $y);

            // Enteête de la fiche
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'FICHE SYNTHESE DE PERFORMANCE DES ELEVES PAR CLASSE', $termName, $school);
            }else
            {
                $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'SUMMARY OF STUDENT PERFORMANCE BY CLASSE', $termName, $school);
            }
            $pdf->Ln();

            // Entête du tableau
            $pdf = $this->enteteFicheSyntheseDePerformanceParClasse($pdf, 'Classes');

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $rowSchool->setSubject('Totaux Etablissement');
            }else
            {
                $rowSchool->setSubject('Totals Establihsment');
            }
            $counterSchool = 0;

            // Cycle 1
            if(!empty($rateOfSuccessPerClass[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totaux Cycle 1');
                $counter = 0;

                foreach($rateOfSuccessPerClass[0] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->ligneFicheSyntheseDePerformanceDesElevesParClasse($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                $pdf = $this->ligneFicheSyntheseDePerformanceDesElevesParClasse($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                
            }
            // Cycle 2
            if(!empty($rateOfSuccessPerClass[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totaux Cycle 2');
                $counter = 0;
                foreach($rateOfSuccessPerClass[1] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->ligneFicheSyntheseDePerformanceDesElevesParClasse($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                
                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys(($rowSchool->getGeneralAverageBoys()/$counterSchool));
                        $rowSchool->setGeneralAverageGirls(($rowSchool->getGeneralAverageGirls()/$counterSchool));
                        $rowSchool->setGeneralAverage(($rowSchool->getGeneralAverage()/$counterSchool));
                    }

                    
                // Sous total Cycle 2
                $pdf = $this->ligneFicheSyntheseDePerformanceDesElevesParClasse($pdf, $rowCycle, true);
                $rowCounter++;
                
                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

            }

            // Total Etablissement
            $pdf = $this->totalEtablissementFicheSyntheseDePerformanceDesElevesParClasse($pdf, $rowSchool, true);

            $pdf->Ln(3);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(36, 6, '', 0, 0, 'C');
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
                
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Supervisor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                    
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }
        }else
        {
            if($firstPeriodLetter === 't')
            {
                $termName = 'TERM '.$this->termRepository->find($idP)->getTerm();

            }elseif($firstPeriodLetter === 's')
            {
                $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
            }else
            {
                $termName = 'ANNUAL';

            }

            $pdf = new Pagination();

            if(empty($rateOfSuccessPerClass))
            {
                    $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the class contains at least one student'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure all notes are entered'), 0, 1, 'C');

                return $pdf;
            }

            $pageCounter = 0;
            $rowCounter = 0;

            // Oninsère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on rempli l'entête administraive
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // Logo de l'établissement
            // $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            // $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900); 
            
            $pdf->setXY($x, $y);

            // Enteête de la fiche
            $pdf = $this->generalService->staisticSlipHeaderPagination($pdf, 'STATISTICAL SHEET RELATING TO SUCCESS RATES', $termName, $school);
            $pdf->Ln();

            // Entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Class');

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            $rowSchool->setSubject('Establishment Totals');
            $counterSchool = 0;

            // Cycle 1
            if(!empty($rateOfSuccessPerClass[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totals Cycle 1');
                $counter = 0;

                foreach($rateOfSuccessPerClass[0] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;

                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                
            }
            // Cycle 2
            if(!empty($rateOfSuccessPerClass[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totals Cycle 2');
                $counter = 0;
                foreach($rateOfSuccessPerClass[1] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->statisticTableRowPagination($pdf, $row);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation( $rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys(($rowSchool->getGeneralAverageBoys()/$counterSchool));
                        $rowSchool->setGeneralAverageGirls(($rowSchool->getGeneralAverageGirls()/$counterSchool));
                        $rowSchool->setGeneralAverage(($rowSchool->getGeneralAverage()/$counterSchool));
                    }

                    $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));

                // Sous total Cycle 2
                $pdf = $this->statisticTableRowPagination($pdf, $rowCycle, true);
                $rowCounter++;
                
                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

                $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));
            }

            // Total Etablissement
            $pdf = $this->statisticTableRowPagination($pdf, $rowSchool, true);

            $pdf->Ln(3);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(36, 6, '', 0, 0, 'C');

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'Le Censeur', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Proviseur', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'Le Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'Le Directeur', 0, 0, 'R');
                    }
                }else
                {
                    $pdf->Cell(100, 6, utf8_decode("Le Préfet des études"), 0, 0, 'L');
                    $pdf->Cell(100, 6, 'Le Principal', 0, 0, 'R');
                }
            }else
            {
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        $pdf->Cell(100, 6, 'The Censor', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                    }else
                    {
                        $pdf->Cell(100, 6, 'The Surveillant', 0, 0, 'L');
                        $pdf->Cell(100, 6, 'The Director', 0, 0, 'R');
                    }
                }else
                {
                    $pdf->Cell(100, 6, 'The Prefect of Studies', 0, 0, 'L');
                    $pdf->Cell(100, 6, 'The Principal', 0, 0, 'R');
                }
            }
            
        }

        return $pdf;
    }


    /**
     * FICHE DE CONSEIL DE CLASSE
     *
     * @param array $allStudentReports
     * @param array $statisticSlipPerClass
     * @param integer $numberOfLessons
     * @param SchoolYear $schoolYear
     * @param Classroom $classroom
     * @param integer $numberOfStudents
     * @param integer $numberOfBoys
     * @param integer $numberOfGirls
     * @param Term $term
     * @param School $school
     * @param SubSystem $subSystem
     * @return Pagination
     */
    public function printClassCouncil(array $allStudentReports, array $statisticSlipPerClass, int $numberOfLessons, SchoolYear $schoolYear, Classroom $classroom, int $numberOfStudents, int $numberOfBoys, int $numberOfGirls, Term $term, School $school, SubSystem $subSystem): Pagination
    {
        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            $pdf = new Pagination();
            $fontSize = 10;

            if(empty($allStudentReports))
            {
                $pdf->addPage();
                $pdf->setFont('Times', '', 20);
                $pdf->setTextColor(0, 0, 0);
                $pdf->SetLeftMargin(15);
                $pdf->SetFillColor(200);

                $pdf->Cell(0, 10, utf8_decode('Impression de la fiche de conseil impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les bulletins de la classe ont été impimés.'), 0, 1, 'C');

                return $pdf;
            }

            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeight5 = 5;
            $cellHeight7 = 7;

            $cellWidth1 = 40;
            $cellWidth2 = 80;
            $cellWidth3 = 20;
            $cellWidth4= 30;
            $cellWidth5 = 20;

            // PAGE 1

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            if($term->getTerm() == ConstantsClass::ANNUEL_TERM)
            {
                $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
            }else
            {
                $pdf->Cell(0, 5, "CONSEIL DE CLASSE DE FIN DE TRIMESTRE", 0, 2, 'C');
            }

            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'C');
            $pdf->Cell(0, 5, 'Classe : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
            $pdf->Ln();

            if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
            {
                $pdf->Cell(0, 5, utf8_decode('FICHE STATISTIQUE DU TRIMESTRE N°'.$term->getTerm()), 0, 1, 'C');

            }else
            {
                $pdf->Cell(0, 5, utf8_decode('FICHE STATISTIQUE ANNUELLE'), 0, 1, 'C');
            }

            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(20, 5, 'Classe : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(40, 5, 'Professeur Principal: ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);

            $principalTeacher = $classroom->getPrincipalTeacher();

            $pdf->Cell(90, 5, utf8_decode($this->generalService->getNameWithTitle($principalTeacher->getFullName(), $principalTeacher->getSex()->getSex())), 0, 1, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, 7, 'Conseil de classe tenu le : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, 7, utf8_decode('Président du conseil : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'L');
            $pdf->Ln();
            $pdf->Cell(0, 5, utf8_decode('Membres présents au conseil de classe :  '), 0, 1, 'L');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);

            // ENTETE DU TABLEAU
            $pdf->Cell($cellWidth1, $cellHeight7, utf8_decode('Matières'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeight7, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth3, $cellHeight7, utf8_decode('Grade'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth4, $cellHeight7, utf8_decode('Qualité'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell($cellWidth5, $cellHeight7, utf8_decode('Emargement'), 1, 1, 'C', true);
            $pdf->SetFont('Times', '', 10);

            // CONTENU DU TABLEAU

            // Affichage du chef d'etablissement
            $headmaster = $school->getHeadmaster();

            $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
            $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($headmaster->getFullName()), 1, 0, 'L');
            $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($headmaster->getGrade() ? $headmaster->getGrade()->getGrade(): ""), 1, 0, 'L');
            $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode($headmaster->getDuty()->getDuty()), 1, 0, 'L');
            $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');

            // Affichage du censeur d'attache
            $censor = $classroom->getCensor();
            if($censor)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($censor->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($censor->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode($censor->getDuty()->getDuty()), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Censeur"), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }

            // Affichage du surveillant general d'attache
            $supervisor = $classroom->getSupervisor();
            if($supervisor)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($supervisor->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($supervisor->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("SUR. GEN"), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Sur. Gén."), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }

            // Affichage du conseiller d'orientation d'attache
            $counsellor = $classroom->getCounsellor();
            if($counsellor)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ORIENTATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($counsellor->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($counsellor->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode($counsellor->getDuty()->getDuty()), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Conseiller d'Orient"), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }

            // Affichage le personnel de l'action sociale d'attache
            $socialAction = $classroom->getActionSociale();
            if($socialAction)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ACTION SOCIALE'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($socialAction->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($socialAction->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode($socialAction->getDuty()->getDuty()), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Action Sociale"), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }


            // Affichage du professeur principal
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $principalTeacherLessons = $this->lessonRepository->findTeacherLessonsInClassroom($classroom, $principalTeacher);
            $counter = count($principalTeacherLessons); 

            if($principalTeacher)
            {
                // on affiche ses matieres 
                foreach ($principalTeacherLessons as $lesson) 
                {
                    if(strlen($lesson->getSubject()->getSubject()) <= 17)
                    {
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($lesson->getSubject()->getSubject()), 1, 1, 'L');
                    }else
                    {
                        $pdf->SetFont('Times', '', 7);
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($lesson->getSubject()->getSubject()), 1, 1, 'L');
                        $pdf->SetFont('Times', '', 10);
                    }
                }

                // on affiche ses autres champs
                $pdf->SetXY($x, $y);
                $pdf->Cell($cellWidth1);
                $pdf->Cell($cellWidth2, $cellHeight5*$counter, utf8_decode($principalTeacher->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5*$counter, utf8_decode($principalTeacher->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5*$counter, utf8_decode('Professeur principal'), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5*$counter, '', 1, 1, 'L');
            }

            // Affichage des autres enseignants
            $otherTeachers = $this->generalService->getOtherTeachers($principalTeacher, $classroom);
            foreach ($otherTeachers as $otherTeacher) 
            {
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // on recupère ses lessons
                $otherTeacherLessons = $this->lessonRepository->findTeacherLessonsInClassroom($classroom, $otherTeacher);
                $counter = count($otherTeacherLessons);
                foreach ($otherTeacherLessons as $otherLesson) 
                {
                    if(strlen($otherLesson->getSubject()->getSubject()) <= 17)
                    {
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($otherLesson->getSubject()->getSubject()), 1, 1, 'L');
                    }else
                    {
                        $pdf->SetFont('Times', '', 7);
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($otherLesson->getSubject()->getSubject()), 1, 1, 'L');
                        $pdf->SetFont('Times', '', 10);
                    }
                }

                // on affiche ses autres champs
                $pdf->SetXY($x, $y);
                $pdf->Cell($cellWidth1);
                $pdf->Cell($cellWidth2, $cellHeight5*$counter, utf8_decode($otherTeacher->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5*$counter, utf8_decode($otherTeacher->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5*$counter, utf8_decode('Membre'), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5*$counter, '', 1, 1, 'L');

                
            }

            $classroomProfile = $allStudentReports[0]->getReportFooter()->getClassroomProfile();

            $classifiedStudents = $this->reportRepository->findClassifiedStudents($classroom, $term);

            // dd($classifiedStudents);

            $pdf->Ln(10);

            // A. STATISTIQUES
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'A. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'STATISTIQUES', 0, 1, 'L');
            $pdf->Ln();

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(40, 5, 'Effectif global : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, $this->generalService->formatInteger($numberOfStudents), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(30, 5, utf8_decode('Garçons : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, $this->generalService->formatInteger($numberOfBoys), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(30, 5, utf8_decode('Filles : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, $this->generalService->formatInteger($numberOfGirls), 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode("Nombre d'élèves classés : "), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatInteger(count($classifiedStudents)), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode("Nombre d'élèves non classés : "), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5,  $this->generalService->formatInteger($numberOfStudents - count($classifiedStudents)), 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Moyenne >= 10 : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatInteger($this->generalService->getNumberOfSuccedStudents($classifiedStudents)), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Taux de réussite : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getSuccessRate()).' %', 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Moyenne du 1er : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getFirstAverage()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Moyenne du dernier : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getLastAverage()), 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Moyenne Generale : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getClassroomAverage()), 0, 1, 'L');

            // PAGE 2
            
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
            $pdf->Image('images/school/'.$school->getFiligree(), 43, 100, -90); 

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'B. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'CLASSEMENT PAR MERITE ', 0, 1, 'L');
            $pdf->Ln();

            // Les 5 premiers
            $first5 = $this->generalService->getFirst5($classifiedStudents);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '1. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, 'Les cinq premiers', 0, 1, 'L');
            $pdf->Ln();

            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeight7, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell(15, $cellHeight7, utf8_decode('Sexe'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellHeight7, utf8_decode('Date et lieu de naissance'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('Moyenne'), 1, 1, 'C', true);

            $pdf->SetFont('Times', '', 10);
            $counter = 1;
            foreach ($first5 as $report) 
            {
                if ($counter % 2 != 0) 
                {
                    $pdf->SetFillColor(219,238,243);
                }else {
                    $pdf->SetFillColor(255,255,255);
                }
                $student = $report->getStudent();
                $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C', true);
                $pdf->Cell(80, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L', true);
                $pdf->Cell(15, $cellHeight5, utf8_decode($student->getSex()->getSex()), 1, 0, 'C', true);
                $pdf->Cell(65, $cellHeight5, utf8_decode($student->getBirthday()->format('d/m/Y')).utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                $pdf->Cell(20, $cellHeight5, utf8_decode($this->generalService->formatMark($report->getMoyenne())), 1, 1, 'C', true);
                $counter++;
            }

            // Les 5 derniers
            $last5 = $this->generalService->getLast5($classifiedStudents);
            
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '2. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, 'Les cinq derniers', 0, 1, 'L');
            $pdf->Ln();

            $pdf->SetFillColor(200, 200, 200);
            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeight7, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell(15, $cellHeight7, utf8_decode('Sexe'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellHeight7, utf8_decode('Date et lieu de naissance'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('Moyenne'), 1, 1, 'C', true);

            $pdf->SetFont('Times', '', 10);
            $counter = 1;
            
            foreach ($last5 as $report) 
            {
                if ($counter % 2 != 0) 
                {
                    $pdf->SetFillColor(219,238,243);
                }else {
                    $pdf->SetFillColor(255,255,255);
                }
                $student = $report->getStudent();
                $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C', true);
                $pdf->Cell(80, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L', true);
                $pdf->Cell(15, $cellHeight5, utf8_decode($student->getSex()->getSex()), 1, 0, 'C', true);
                $pdf->Cell(65, $cellHeight5, utf8_decode($student->getBirthday()->format('d/m/Y')).utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                $pdf->Cell(20, $cellHeight5, utf8_decode($this->generalService->formatMark($report->getMoyenne())), 1, 1, 'C', true);
                $counter++;
            }

            // Les sanctions
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'C. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'SANCTIONS ', 0, 1, 'L');
            $pdf->Ln();

            // les sanctions positives
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '1. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, 'Positives', 0, 1, 'L');
            $pdf->Ln();

            $pdf->SetFillColor(200, 200, 200);
            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeight7, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell(18, $cellHeight7, 'Moy', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeight7, 'Sexe', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(24, $cellHeight7, utf8_decode("Tableau d'honneur"), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(24, $cellHeight7, 'Encouragement', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Félicitation'), 1, 1, 'C', true);
            $pdf->SetFont('Times', 'B', 12);

            // on recupère les élèves à sanctions positives
            $best = $this->generalService->getBest($allStudentReports);
            $bestTotal = $this->generalService->getBestTotal($best);

            // on affiche les sanctions positives
            $counter = 1;
            if(!empty($best))
            {
                foreach ($best as $report) 
                {
                    if ($counter % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $student = $report->getReportHeader()->getStudent();
                    $studentWork = $report->getReportFooter()->getStudentWork();
                    $pdf->SetFont('Times', '', 10);
                    $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C', true);
                    $pdf->Cell(80, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(18, $cellHeight5, $this->generalService->formatMark($report->getReportFooter()->getStudentResult()->getMoyenne()), 1, 0, 'C', true);
                    $pdf->Cell(10, $cellHeight5, $student->getSex()->getSex(), 1, 0, 'C', true);

                    $pdf->SetFont('Times', 'B', 10);

                    $pdf->Cell(24, $cellHeight5, $studentWork->getRollOfHonour(), 1, 0, 'C', true);
                    $pdf->Cell(24, $cellHeight5, $studentWork->getEncouragement(), 1, 0, 'C', true);
                    $pdf->Cell(24, $cellHeight5, $studentWork->getCongratulation(), 1, 1, 'C', true);

                    $counter++;
                }
            }else
            {
                $pdf->Cell(190, $cellHeight5*3, utf8_decode('Aucune sanction positive enregistrée'), 1, 1, 'C');
            }

            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(118, $cellHeight7*3, 'TOTAL', 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(24, $cellHeight7, utf8_decode("Tableau d'honneur"), 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Encouragement'), 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Félicitation'), 1, 1, 'C',true);
            $pdf->SetFont('Times', 'B', 12);

            $pdf->Cell(118);
            $pdf->Cell(8, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(8, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(8, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'T', 1, 1, 'C',true);

            $pdf->Cell(118);
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysTH']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['girlsTH']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysTH'] + $bestTotal['girlsTH']), 1, 0, 'C');

            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysENC']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['girlsENC']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysENC'] + $bestTotal['girlsENC']), 1, 0, 'C');

            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysFEL']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['girlsFEL']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysFEL']+$bestTotal['girlsFEL']), 1, 1, 'C');

            $pdf->Ln();
            // PAGE 3

            // On insère une page
            // $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
            // $pdf->Image('images/school/'.$school->getFiligree(), 43, 100, -90); 
            
            // les sanctions négatives
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '2. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, utf8_decode('Négatives'), 0, 1, 'L');
            $pdf->Ln();

            $pdf->SetFillColor(200, 200, 200);
            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(70, $cellHeight7, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeight7, 'Moy', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeight7, 'Sexe', 1, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Avertissement', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Conduite', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blâme'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Conduite', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Avertissement', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Travail', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blâme'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Travail', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Exclusion', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Temporaire', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Conseil de', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Discipline', 'LBR', 1, 'C', true);
            
            $pdf->SetFont('Times', 'B', 12);

            // on recupère les élèves à sanctions négatives
            $bad = $this->generalService->getBad($allStudentReports);
            $badTotal = $this->generalService->getTotalBad($bad);

            $absences = $this->generalService->getAllAbsence($allStudentReports);

            // on affiche les sanctions négatives
            $counter = 1;
            if(!empty($bad))
            {
                foreach ($bad as $report) 
                {   
                    if ($counter % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $student = $report->getReportHeader()->getStudent();
                    $studentWork = $report->getReportFooter()->getStudentWork();
                    $studentDiscipline = $report->getReportFooter()->getDiscipline();
        
                    $pdf->SetFont('Times', '', 10);
                    $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C',true);
                    $pdf->Cell(70, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L',true);
                    $pdf->Cell(10, $cellHeight5, $this->generalService->formatMark($report->getReportFooter()->getStudentResult()->getMoyenne()), 1, 0, 'C',true);
                    $pdf->Cell(10, $cellHeight5, $student->getSex()->getSex(), 1, 0, 'C',true);
        
                    $pdf->SetFont('Times', 'B', 10);
        
                    $pdf->Cell(15, $cellHeight5, $studentDiscipline->getWarningBehaviour(), 1, 0, 'C',true);
                    $pdf->Cell(15, $cellHeight5, $studentDiscipline->getBlameBehaviour(), 1, 0, 'C',true);
                    $pdf->Cell(15, $cellHeight5, $studentWork->getWarningWork(), 1, 0, 'C',true);
                    $pdf->Cell(15, $cellHeight5, $studentWork->getBlameWork(), 1, 0, 'C',true);
                    if($studentDiscipline->getExclusion())
                    {
                        $pdf->Cell(15, $cellHeight5, 'X', 1, 0, 'C',true);  
                    }else
                    {
                        $pdf->Cell(15, $cellHeight5, '', 1, 0, 'C',true);
                    }
                    $pdf->Cell(15, $cellHeight5, $studentDiscipline->getDisciplinaryCommitee(), 1, 1, 'C',true);

                    
                    $counter++;
                }

            }else
            {
                $pdf->Cell(190, $cellHeight5*3, utf8_decode('Aucune sanction négative enregistrée'), 1, 1, 'C');
            }

            
            $pdf->SetFont('Times', 'B', 12);
            // $pdf->Image('images/school/'.$school->getFiligree(), 43, 100, -90); 

            $pdf->SetFillColor(200, 200, 200);
            $pdf->Cell(100, $cellHeight7*3, 'TOTAL', 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Avertissement', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Conduite', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blâme'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Conduite', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Avertissement', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Travail', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blâme'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Travail', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Exclusion', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Temporaire', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Conseil de', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Discipline', 'LBR', 1, 'C', true);
            
            $pdf->SetFont('Times', 'B', 12);

            $pdf->Cell(100);
            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'F', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 1, 'C',true);

            $pdf->SetFont('Times', 'B', 10);

            $pdf->Cell(100);
            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['boysAC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsAC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysAC']+$badTotal['girlsAC']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsBC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBC']+$badTotal['girlsBC']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysAT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsAT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysAT']+$badTotal['girlsAT']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsBT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBT']+$badTotal['girlsBT']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysEXT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsEXT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysEXT']+$badTotal['girlsEXT']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsCD']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']+$badTotal['girlsCD']), 1, 0, 'C');

            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Observations conséquentes : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');


            // PAGE 4

            // nouvelle page en paysage
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pdf->Image('images/school/'.$school->getFiligree(), 80, 30, -90);

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'D. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'RESULTATS PAR MATIERE ', 0, 1, 'L');
            $pdf->Ln();

            $pdf->SetFillColor(200, 200, 200);
            // entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Matières');

            foreach($statisticSlipPerClass[0]['rows'] as $row)
            {
                // une ligne du tableau
                $pdf = $this->statisticTableRowPagination($pdf, $row);
                    
            }
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, 'Analyse des performances : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');

            // PAGE 5

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
            $pdf->Image('images/school/'.$school->getFiligree(), 43, 100, -90); 

            // Etat disciplinaire
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'E. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'ETAT DISCIPLINAIRE ', 0, 1, 'L');
            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(30, $cellHeight7, '', 'LRT', 0, 'C', true);
            $pdf->Cell(84, $cellHeight7, 'Sanctions disciplinaires', 1, 0, 'C', true);
            $pdf->Cell(36, $cellHeight7, 'Nombre de', 'LRT', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, 'Taux', 'LRT', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, 'Taux', 'LRT', 1, 'C', true);

            $pdf->Cell(30, $cellHeight7, 'Classes', 'LR', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, 'AC', 'LRT', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, 'BC', 'LRT', 0, 'C', true);
            $pdf->Cell(60, $cellHeight7, 'Exclusions', 1, 0, 'C', true);
            $pdf->Cell(36, $cellHeight7, 'sanctions', 'LRB', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode("d'absences"), 'LR', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode("d'assiduité"), 'LR', 1, 'C', true);



            $pdf->Cell(30, $cellHeight7, '', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '3j', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '5j', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '8j', 1, 0, 'C', true);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Définitives'), 1, 0, 'C', true);
            $pdf->Cell(24, $cellHeight7, 'Absences', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, 'CD', 'LRB', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('( % )'), 'LRB', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('( % )'), 'LRB', 1, 'C', true);

            $absenceRateBoys = $this->generalService->getFormatRatio($absences['absenceBoys'], $this->generalService->getNumberOfTermHours($classroom, 'M'));
            $presenceRateBoys = 100 - (float)$absenceRateBoys;

            $absenceRateGirls = $this->generalService->getFormatRatio($absences['absenceGirls'], $this->generalService->getNumberOfTermHours($classroom, 'F'));
            $presenceRateGirls = 100 - (float)$absenceRateGirls;

            $totalAbsence = $absences['absenceBoys'] + $absences['absenceGirls'];
            $totalAbsenceRate = $this->generalService->getFormatRatio($totalAbsence, $this->generalService->getNumberOfTermHours($classroom));
            $totalPresneceRate = 100 - (float)$totalAbsenceRate;

            $pdf->Cell(30, $cellHeight7, utf8_decode('Garcons'), 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysAC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysBC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion3']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion5']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion8']), 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, '', 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, $this->generalService->formatInteger($absences['absenceBoys']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']), 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $absenceRateBoys, 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $presenceRateBoys, 1, 1, 'C');

            $pdf->Cell(30, $cellHeight7, 'Filles', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsAC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsBC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsEclusion3']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsEclusion5']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsEclusion8']), 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, '', 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, $this->generalService->formatInteger($absences['absenceGirls']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsCD']), 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $absenceRateGirls, 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $presenceRateGirls, 1, 1, 'C');

            $pdf->Cell(30, $cellHeight7, 'Total', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysAC']+$badTotal['girlsAC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysBC']+$badTotal['girlsBC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion3']+$badTotal['girlsEclusion3']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion5']+$badTotal['girlsEclusion5']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion8']+$badTotal['girlsEclusion8']), 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, '', 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, $this->generalService->formatInteger($absences['absenceBoys']+$absences['absenceGirls']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']+$badTotal['girlsCD']), 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $totalAbsenceRate, 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $totalPresneceRate, 1, 1, 'C');


            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Observations conséquentes : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');


            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, 'OBSERVATIONS GENERALES :', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');

            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(0, $cellHeight7, 'Remarques : ', 0, 1, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('La présente fiche statistique doit être soigneusement remplie et impérativement déposée à la surveillance '), 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, utf8_decode('générale en vue de sa conservation et de son exploitation.'), 0, 1, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(0, $cellHeight7, 'NB :', 0, 1, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode(' En cas de nécessité, le professeur principal doit établir une liste additive de sanctions positives et/ou '), 0, 1, 'L');

            $pdf->Cell(0, $cellHeight7, utf8_decode("négatives, qu'il faudra bien joindre à la présente fiche."), 0, 1, 'L');

            $pdf->Ln();

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _'), 0, 0, 'R');

            }else
            {
                $pdf->Cell(0, $cellHeight7, utf8_decode('Done at '.$school->getPlace().', On _ _ _ _ _ _ _ _'), 0, 0, 'R');
            }
            

            $pdf->Ln();
            $pdf->Ln();
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(90, $cellHeight7, utf8_decode('Le Professeur Principal'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
        }else
        {
            $pdf = new Pagination();
            $fontSize = 10;

            if(empty($allStudentReports))
            {
                $pdf->addPage();
                $pdf->setFont('Times', '', 20);
                $pdf->setTextColor(0, 0, 0);
                $pdf->SetLeftMargin(15);
                $pdf->SetFillColor(200);

                $pdf->Cell(0, 10, utf8_decode('Printing of the advice sheet impossible!'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure class report cards have been printed.'), 0, 1, 'C');

                return $pdf;
            }

            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeight5 = 5;
            $cellHeight7 = 7;

            $cellWidth1 = 40;
            $cellWidth2 = 80;
            $cellWidth3 = 20;
            $cellWidth4= 30;
            $cellWidth5 = 20;

            // PAGE 1

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            if($term->getTerm() == ConstantsClass::ANNUEL_TERM)
            {
                $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            }else
            {
                $pdf->Cell(0, 5, 'END OF TERM CLASS COUNCIL', 0, 2, 'C');
            }

            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'C');
            $pdf->Cell(0, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
            $pdf->Ln();

            if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
            {
                $pdf->Cell(0, 5, utf8_decode('QUARTER STATISTICAL SHEET N°'.$term->getTerm()), 0, 1, 'C');

            }else
            {
                $pdf->Cell(0, 5, utf8_decode('ANNUAL STATISTICAL SHEET'), 0, 1, 'C');
            }

            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(20, 5, 'Class : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(40, 5, 'Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);

            $principalTeacher = $classroom->getPrincipalTeacher();

            $pdf->Cell(90, 5, utf8_decode($this->generalService->getNameWithTitle($principalTeacher->getFullName(), $principalTeacher->getSex()->getSex())), 0, 1, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, 7, 'Class Council held on : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, 7, utf8_decode('President of the council : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'L');
            $pdf->Ln();
            $pdf->Cell(0, 5, utf8_decode('Members present at the class council :  '), 0, 1, 'L');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);

            // ENTETE DU TABLEAU
            $pdf->Cell($cellWidth1, $cellHeight7, utf8_decode('Subjects'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth2, $cellHeight7, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth3, $cellHeight7, utf8_decode('Grade'), 1, 0, 'C', true);
            $pdf->Cell($cellWidth4, $cellHeight7, utf8_decode('Quality'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell($cellWidth5, $cellHeight7, utf8_decode('Emargement'), 1, 1, 'C', true);
            $pdf->SetFont('Times', '', 10);

            // CONTENU DU TABLEAU

            // Affichage du chef d'etablissement
            $headmaster = $school->getHeadmaster();

            $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
            $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($headmaster->getFullName()), 1, 0, 'L');
            $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($headmaster->getGrade()->getGrade()), 1, 0, 'L');
            $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode($headmaster->getDuty()->getDuty()), 1, 0, 'L');
            $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');

            // Affichage du censeur d'attache
            $censor = $classroom->getCensor();
            if($censor)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($censor->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($censor->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Vice-Principal"), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }
            else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Vice-Principal"), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }

            // Affichage du surveillant general d'attache
            $supervisor = $classroom->getSupervisor();
            if($supervisor)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($supervisor->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($supervisor->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Att. Sup."), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }
            else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ADMINISTRATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Att. Sup."), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }

            // Affichage du conseiller d'orientation d'attache
            $counsellor = $classroom->getCounsellor();
            if($counsellor)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ORIENTATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($counsellor->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($counsellor->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode($counsellor->getDuty()->getDuty()), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }
            else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('ORIENTATION'), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode("Orientation"), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }

            // Affichage le personnel de l'action sociale d'attache
            $socialAction = $classroom->getActionSociale();
            if($socialAction)
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('SOCIALE ACTION '), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode($socialAction->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode($socialAction->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode($socialAction->getDuty()->getDuty()), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }else
            {
                $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode('SOCIALE ACTION '), 1, 0, 'L');
                $pdf->Cell($cellWidth2, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5, utf8_decode(""), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5, '', 1, 1, 'L');
            }


            // Affichage du professeur principal
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $principalTeacherLessons = $this->lessonRepository->findTeacherLessonsInClassroom($classroom, $principalTeacher);
            $counter = count($principalTeacherLessons); 

            if($principalTeacher)
            {
                // on affiche ses matieres 
                foreach ($principalTeacherLessons as $lesson) 
                {
                    if(strlen($lesson->getSubject()->getSubject()) <= 17)
                    {
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($lesson->getSubject()->getSubject()), 1, 1, 'L');
                    }else
                    {
                        $pdf->SetFont('Times', '', 7);
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($lesson->getSubject()->getSubject()), 1, 1, 'L');
                        $pdf->SetFont('Times', '', 10);
                    }
                }

                // on affiche ses autres champs
                $pdf->SetXY($x, $y);
                $pdf->Cell($cellWidth1);
                $pdf->Cell($cellWidth2, $cellHeight5*$counter, utf8_decode($principalTeacher->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5*$counter, utf8_decode($principalTeacher->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5*$counter, utf8_decode('Head teacher'), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5*$counter, '', 1, 1, 'L');
            }

            // Affichage des autres enseignants
            $otherTeachers = $this->generalService->getOtherTeachers($principalTeacher, $classroom);
            foreach ($otherTeachers as $otherTeacher) 
            {
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                // on recupère ses lessons
                $otherTeacherLessons = $this->lessonRepository->findTeacherLessonsInClassroom($classroom, $otherTeacher);
                $counter = count($otherTeacherLessons);
                foreach ($otherTeacherLessons as $otherLesson) 
                {
                    if(strlen($otherLesson->getSubject()->getSubject()) <= 17)
                    {
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($otherLesson->getSubject()->getSubject()), 1, 1, 'L');
                    }else
                    {
                        $pdf->SetFont('Times', '', 7);
                        $pdf->Cell($cellWidth1, $cellHeight5, utf8_decode($otherLesson->getSubject()->getSubject()), 1, 1, 'L');
                        $pdf->SetFont('Times', '', 10);
                    }
                }

                // on affiche ses autres champs
                $pdf->SetXY($x, $y);
                $pdf->Cell($cellWidth1);
                $pdf->Cell($cellWidth2, $cellHeight5*$counter, utf8_decode($otherTeacher->getFullName()), 1, 0, 'L');
                $pdf->Cell($cellWidth3, $cellHeight5*$counter, utf8_decode($otherTeacher->getGrade()->getGrade()), 1, 0, 'L');
                $pdf->Cell($cellWidth4, $cellHeight5*$counter, utf8_decode('Member'), 1, 0, 'L');
                $pdf->Cell($cellWidth5, $cellHeight5*$counter, '', 1, 1, 'L');

                
            }

            $classroomProfile = $allStudentReports[0]->getReportFooter()->getClassroomProfile();

            $classifiedStudents = $this->reportRepository->findClassifiedStudents($classroom, $term);

            // dd($classifiedStudents);

            $pdf->Ln(10);

            // A. STATISTIQUES
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'A. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'STATISTICS', 0, 1, 'L');
            $pdf->Ln();

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(40, 5, 'Overall workforce : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, $this->generalService->formatInteger($numberOfStudents), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(30, 5, utf8_decode('Boys : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, $this->generalService->formatInteger($numberOfBoys), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(30, 5, utf8_decode('Girls : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, $this->generalService->formatInteger($numberOfGirls), 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode("Number of students classified : "), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatInteger(count($classifiedStudents)), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode("Number of unclassified students : "), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5,  $this->generalService->formatInteger($numberOfStudents - count($classifiedStudents)), 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Average >= 10 : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatInteger($this->generalService->getNumberOfSuccedStudents($classifiedStudents)), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Success rate : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getSuccessRate()).' %', 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Average of the 1st : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getFirstAverage()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Average of last : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getLastAverage()), 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(60, 5, utf8_decode('Overall average : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(35, 5, $this->generalService->formatMark($classroomProfile->getClassroomAverage()), 0, 1, 'L');

            // PAGE 2
            
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
            $pdf->Image('images/school/'.$school->getFiligree(), 43, 100, -90); 

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'B. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'RANKING BY MERIT ', 0, 1, 'L');
            $pdf->Ln();

            // Les 5 premiers
            $first5 = $this->generalService->getFirst5($classifiedStudents);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '1. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, 'The first five', 0, 1, 'L');
            $pdf->Ln();

            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeight7, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell(15, $cellHeight7, utf8_decode('Sex'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellHeight7, utf8_decode('Date and place of birth'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('Average'), 1, 1, 'C', true);

            $pdf->SetFont('Times', '', 10);
            $counter = 1;
            foreach ($first5 as $report) 
            {
                $student = $report->getStudent();
                $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C');
                $pdf->Cell(80, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L');
                $pdf->Cell(15, $cellHeight5, utf8_decode($student->getSex()->getSex()), 1, 0, 'C');
                $pdf->Cell(65, $cellHeight5, utf8_decode($student->getBirthday()->format('d/m/Y')).utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L');
                $pdf->Cell(20, $cellHeight5, utf8_decode($this->generalService->formatMark($report->getMoyenne())), 1, 1, 'C');
                $counter++;
            }

            // Les 5 derniers
            $last5 = $this->generalService->getLast5($classifiedStudents);

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '2. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, 'The last five', 0, 1, 'L');
            $pdf->Ln();

            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeight7, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell(15, $cellHeight7, utf8_decode('Sex'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellHeight7, utf8_decode('Date and place of birth'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('Average'), 1, 1, 'C', true);

            $pdf->SetFont('Times', '', 10);
            $counter = 1;
            
            foreach ($last5 as $report) 
            {
                $student = $report->getStudent();
                $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C');
                $pdf->Cell(80, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L');
                $pdf->Cell(15, $cellHeight5, utf8_decode($student->getSex()->getSex()), 1, 0, 'C');
                $pdf->Cell(65, $cellHeight5, utf8_decode($student->getBirthday()->format('d/m/Y')).utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L');
                $pdf->Cell(20, $cellHeight5, utf8_decode($this->generalService->formatMark($report->getMoyenne())), 1, 1, 'C');
                $counter++;
            }

            // Les sanctions
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'C. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'SANCTIONS ', 0, 1, 'L');
            $pdf->Ln();

            // les sanctions positives
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '1. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, 'Positives', 0, 1, 'L');
            $pdf->Ln();

            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(80, $cellHeight7, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell(18, $cellHeight7, 'Avg', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeight7, 'Sex', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Roll of honor'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(24, $cellHeight7, 'Encouragement', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Congratulat'), 1, 1, 'C', true);
            $pdf->SetFont('Times', 'B', 12);

            // on recupère les élèves à sanctions positives
            $best = $this->generalService->getBest($allStudentReports);
            $bestTotal = $this->generalService->getBestTotal($best);

            // on affiche les sanctions positives
            $counter = 1;
            if(!empty($best))
            {
                foreach ($best as $report) 
                {
                    $student = $report->getReportHeader()->getStudent();
                    $studentWork = $report->getReportFooter()->getStudentWork();
                    $pdf->SetFont('Times', '', 10);
                    $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C');
                    $pdf->Cell(80, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L');
                    $pdf->Cell(18, $cellHeight5, $this->generalService->formatMark($report->getReportFooter()->getStudentResult()->getMoyenne()), 1, 0, 'C');
                    $pdf->Cell(10, $cellHeight5, $student->getSex()->getSex(), 1, 0, 'C');

                    $pdf->SetFont('Times', 'B', 10);

                    $pdf->Cell(24, $cellHeight5, $studentWork->getRollOfHonour(), 1, 0, 'C');
                    $pdf->Cell(24, $cellHeight5, $studentWork->getEncouragement(), 1, 0, 'C');
                    $pdf->Cell(24, $cellHeight5, $studentWork->getCongratulation(), 1, 1, 'C');

                    $counter++;
                }
            }else
            {
                $pdf->Cell(190, $cellHeight5*3, utf8_decode('No positive sanction recorded'), 1, 1, 'C');
            }

            $pdf->Cell(118, $cellHeight7*3, 'TOTAL', 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Roll of honor'), 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Encouragement'), 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Congratulat'), 1, 1, 'C',true);
            $pdf->SetFont('Times', 'B', 12);

            $pdf->Cell(118);
            $pdf->Cell(8, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(8, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(8, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(8, $cellHeight7, 'T', 1, 1, 'C',true);

            $pdf->Cell(118);
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysTH']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['girlsTH']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysTH'] + $bestTotal['girlsTH']), 1, 0, 'C');

            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysENC']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['girlsENC']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysENC'] + $bestTotal['girlsENC']), 1, 0, 'C');

            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysFEL']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['girlsFEL']), 1, 0, 'C');
            $pdf->Cell(8, $cellHeight7, $this->generalService->formatInteger($bestTotal['boysFEL']+$bestTotal['girlsFEL']), 1, 1, 'C');

            $pdf->Ln();
            // PAGE 3

            // On insère une page
            // $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
            // $pdf->Image('images/school/'.$school->getFiligree(), 43, 100, -90); 
            
            // les sanctions négatives
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, '', 0, 0, 'L');
            $pdf->Cell(10, 5, '2. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(30, 5, utf8_decode('Negative'), 0, 1, 'L');
            $pdf->Ln();

            $pdf->Cell(10, $cellHeight7, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(70, $cellHeight7, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeight7, 'Avg', 1, 0, 'C', true);
            $pdf->Cell(10, $cellHeight7, 'Sex', 1, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Warning', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Behavior', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blame'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Behavior', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Warning', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Work', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blame'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Work', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Exclusion', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Temporary', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Disciplinary ', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Board', 'LBR', 1, 'C', true);
            
            $pdf->SetFont('Times', 'B', 12);

            // on recupère les élèves à sanctions négatives
            $bad = $this->generalService->getBad($allStudentReports);
            $badTotal = $this->generalService->getTotalBad($bad);

            $absences = $this->generalService->getAllAbsence($allStudentReports);

            // on affiche les sanctions négatives
            $counter = 1;
            if(!empty($bad))
            {
                foreach ($bad as $report) 
                {   
                    $student = $report->getReportHeader()->getStudent();
                    $studentWork = $report->getReportFooter()->getStudentWork();
                    $studentDiscipline = $report->getReportFooter()->getDiscipline();
        
                    $pdf->SetFont('Times', '', 10);
                    $pdf->Cell(10, $cellHeight5, $counter, 1, 0, 'C');
                    $pdf->Cell(70, $cellHeight5, utf8_decode($student->getFullName()), 1, 0, 'L');
                    $pdf->Cell(10, $cellHeight5, $this->generalService->formatMark($report->getReportFooter()->getStudentResult()->getMoyenne()), 1, 0, 'C');
                    $pdf->Cell(10, $cellHeight5, $student->getSex()->getSex(), 1, 0, 'C');
        
                    $pdf->SetFont('Times', 'B', 10);
        
                    $pdf->Cell(15, $cellHeight5, $studentDiscipline->getWarningBehaviour(), 1, 0, 'C');
                    $pdf->Cell(15, $cellHeight5, $studentDiscipline->getBlameBehaviour(), 1, 0, 'C');
                    $pdf->Cell(15, $cellHeight5, $studentWork->getWarningWork(), 1, 0, 'C');
                    $pdf->Cell(15, $cellHeight5, $studentWork->getBlameWork(), 1, 0, 'C');
                    if($studentDiscipline->getExclusion())
                    {
                        $pdf->Cell(15, $cellHeight5, 'X', 1, 0, 'C');  
                    }else
                    {
                        $pdf->Cell(15, $cellHeight5, '', 1, 0, 'C');
                    }
                    $pdf->Cell(15, $cellHeight5, $studentDiscipline->getDisciplinaryCommitee(), 1, 1, 'C');

                    
                    $counter++;
                }

            }else
            {
                $pdf->Cell(190, $cellHeight5*3, utf8_decode('No negative sanctions recorded'), 1, 1, 'C');
            }

            
            $pdf->SetFont('Times', 'B', 12);

            $pdf->Cell(100, $cellHeight7*3, 'TOTAL', 1, 0, 'C',true);
            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Warning', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Behavior', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blâme'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Behavior', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell(15, $cellHeight7/2, 'Warning', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, 'Work', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 9);
            $pdf->Cell(15, $cellHeight7/2, utf8_decode('Blame'), 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Work', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Exclusion', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Temporary', 'LBR', 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->SetXY($x, $y-$cellHeight7/2);

            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(15, $cellHeight7/2, 'Disciplinary ', 'LTR', 2, 'C', true);
            $pdf->Cell(15, $cellHeight7/2, 'Board', 'LBR', 1, 'C', true);
            
            $pdf->SetFont('Times', 'B', 12);

            $pdf->Cell(100);
            $pdf->Cell(5, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 0, 'C',true);

            $pdf->Cell(5, $cellHeight7, 'B', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'G', 1, 0, 'C',true);
            $pdf->Cell(5, $cellHeight7, 'T', 1, 1, 'C',true);

            $pdf->SetFont('Times', 'B', 10);

            $pdf->Cell(100);
            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['boysAC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsAC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysAC']+$badTotal['girlsAC']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsBC']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBC']+$badTotal['girlsBC']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysAT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsAT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysAT']+$badTotal['girlsAT']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsBT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysBT']+$badTotal['girlsBT']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysEXT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['girlsEXT']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7,  $this->generalService->formatInteger($badTotal['boysEXT']+$badTotal['girlsEXT']), 1, 0, 'C');

            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsCD']), 1, 0, 'C');
            $pdf->Cell(5, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']+$badTotal['girlsCD']), 1, 0, 'C');

            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Consequent observations : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');


            // PAGE 4

            // nouvelle page en paysage
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pdf->Image('images/school/'.$school->getFiligree(), 80, 30, -90);

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'D. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'RESULTS BY SUBJECT ', 0, 1, 'L');
            $pdf->Ln();

            // entête du tableau
            $pdf = $this->statisticTableHeaderPagination($pdf, 'Subjects');

            foreach($statisticSlipPerClass[0]['rows'] as $row)
            {
                // une ligne du tableau
                $pdf = $this->statisticTableRowPagination($pdf, $row);
                    
            }
            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, 'Performance Analysis : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');

            // PAGE 5

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
            $pdf->Image('images/school/'.$school->getFiligree(), 43, 100, -90); 

            // Etat disciplinaire
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, 'E. ', 0, 0, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(30, 5, 'DISCIPLINARY STATE ', 0, 1, 'L');
            $pdf->Ln();
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(30, $cellHeight7, '', 'LRT', 0, 'C', true);
            $pdf->Cell(84, $cellHeight7, 'Disciplinary sanctions', 1, 0, 'C', true);
            $pdf->Cell(36, $cellHeight7, 'Nomber of', 'LRT', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, 'Rate', 'LRT', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, 'Rate', 'LRT', 1, 'C', true);

            $pdf->Cell(30, $cellHeight7, 'Class', 'LR', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, 'WB', 'LRT', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, 'BB', 'LRT', 0, 'C', true);
            $pdf->Cell(60, $cellHeight7, 'Exclusions', 1, 0, 'C', true);
            $pdf->Cell(36, $cellHeight7, 'sanctions', 'LRB', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('absences'), 'LR', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('attendance'), 'LR', 1, 'C', true);



            $pdf->Cell(30, $cellHeight7, '', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '3j', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '5j', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, '8j', 1, 0, 'C', true);
            $pdf->Cell(24, $cellHeight7, utf8_decode('Definitive'), 1, 0, 'C', true);
            $pdf->Cell(24, $cellHeight7, 'Absences', 'LRB', 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, 'CD', 'LRB', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('( % )'), 'LRB', 0, 'C', true);
            $pdf->Cell(20, $cellHeight7, utf8_decode('( % )'), 'LRB', 1, 'C', true);

            $absenceRateBoys = $this->generalService->getFormatRatio($absences['absenceBoys'], $this->generalService->getNumberOfTermHours($classroom, 'M'));
            $presenceRateBoys = 100 - (float)$absenceRateBoys;

            $absenceRateGirls = $this->generalService->getFormatRatio($absences['absenceGirls'], $this->generalService->getNumberOfTermHours($classroom, 'F'));
            $presenceRateGirls = 100 - (float)$absenceRateGirls;

            $totalAbsence = $absences['absenceBoys'] + $absences['absenceGirls'];
            $totalAbsenceRate = $this->generalService->getFormatRatio($totalAbsence, $this->generalService->getNumberOfTermHours($classroom));
            $totalPresneceRate = 100 - (float)$totalAbsenceRate;

            $pdf->Cell(30, $cellHeight7, utf8_decode('Garcons'), 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysAC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysBC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion3']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion5']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion8']), 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, '', 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, $this->generalService->formatInteger($absences['absenceBoys']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']), 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $absenceRateBoys, 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $presenceRateBoys, 1, 1, 'C');

            $pdf->Cell(30, $cellHeight7, 'Filles', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsAC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsBC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsEclusion3']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsEclusion5']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsEclusion8']), 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, '', 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, $this->generalService->formatInteger($absences['absenceGirls']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['girlsCD']), 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $absenceRateGirls, 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $presenceRateGirls, 1, 1, 'C');

            $pdf->Cell(30, $cellHeight7, 'Total', 1, 0, 'C', true);
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysAC']+$badTotal['girlsAC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysBC']+$badTotal['girlsBC']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion3']+$badTotal['girlsEclusion3']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion5']+$badTotal['girlsEclusion5']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysEclusion8']+$badTotal['girlsEclusion8']), 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, '', 1, 0, 'C');
            $pdf->Cell(24, $cellHeight7, $this->generalService->formatInteger($absences['absenceBoys']+$absences['absenceGirls']), 1, 0, 'C');
            $pdf->Cell(12, $cellHeight7, $this->generalService->formatInteger($badTotal['boysCD']+$badTotal['girlsCD']), 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $totalAbsenceRate, 1, 0, 'C');
            $pdf->Cell(20, $cellHeight7, $totalPresneceRate, 1, 1, 'C');


            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Consequent observations  : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');


            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, 'GENERAL OBSERVATIONS :', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _', 0, 1, 'L');

            $pdf->Ln();
            $pdf->Ln();
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(0, $cellHeight7, 'Remarques : ', 0, 1, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('This statistical form must be carefully completed and must be submitted to the surveillance '), 0, 1, 'L');
            $pdf->Cell(0, $cellHeight7, utf8_decode('general for its conservation and exploitation.'), 0, 1, 'L');
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(0, $cellHeight7, 'NB :', 0, 1, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode(' If necessary, the head teacher must draw up an additional list of positive sanctions and/or '), 0, 1, 'L');

            $pdf->Cell(0, $cellHeight7, utf8_decode('negative, which must be attached to this form.'), 0, 1, 'L');

            $pdf->Ln();

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _'), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Ln();
                $pdf->SetFont('Times', 'BU', 12);
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Professeur Principal'), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');

            }else
            {
                $pdf->Cell(0, $cellHeight7, utf8_decode('Done at '.$school->getPlace().', On _ _ _ _ _ _ _ _'), 0, 0, 'R');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Ln();
                $pdf->SetFont('Times', 'BU', 12);
                $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');
            }
            

        }
        

        return $pdf;

    }

    
    /**
     * fiche des taux d'assiduité par classe
     *
     * @param Term $term
     * @param array $classrooms
     * @param SchoolYear $schoolYear
     * @param School $school
     * @return Pagination
     */
    public function printRateOfPresencePerClass(Term $term, array $classrooms, SchoolYear $schoolYear, School $school): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }
        // tableaux des classes de niveau 1 à 7
        $classroomsLevel1 = [];
        $classroomsLevel2 = [];
        $classroomsLevel3 = [];
        $classroomsLevel4 = [];
        $classroomsLevel5 = [];
        $classroomsLevel6 = [];
        $classroomsLevel7 = [];

        // Tableau des meilleurs classes dans le classement
        $bestClassrooms = [];
        
        foreach ($classrooms as $classroom) 
        {
            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                // On repartit les classes par niveau
                switch ($classroom->getLevel()->getLevel()) 
                {
                    case 1:
                        $classroomsLevel1[] = $classroom;
                        break;

                        case 2:
                            $classroomsLevel2[] = $classroom;
                        break;

                        case 3:
                            $classroomsLevel3[] = $classroom;
                        break;

                        case 4:
                            $classroomsLevel4[] = $classroom;
                        break;

                        case 5:
                            $classroomsLevel5[] = $classroom;
                        break;

                        case 6:
                            $classroomsLevel6[] = $classroom;
                        break;

                        case 7:
                            $classroomsLevel7[] = $classroom;
                        break;
                }
            }else
            {
                // On repartit les classes par niveau
                switch ($classroom->getLevel()->getLevel()) 
                {
                    case 1:
                        $classroomsLevel1[] = $classroom;
                        break;

                        case 2:
                            $classroomsLevel2[] = $classroom;
                        break;

                        case 3:
                            $classroomsLevel3[] = $classroom;
                        break;

                        case 4:
                            $classroomsLevel4[] = $classroom;
                        break;

                        case 5:
                            $classroomsLevel5[] = $classroom;
                        break;

                        case 6:
                            $classroomsLevel6[] = $classroom;
                        break;

                        case 7:
                            $classroomsLevel7[] = $classroom;
                        break;
                }
            }
        }

        $pdf = new Pagination();

        $fontSize = 10;
        $cellHeaderHeight = 3;
        $cellTableHeight = 5.5;
        $cellTableClassroom = 25;
        $cellTableObservation = 26;
        $cellTablePresence = 36;
        $cellTablePresence3 = $cellTablePresence/3 ;

        // On définit le titre du trimestre
        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            if($term->getTerm() == 0)
            {
                $termTitle = 'ANNUEL';
            }else
            {
                $termTitle = 'TRIMESTRE '.$term->getTerm();
            }
        }else
        {
            if($term->getTerm() == 0)
            {
                $termTitle = 'ANNUAL';
            }else
            {
                $termTitle = 'TERM '.$term->getTerm();
            }
        }

        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

        $pdf->SetFillColor(200, 200, 200);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

        // Entête de la fiche
        $pdf->SetFont('Times', 'B', $fontSize+4);

        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            $pdf->Cell(190, 7, utf8_decode("ETAT D'ASSIDUITE DES ELEVES"), 0, 1, 'C');
        }else
        {
            $pdf->Cell(190, 7, utf8_decode("STUDENT ATTENDANCE STATUS"), 0, 1, 'C');
        }

        $pdf->Cell(0, 7, utf8_decode($termTitle), 0, 1, 'C');
        // $pdf->Cell(150, 7, utf8_decode($school->getFrenchName()), 0, 1, 'C');
        $pdf->Ln(3);

        // Entête du tableau
        $pdf = $this->getTableHeaderRateOfPresence($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence, $cellTableObservation, $cellTablePresence3, $subSystem);

        // Contenu du tableau

        // Tableau devant contenir les totaux par cycle et pour tout l'établissement
        $rateOfPresence = ['pdf' => $pdf, 
                            'termHoursBoysCycle' => 0,
                            'termHoursGirlsCycle' => 0,
                            'absencesBoysCycle' => 0,
                            'absencesGirlsCycle' => 0,
                            'termHoursBoysSchool' => 0,
                            'termHoursGirlsSchool' => 0,
                            'absencesBoysSchool' => 0,
                            'absencesGirlsSchool' => 0,
                            'bestClassrooms' => []
                            ];
        
        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            // Classe de niveau 1
            $rateOfPresence = $this->displayTableContent( $classroomsLevel1, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, '6ème');

            // Classe de niveau 2
            $rateOfPresence = $this->displayTableContent( $classroomsLevel2, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, '5ème');

            // Classe de niveau 3
            $rateOfPresence = $this->displayTableContent( $classroomsLevel3, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, '4ème');

            // Classe de niveau 4
            $rateOfPresence = $this->displayTableContent( $classroomsLevel4, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, '3ème');

            // Recapitulatif cycle 1
            $rateOfPresence = $this->displaySummary($rateOfPresence, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, $fontSize, 'cycle 1');
            
            $termHoursSummaryCycle1 =  $rateOfPresence['termHoursBoysCycle'] + $rateOfPresence['termHoursGirlsCycle'];
            $absenceSummaryCycle1 = $rateOfPresence['absencesBoysCycle'] + $rateOfPresence['absencesGirlsCycle'];

            // Classe de niveau 5
            $rateOfPresence = $this->displayTableContent( $classroomsLevel5, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, '2nde');

            // Classe de niveau 6
            $rateOfPresence = $this->displayTableContent( $classroomsLevel6, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, '1ère');

            // Classe de niveau 7
            $rateOfPresence = $this->displayTableContent( $classroomsLevel7, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'Tle');

            // Recapitulatif cycle 2
            $rateOfPresence = $this->displaySummary($rateOfPresence, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, $fontSize, 'cycle 2');
            
            $termHoursSummaryCycle2 =  $rateOfPresence['termHoursBoysCycle'] + $rateOfPresence['termHoursGirlsCycle'];
            $absenceSummaryCycle2 = $rateOfPresence['absencesBoysCycle'] + $rateOfPresence['absencesGirlsCycle'];

            //  recapitulatif de l'établissement
            $rateOfPresence = $this->displaySummary($rateOfPresence, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, $fontSize, 'étab.', false);

        }else
        {
            // Class of from 1
            $rateOfPresence = $this->displayTableContent( $classroomsLevel1, $rateOfPresence, $term, $fontSize,  $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'From 1');
            
            // Class of from 2
            $rateOfPresence = $this->displayTableContent( $classroomsLevel2, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'From 2');
           
            // Class of from 3
            $rateOfPresence = $this->displayTableContent( $classroomsLevel3, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'From 3');

            // Class of from 4
            $rateOfPresence = $this->displayTableContent( $classroomsLevel4, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'From 4');

            // Class of from 5
            $rateOfPresence = $this->displayTableContent( $classroomsLevel5, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'From 5');
            
            // Recapitulatif cycle 1
            $rateOfPresence = $this->displaySummary($rateOfPresence, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, $fontSize, 'cycle 1');
            
           
            $termHoursSummaryCycle1 =  $rateOfPresence['termHoursBoysCycle'] + $rateOfPresence['termHoursGirlsCycle'];
            $absenceSummaryCycle1 = $rateOfPresence['absencesBoysCycle'] + $rateOfPresence['absencesGirlsCycle'];

            // Class of lower 6
            $rateOfPresence = $this->displayTableContent($classroomsLevel6, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'Lower 6');
            
            // Class of Upper 6
            $rateOfPresence = $this->displayTableContent( $classroomsLevel7, $rateOfPresence, $term, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, 'Upper 6');
            
            // Recapitulatif cycle 2
            $rateOfPresence = $this->displaySummary($rateOfPresence, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, $fontSize, 'cycle 2');
          
            $termHoursSummaryCycle2 =  $rateOfPresence['termHoursBoysCycle'] + $rateOfPresence['termHoursGirlsCycle'];
            
            $absenceSummaryCycle2 = $rateOfPresence['absencesBoysCycle'] + $rateOfPresence['absencesGirlsCycle'];
            

            //  recapitulatif de l'établissement
            $rateOfPresence = $this->displaySummary($rateOfPresence, $cellTableClassroom, $cellTableHeight, $cellTablePresence3, $cellTableObservation, $fontSize, 'estab.', false);
        }

        $pdf =  $rateOfPresence['pdf'];
        $bestClassrooms = $rateOfPresence['bestClassrooms'];
        
        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
        $pdf->Image('images/school/'.$school->getFiligree(), 40, 90, -80); 

        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            // entête de la page de resumé
            $pdf->SetFont('Times', 'BU', $fontSize+4);
            $pdf->Cell(0, 7, utf8_decode('RESUME'), 0, 1, 'C');
            $pdf->Ln();

            // entête du tableau de resumé
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableClassroom , $cellTableHeight, 'Cycle', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Heures dues', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Heures faites', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Absences', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+1);
            $pdf->Cell($cellTablePresence , $cellTableHeight,  utf8_decode("Taux d'assiduité (%)"), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableObservation , $cellTableHeight, 'Observation', 1, 1, 'C', true);
        }else
        {
            // entête de la page de resumé
            $pdf->SetFont('Times', 'BU', $fontSize+4);
            $pdf->Cell(0, 7, utf8_decode('SUMMARY'), 0, 1, 'C');
            $pdf->Ln();

            // entête du tableau de resumé
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableClassroom , $cellTableHeight, 'Cycle', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Hours due', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Hours done', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Absences', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+1);
            $pdf->Cell($cellTablePresence , $cellTableHeight,  utf8_decode('Attendance rate (%)'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableObservation , $cellTableHeight, 'Observation', 1, 1, 'C', true);
        }
        // Contenu du tableau de resumé

        // Etablissement resumé
        if($school->isLycee() || !$school->isPublic())
        {
            $rate = $this->generalService->getFormatRatio($termHoursSummaryCycle1 + $termHoursSummaryCycle2 - $absenceSummaryCycle1 - $absenceSummaryCycle2, $termHoursSummaryCycle1 + $termHoursSummaryCycle2);
        }else
        {
            $rate = $this->generalService->getFormatRatio($termHoursSummaryCycle1 - $absenceSummaryCycle1, $termHoursSummaryCycle1);
        }
        $appreciation = $this->generalService->getApoAppreciation(((float)$rate)/5);
        
        $pdf->SetFont('Times', 'B', $fontSize+1);
        
        // Cycle 1 resumé
        $rate = $this->generalService->getFormatRatio($termHoursSummaryCycle1 - $absenceSummaryCycle1, $termHoursSummaryCycle1);
        $appreciation = $this->generalService->getApoAppreciation(((float)$rate)/5);

        switch($appreciation)
        {
            case constantsClass::FAIBLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::FAIBLE;
                }else
                {
                    $appreciation = constantsClass::WEAK;
                }
                break;

            case constantsClass::INSUFFISANT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::INSUFFISANT;
                }else
                {
                    $appreciation = constantsClass::INSUFFICIENT;
                }
                break;

            case constantsClass::MEDIOCRE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::MEDIOCRE;
                }else
                {
                    $appreciation = constantsClass::POOR;
                }
                break;

            case constantsClass::PASSABLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::PASSABLE;
                }else
                {
                    $appreciation = constantsClass::FAIR;
                }
                break;

            case constantsClass::ASSEZ_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::ASSEZ_BIEN;
                }else
                {
                    $appreciation = constantsClass::PRETTY_GOOD;
                }
                break;

            case constantsClass::BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::BIEN;
                }else
                {
                    $appreciation = constantsClass::GOOD;
                }
                break;

            case constantsClass::TRES_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::TRES_BIEN;
                }else
                {
                    $appreciation = constantsClass::ALRIGHT;
                }
                break;

            case constantsClass::EXCELLENT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::EXCELLENT;
                }else
                {
                    $appreciation = constantsClass::EXCELENT;
                }
                break;

            case constantsClass::PARFAIT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::PARFAIT;
                }else
                {
                    $appreciation = constantsClass::PERFECT;
                }
                break;
        }

        $pdf->Cell($cellTableClassroom , $cellTableHeight, 'Cycle 1', 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($termHoursSummaryCycle1, 0, '.', ' '), 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($termHoursSummaryCycle1 - $absenceSummaryCycle1, 0, '.', ' ') , 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($absenceSummaryCycle1, 0, '.', ' ') , 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, $rate, 1, 0, 'C');
        $pdf->Cell($cellTableObservation , $cellTableHeight, utf8_decode($appreciation), 1, 1, 'L');

        // Cycle 2 resumé
        $rate = $this->generalService->getFormatRatio($termHoursSummaryCycle2 - $absenceSummaryCycle2, $termHoursSummaryCycle2);
        
        $appreciation = $this->generalService->getApoAppreciation(((float)$rate)/5);
        
        switch($appreciation)
        {
            case constantsClass::FAIBLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::FAIBLE;
                }else
                {
                    $appreciation = constantsClass::WEAK;
                }
                break;

            case constantsClass::INSUFFISANT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::INSUFFISANT;
                }else
                {
                    $appreciation = constantsClass::INSUFFICIENT;
                }
                break;

            case constantsClass::MEDIOCRE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::MEDIOCRE;
                }else
                {
                    $appreciation = constantsClass::POOR;
                }
                break;

            case constantsClass::PASSABLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::PASSABLE;
                }else
                {
                    $appreciation = constantsClass::FAIR;
                }
                break;

            case constantsClass::ASSEZ_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::ASSEZ_BIEN;
                }else
                {
                    $appreciation = constantsClass::PRETTY_GOOD;
                }
                break;

            case constantsClass::BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::BIEN;
                }else
                {
                    $appreciation = constantsClass::GOOD;
                }
                break;

            case constantsClass::TRES_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::TRES_BIEN;
                }else
                {
                    $appreciation = constantsClass::ALRIGHT;
                }
                break;

            case constantsClass::EXCELLENT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::EXCELLENT;
                }else
                {
                    $appreciation = constantsClass::EXCELENT;
                }
                break;

            case constantsClass::PARFAIT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::PARFAIT;
                }else
                {
                    $appreciation = constantsClass::PERFECT;
                }
                break;
        }
        
        $pdf->Cell($cellTableClassroom , $cellTableHeight, 'Cycle 2', 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($termHoursSummaryCycle2, 0, '.', ' '), 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($termHoursSummaryCycle2 - $absenceSummaryCycle2, 0, '.', ' ') , 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($absenceSummaryCycle2, 0, '.', ' ') , 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, $rate, 1, 0, 'C');
        $pdf->Cell($cellTableObservation , $cellTableHeight, utf8_decode($appreciation), 1, 1, 'L');
        
        
        ///////////////total
        $pdf->Cell($cellTableClassroom , $cellTableHeight, 'Total', 1, 0, 'C');
        $pdf->SetFont('Times', 'B', $fontSize+2);
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($termHoursSummaryCycle1 + $termHoursSummaryCycle2, 0, '.', ' '), 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($termHoursSummaryCycle1 + $termHoursSummaryCycle2 - $absenceSummaryCycle1 - $absenceSummaryCycle2, 0, '.', ' ') , 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, number_format($absenceSummaryCycle1 + $absenceSummaryCycle2, 0, '.', ' ') , 1, 0, 'C');
        $pdf->Cell($cellTablePresence , $cellTableHeight, $rate, 1, 0, 'C');
        $pdf->Cell($cellTableObservation , $cellTableHeight, utf8_decode($appreciation), 1, 1, 'L');
        

        // Classes les plus assidues
        $pdf->Ln($cellTableHeight*2);
        $pdf->SetFont('Times', 'BU', $fontSize+4);

        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, 7, utf8_decode('CLASSES LES PLUS ASSIDUES'), 0, 1, 'C');
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);

            $pdf->Cell($cellTableClassroom*2);
            $pdf->Cell($cellTableClassroom*2 , $cellTableHeight, 'Classes', 1, 0, 'C', true);
            $pdf->Cell($cellTableClassroom*2 , $cellTableHeight, utf8_decode("Taux d'assiduité (%)"), 1, 1, 'C', true);
        }else
        {
            $pdf->Cell(0, 7, utf8_decode('MOST FREQUENTED CLASSES'), 0, 1, 'C');
            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize+2);

            $pdf->Cell($cellTableClassroom*2);
            $pdf->Cell($cellTableClassroom*2 , $cellTableHeight, 'Classes', 1, 0, 'C', true);
            $pdf->Cell($cellTableClassroom*2 , $cellTableHeight, utf8_decode('Attendance rate (%)'), 1, 1, 'C', true);
        }

        foreach ($bestClassrooms as $oneBest) 
        {
            $pdf->Cell($cellTableClassroom*2);
            $pdf->Cell($cellTableClassroom*2 , $cellTableHeight, utf8_decode($oneBest['classroom']->getClassroom()), 1, 0, 'L');
            $pdf->Cell($cellTableClassroom*2 , $cellTableHeight, utf8_decode($oneBest['rate']), 1, 1, 'C');
        }

        $pdf->Ln($cellTableHeight*6);
        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            $pdf->Cell(190 , $cellTableHeight, utf8_decode('Fait à '.$school->getPlace().' le _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ __ '), 0, 1, 'R');

            $pdf->Ln($cellTableHeight);

            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(150 , $cellTableHeight, utf8_decode("Le Proviseur"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(150 , $cellTableHeight, utf8_decode("Le Directeur"), 0, 1, 'R');
                }
            }else
            {
                $pdf->Cell(150 , $cellTableHeight, utf8_decode("Le Principal"), 0, 1, 'R');

            }

        }else
        {
            $pdf->Cell(190 , $cellTableHeight, utf8_decode('Done at '.$school->getPlace().' on _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ __ '), 0, 1, 'R');

            $pdf->Ln($cellTableHeight);

            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(150 , $cellTableHeight, utf8_decode("The Principal"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(150 , $cellTableHeight, utf8_decode("The Director"), 0, 1, 'R');
                }
            }else
            {
                $pdf->Cell(150 , $cellTableHeight, utf8_decode("The Principal"), 0, 1, 'R');

            }
        }

        $pdf->Cell(40 , $cellTableHeight, utf8_decode(''), 0, 1, 'R');


        return $pdf;
    }


    /**
     * ENTETE DU TABLEAU TAUX DE PRESENCE
     *
     * @param Pagination $pdf
     * @param integer $fontSize
     * @param integer $cellTableClassroom
     * @param integer $cellTableHeight
     * @param integer $cellTablePresence
     * @param integer $cellTableObservation
     * @param integer $cellTablePresence3
     * @return Pagination
     */
    public function getTableHeaderRateOfPresence(Pagination $pdf, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTablePresence, int $cellTableObservation, int $cellTablePresence3): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Times', 'B', $fontSize+2);
        $pdf->Cell($cellTableClassroom , $cellTableHeight*2, 'Classes', 1, 0, 'C', true);
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Heures dues', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Heures faites', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Absences', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+1);
            $pdf->Cell($cellTablePresence , $cellTableHeight,  utf8_decode("Taux d'assiduité (%)"), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableObservation , $cellTableHeight*2, 'Observation', 1, 1, 'C', true);


            $pdf->SetXY($x, $y + $cellTableHeight);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'F', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 1, 'C', true);

        }else
        {
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Hours due', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Hours done', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight, 'Absences', 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+1);
            $pdf->Cell($cellTablePresence , $cellTableHeight,  utf8_decode('Attendance rate (%)'), 1, 0, 'C', true);
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableObservation , $cellTableHeight*2, 'Observation', 1, 1, 'C', true);


            $pdf->SetXY($x, $y + $cellTableHeight);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'B', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'G', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence3 , $cellTableHeight, 'T', 1, 1, 'C', true);
        }

        return $pdf;
    }

    /**
     *  ligne de la fiche statistique d'assiduité des élèves
     *
     * @param array $classrooms
     * @param array $rateOfPresence
     * @param Term $term
     * @param integer $fontSize
     * @param integer $cellTableClassroom
     * @param integer $cellTableHeight
     * @param integer $cellTablePresence3
     * @param integer $cellTableObservation
     * @param string $levelName
     * @return array
     */
    public function displayTableContent(array $classrooms, array $rateOfPresence, Term $term, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTablePresence3, int $cellTableObservation, string $levelName): array
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $termHoursBoysLevel = 0;
        $termHoursGirlsLevel = 0;
        $absencesBoysLevel = 0;
        $absencesGirlsLevel = 0;
        
        foreach ($classrooms as $classroom) 
        {
            $rateOfPresence['pdf']->SetFont('Times', 'B', $fontSize);
            // Nombre d'heures prévues pour le trimestre(garçons)
            $termHoursBoys = $this->generalService->getNumberOfTermHours($classroom, 'M', $term->getTerm());

            // Nombre d'heures prévues pour le trimestre(filles)
            $termHoursGirls = $this->generalService->getNumberOfTermHours($classroom, 'F', $term->getTerm());

            $termHoursBoysLevel += $termHoursBoys;
            $termHoursGirlsLevel += $termHoursGirls;

            // Nombre d'heures d'absence pour le trimestre (garçons)
            $absencesBoys = $this->generalService->getNumberOfAbsences($term, $classroom, 'M');

            // Nombre d'heures d'absence pour le trimestre (filles)
            $absencesGirls =  $this->generalService->getNumberOfAbsences($term, $classroom, 'F');

            // On incrémente le total par niveau
            $absencesBoysLevel += $absencesBoys;
            $absencesGirlsLevel += $absencesGirls;

            // Taux d'assiduité (garçons)
            $rateBoys = $this->generalService->getFormatRatio($termHoursBoys - $absencesBoys, $termHoursBoys);
            // Taux d'assiduité (filles)
            $rateGirls = $this->generalService->getFormatRatio($termHoursGirls - $absencesGirls, $termHoursGirls);
            // Taux d'assiduité (total)
            $rateTotal = $this->generalService->getFormatRatio($termHoursBoys - $absencesBoys + $termHoursGirls - $absencesGirls, $termHoursBoys +$termHoursGirls);

            // Appreciation Par classe
            $appreciation = $this->generalService->getApoAppreciation(((float)$rateTotal)/5);

            switch($appreciation)
            {
                case constantsClass::FAIBLE:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::FAIBLE;
                    }else
                    {
                        $appreciation = constantsClass::WEAK;
                    }
                    break;

                case constantsClass::INSUFFISANT:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::INSUFFISANT;
                    }else
                    {
                        $appreciation = constantsClass::INSUFFICIENT;
                    }
                    break;

                case constantsClass::MEDIOCRE:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::MEDIOCRE;
                    }else
                    {
                        $appreciation = constantsClass::POOR;
                    }
                    break;

                case constantsClass::PASSABLE:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::PASSABLE;
                    }else
                    {
                        $appreciation = constantsClass::FAIR;
                    }
                    break;

                case constantsClass::ASSEZ_BIEN:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::ASSEZ_BIEN;
                    }else
                    {
                        $appreciation = constantsClass::PRETTY_GOOD;
                    }
                    break;

                case constantsClass::BIEN:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::BIEN;
                    }else
                    {
                        $appreciation = constantsClass::GOOD;
                    }
                    break;

                case constantsClass::TRES_BIEN:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::TRES_BIEN;
                    }else
                    {
                        $appreciation = constantsClass::ALRIGHT;
                    }
                    break;

                case constantsClass::EXCELLENT:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::EXCELLENT;
                    }else
                    {
                        $appreciation = constantsClass::EXCELENT;
                    }
                    break;

                case constantsClass::PARFAIT:
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $appreciation = constantsClass::PARFAIT;
                    }else
                    {
                        $appreciation = constantsClass::PERFECT;
                    }
                    break;
            }

            if((float)$rateTotal >= 98)
            {
                // Si te taux d'assiduité est à 100%, on classe la classe parmi les meilleures
                $rateOfPresence['bestClassrooms'][] = ['classroom' => $classroom, 'rate' => $rateTotal];
            }

            // On affiche la ligne correspondant à une classe

            // On adapte la taille de la police en fonction de la longueur du nom de la classe
            $classroomName = $classroom->getClassroom();
            if(strlen($classroomName) <= 12)
            {
                $rateOfPresence['pdf']->Cell($cellTableClassroom , $cellTableHeight, utf8_decode($classroom->getClassroom()), 1, 0, 'L');

            }elseif(strlen($classroomName) > 12 && strlen($classroomName) <= 15) 
            {
                $rateOfPresence['pdf']->SetFont('Times', 'B', $fontSize-1);
                $rateOfPresence['pdf']->Cell($cellTableClassroom , $cellTableHeight, utf8_decode($classroom->getClassroom()), 1, 0, 'L');
                $rateOfPresence['pdf']->SetFont('Times', 'B', $fontSize);
            }else
            {
                $rateOfPresence['pdf']->SetFont('Times', 'B', $fontSize-3.5);
                $rateOfPresence['pdf']->Cell($cellTableClassroom , $cellTableHeight, utf8_decode($classroom->getClassroom()), 1, 0, 'L');
                $rateOfPresence['pdf']->SetFont('Times', 'B', $fontSize);
            }

            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoys, 0, '.', ' '), 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursGirls, 0, '.', ' '), 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoys + $termHoursGirls, 0, '.', ' ') , 1, 0, 'C');

            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoys - $absencesBoys, 0, '.', ' ') , 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursGirls - $absencesGirls, 0, '.', ' ') , 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoys + $termHoursGirls - $absencesBoys - $absencesGirls, 0, '.', ' ') , 1, 0, 'C');

            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesBoys, 0, '.', ' ') , 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesGirls, 0, '.', ' ') , 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesBoys + $absencesGirls, 0, '.', ' ') , 1, 0, 'C');

            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateBoys, 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateGirls , 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateTotal, 1, 0, 'C');
            $rateOfPresence['pdf']->Cell($cellTableObservation , $cellTableHeight, utf8_decode($appreciation), 1, 1, 'L');
            
        }

        // On affiche le récapitulatif du niveau

        $rateBoysLevel = $this->generalService->getFormatRatio($termHoursBoysLevel - $absencesBoysLevel, $termHoursBoysLevel);
        $rateGirlsLevel = $this->generalService->getFormatRatio($termHoursGirlsLevel - $absencesGirlsLevel, $termHoursGirlsLevel);
        $rateTotalLevel = $this->generalService->getFormatRatio($termHoursBoysLevel - $absencesBoysLevel + $termHoursGirlsLevel - $absencesGirlsLevel, $termHoursBoysLevel +$termHoursGirlsLevel);
        
        //////appreciation pas niveau
        $appreciationLevel = $this->generalService->getApoAppreciation(((float)$rateTotalLevel)/5);

        switch($appreciationLevel)
        {
            case constantsClass::FAIBLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::FAIBLE;
                }else
                {
                    $appreciationLevel = constantsClass::WEAK;
                }
                break;

            case constantsClass::INSUFFISANT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::INSUFFISANT;
                }else
                {
                    $appreciationLevel = constantsClass::INSUFFICIENT;
                }
                break;

            case constantsClass::MEDIOCRE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::MEDIOCRE;
                }else
                {
                    $appreciationLevel = constantsClass::POOR;
                }
                break;

            case constantsClass::PASSABLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::PASSABLE;
                }else
                {
                    $appreciationLevel = constantsClass::FAIR;
                }
                break;

            case constantsClass::ASSEZ_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::ASSEZ_BIEN;
                }else
                {
                    $appreciationLevel = constantsClass::PRETTY_GOOD;
                }
                break;

            case constantsClass::BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::BIEN;
                }else
                {
                    $appreciationLevel = constantsClass::GOOD;
                }
                break;

            case constantsClass::TRES_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::TRES_BIEN;
                }else
                {
                    $appreciationLevel = constantsClass::ALRIGHT;
                }
                break;

            case constantsClass::EXCELLENT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::EXCELLENT;
                }else
                {
                    $appreciationLevel = constantsClass::EXCELENT;
                }
                break;

            case constantsClass::PARFAIT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciationLevel = constantsClass::PARFAIT;
                }else
                {
                    $appreciationLevel = constantsClass::PERFECT;
                }
                break;
        }

        $rateOfPresence['pdf']->Cell($cellTableClassroom , $cellTableHeight, utf8_decode('Total '.$levelName), 1, 0, 'L', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysLevel, 0, '.', ' '), 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursGirlsLevel, 0, '.', ' '), 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysLevel + $termHoursGirlsLevel, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysLevel - $absencesBoysLevel, 0, '.', ' ') , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursGirlsLevel - $absencesGirlsLevel, 0, '.', ' ') , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysLevel + $termHoursGirlsLevel - $absencesBoysLevel - $absencesGirlsLevel, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesBoysLevel, 0, '.', ' ') , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesGirlsLevel, 0, '.', ' ') , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesBoysLevel + $absencesGirlsLevel, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateBoysLevel, 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateGirlsLevel , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateTotalLevel, 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTableObservation , $cellTableHeight, utf8_decode($appreciationLevel), 1, 1, 'L', true);

        $rateOfPresence['termHoursBoysCycle'] += $termHoursBoysLevel;
        $rateOfPresence['termHoursGirlsCycle'] += $termHoursGirlsLevel;
        $rateOfPresence['absencesBoysCycle'] += $absencesBoysLevel;
        $rateOfPresence['absencesGirlsCycle'] += $absencesGirlsLevel;

        return $rateOfPresence;
    }

    /**
     * Affiche le total par cycle et établissement
     *
     * @param array $rateOfPresence
     * @param integer $cellTableClassroom
     * @param integer $cellTableHeight
     * @param integer $cellTablePresence3
     * @param integer $cellTableObservation
     * @param integer $fontSize
     * @param string $summaryName
     * @param boolean $cycle
     * @return array
     */
    public function displaySummary(array $rateOfPresence, int $cellTableClassroom, int $cellTableHeight, int $cellTablePresence3, int $cellTableObservation, int $fontSize, string $summaryName, bool $cycle = true): array
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        // Recapitulatif cycle
        if($cycle)
        {
            $termHoursBoysCycle = $rateOfPresence['termHoursBoysCycle'];
            $termHoursGirlsCycle = $rateOfPresence['termHoursGirlsCycle'];
            $absencesBoysCycle = $rateOfPresence['absencesBoysCycle'];
            $absencesGirlsCycle = $rateOfPresence['absencesGirlsCycle'];
        }else
        {
            $termHoursBoysCycle = $rateOfPresence['termHoursBoysSchool'];
            $termHoursGirlsCycle = $rateOfPresence['termHoursGirlsSchool'];
            $absencesBoysCycle = $rateOfPresence['absencesBoysSchool'];
            $absencesGirlsCycle = $rateOfPresence['absencesGirlsSchool'];
        }

        $rateBoys = $this->generalService->getFormatRatio($termHoursBoysCycle - $absencesBoysCycle, $termHoursBoysCycle);

        $rateGirls = $this->generalService->getFormatRatio($termHoursGirlsCycle - $absencesGirlsCycle, $termHoursGirlsCycle);

        $rateTotal = $this->generalService->getFormatRatio($termHoursBoysCycle - $absencesBoysCycle + $termHoursGirlsCycle - $absencesGirlsCycle, $termHoursBoysCycle + $termHoursGirlsCycle);


        /////////////apreciation cycle
        $appreciation = $this->generalService->getApoAppreciation(((float)$rateTotal)/5);

        switch($appreciation)
        {
            case constantsClass::FAIBLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::FAIBLE;
                }else
                {
                    $appreciation = constantsClass::WEAK;
                }
                break;

            case constantsClass::INSUFFISANT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::INSUFFISANT;
                }else
                {
                    $appreciation = constantsClass::INSUFFICIENT;
                }
                break;

            case constantsClass::MEDIOCRE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::MEDIOCRE;
                }else
                {
                    $appreciation = constantsClass::POOR;
                }
                break;

            case constantsClass::PASSABLE:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::PASSABLE;
                }else
                {
                    $appreciation = constantsClass::FAIR;
                }
                break;

            case constantsClass::ASSEZ_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::ASSEZ_BIEN;
                }else
                {
                    $appreciation = constantsClass::PRETTY_GOOD;
                }
                break;

            case constantsClass::BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::BIEN;
                }else
                {
                    $appreciation = constantsClass::GOOD;
                }
                break;

            case constantsClass::TRES_BIEN:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::TRES_BIEN;
                }else
                {
                    $appreciation = constantsClass::ALRIGHT;
                }
                break;

            case constantsClass::EXCELLENT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::EXCELLENT;
                }else
                {
                    $appreciation = constantsClass::EXCELENT;
                }
                break;

            case constantsClass::PARFAIT:
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $appreciation = constantsClass::PARFAIT;
                }else
                {
                    $appreciation = constantsClass::PERFECT;
                }
                break;
        }

        if($cycle)
        {
            $rateOfPresence['pdf']->Cell($cellTableClassroom , $cellTableHeight, utf8_decode('Total '.$summaryName), 1, 0, 'L', true);
            
        }else
        {
            $rateOfPresence['pdf']->SetFont('Times', 'B', $fontSize-2);
            $rateOfPresence['pdf']->Cell($cellTableClassroom , $cellTableHeight, utf8_decode('Total '.$summaryName), 1, 0, 'L', true);
            $rateOfPresence['pdf']->SetFont('Times', 'B', $fontSize);
        }

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysCycle, 0, '.', ' '), 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursGirlsCycle, 0, '.', ' '), 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysCycle + $termHoursGirlsCycle, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysCycle - $absencesBoysCycle, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursGirlsCycle - $absencesGirlsCycle, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($termHoursBoysCycle + $termHoursGirlsCycle - $absencesBoysCycle - $absencesGirlsCycle, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesBoysCycle, 0, '.', ' ') , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesGirlsCycle, 0, '.', ' ') , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, number_format($absencesBoysCycle + $absencesGirlsCycle, 0, '.', ' ') , 1, 0, 'C', true);

        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateBoys, 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateGirls , 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTablePresence3 , $cellTableHeight, $rateTotal, 1, 0, 'C', true);
        $rateOfPresence['pdf']->Cell($cellTableObservation , $cellTableHeight, utf8_decode($appreciation), 1, 1, 'L', true);

        $rateOfPresence['termHoursBoysSchool'] += $termHoursBoysCycle;
        $rateOfPresence['termHoursGirlsSchool'] += $termHoursGirlsCycle;
        $rateOfPresence['absencesBoysSchool'] += $absencesBoysCycle;
        $rateOfPresence['absencesGirlsSchool'] += $absencesGirlsCycle;


        return $rateOfPresence;
    }

    /**
     * RECAP DES DELIBERATION
     *
     * @param array $classrooms
     * @param School $school
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @return Pagination
     */
    public function printDeliberationRecapList(array $classrooms, School $school, SchoolYear $schoolYear, SubSystem $subSystem): Pagination
    {
        $classroomsLevel1 = [];
        $classroomsLevel2 = [];
        $classroomsLevel3 = [];
        $classroomsLevel4 = [];
        $classroomsLevel5 = [];
        $classroomsLevel6 = [];
        $classroomsLevel7 = [];

        foreach ($classrooms as $classroom) 
        {
           switch ($classroom->getLevel()->getLevel()) 
           {
               case 1:
                   $classroomsLevel1[] = $classroom;
                break;

                case 2:
                    $classroomsLevel2[] = $classroom;
                break;

                case 3:
                    $classroomsLevel3[] = $classroom;
                break;

                case 4:
                    $classroomsLevel4[] = $classroom;
                break;

                case 5:
                    $classroomsLevel5[] = $classroom;
                break;

                case 6:
                    $classroomsLevel6[] = $classroom;
                break;

                case 7:
                    $classroomsLevel7[] = $classroom;
                break;
           }
        }

        $pdf = new Pagination();

        $fontSize = 10;
        $cellHeaderHeight2 = 8;
        $cellTableHeight = 6;
        $cellTableClassroom = 40;
        $cellTableDeliberation = 48;
        $cellTableDeliberation3 = $cellTableDeliberation/3 ;

        // On insère une page
        $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

        // on rempli l'entête administrative
        $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);

        // Entête de la fiche
        $pdf->SetFont('Times', 'B', $fontSize+3);
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, 7, utf8_decode('ETAT DES DELIBERATIONS'), 0, 1, 'C');
        }else
        {
            $pdf->Cell(0, 7, utf8_decode('STATUS OF DELIBERATIONS'), 0, 1, 'C');
        }
        
        // $pdf->Cell(40, 7, utf8_decode($termTitle), 0, 0, 'C');
        // $pdf->Cell(0, 7, utf8_decode($school->getFrenchName()), 0, 1, 'C');
        $pdf->Ln(3);

        // Entête du tableau
        $pdf = $this->getTableHeaderDeliberationRecap($pdf, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation, $cellTableDeliberation3, $subSystem);

        $deliberationRecap = [
                            'boysLevel' => 0,
                            'girlsLevel' => 0,
                            'boysCycle' => 0,
                            'girlsCycle' => 0,
                            'boysSchool' => 0,
                            'girlsSchool' => 0,
                            
                            'passedBoysLevel' => 0,
                            'passedGirlsLevel' => 0,
                            'passedBoysCycle' => 0,
                            'passedGirlsCycle' => 0,
                            'passedBoysSchool' => 0,
                            'passedGirlsSchool' => 0,
                            
                            'repeatedBoysLevel' => 0,
                            'repeatedGirlsLevel' => 0,
                            'repeatedBoysCycle' => 0,
                            'repeatedGirlsCycle' => 0,
                            'repeatedBoysSchool' => 0,
                            'repeatedGirlsSchool' => 0,
                            
                            'expelledBoysLevel' => 0,
                            'expelledGirlsLevel' => 0,
                            'expelledBoysCycle' => 0,
                            'expelledGirlsCycle' => 0,
                            'expelledBoysSchool' => 0,
                            'expelledGirlsSchool' => 0,

                            'resignedBoysLevel' => 0,
                            'resignedGirlsLevel' => 0,
                            'resignedBoysCycle' => 0,
                            'resignedGirlsCycle' => 0,
                            'resignedBoysSchool' => 0,
                            'resignedGirlsSchool' => 0

                            ];

        // // Contenu du tableau

        // Classe de niveau 1
        foreach ($classroomsLevel1 as $classroom) 
        {
           $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
        }

        // Somme du niveau 1
        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
        {
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LEVEL_1, 1);
        
            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 2
            foreach ($classroomsLevel2 as $classroom) 
            {
                $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 2
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LEVEL_2, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 3
            foreach ($classroomsLevel3 as $classroom) 
            {
                $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 3
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LEVEL_3, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 4
            foreach ($classroomsLevel4 as $classroom) 
            {
                $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 4
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LEVEL_4, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Somme Cycle 1
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::CYCLE_1, 0, 1);

            $this->resetDeliberationRecap($deliberationRecap, 0, 1);
            

            // Classe de niveau 5
            foreach ($classroomsLevel5 as $classroom) 
            {
                $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 5
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LEVEL_5, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 6
            foreach ($classroomsLevel6 as $classroom) 
            {
            $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 6
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LEVEL_6, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 7
            foreach ($classroomsLevel7 as $classroom) 
            {
            $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 7
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LEVEL_7, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Somme Cycle 2
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::CYCLE_2, 0, 1);

            $this->resetDeliberationRecap($deliberationRecap, 0, 1);

            // Somme Etablissement
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::SCHOOL_SUMMARY, 0, 0, 1);

            $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);

        }else
        {
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::FROM_1, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 2
            foreach ($classroomsLevel2 as $classroom) 
            {
           $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 2
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::FROM_2, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 3
            foreach ($classroomsLevel3 as $classroom) 
            {
            $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 3
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::FROM_3, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 4
            foreach ($classroomsLevel4 as $classroom) 
            {
            $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 4
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::FROM_4, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);


            // Classe de niveau 5
            foreach ($classroomsLevel5 as $classroom) 
            {
            $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 5
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::FROM_5, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Somme Cycle 1
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::CYCLE_1, 0, 1);

            $this->resetDeliberationRecap($deliberationRecap, 0, 1);

            // Classe de niveau 6
            foreach ($classroomsLevel6 as $classroom) 
            {
                $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 6
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::LOWER_6, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Classe de niveau 7
            foreach ($classroomsLevel7 as $classroom) 
            {
                $pdf = $this->displayDeliberationRecapContent($pdf, $classroom,  $deliberationRecap, $fontSize, $cellTableClassroom,  $cellTableHeight, $cellTableDeliberation3);
            }

            // Somme du niveau 7
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::UPPER_6, 1);

            $this->resetDeliberationRecap($deliberationRecap, 1);

            // Somme Cycle 2
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::CYCLE_2, 0, 1);

            $this->resetDeliberationRecap($deliberationRecap, 0, 1);

            // Somme Etablissement
            $pdf = $this->displaySummaryDeliberationRecapContent($pdf, $deliberationRecap, $fontSize, $cellTableClassroom, $cellTableHeight, $cellTableDeliberation3, 'Total '.ConstantsClass::SCHOOL, 0, 0, 1);

            $pdf = $this->generalService->doAtPagination($pdf, $school, $cellHeaderHeight2);
        }

        return $pdf;
    }

    public function displayDeliberationRecapContent(Pagination $pdf, Classroom $classroom, array &$deliberationRecap, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTableDeliberation3, bool $fill = false): Pagination
    {

        $boys = $this->generalService->getNumberOfBoys($classroom);
        $girls = $this->generalService->getNumberOfGirls($classroom);

        $passedBoys = $this->generalService->getNumberOfPassedBoys($classroom);
        $passedGirls = $this->generalService->getNumberOfPassedGirls($classroom);

        $repeatedBoys = $this->generalService->getNumberOfRepeatedBoys($classroom);
        $repeatedGirls = $this->generalService->getNumberOfRepeatedGirls($classroom);

        $expelledBoys = $this->generalService->getNumberOfExpelledBoys($classroom);
        $expelledGirls = $this->generalService->getNumberOfExpelledGirls($classroom);

        $resignedBoys = $this->generalService->getNumberOfResignedBoys($classroom);
        $resignedGirls = $this->generalService->getNumberOfResignedGirls($classroom);

        $boysRate = $this->generalService->getFormatRatio($passedBoys, $boys);
        $girlsRate = $this->generalService->getFormatRatio($passedGirls, $girls);
        $totalRate = $this->generalService->getFormatRatio($passedGirls + $passedBoys, $girls + $boys);

        $deliberationRecap['boysLevel'] +=  $boys;
        $deliberationRecap['girlsLevel'] +=  $girls;
        $deliberationRecap['boysCycle'] +=  $boys;
        $deliberationRecap['girlsCycle'] +=  $girls;
        $deliberationRecap['boysSchool'] +=  $boys;
        $deliberationRecap['girlsSchool'] +=  $girls;

        $deliberationRecap['passedBoysLevel'] +=  $passedBoys;
        $deliberationRecap['passedGirlsLevel'] +=  $passedGirls;
        $deliberationRecap['passedBoysCycle'] +=  $passedBoys;
        $deliberationRecap['passedGirlsCycle'] +=  $passedGirls;
        $deliberationRecap['passedBoysSchool'] +=  $passedBoys;
        $deliberationRecap['passedGirlsSchool'] +=  $passedGirls;

        $deliberationRecap['repeatedBoysLevel'] +=  $repeatedBoys;
        $deliberationRecap['repeatedGirlsLevel'] +=  $repeatedGirls;
        $deliberationRecap['repeatedBoysCycle'] +=  $repeatedBoys;
        $deliberationRecap['repeatedGirlsCycle'] +=  $repeatedGirls;
        $deliberationRecap['repeatedBoysSchool'] +=  $repeatedBoys;
        $deliberationRecap['repeatedGirlsSchool'] +=  $repeatedGirls;

        $deliberationRecap['expelledBoysLevel'] +=  $expelledBoys;
        $deliberationRecap['expelledGirlsLevel'] +=  $expelledGirls;
        $deliberationRecap['expelledBoysCycle'] +=  $expelledBoys;
        $deliberationRecap['expelledGirlsCycle'] +=  $expelledGirls;
        $deliberationRecap['expelledBoysSchool'] +=  $expelledBoys;
        $deliberationRecap['expelledGirlsSchool'] +=  $expelledGirls;

        $deliberationRecap['resignedBoysLevel'] +=  $resignedBoys;
        $deliberationRecap['resignedGirlsLevel'] +=  $resignedGirls;
        $deliberationRecap['resignedBoysCycle'] +=  $resignedBoys;
        $deliberationRecap['resignedGirlsCycle'] +=  $resignedGirls;
        $deliberationRecap['resignedBoysSchool'] +=  $resignedBoys;
        $deliberationRecap['resignedGirlsSchool'] +=  $resignedGirls;

        $pdf->SetFont('Times', 'B', $fontSize);
        $pdf->Cell($cellTableClassroom-5 , $cellTableHeight, utf8_decode($classroom->getClassroom()), 1, 0, 'L', $fill);
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($boys, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($girls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode(str_pad($boys + $girls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);

        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($passedBoys, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($passedGirls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($passedBoys + $passedGirls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);

        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($repeatedBoys, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($repeatedGirls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($repeatedBoys + $repeatedGirls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);

        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($expelledBoys, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($expelledGirls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($expelledBoys + $expelledGirls, 2, '0', STR_PAD_LEFT)), 1, 0, 'C', $fill);
        
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($resignedBoys, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($resignedGirls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($resignedBoys + $resignedGirls, 2, '0', STR_PAD_LEFT)), "TLB", 0, 'C', $fill);

        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($boysRate), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($girlsRate), "TLB", 0, 'C', $fill);
        $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode($totalRate), 1, 1, 'C', $fill);

        return $pdf;
    }

    public function getTableHeaderDeliberationRecap(Pagination $pdf, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTableDeliberation, int $cellTableDeliberation3, SubSystem $subSystem): Pagination
    {
        $pdf->SetFont('Times', 'B', $fontSize);

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell($cellTableClassroom-5 , $cellTableHeight*2, 'Classes', 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Effectifs classés'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Promus'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Redoublants'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Exclus'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Démissionnaires'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7, $cellTableHeight,  utf8_decode('Taux de réussite (%)'), 1, 0, 'C', true);

            $pdf->SetXY($x, $y + $cellTableHeight);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', 1, 1, 'C', true);
        }else
        {
            $pdf->Cell($cellTableClassroom-5 , $cellTableHeight*2, 'Classes', 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Classified workforce'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Promote'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Repeaters'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Excluded'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight, utf8_decode('Resigned'), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation-7 , $cellTableHeight,  utf8_decode('Success rate (%)'), 1, 0, 'C', true);

            $pdf->SetXY($x, $y + $cellTableHeight);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', "TLB", 0, 'C', true);

            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'G', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'F', "TLB", 0, 'C', true);
            $pdf->Cell(($cellTableDeliberation-7)/3 , $cellTableHeight, 'T', 1, 1, 'C', true);
        }
        

        return $pdf;
    }


    public function displaySummaryDeliberationRecapContent(Pagination $pdf, array &$deliberationRecap, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTableDeliberation3, string $title, int $levelSummary = 0, int $cycleSummary = 0, int $schoolSummary = 0): Pagination
    {
        if($levelSummary)
        {
            $boysRate = $this->generalService->getFormatRatio($deliberationRecap['passedBoysLevel'], $deliberationRecap['boysLevel']);
            $girlsRate = $this->generalService->getFormatRatio($deliberationRecap['passedGirlsLevel'], $deliberationRecap['girlsLevel']);
            $totalRate = $this->generalService->getFormatRatio($deliberationRecap['passedGirlsLevel'] + $deliberationRecap['passedBoysLevel'], $deliberationRecap['girlsLevel'] + $deliberationRecap['boysLevel']);
    
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->Cell($cellTableClassroom-5 , $cellTableHeight, utf8_decode($title), 1, 0, 'L', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['boysLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['girlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['boysLevel'] + $deliberationRecap['girlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['passedBoysLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['passedGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['passedBoysLevel'] + $deliberationRecap['passedGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['repeatedBoysLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['repeatedGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['repeatedBoysLevel'] + $deliberationRecap['repeatedGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['expelledBoysLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['expelledGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['expelledBoysLevel'] + $deliberationRecap['expelledGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['resignedBoysLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['resignedGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['resignedBoysLevel'] + $deliberationRecap['resignedGirlsLevel'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);

            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($boysRate), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($girlsRate), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode($totalRate), 1, 1, 'C', true);
        }
        elseif($cycleSummary)
        {
            $boysRate = $this->generalService->getFormatRatio($deliberationRecap['passedBoysCycle'], $deliberationRecap['boysCycle']);
            $girlsRate = $this->generalService->getFormatRatio($deliberationRecap['passedGirlsCycle'], $deliberationRecap['girlsCycle']);
            $totalRate = $this->generalService->getFormatRatio($deliberationRecap['passedGirlsCycle'] + $deliberationRecap['passedBoysCycle'], $deliberationRecap['girlsCycle'] + $deliberationRecap['boysCycle']);
    
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->Cell($cellTableClassroom-5 , $cellTableHeight, utf8_decode($title), 1, 0, 'L', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['boysCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['girlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['boysCycle'] + $deliberationRecap['girlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['passedBoysCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['passedGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['passedBoysCycle'] + $deliberationRecap['passedGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['repeatedBoysCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['repeatedGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['repeatedBoysCycle'] + $deliberationRecap['repeatedGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['expelledBoysCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3, $cellTableHeight, utf8_decode(str_pad($deliberationRecap['expelledGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['expelledBoysCycle'] + $deliberationRecap['expelledGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['resignedBoysCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3, $cellTableHeight, utf8_decode(str_pad($deliberationRecap['resignedGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['resignedBoysCycle'] + $deliberationRecap['resignedGirlsCycle'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($boysRate), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($girlsRate), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode($totalRate), 1, 1, 'C', true);
            
        }else
        {
            $boysRate = $this->generalService->getFormatRatio($deliberationRecap['passedBoysSchool'], $deliberationRecap['boysSchool']);
            $girlsRate = $this->generalService->getFormatRatio($deliberationRecap['passedGirlsSchool'], $deliberationRecap['girlsSchool']);
            $totalRate = $this->generalService->getFormatRatio($deliberationRecap['passedGirlsSchool'] + $deliberationRecap['passedBoysSchool'], $deliberationRecap['girlsSchool'] + $deliberationRecap['boysSchool']);
    
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->Cell($cellTableClassroom-5 , $cellTableHeight, utf8_decode($title), 1, 0, 'L', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['boysSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['girlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['boysSchool'] + $deliberationRecap['girlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['passedBoysSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['passedGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['passedBoysSchool'] + $deliberationRecap['passedGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['repeatedBoysSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['repeatedGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['repeatedBoysSchool'] + $deliberationRecap['repeatedGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['expelledBoysSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['expelledGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['expelledBoysSchool'] + $deliberationRecap['expelledGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['resignedBoysSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode(str_pad($deliberationRecap['resignedGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight,  utf8_decode(str_pad($deliberationRecap['resignedBoysSchool'] + $deliberationRecap['resignedGirlsSchool'], 2, '0', STR_PAD_LEFT)), 1, 0, 'C', true);
            
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($boysRate), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.3 , $cellTableHeight, utf8_decode($girlsRate), 1, 0, 'C', true);
            $pdf->Cell($cellTableDeliberation3-2.4 , $cellTableHeight, utf8_decode($totalRate), 1, 1, 'C', true);
        }
       

        return $pdf;
    }

    public function resetDeliberationRecap(array &$deliberationRecap, int $resetLevel = 0, int $resetCycle = 0)
    {
        if($resetLevel)
        {
            $deliberationRecap['boysLevel'] = 0;
            $deliberationRecap['girlsLevel'] = 0;

            $deliberationRecap['passedBoysLevel']  = 0;
            $deliberationRecap['passedGirlsLevel']  = 0;

            $deliberationRecap['repeatedBoysLevel']  = 0;
            $deliberationRecap['repeatedGirlsLevel']  = 0;

            $deliberationRecap['expelledBoysLevel']  = 0;
            $deliberationRecap['expelledGirlsLevel']  = 0;

            $deliberationRecap['resignedBoysLevel']  = 0;
            $deliberationRecap['resignedGirlsLevel']  = 0;

        }elseif($resetCycle)
        {
            $deliberationRecap['boysCycle'] = 0;
            $deliberationRecap['girlsCycle'] = 0;

            $deliberationRecap['passedBoysCycle']  = 0;
            $deliberationRecap['passedGirlsCycle']  = 0;

            $deliberationRecap['repeatedBoysCycle']  = 0;
            $deliberationRecap['repeatedGirlsCycle']  = 0;

            $deliberationRecap['expelledBoysCycle']  = 0;
            $deliberationRecap['expelledGirlsCycle']  = 0;

            $deliberationRecap['resignedBoysCycle']  = 0;
            $deliberationRecap['resignedGirlsCycle']  = 0;
        }
        
    }

    /////////////////////////

     /**
     * Entête du tablea de la fiche de synthese pedagogique
     *
     * @param Pagination $pdf
     * @param string $firstColum
     * @return Pagination
     */
    public function pedagogicalSummarySheetTableHeader(Pagination $pdf)
    {
        $couvertureProgramme = 75;

        $pdf->SetFont('Times', 'B', 9);
        $pdf->Cell($couvertureProgramme-47, 5*4, utf8_decode("CLASSES"), 1, 0, 'C', true);
        // $pdf->Cell(30, 5*2.5, utf8_decode("Nom de"), 1, 0, 'C', true);
        $pdf->Cell($couvertureProgramme-30, 5, utf8_decode("TAUX DE COUVERTURE"), 'LTR', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("NOMBRE D'HEURES"), 'LTR', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("NOMBRE D'HEURES"), 'LTR', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("TAUX DE REUSSITE"), 'LTR', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("TAUX D'ASSIDUITE"), 'LTR', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("TAUX D'ASSIDUITE"), 'LTR', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode(""), 'LTR', 1, 'C', true);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->SetXY($x+28, $y);
        $pdf->Cell($couvertureProgramme-30, 5, utf8_decode("DES PROGRAMMES"), 'LRB', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("PREVUES"), 'LRB', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("FAITES"), 'LRB', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("DES ELEVES"), 'LRB', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("DES ELEVES"), 'LRB', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode("DES ENSEIGNANTS"), 'LRB', 0, 'C', true);
        $pdf->Cell($couvertureProgramme-40, 5, utf8_decode(""), 'LR', 1, 'C', true);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->SetXY($x+28, $y);
        // $pdf->Cell(30, 5*2.5, utf8_decode("de l'enseignant"), 1, 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-30)/2), 5, utf8_decode("TRIM"), 'LR', 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-30)/2), 5, utf8_decode("AN"), 'LR', 0, 'C', true);

        $pdf->Cell((($couvertureProgramme-40)/2), 5, utf8_decode("TRIM"), 'LR', 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-40)/2), 5, utf8_decode("AN"), 'LR', 0, 'C', true);

        $pdf->Cell((($couvertureProgramme-40)/2), 5, utf8_decode("TRIM"), 'LR', 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-40)/2), 5, utf8_decode("AN"), 'LR', 0, 'C', true);

        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("G"), 'LR', 0, 'C', true);
        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("F"), 'LR', 0, 'C', true);
        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("T"), 'LR', 0, 'C', true);

        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("G"), 'LR', 0, 'C', true);
        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("F"), 'LR', 0, 'C', true);
        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("T"), 'LR', 0, 'C', true);

        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("G"), 'LR', 0, 'C', true);
        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("F"), 'LR', 0, 'C', true);
        $pdf->Cell(($couvertureProgramme-40)/3, 5*2, utf8_decode("T"), 'LR', 0, 'C', true);

        $pdf->Cell(($couvertureProgramme-40), 5*2, utf8_decode("OBS"), 'LR', 1, 'C', true);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->SetXY($x, $y-5);
        $pdf->Cell($couvertureProgramme-47, 5, utf8_decode(""), 'L', 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-30)/2)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-30)/2)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);

        $pdf->Cell((($couvertureProgramme-30)/2)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-30)/2)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);

        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);

        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);

        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Prat"), 1, 0, 'C', true);

        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Theo"), 1, 0, 'C', true);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, 5, utf8_decode("Prat"), 1, 1, 'C', true);

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        return $pdf;
    }

    /////LIGNE DE MON TABLEAU DE LA FICHE DE SYNTHESE PEDAGOGIQUE
    public function pedagogicalSummarySheetTableRow(Pagination $pdf, ClassroomStatisticSlipRow $row, array $lessons, bool $fill = false): Pagination
    {
        $couvertureProgramme = 75;
        $cellHeigh0 = 4;

        if(strlen($row->getSubject()) <= 15)
        {
            $pdf->Cell($couvertureProgramme-47, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);

        }elseif(strlen($row->getSubject()) <= 20)
        {
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell($couvertureProgramme-47, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }else
        {
            $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($couvertureProgramme-47, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L', $fill);
            $pdf->SetFont('Times', 'B', 9);
        }


        $pdf->SetFont('Times', 'B', 9);

        ///// TAUX DE COUVERTURE DES PROGRAMMES
        ///////TRIM
        $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, "22", 1, 0, 'C', $fill);
        $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);

        ///////AN
        $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);
        $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);
        
        ///// NOMBRE D'HEURES PREVUES
        //////TTRIM
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);

        ///////AN
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);

        ///// NOMBRE D'HEURES FAITES
        //////TTRIM
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);

        ///////AN
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);
        $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C', $fill);

        //////////////TAUX DE REUSSITE DES ELEVES
        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedBoys()*100, $row->getComposedBoys())), 1, 0, 'C', $fill);

        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedGirls()*100, $row->getComposedGirls())), 1, 0, 'C', $fill);

        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 0, 'C', $fill);

        

        ///////////////////TAUX D'ASSIDUITE DES ELEVES
        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getComposedBoys()*100, $row->getRegisteredBoys())), 1, 0, 'C', $fill);

        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0,  $this->generalService->formatMark($this->generalService->getRatio($row->getComposedGirls()*100, $row->getRegisteredGirls())), 1, 0, 'C', $fill);

        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls()))), 1, 0, 'C', $fill);

        //////////////TAUX D'ASSIDUITE DES ENSEIGNANTS
        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);

        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);

        $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, "00", 1, 0, 'C', $fill);

        //////////////OBSERVATIONS
        $pdf->Cell(($couvertureProgramme-40), $cellHeigh0, "", 1, 0, 'C', $fill);
        $pdf->Ln(); 
        
        return $pdf;
    }


    /**
     * Imprime la fiche de synthese pedagogique
     *
     * @param array $classroomStatisticSlipPerSubjects
     * @param string $firstPeriodLetter
     * @param integer $idP
     * @param School $school
     * @param SchoolYear $schoolYear
     * @param array $nbreClasseParCycles
     * @return Pagination
     */
    public function printPedagogicalSummarySheet(array $classroomStatisticSlipPerSubjects, string $firstPeriodLetter, int $idP, School $school, SchoolYear $schoolYear, array $lessons): Pagination
    {
        $cellHeigh0 = 4;
        $couvertureProgramme = 75;

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($firstPeriodLetter === 't')
        {
            $termName = 'TRIMESTRE '.$this->termRepository->find($idP)->getTerm();

        }elseif($firstPeriodLetter === 's')
        {
            $termName = 'EVALUATION '.$this->sequenceRepository->find($idP)->getSequence();
        }else
        {
            $termName = 'ANNUEL';

        }

        $pdf = new Pagination();

        if(empty($classroomStatisticSlipPerSubjects))
        {
            // Oninsère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Impression du document impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que la matière soit dispensée dans au moins une classe'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que les notes de cette matière soient saisies'), 0, 1, 'C');

            }else
            {
                $pdf->SetFont('Times', '', 20);
                $pdf->Cell(0, 10, utf8_decode('Unable to print document !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the subject is taught in at least one class'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure the notes for this subject are entered'), 0, 1, 'C');
            }
            return $pdf;
        }

        foreach($classroomStatisticSlipPerSubjects as $statistics)
        {
            $pageCounter = 0;
            $rowCounter = 0;

            // Oninsère une page
            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pageCounter++;

            // on rempli l'entête administrative
            $pdf = $this->generalService->statisticAdministrativeHeaderPagination($pdf, $school, $schoolYear);
            
            $pdf->Ln(2);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            // Logo de l'établissement
            $pdf->Image('images/school/'.$school->getLogo(), 140, 7, -170);
            $pdf->Image('images/school/'.$school->getFiligree(), 80, 49, -900);  
            $pdf->setXY($x, $y);

            // Entête de la fiche
            if(!empty($statistics[0]))
                $discipline = $statistics[0][0]->getTitle();
            elseif(!empty($statistics[1]))
                $discipline = $statistics[1][0]->getTitle();
            else
                $discipline = "";

            // Entête de la fiche
            // $pdf = $this->generalService->staisticSlipHeader($pdf, 'FICHE DE COLLECTE DE DONNEES', $termName, $school,  'Discipline', $discipline);
            $pdf->SetFont('Times', 'B', 15);
            $pdf->Cell(0, 6, utf8_decode("FICHE DE SYNTHESE PEDAGOGIQUE"), 0, 1, 'C');
            $pdf->Cell(150, 6, utf8_decode("DEPARTEMENT : ".$discipline), 0, 0, 'C');
            $pdf->Cell(150, 6, utf8_decode("TRIMESTRE N° : ".$termName), 0, 1, 'C');
            // $pdf->Cell(150, 6, utf8_decode("CYCLE : 1 "), 0, 0, 'C');
            $pdf->Ln(5);

            // Entête du tableau
            // $pdf = $this->statisticTableHeader($pdf, 'Classes');
            $pdf = $this->pedagogicalSummarySheetTableHeader($pdf);

            // Contenu du tableau
            $rowSchool = new ClassroomStatisticSlipRow();
            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                $rowSchool->setSubject('Totaux Etablissement');
            }else
            {
                $rowSchool->setSubject('Totals Establihsment');
            }
            $counterSchool = 0;

            // Cycle 1
            if(!empty($statistics[0]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totaux Cycle 1');
                $counter = 0;

                foreach($statistics[0] as $row)
                {
                    // une ligne du tableau
                    //$pdf = $this->pedagogicalSummarySheetTableRow($pdf, $row, $lessons, false);
                    $couvertureProgramme = 75;
                    $cellHeigh0 = 4;
            
                    if(strlen($row->getSubject()) <= 15)
                    {
                        $pdf->Cell($couvertureProgramme-47, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L');
            
                    }elseif(strlen($row->getSubject()) <= 20)
                    {
                        $pdf->SetFont('Times', 'B', 8);
                        $pdf->Cell($couvertureProgramme-47, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', 9);
                    }else
                    {
                        $pdf->SetFont('Times', 'B', 5);
                        $pdf->Cell($couvertureProgramme-47, $cellHeigh0, utf8_decode($row->getSubject()), 1, 0, 'L');
                        $pdf->SetFont('Times', 'B', 9);
                    }
            
            
                    $pdf->SetFont('Times', 'B', 9);
            
                    ///// TAUX DE COUVERTURE DES PROGRAMMES
                    ///////TRIM
                    foreach($lessons as $lesson)
                    {
                        if(($lesson->getClassroom()->getClassroom() == $row->getSubject()) && ($lesson->getSubject()->getSubject() == $row->getTitle()) )
                        {
                            switch($idP)
                            {
                                case 1:
                                    $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, 
                                    ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2()) ? number_format(
                                        (($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2())/($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2()))*100,2):"00", 1, 0, 'C');

                                        $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, 
                                        ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2()) ? number_format(
                                            (($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2())/($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2()))*100,2):"00", 1, 0, 'C');
                                    break;

                                case 2:
                                    $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, 
                                    ($lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4()) ? number_format(
                                        (($lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4())/($lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4()))*100,2):"00", 1, 0, 'C');

                                        $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, 
                                        ($lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4()) ? number_format(
                                            (($lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4())/($lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4()))*100,2):"00", 1, 0, 'C');
                                    break;
                                case 3:
                                    $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, 
                                    ($lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ? number_format(
                                        (($lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/($lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100,2):"00", 1, 0, 'C');

                                        $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, 
                                        ($lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ? number_format(
                                            (($lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/($lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100,2):"00", 1, 0, 'C');
                                    break;
                            }
                            
                        }
                    }
                    
                    ///////AN
                    $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
                    $pdf->Cell((($couvertureProgramme-30)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
                    
                    ///// NOMBRE D'HEURES PREVUES
                    //////TTRIM
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
            
                    ///////AN
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
            
                    ///// NOMBRE D'HEURES FAITES
                    //////TTRIM
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
            
                    ///////AN
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');
                    $pdf->Cell((($couvertureProgramme-40)/2)/2, $cellHeigh0, "00", 1, 0, 'C');


                    //////////////TAUX DE REUSSITE DES ELEVES
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedBoys()*100, $row->getComposedBoys())), 1, 0, 'C');
            
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getPassedGirls()*100, $row->getComposedGirls())), 1, 0, 'C');
            
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio(($row->getPassedBoys() + $row->getPassedGirls())*100, ($row->getComposedBoys() + $row->getComposedGirls()))), 1, 0, 'C');
            
                    
            
                    ///////////////////TAUX D'ASSIDUITE DES ELEVES
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio($row->getComposedBoys()*100, $row->getRegisteredBoys())), 1, 0, 'C');
            
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0,  $this->generalService->formatMark($this->generalService->getRatio($row->getComposedGirls()*100, $row->getRegisteredGirls())), 1, 0, 'C');
            
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, $this->generalService->formatMark($this->generalService->getRatio(($row->getComposedBoys() + $row->getComposedGirls())*100, ($row->getRegisteredBoys() + $row->getRegisteredGirls()))), 1, 0, 'C');
            
                    //////////////TAUX D'ASSIDUITE DES ENSEIGNANTS
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, "00", 1, 0, 'C');
            
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, "00", 1, 0, 'C');
            
                    $pdf->Cell(($couvertureProgramme-40)/3, $cellHeigh0, "00", 1, 0, 'C');
            
                    //////////////OBSERVATIONS
                    $pdf->Cell(($couvertureProgramme-40), $cellHeigh0, "", 1, 0, 'C');
                    $pdf->Ln(); 


                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);

                    // On recupère les tataux du cycle 1
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }

                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                // Sous total Cycle 1
                // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
                $pdf->Cell($couvertureProgramme-47 , 4, 'Totaux 1er cycle', 1, 0, 'L', true);

                $nbreLeconTheoPrevue1 = 0;
                $nbreLeconPratPrevue1 = 0;
                $nbreLeconTheoFaite1 = 0;
                $nbreLeconPratFaite1 = 0;

                $pourcentageLeconTheo1 = 0;
                $pourcentageLeconPrat1 = 0;

                $nbreHeureDueTheo1 = 0;
                $nbreHeureDuePrat1 = 0;
                $nbreHeureFaiteTheo1 = 0;
                $nbreHeureFaitePrat1 = 0;

                $pourcentageHeureDue1 = 0;
                $pourcentageHeureFaite1 = 0;

                $pourcentageAssiduiteEnseignant1 = 0;

                $nbreClasse1erCycle = 0;
                $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                foreach($nbreClasseParCycles as $nbreClasseParCycle)
                {
                    if ($nbreClasseParCycle['level'] == 1 || $nbreClasseParCycle['level'] == 2 || $nbreClasseParCycle['level'] == 3 || $nbreClasseParCycle['level'] == 4 )
                    {
                        $nbreClasse1erCycle++;
                    }
                }


                foreach ($lessons as $lesson) 
                {
                    if ($lesson->getClassroom()->getLevel()->getLevel() == 1 || $lesson->getClassroom()->getLevel()->getLevel() == 2 || $lesson->getClassroom()->getLevel()->getLevel() == 3 || $lesson->getClassroom()->getLevel()->getLevel() == 4) 
                    { 
                        $nbreLeconTheoPrevue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                        $nbreLeconPratPrevue1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                        $nbreLeconTheoFaite1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                        $nbreLeconPratFaite1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                        //////////////////////////
                        $pourcentageLeconTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                        (number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                        ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";

                         //////////////////////////
                         $pourcentageLeconTheo1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                         (number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                         ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse1erCycle),2)) :"00";


                         ///////somme heures dues théoriques
                        $nbreHeureDueTheo1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                        //////somme heures dues pratique
                        $nbreHeureDuePrat1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                        ///////somme heures faites théoriques
                        $nbreHeureFaiteTheo1 += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                        //////somme heures faites pratique
                        $nbreHeureFaitePrat1 += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                        //////pourcentage heures théorique
                        $pourcentageHeureDue1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                            number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100
                        )/ $nbreClasse1erCycle),2):"00";


                        ////////////pourcentage des heures pratiques
                        $pourcentageHeureFaite1 += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse1erCycle),2):"00";


                        /////////ASSIDUITE DES ENSEIGNANTS
                        $pourcentageAssiduiteEnseignant1 += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                            (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours())*100)/ $nbreClasse1erCycle),2):"00";
                         
                    }
                }

                // dd($nbreClasse1erCycle);
                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconTheoPrevue1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconPratPrevue1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconTheoFaite1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconPratFaite1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($pourcentageLeconTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($pourcentageLeconPrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                //////taux de réusite des élèves
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                //////taux d'assiduité des élèves
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                 //////taux d'assiduité des enseignants
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                 ////////OBSERVATION
                 $pdf->Cell($couvertureProgramme-40, $cellHeigh0, utf8_decode(""), 1, 1, 'C', true);


                $rowCounter++;

                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                $pdf = $this->addNewPageSlipPerSubjectPagination($pdf, $pageCounter, $rowCounter);
                
            }

            $pdf = $pdf = $this->generalService->newPagePagination($pdf, 'L', 10, 7);
            $pdf = $this->pedagogicalSummarySheetTableHeader($pdf);

            // Cycle 2
            if(!empty($statistics[1]))
            {
                $rowCycle = new ClassroomStatisticSlipRow();
                $rowCycle->setSubject('Totaux Cycle 2');
                $counter = 0;

                foreach($statistics[1] as $row)
                {
                    // une ligne du tableau
                    $pdf = $this->pedagogicalSummarySheetTableRow($pdf, $row, $lessons, false);
                    $rowCounter++;

                    // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                    // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

                    // on met à jour les totaux du cycle 2
                    if($row->getLastMark() < $rowCycle->getLastMark())
                        $rowCycle->setLastMark($row->getLastMark());
                    if($row->getFirstMark() > $rowCycle->getFirstMark())
                        $rowCycle->setFirstMark($row->getFirstMark());
                    
                    $rowCycle->setRegisteredBoys($rowCycle->getRegisteredBoys()+$row->getRegisteredBoys())
                            ->setRegisteredGirls($rowCycle->getRegisteredGirls()+$row->getRegisteredGirls())
                            ->setComposedBoys($rowCycle->getComposedBoys()+$row->getComposedBoys())
                            ->setComposedGirls($rowCycle->getComposedGirls()+$row->getComposedGirls())
                            ->setPassedBoys($rowCycle->getPassedBoys()+$row->getPassedBoys())
                            ->setPassedGirls($rowCycle->getPassedGirls()+$row->getPassedGirls())
                            ->setGeneralAverageBoys($rowCycle->getGeneralAverageBoys()+$row->getGeneralAverageBoys())
                            ->setGeneralAverageGirls($rowCycle->getGeneralAverageGirls()+$row->getGeneralAverageGirls())
                            ->setGeneralAverage($rowCycle->getGeneralAverage()+$row->getGeneralAverage())
                    ;
                    $counter++;
                }
                $generalMarkBoys = $rowCycle->getGeneralAverageBoys();
                $generalMarkGirls = $rowCycle->getGeneralAverageGirls();
                $generalMark = $rowCycle->getGeneralAverage();

                if($counter)
                {
                    $rowCycle->setGeneralAverageBoys($this->generalService->getRatio($generalMarkBoys, $counter));
                    $rowCycle->setGeneralAverageGirls($this->generalService->getRatio($generalMarkGirls, $counter));
                    $rowCycle->setGeneralAverage($this->generalService->getRatio($generalMark, $counter));
                }

                $rowCycle->setAppreciation($this->generalService->getApoAppreciation($rowCycle->getGeneralAverage()));

                $counterSchool += $counter;

                // on met à jour les totaux de l'établissement
                if($rowCycle->getLastMark() < $rowSchool->getLastMark())
                        $rowSchool->setLastMark($rowCycle->getLastMark());
                    if($rowCycle->getFirstMark() > $rowSchool->getFirstMark())
                        $rowSchool->setFirstMark($rowCycle->getFirstMark());
                    
                    $rowSchool->setRegisteredBoys($rowSchool->getRegisteredBoys()+$rowCycle->getRegisteredBoys())
                            ->setRegisteredGirls($rowSchool->getRegisteredGirls()+$rowCycle->getRegisteredGirls())
                            ->setComposedBoys($rowSchool->getComposedBoys()+$rowCycle->getComposedBoys())
                            ->setComposedGirls($rowSchool->getComposedGirls()+$rowCycle->getComposedGirls())
                            ->setPassedBoys($rowSchool->getPassedBoys()+$rowCycle->getPassedBoys())
                            ->setPassedGirls($rowSchool->getPassedGirls()+$rowCycle->getPassedGirls())
                            ->setGeneralAverageBoys($rowSchool->getGeneralAverageBoys()+$generalMarkBoys)
                            ->setGeneralAverageGirls($rowSchool->getGeneralAverageGirls()+$generalMarkGirls)
                            ->setGeneralAverage($rowSchool->getGeneralAverage()+$generalMark)
                    ;

                    if($counterSchool)
                    {
                        $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                        $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                        $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                    }

                        $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));



                // Sous total Cycle 2
                // $pdf = $this->statisticTableRow($pdf, $rowCycle, true);
                $pdf->Cell($couvertureProgramme-47, 4, 'Totaux 2nd cycle', 1, 0, 'L', true);
                
                $nbreLeconTheoPrevue = 0;
                $nbreLeconPratPrevue = 0;
                $nbreLeconTheoFaite = 0;
                $nbreLeconPratFaite = 0;

                $pourcentageLeconTheo = 0;
                $pourcentageLeconPrat = 0;

                $nbreHeureDueTheo = 0;
                $nbreHeureDuePrat = 0;
                $nbreHeureFaiteTheo = 0;
                $nbreHeureFaitePrat = 0;

                $pourcentageHeureDue = 0;
                $pourcentageHeureFaite = 0;

                $pourcentageAssiduiteEnseignant = 0;

                $nbreClasse2ndCycle = 0;
                $nbreClasseParCycles = $this->lessonRepository->getNbreClasseParCycle($schoolYear);

                foreach($nbreClasseParCycles as $nbreClasseParCycle)
                {
                    if ($nbreClasseParCycle['level'] == 5 || $nbreClasseParCycle['level'] == 6 || $nbreClasseParCycle['level'] == 7 )
                    {
                        $nbreClasse2ndCycle++;
                    }
                }


                foreach ($lessons as $lesson) 
                {
                    if ($lesson->getClassroom()->getLevel()->getLevel() == 5 || $lesson->getClassroom()->getLevel()->getLevel() == 6 || $lesson->getClassroom()->getLevel()->getLevel() == 7 ) 
                    { 
                        $nbreLeconTheoPrevue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6());

                        $nbreLeconPratPrevue += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6());

                        $nbreLeconTheoFaite += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6());

                        $nbreLeconPratFaite += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6());

                        //////////////////////////POURCENTAGE LECON THEO
                        $pourcentageLeconTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ?
                        number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6())/
                        ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()))*100/ $nbreClasse2ndCycle),2) :"00";

                         //////////////////////////
                         $pourcentageLeconPrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                         number_format((((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6())/
                         ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()))*100)/ $nbreClasse2ndCycle),2):"00";


                         ///////somme heures dues théoriques
                        $nbreHeureDueTheo += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours();

                        //////somme heures dues pratique
                        $nbreHeureDuePrat += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours() ;


                        ///////somme heures faites théoriques
                        $nbreHeureFaiteTheo += ($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours();

                        //////somme heures faites pratique
                        $nbreHeureFaitePrat += ($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() ;

                        //////pourcentage heures théorique
                        $pourcentageHeureDue += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) ? 
                            number_format(((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle,2):"00";


                        ////////////pourcentage des heures pratiques
                        $pourcentageHeureFaite += ($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            number_format(((($lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours() / (($lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100
                        )/ $nbreClasse2ndCycle,2):"00";


                        /////////ASSIDUITE DES PARENTS
                        $pourcentageAssiduiteEnseignant += ($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) ?
                            number_format((((($lesson->getNbreLessonTheoriqueFaiteSeq1() + $lesson->getNbreLessonTheoriqueFaiteSeq2() + $lesson->getNbreLessonTheoriqueFaiteSeq3() + $lesson->getNbreLessonTheoriqueFaiteSeq4() + $lesson->getNbreLessonTheoriqueFaiteSeq5() + $lesson->getNbreLessonTheoriqueFaiteSeq6() + $lesson->getNbreLessonPratiqueFaiteSeq1() + $lesson->getNbreLessonPratiqueFaiteSeq2() + $lesson->getNbreLessonPratiqueFaiteSeq3() + $lesson->getNbreLessonPratiqueFaiteSeq4() + $lesson->getNbreLessonPratiqueFaiteSeq5() + $lesson->getNbreLessonPratiqueFaiteSeq6()) * $lesson->getWeekHours())/
                            (($lesson->getNbreLessonTheoriquePrevueSeq1() + $lesson->getNbreLessonTheoriquePrevueSeq2() + $lesson->getNbreLessonTheoriquePrevueSeq3() + $lesson->getNbreLessonTheoriquePrevueSeq4() + $lesson->getNbreLessonTheoriquePrevueSeq5() + $lesson->getNbreLessonTheoriquePrevueSeq6() + $lesson->getNbreLessonPratiquePrevueSeq1() + $lesson->getNbreLessonPratiquePrevueSeq2() + $lesson->getNbreLessonPratiquePrevueSeq3() + $lesson->getNbreLessonPratiquePrevueSeq4() + $lesson->getNbreLessonPratiquePrevueSeq5() + $lesson->getNbreLessonPratiquePrevueSeq6()) * $lesson->getWeekHours()))*100)/ $nbreClasse2ndCycle ,2):"00";
                         
                    }
                }

                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconTheoPrevue1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconPratPrevue1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconTheoFaite1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconPratFaite1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($pourcentageLeconTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($pourcentageLeconPrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                //////taux de réusite des élèves
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                //////taux d'assiduité des élèves
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                 //////taux d'assiduité des enseignants
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                 ////////OBSERVATION
                 $pdf->Cell($couvertureProgramme-40, $cellHeigh0, utf8_decode(""), 1, 1, 'C', true);


                $rowCounter++;

                // on insère une nouvelle page avec entête de tableau si c'est nécessaire
                // $pdf = $this->addNewPageSlipPerSubject($pdf, $pageCounter, $rowCounter);

            }else
            {
                if($counterSchool)
                {
                    $rowSchool->setGeneralAverageBoys($this->generalService->getRatio($rowSchool->getGeneralAverageBoys(), $counterSchool));
                    $rowSchool->setGeneralAverageGirls($this->generalService->getRatio($rowSchool->getGeneralAverageGirls(), $counterSchool));
                    $rowSchool->setGeneralAverage($this->generalService->getRatio($rowSchool->getGeneralAverage(), $counterSchool));
                }

                $rowSchool->setAppreciation($this->generalService->getApoAppreciation( $rowSchool->getGeneralAverage()));
            }

            
            // Total Etablissement
            // $pdf = $this->statisticTableRow($pdf, $rowSchool, true);
            $pdf->Cell($couvertureProgramme-47, 4, 'Etablissement', 1, 0, 'L', true);

            $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconTheoPrevue1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconPratPrevue1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconTheoFaite1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-30)/2)/2), $cellHeigh0, utf8_decode($nbreLeconPratFaite1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($pourcentageLeconTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($pourcentageLeconPrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/2)/2), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                //////taux de réusite des élèves
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                //////taux d'assiduité des élèves
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                 //////taux d'assiduité des enseignants
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDueTheo1), 1, 0, 'C', true);
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);
                 $pdf->Cell(((($couvertureProgramme-40)/3)), $cellHeigh0, utf8_decode($nbreHeureDuePrat1), 1, 0, 'C', true);

                 ////////OBSERVATION
                 $pdf->Cell($couvertureProgramme-40, $cellHeigh0, utf8_decode(""), 1, 1, 'C', true);

        }

        return $pdf;
    }


}
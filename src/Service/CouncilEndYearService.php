<?php

namespace App\Service;

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
use App\Repository\AbsenceRepository;
use App\Repository\StudentRepository;
use App\Repository\SequenceRepository;
use App\Entity\StatisticElements\ClassroomStatisticSlipRow;

class CouncilEndYearService 
{
    public function __construct(
        protected SexRepository $sexRepository, 
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected SkillRepository $skillRepository, 
        protected LessonRepository $lessonRepository, 
        protected ReportRepository $reportRepository, 
        protected AbsenceRepository $absenceRepository,
        protected StudentRepository $studentRepository, 
        protected SequenceRepository $sequenceRepository, 
        )
    {}


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

    public function printClassCouncilEndYear(SchoolYear $schoolYear, Classroom $classroom, int $numberOfStudents, int $numberOfBoys, int $numberOfGirls, School $school, int $numberOfRepeaters, SubSystem $subSystem): Pagination
    {
        $pdf = new Pagination();
        $fontSize = 10;
        
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            if($classroom->isIsDeliberated() == false ) 
            {
                $pdf->addPage();
                $pdf->setFont('Times', '', 20);
                $pdf->setTextColor(0, 0, 0);
                $pdf->SetLeftMargin(15);
                $pdf->SetFillColor(200);

                $pdf->Cell(0, 10, utf8_decode('Impression de la fiche de conseil impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Assurez-vous que vous déjà finis les délibérations.'), 0, 1, 'C');

                return $pdf;
            }

            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeight7 = 7;
            // PAGE 1

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("FICHE DE CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(17, 5, 'Effectif : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, utf8_decode($numberOfStudents), 0, 0, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(12, 5, 'Filles : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, utf8_decode($numberOfGirls), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, utf8_decode('Garçons : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, utf8_decode($numberOfBoys), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(30, 5, utf8_decode('Redoublablants : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, utf8_decode($numberOfRepeaters), 0, 1, 'C');
            $pdf->Ln(10);
            
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('I. TRAVAIL'), 0, 1, 'C');

            $largeur = 92;
            $hauteur = 5;
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Elèves classés : '), 'TLB', 0, 'L');

            /////ELEVES CLASSES FIN D'ANNEE
            
            $classifiedStudentsEndyear = $this->reportRepository->findClassifiedStudentsEndYear($classroom);
            
            /////MOYENNE DU 1er A LA FIN D'ANNEE
            $averageFirstStudentsEndYear = $this->reportRepository->findAverageFirstStudentsEndYear($classroom);
            
            /////MOYENNE DU DERNIER A LA FIN D'ANNEE
            $averageLastStudentsEndYear = $this->reportRepository->findAverageLastStudentsEndYear($classroom);

            //////VARIABLES DES TABLEAUX D'HONNEUR
            $rhCongratulation = 0;
            $rhEncouragement = 0;
            $rhSimple = 0;

            ///////VARIABLES TRAVAIL
            $warningWork = 0;
            $blameWork = 0;
            $fraude = 0;

            ///////ELEVES MONTANTS (10/20)
            $studentsAdmisEndYear = 0;

            ////SOMME DES MOYENNES
            $sumAverage = 0;

            foreach ($averageFirstStudentsEndYear as $averageFirstStudentsEndYea) 
            {
                // dump($averageFirstStudentsEndYea->getMoyenne());
                if ($averageFirstStudentsEndYea->getMoyenne() >= 16) 
                {
                    $rhCongratulation = $rhCongratulation + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 14 && $averageFirstStudentsEndYea->getMoyenne() < 16) 
                {
                    $rhEncouragement = $rhEncouragement + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 12 && $averageFirstStudentsEndYea->getMoyenne() < 14) 
                {
                    $rhSimple = $rhSimple + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 7 && $averageFirstStudentsEndYea->getMoyenne() < 9) 
                {
                    $warningWork = $warningWork + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() <= 6 ) 
                {
                    $blameWork = $blameWork + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 10 ) 
                {
                    $studentsAdmisEndYear = $studentsAdmisEndYear + 1;
                }
                
                
                $sumAverage += $averageFirstStudentsEndYea->getMoyenne();
            }

            ///MOYENNE GENERALE
            
            $generalAverage = $sumAverage/count($averageFirstStudentsEndYear);
            
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, $this->generalService->formatInteger(count($classifiedStudentsEndyear)), 'TRB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Elèves montants (10/20) : '), 'LTB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, $this->generalService->formatInteger($studentsAdmisEndYear), 'RTB', 1, 'L');
            
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Moyenne la plus forte : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, $this->generalService->formatMark($averageFirstStudentsEndYear[0]->getMoyenne()), 'RB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Moyenne la plus faible : '), 'TB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($averageLastStudentsEndYear[0]->getMoyenne())), 'RTB', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('T.H. avec Félicitations : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhCongratulation), 'RB', 0, 'LT');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Avertissement de travail : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($warningWork), 'RB', 1, 'LT');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('T.H. avec Encouragements : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhEncouragement), 'RB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Blâme de travail : '), 'B', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($blameWork), 'RB', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('T.H. Simples : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhSimple), 'RB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Fraudes : '), 'B', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode(''), 'RB', 1, 'L');

            //////////////////////////////////

            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'L', 0, 'L');
            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'LR', 1, 'L');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/4, $hauteur, utf8_decode('TOTAL : '), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhCongratulation + $rhEncouragement + $rhSimple), 1, 0, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/4, $hauteur, utf8_decode(' '), 'R', 0, 'L');

            $pdf->Cell($largeur/4, $hauteur, utf8_decode('TOTAL : '), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($warningWork + $blameWork), 1, 0, 'C');
            $pdf->Cell($largeur/4, $hauteur, utf8_decode(' '), 'R', 1, 'L');

            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'LBR', 0, 'L');
            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'RB', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Moyenne Générale : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($generalAverage)), 'RB', 0, 'L');


            //////TAUX DE REUSSITE
            $passRate = ($studentsAdmisEndYear/count($averageFirstStudentsEndYear))*100;

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Taux de réussite : '), 'B', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($passRate)." %"), 'RB', 1, 'L');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur-68, $hauteur, utf8_decode('Premier(ère) : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur+68, $hauteur, utf8_decode($averageFirstStudentsEndYear[0]->getStudent()->getFullName()), 'RB', 1, 'L');
            
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur-68, $hauteur, utf8_decode('Dernier(ère) : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur+68, $hauteur, utf8_decode($averageLastStudentsEndYear[0]->getStudent()->getFullName()), 'RB', 1, 'L');
            
            $pdf->Ln(10);

            ////////HEURES D'ABSENCE NON JUSTIFIEES
            $absences = $this->absenceRepository->findAbsencesEndYear($classroom);
            $unjustifiedAbsences = 0;

            foreach ($absences as $absence) 
            {
                $unjustifiedAbsences += $absence->getAbsence() ;
            }

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('II. DISCIPLINE'), 0, 1, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Nombre d'heures d'absences : "), 'LT', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($unjustifiedAbsences), 'T', 0, 'C');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur, $hauteur, utf8_decode("Nombre d'heures de consigne : "), 'LTR', 1, 'L');

            /////////AVERT TISSEMENT ET BLAME TRAVAIL
            $warningBehavior = 0;
            $blameBehavior = 0;

            $exclusion3days = 0;
            $exclusion5days = 0;
            $exclusion8days = 0;
            $exclusionCommitee = 0;

            $absences = $this->absenceRepository->getSumAbsencePerStudent($classroom);
            
            foreach ($absences as $absence) 
            {
                if ($absence['sommeHeure'] >= 6) 
                {
                    $warningBehavior = $warningBehavior + 1;
                }

                if ($absence['sommeHeure'] >= 10) 
                {
                    $blameBehavior = $blameBehavior + 1;
                }

                if ($absence['sommeHeure'] >= 15) 
                {
                    $exclusion3days = $exclusion3days + 1;
                }

                if ($absence['sommeHeure'] >= 19 ) 
                {
                    $exclusion5days = $exclusion5days + 1;
                }

                if ($absence['sommeHeure'] >= 26) 
                {
                    $exclusion8days = $exclusion8days + 1;
                }

                if ($absence['sommeHeure'] > 30 ) 
                {
                    $exclusionCommitee = $exclusionCommitee + 1;
                }
                
            }
        
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Avertissement conduite : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($warningBehavior), 'R', 0, 'C');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Blâme de conduite : "), '', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($blameBehavior), 'R', 1, 'C');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Jours d'exclusion temporaire : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($exclusion3days + $exclusion5days + $exclusion8days), 'R', 0, 'C');

            $pdf->SetFont('Times', '', 11);

            $pdf->Cell($largeur, $hauteur, utf8_decode("Flagrants délits : "), 'LR', 1, 'L');

            $pdf->Cell($largeur, $hauteur, utf8_decode("Fautes récurrentes : "), 'LB', 0, 'L');
            $pdf->Cell($largeur, $hauteur, utf8_decode(""), 'LRB', 1, 'L');

            $pdf->Cell($largeur*2, $hauteur, utf8_decode("Nom de l'indiscipliné notoire : "), 'LTR', 1, 'L');

            $students = $classroom->getStudents();
            ////////LE CHEF DE CLASSE
            $studentChief = "";
            foreach ($students as $student) 
            {
                if($student->getResponsability())
                {
                    if ($student->getResponsability()->getResponsability() == ConstantsClass::RESPONSABILITY_KING_1 ) 
                    {
                        $studentChief = $student;
                    }
                }
                
            }

            $pdf->Cell($largeur-40, $hauteur, utf8_decode("Nom du chef de classe : "), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur+40, $hauteur, utf8_decode($studentChief ? $studentChief->getFullName(): "PAS DE CHEF DE CLASSE"), 'BR', 1, 'L');
            $pdf->SetFont('Times', '', 11);

            $pdf->Ln(10);

            //////ELEVES ADMIS FIN D'ANNEE
            $studentsAdmiEndYear = 0;

            ////////////REDOUBLANTS
            $studentsRepeaterEndYear = 0;

            ///////DEMISSIONNAIRES
            $studentsResignedEndYear = 0;

            /////////EXCLUS
            $studentsExpelledEndYear = 0;

            foreach ($students as $student) 
            {
                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_PASSED ) 
                {
                    $studentsAdmiEndYear = $studentsAdmiEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_REAPETED ) 
                {
                    $studentsRepeaterEndYear = $studentsRepeaterEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_RESIGNED ) 
                {
                    $studentsResignedEndYear = $studentsResignedEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_FINISHED ) 
                {
                    $studentsResignedEndYear = $studentsResignedEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_EXPELLED ) 
                {
                    $studentsExpelledEndYear = $studentsExpelledEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_EXPELLED_IF_FAILED ) 
                {
                    $studentsExpelledEndYear = $studentsExpelledEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_REAPETED_IF_FAILED ) 
                {
                    $studentsExpelledEndYear = $studentsExpelledEndYear + 1;
                }
            }
            

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("III. FIN D'ANNEE"), 0, 1, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Promus : "), 'LT', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsAdmiEndYear), 'T', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Redoublants : "), 'LT', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsRepeaterEndYear), 'TR', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Démissionnaires : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsResignedEndYear), 'R', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Exclus : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsExpelledEndYear), 'R', 1, 'L');

            /////////TAUS DE REUSSITE FIN D'ANNEE
            $endYearPassRate = ($studentsAdmiEndYear/count($students))*100;
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Taux de réussite : "), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($endYearPassRate)." %"), 'RB', 0, 'L');

            // dd($unjustifiedAbsences);
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur-40, $hauteur, utf8_decode("Heures d'absence non justifiées : "), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur-52, $hauteur, utf8_decode($unjustifiedAbsences), 'RB', 1, 'C');


            $pdf->Ln(10);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("IV. OBSERVATIONS GENERALES"), 0, 1, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LTR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode(""), 'LBR', 1, 'L');


            $pdf->Ln(5);


            $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(90, $cellHeight7, utf8_decode('Le Professeur Principal'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');


            /////////////////////////////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'Professeur Principal : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();
            

            //////////////REQUETE DES PROMUS
            $reportsFinisheds = $this->reportRepository->findFinishedEndYear($classroom);

            if ($classroom->getLevel()->getLevel() != 7) 
            {   
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("PROMUS"), 0, 2, 'C');
        
                $reports = $this->reportRepository->findStudentsAdmisEndYear($classroom);

                if($reports)
                {
                    // dd($reports);
                    // $pdf->SetFont('Times', '', 12);
                    // $pdf->SetX(18);
                    // $pdf->Cell(25, 5, 'En classe de : ', 0, 0, 'C');
                    // $pdf->SetFont('Times', 'B', 12);
                    // $pdf->Cell(25, 5, utf8_decode($reports[0]->getStudent()->getNextClassroomName()), 0, 1, 'L');
                    
                    $pdf->Ln();
                
                    ///ENETE DU TABLEAU
                    $pdf = $this->header($pdf, $subSystem);

                    $pdf->SetFont('Times', '', 10);
                    $i = 1;
                    foreach ($reports as $report) 
                    { 
                        if ($i % 2 != 0) 
                        {
                            $pdf->SetFillColor(219,238,243);
                        }else {
                            $pdf->SetFillColor(255,255,255);
                        }
                        
                        $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                        $pdf->Cell(85, 5, utf8_decode($report->getStudent()->getFullName()), 1, 0, 'L', true);
                        $pdf->Cell(25, 5, utf8_decode(date_format($report->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                        $pdf->Cell(15, 5, utf8_decode($report->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                        $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($report->getMoyenne())), 1, 0, 'C', true);

                        $pdf->SetFont('Times', '', 8);
                        if($report->getStudent()->getSex()->getSex() == "M")
                        {
                            $pdf->Cell(33, 5, utf8_decode("Admis en ".$report->getStudent()->getNextClassroomName()), 1, 1, 'C', true);

                        }else
                        {
                            $pdf->Cell(33, 5, utf8_decode("Admise en ".$report->getStudent()->getNextClassroomName()), 1, 1, 'C', true);

                        }
                        $pdf->SetFont('Times', '', 10);

                        $i++;
                    }
                }else
                {
                    $pdf->SetFont('Times', 'BI', 14);
                    $pdf->Ln();
                    $pdf->Cell(0, 5, 'PAS DE PROMUS : ', 0, 0, 'C');
                }
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);

                if ($reportsFinisheds == null) 
                {
                    $pdf->Cell(0, 5, utf8_decode("NEANT"), 0, 2, 'C');
                }else
                {
                    $pdf->Cell(0, 5, utf8_decode("AYANT TERMINES"), 0, 2, 'C');
                    $pdf->Ln();
                    ///ENETE DU TABLEAU
                    $pdf = $this->header($pdf, $subSystem);

                    $pdf->SetFont('Times', '', 10);
                    $i = 1;
                    
                    foreach ($reportsFinisheds as $reportsFinished) 
                    { 
                        if ($i % 2 != 0) 
                        {
                            $pdf->SetFillColor(219,238,243);
                        }else {
                            $pdf->SetFillColor(255,255,255);
                        }
                        
                        $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                        $pdf->Cell(85, 5, utf8_decode($reportsFinished->getStudent()->getFullName()), 1, 0, 'L', true);
                        $pdf->Cell(25, 5, utf8_decode(date_format($reportsFinished->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                        $pdf->Cell(15, 5, utf8_decode($reportsFinished->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                        $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportsFinished->getMoyenne())), 1, 0, 'C', true);

                        $pdf->SetFont('Times', '', 8);
                        $pdf->Cell(33, 5, utf8_decode("TERMINE(E)"), 1, 1, 'C', true);

                        
                        $pdf->SetFont('Times', '', 10);

                        $i++;
                    }
                    
                    $pdf->Ln(5);


                    $pdf->SetFont('Times', 'B', 12);
                    $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');
        
                    $pdf->Ln();
                    $pdf->Cell(90, $cellHeight7, utf8_decode(''), 0, 0, 'L');
                    $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
                }
                
            }
            
            ////////////////////REDOUBLANTS/////////////////////////////
            // On insère une page
            

            $reportRepeaters = $this->reportRepository->findStudentsRepeaterEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if(count($reportRepeaters) > 0)
            {
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

                // Entête du bulletin
    
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
                // ENTETE DE LA FICHE
                $pdf->SetFont('Times', 'B', 14);
    
                $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
                
                $pdf->Ln(2);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
                $pdf->Ln();
    
                $pdf->Ln(2);
                $pdf->SetX(18);
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
    
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(50, 5, 'Professeur Principal : ', 0, 0, 'L');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');
    
                $pdf->Ln();
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("REDOUBLANTS"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportRepeaters as $reportRepeater) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->SetFont('Times', '', 10);
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportRepeater->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportRepeater->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportRepeater->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportRepeater->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Redouble la '.$reportRepeater->getStudent()->getClassroom()->getClassroom()), 1, 1, 'C', true);
                    
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
                
                $pdf->Ln(5);

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');
    
                $pdf->Ln();
                $pdf->Cell(90, $cellHeight7, utf8_decode(''), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
            }

            
            ////////////////////////EXCLUS/////////////////////////
            
            
            $reportExpelleds = $this->reportRepository->findStudentsExpelledEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);
            
            if(count($reportExpelleds) > 0)
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
    
                // Entête du bulletin
    
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
                
                // ENTETE DE LA FICHE
                $pdf->SetFont('Times', 'B', 14);
    
                $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
                
                $pdf->Ln(2);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
                $pdf->Ln();
    
                $pdf->Ln(2);
                $pdf->SetX(18);
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
    
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(50, 5, 'Professeur Principal : ', 0, 0, 'L');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');
    
                $pdf->Ln();
                
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("EXCLUS"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf->SetFont('Times', '', 12);
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportExpelleds as $reportExpelled) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportExpelled->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportExpelled->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportExpelled->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportExpelled->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    if($reportExpelled->getStudent()->getSex()->getSex() == "F")
                    {
                        $pdf->Cell(33, 5, utf8_decode('Exclue pour '.$reportExpelled->getStudent()->getMotif()), 1, 1, 'C', true);
                    }else
                    {
                        $pdf->Cell(33, 5, utf8_decode('Exclu pour '.$reportExpelled->getStudent()->getMotif()), 1, 1, 'C', true);
                    }
                    
                    $pdf->SetFont('Times', '', 10);
                    $i++;
                }
                
                $pdf->Ln(5);

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');
    
                $pdf->Ln();
                $pdf->Cell(90, $cellHeight7, utf8_decode(''), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
            }
            



            /////////////////////DEMISSIONNIARES////////////////////////////
            $reportResigneds = $this->reportRepository->findStudentsResignedEndYear($classroom);
            $i = 1;

            if(count($reportResigneds) > 0)
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
    
                // Entête du bulletin
    
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
                // ENTETE DE LA FICHE
                $pdf->SetFont('Times', 'B', 14);
    
                $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
                
                $pdf->Ln(2);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
                $pdf->Ln();
    
                $pdf->Ln(2);
                $pdf->SetX(18);
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
    
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(50, 5, 'Professeur Principal : ', 0, 0, 'L');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');
    
                $pdf->Ln();
                
                
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("DEMISSIONNAIRES"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportResigneds as $reportResigned) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportResigned->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportResigned->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportResigned->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportResigned->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Démissionnaire'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
                
                $pdf->Ln(5);

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');
    
                $pdf->Ln();
                $pdf->Cell(90, $cellHeight7, utf8_decode(''), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
            }

            

            
            /////////////////////REDOUBLES SI ECHEC////////////////////////////
           
            $reportRepeatIfFailStudents = $this->reportRepository->findStudentsRepeaterIfFailedEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if(count($reportRepeatIfFailStudents) > 0)
            {
                 // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
    
                // Entête du bulletin
    
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
                // ENTETE DE LA FICHE
                $pdf->SetFont('Times', 'B', 14);
    
                $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
                
                $pdf->Ln(2);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
                $pdf->Ln();
    
                $pdf->Ln(2);
                $pdf->SetX(18);
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
    
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(50, 5, 'Professeur Principal : ', 0, 0, 'L');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');
    
                $pdf->Ln();
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("REDOUBLANTS SI ECHEC"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportRepeatIfFailStudents as $reportRepeatIfFailStudent) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportRepeatIfFailStudent->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportRepeatIfFailStudent->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportRepeatIfFailStudent->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportRepeatIfFailStudent->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Redouble si échec'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
                
                $pdf->Ln(5);

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');
    
                $pdf->Ln();
                $pdf->Cell(90, $cellHeight7, utf8_decode(''), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
            }

            


            /////////////////////EXCLUS SI ECHEC////////////////////////////
            

            $reportExpelledIfFailStudents = $this->reportRepository->findStudentsExpelledIfFailedEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if(count($reportExpelledIfFailStudents) > 0)
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
    
                // Entête du bulletin
    
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
                // ENTETE DE LA FICHE
                $pdf->SetFont('Times', 'B', 14);
    
                $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
                
                $pdf->Ln(2);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
                $pdf->Ln();
    
                $pdf->Ln(2);
                $pdf->SetX(18);
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
    
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(50, 5, 'Professeur Principal : ', 0, 0, 'L');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');
    
                $pdf->Ln();
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("EXCLUS SI ECHEC"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportExpelledIfFailStudents as $reportExpelledIfFailStudent) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportExpelledIfFailStudent->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportExpelledIfFailStudent->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportExpelledIfFailStudent->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportExpelledIfFailStudent->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Exclus si échec'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
                
                $pdf->Ln(5);

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');
    
                $pdf->Ln();
                $pdf->Cell(90, $cellHeight7, utf8_decode(''), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
            }

            

            

            /////////////////////RATTRAPAGE////////////////////////////
            

            $reportCatchuppedStudents = $this->reportRepository->findStudentsCatchuppedEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if(count($reportCatchuppedStudents) > 0)
            {
                // On insère une page
                $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);
    
                // Entête du bulletin
    
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
    
                // ENTETE DE LA FICHE
                $pdf->SetFont('Times', 'B', 14);
    
                $pdf->Cell(0, 5, utf8_decode("CONSEIL DE CLASSE DE FIN D'ANNEE"), 0, 2, 'C');
                
                $pdf->Ln(2);
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, 5, utf8_decode('Année scolaire : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
                $pdf->Ln();
    
                $pdf->Ln(2);
                $pdf->SetX(18);
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(15, 5, 'Classe : ', 0, 0, 'C');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');
    
                $pdf->SetFont('Times', '', 12);
                $pdf->Cell(50, 5, 'Professeur Principal : ', 0, 0, 'L');
                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');
    
                $pdf->Ln();
                
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("ELEVES AU RATTRAPAGE"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportCatchuppedStudents as $reportCatchuppedStudent) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportCatchuppedStudent->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportCatchuppedStudent->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportCatchuppedStudent->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportCatchuppedStudent->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Rattrapage'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
                
                $pdf->Ln(5);

                $pdf->SetFont('Times', 'B', 12);
                $pdf->Cell(0, $cellHeight7, utf8_decode('Fait à '.$school->getPlace().', Le _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');
    
                $pdf->Ln();
                $pdf->Cell(90, $cellHeight7, utf8_decode(''), 0, 0, 'L');
                $pdf->Cell(90, $cellHeight7, utf8_decode('Le Président du Conseil de Classe'), 0, 0, 'R');
            }

            
        
        }else
        {
            //////////////SOUS SYSTEM ANGLOPHONE
            if($classroom->isIsDeliberated() == false ) 
            {
                $pdf->addPage();
                $pdf->setFont('Times', '', 20);
                $pdf->setTextColor(0, 0, 0);
                $pdf->SetLeftMargin(15);
                $pdf->SetFillColor(200);

                $pdf->Cell(0, 10, utf8_decode('Printing of the advice sheet impossible !'), 0, 1, 'C');
                $pdf->Ln();
                $pdf->Ln();
                $pdf->Cell(0, 10, utf8_decode('- Make sure you have already finished deliberations.'), 0, 1, 'C');

                return $pdf;
            }

            $fontSize = 10;
            $cellHeaderHeight = 3;

            $cellHeight7 = 7;
            // PAGE 1

            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS ADVICE SHEET"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year: '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(20, 5, 'Effective : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($numberOfStudents), 0, 0, 'L');
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Girls : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, utf8_decode($numberOfGirls), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(18, 5, utf8_decode('Boys : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, utf8_decode($numberOfBoys), 0, 0, 'C');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(30, 5, utf8_decode('Repeaters : '), 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(10, 5, utf8_decode($numberOfRepeaters), 0, 1, 'C');
            $pdf->Ln(10);
            
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('I. Work'), 0, 1, 'C');

            $largeur = 92;
            $hauteur = 5;
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Classified students : '), 'TLB', 0, 'L');

            /////ELEVES CLASSES FIN D'ANNEE
            
            $classifiedStudentsEndyear = $this->reportRepository->findClassifiedStudentsEndYear($classroom);
            
            /////MOYENNE DU 1er A LA FIN D'ANNEE
            $averageFirstStudentsEndYear = $this->reportRepository->findAverageFirstStudentsEndYear($classroom);
            
            /////MOYENNE DU DERNIER A LA FIN D'ANNEE
            $averageLastStudentsEndYear = $this->reportRepository->findAverageLastStudentsEndYear($classroom);

            //////VARIABLES DES TABLEAUX D'HONNEUR
            $rhCongratulation = 0;
            $rhEncouragement = 0;
            $rhSimple = 0;

            ///////VARIABLES TRAVAIL
            $warningWork = 0;
            $blameWork = 0;
            $fraude = 0;

            ///////ELEVES MONTANTS (10/20)
            $studentsAdmisEndYear = 0;

            ////SOMME DES MOYENNES
            $sumAverage = 0;

            foreach ($averageFirstStudentsEndYear as $averageFirstStudentsEndYea) 
            {
                // dump($averageFirstStudentsEndYea->getMoyenne());
                if ($averageFirstStudentsEndYea->getMoyenne() >= 16) 
                {
                    $rhCongratulation = $rhCongratulation + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 14 && $averageFirstStudentsEndYea->getMoyenne() < 16) 
                {
                    $rhEncouragement = $rhEncouragement + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 12 && $averageFirstStudentsEndYea->getMoyenne() < 14) 
                {
                    $rhSimple = $rhSimple + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 7 && $averageFirstStudentsEndYea->getMoyenne() < 9) 
                {
                    $warningWork = $warningWork + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() <= 6 ) 
                {
                    $blameWork = $blameWork + 1;
                }

                if ($averageFirstStudentsEndYea->getMoyenne() >= 10 ) 
                {
                    $studentsAdmisEndYear = $studentsAdmisEndYear + 1;
                }
                
                
                $sumAverage += $averageFirstStudentsEndYea->getMoyenne();
            }

            ///MOYENNE GENERALE
            
            $generalAverage = $sumAverage/count($averageFirstStudentsEndYear);
            
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, $this->generalService->formatInteger(count($classifiedStudentsEndyear)), 'TRB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Rising students(10/20) : '), 'LTB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, $this->generalService->formatInteger($studentsAdmisEndYear), 'RTB', 1, 'L');
            
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Highest average : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, $this->generalService->formatMark($averageFirstStudentsEndYear[0]->getMoyenne()), 'RB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Lowest average : '), 'TB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($averageLastStudentsEndYear[0]->getMoyenne())), 'RTB', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('R.H. with Congratulations: '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhCongratulation), 'RB', 0, 'LT');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Work Warning : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($warningWork), 'RB', 1, 'LT');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('R.H. with Encouragements : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhEncouragement), 'RB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Work blame : '), 'B', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($blameWork), 'RB', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('R.H. Simples : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhSimple), 'RB', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Fraud : '), 'B', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode(''), 'RB', 1, 'L');

            //////////////////////////////////

            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'L', 0, 'L');
            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'LR', 1, 'L');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/4, $hauteur, utf8_decode('TOTAL : '), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($rhCongratulation + $rhEncouragement + $rhSimple), 1, 0, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/4, $hauteur, utf8_decode(' '), 'R', 0, 'L');

            $pdf->Cell($largeur/4, $hauteur, utf8_decode('TOTAL : '), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($warningWork + $blameWork), 1, 0, 'C');
            $pdf->Cell($largeur/4, $hauteur, utf8_decode(' '), 'R', 1, 'L');

            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'LBR', 0, 'L');
            $pdf->Cell($largeur, $hauteur-4, utf8_decode(' '), 'RB', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Overall average : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($generalAverage)), 'RB', 0, 'L');


            //////TAUX DE REUSSITE
            $passRate = ($studentsAdmisEndYear/count($averageFirstStudentsEndYear))*100;

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode('Success rate : '), 'B', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($passRate)." %"), 'RB', 1, 'L');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur-68, $hauteur, utf8_decode('First : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur+68, $hauteur, utf8_decode($averageFirstStudentsEndYear[0]->getStudent()->getFullName()), 'RB', 1, 'L');
            
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur-68, $hauteur, utf8_decode('Last : '), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur+68, $hauteur, utf8_decode($averageLastStudentsEndYear[0]->getStudent()->getFullName()), 'RB', 1, 'L');
            
            $pdf->Ln(10);

            ////////HEURES D'ABSENCE NON JUSTIFIEES
            $absences = $this->absenceRepository->findAbsencesEndYear($classroom);
            $unjustifiedAbsences = 0;

            foreach ($absences as $absence) 
            {
                $unjustifiedAbsences += $absence->getAbsence() ;
            }

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('II. DISCIPLINE'), 0, 1, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Number of hours of absence: "), 'LT', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($unjustifiedAbsences), 'T', 0, 'C');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur, $hauteur, utf8_decode("Number of set hours : "), 'LTR', 1, 'L');

            /////////AVERT TISSEMENT ET BLAME TRAVAIL
            $warningBehavior = 0;
            $blameBehavior = 0;

            $exclusion3days = 0;
            $exclusion5days = 0;
            $exclusion8days = 0;
            $exclusionCommitee = 0;

            $absences = $this->absenceRepository->getSumAbsencePerStudent($classroom);
            
            foreach ($absences as $absence) 
            {
                if ($absence['sommeHeure'] >= 6) 
                {
                    $warningBehavior = $warningBehavior + 1;
                }

                if ($absence['sommeHeure'] >= 10) 
                {
                    $blameBehavior = $blameBehavior + 1;
                }

                if ($absence['sommeHeure'] >= 15) 
                {
                    $exclusion3days = $exclusion3days + 1;
                }

                if ($absence['sommeHeure'] >= 19 ) 
                {
                    $exclusion5days = $exclusion5days + 1;
                }

                if ($absence['sommeHeure'] >= 26) 
                {
                    $exclusion8days = $exclusion8days + 1;
                }

                if ($absence['sommeHeure'] > 30 ) 
                {
                    $exclusionCommitee = $exclusionCommitee + 1;
                }
                
            }
        
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Driving warning : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($warningBehavior), 'R', 0, 'C');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Driving blame : "), '', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($blameBehavior), 'R', 1, 'C');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Temporary exclusion days : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($exclusion3days + $exclusion5days + $exclusion8days), 'R', 0, 'C');

            $pdf->SetFont('Times', '', 11);

            $pdf->Cell($largeur, $hauteur, utf8_decode("Flagrant crimes: "), 'LR', 1, 'L');

            $pdf->Cell($largeur, $hauteur, utf8_decode("Recurring faults : "), 'LB', 0, 'L');
            $pdf->Cell($largeur, $hauteur, utf8_decode(""), 'LRB', 1, 'L');

            $pdf->Cell($largeur*2, $hauteur, utf8_decode("Name of the notorious unruly : "), 'LTR', 1, 'L');

            $students = $classroom->getStudents();
            ////////LE CHEF DE CLASSE
            $studentChief = "";
            foreach ($students as $student) 
            {
                if($student->getResponsability())
                {
                    if ($student->getResponsability()->getResponsability() == ConstantsClass::RESPONSABILITY_KING_1 ) 
                    {
                        $studentChief = $student;
                    }
                }
                
            }

            $pdf->Cell($largeur-40, $hauteur, utf8_decode("Name of class leader: "), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur+40, $hauteur, utf8_decode($studentChief ? $studentChief->getFullName(): "NO CLASS LEADER"), 'BR', 1, 'L');
            $pdf->SetFont('Times', '', 11);

            $pdf->Ln(10);

            //////ELEVES ADMIS FIN D'ANNEE
            $studentsAdmiEndYear = 0;

            ////////////REDOUBLANTS
            $studentsRepeaterEndYear = 0;

            ///////DEMISSIONNAIRES
            $studentsResignedEndYear = 0;

            /////////EXCLUS
            $studentsExpelledEndYear = 0;

            foreach ($students as $student) 
            {
                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_PASSED ) 
                {
                    $studentsAdmiEndYear = $studentsAdmiEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_REAPETED ) 
                {
                    $studentsRepeaterEndYear = $studentsRepeaterEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_RESIGNED ) 
                {
                    $studentsResignedEndYear = $studentsResignedEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_FINISHED ) 
                {
                    $studentsResignedEndYear = $studentsResignedEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_EXPELLED ) 
                {
                    $studentsExpelledEndYear = $studentsExpelledEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_EXPELLED_IF_FAILED ) 
                {
                    $studentsExpelledEndYear = $studentsExpelledEndYear + 1;
                }

                if ($student->getDecision()->getDecision() == ConstantsClass::DECISION_REAPETED_IF_FAILED ) 
                {
                    $studentsExpelledEndYear = $studentsExpelledEndYear + 1;
                }
            }
            

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("III. END OF THE YEAR"), 0, 1, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Promoted : "), 'LT', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsAdmiEndYear), 'T', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Repeaters : "), 'LT', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsRepeaterEndYear), 'TR', 1, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Resigners : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsResignedEndYear), 'R', 0, 'L');

            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Excluded : "), 'L', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($studentsExpelledEndYear), 'R', 1, 'L');

            /////////TAUS DE REUSSITE FIN D'ANNEE
            $endYearPassRate = ($studentsAdmiEndYear/count($students))*100;
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode("Success rate : "), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur/2, $hauteur, utf8_decode($this->generalService->formatMark($endYearPassRate)." %"), 'RB', 0, 'L');

            // dd($unjustifiedAbsences);
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell($largeur-40, $hauteur, utf8_decode("Heures d'absence non justifiées : "), 'LB', 0, 'L');
            $pdf->SetFont('Times', 'B', 11);
            $pdf->Cell($largeur-52, $hauteur, utf8_decode($unjustifiedAbsences), 'RB', 1, 'C');


            $pdf->Ln(10);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("IV. GENERAL OBSERVATIONS"), 0, 1, 'C');
            $pdf->SetFont('Times', '', 11);
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LTR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode("_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 'LR', 1, 'L');
            $pdf->Cell(0, $hauteur, utf8_decode(""), 'LBR', 1, 'L');


            $pdf->Ln(5);


            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at '.$school->getPlace().', On _ _ _ _ _ _ _ _ _ _ _ _ '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->SetFont('Times', 'BU', 12);
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');


            /////////////////////////////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'The Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();
            

            //////////////REQUETE DES PROMOTED
            $reportsFinisheds = $this->reportRepository->findFinishedEndYear($classroom);

            if ($classroom->getLevel()->getLevel() != 7) 
            {   
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("PROMOTED"), 0, 2, 'C');
        
                $reports = $this->reportRepository->findStudentsAdmisEndYear($classroom);

                if($reports)
                {

                    $pdf->SetFont('Times', '', 12);
                    $pdf->SetX(18);
                    $pdf->Cell(25, 5, 'In class : ', 0, 0, 'C');
                    $pdf->SetFont('Times', 'B', 12);
                    $pdf->Cell(25, 5, utf8_decode($reports[0]->getStudent()->getNextClassroomName()), 0, 1, 'L');
                    
                    $pdf->Ln();
                
                    ///ENETE DU TABLEAU
                    $pdf = $this->header($pdf, $subSystem);

                    $pdf->SetFont('Times', '', 10);
                    $i = 1;
                    foreach ($reports as $report) 
                    { 
                        if ($i % 2 != 0) 
                        {
                            $pdf->SetFillColor(219,238,243);
                        }else {
                            $pdf->SetFillColor(255,255,255);
                        }
                        
                        $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                        $pdf->Cell(85, 5, utf8_decode($report->getStudent()->getFullName()), 1, 0, 'L', true);
                        $pdf->Cell(25, 5, utf8_decode(date_format($report->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                        $pdf->Cell(15, 5, utf8_decode($report->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                        $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($report->getMoyenne())), 1, 0, 'C', true);

                        $pdf->SetFont('Times', '', 8);
                        if($report->getStudent()->getSex()->getSex() == "M")
                        {
                            $pdf->Cell(33, 5, utf8_decode("Admitted in ".$report->getStudent()->getNextClassroomName()), 1, 1, 'C', true);

                        }else
                        {
                            $pdf->Cell(33, 5, utf8_decode("Admitted in ".$report->getStudent()->getNextClassroomName()), 1, 1, 'C', true);

                        }
                        $pdf->SetFont('Times', '', 10);

                        $i++;
                    }
                }else
                {
                    $pdf->SetFont('Times', 'BI', 14);
                    $pdf->Ln();
                    $pdf->Cell(0, 5, 'NO PROMOTIONS : ', 0, 0, 'C');
                }
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);

                if ($reportsFinisheds == null) 
                {
                    $pdf->Cell(0, 5, utf8_decode("NIL"), 0, 2, 'C');
                }else
                {
                    $pdf->Cell(0, 5, utf8_decode("HAVING COMPLETED"), 0, 2, 'C');
                    $pdf->Ln();
                    ///ENETE DU TABLEAU
                    $pdf = $this->header($pdf, $subSystem);

                    $pdf->SetFont('Times', '', 10);
                    $i = 1;
                    
                    foreach ($reportsFinisheds as $reportsFinished) 
                    { 
                        if ($i % 2 != 0) 
                        {
                            $pdf->SetFillColor(219,238,243);
                        }else {
                            $pdf->SetFillColor(255,255,255);
                        }
                        
                        $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                        $pdf->Cell(85, 5, utf8_decode($reportsFinished->getStudent()->getFullName()), 1, 0, 'L', true);
                        $pdf->Cell(25, 5, utf8_decode(date_format($reportsFinished->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                        $pdf->Cell(15, 5, utf8_decode($reportsFinished->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                        $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportsFinished->getMoyenne())), 1, 0, 'C', true);

                        $pdf->SetFont('Times', '', 8);
                        $pdf->Cell(33, 5, utf8_decode("COMPLETED"), 1, 1, 'C', true);

                        
                        $pdf->SetFont('Times', '', 10);

                        $i++;
                    }
                }
                
                
            }
            
            $pdf->Ln(5);


            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');


            ////////////////////REDOUBLANTS/////////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'The Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();

            $reportRepeaters = $this->reportRepository->findStudentsRepeaterEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if($reportRepeaters == null)
            {
                $pdf->SetFont('Times', 'B', 20);
                $pdf->Cell(0, 5, utf8_decode("NO REPEATERS"), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("REPEATERS"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportRepeaters as $reportRepeater) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->SetFont('Times', '', 10);
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportRepeater->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportRepeater->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportRepeater->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportRepeater->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Redouble it '.$reportRepeater->getStudent()->getClassroom()->getClassroom()), 1, 1, 'C', true);
                    
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
            }


            $pdf->Ln(5);

            
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');



            ////////////////////////EXCLUS/////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'The Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();
            

            $reportExpelleds = $this->reportRepository->findStudentsExpelledEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if($reportExpelleds == null)
            {
                $pdf->SetFont('Times', 'B', 20);
                $pdf->Cell(0, 5, utf8_decode("NO EXCLUSIVES"), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("EXCLUDED"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf->SetFont('Times', '', 12);
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportExpelleds as $reportExpelled) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportExpelled->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportExpelled->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportExpelled->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportExpelled->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    if($reportExpelled->getStudent()->getSex()->getSex() == "F")
                    {
                        $pdf->Cell(33, 5, utf8_decode('Excluded for '.$reportExpelled->getStudent()->getMotif()), 1, 1, 'C', true);
                    }else
                    {
                        $pdf->Cell(33, 5, utf8_decode('Excluded for '.$reportExpelled->getStudent()->getMotif()), 1, 1, 'C', true);
                    }
                    
                    $pdf->SetFont('Times', '', 10);
                    $i++;
                }
            }
            


            $pdf->Ln(5);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');




            /////////////////////DEMISSIONNIARES////////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'The Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();
            $reportResigneds = $this->reportRepository->findStudentsResignedEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if($reportResigneds == null)
            {
                $pdf->SetFont('Times', 'B', 20);
                $pdf->Cell(0, 5, utf8_decode("NO RESIGNED"), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("RESIGNED"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportResigneds as $reportResigned) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportResigned->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportResigned->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportResigned->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportResigned->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Resigned'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
            }

            $pdf->Ln(5);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');

            
            /////////////////////REDOUBLES SI ECHEC////////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'The Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();
            $reportRepeatIfFailStudents = $this->reportRepository->findStudentsRepeaterIfFailedEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if($reportRepeatIfFailStudents == null)
            {
                $pdf->SetFont('Times', 'B', 20);
                $pdf->Cell(0, 5, utf8_decode("NO REPEATS IF FAILURE"), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("REPEATED IF FAILURE"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportRepeatIfFailStudents as $reportRepeatIfFailStudent) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportRepeatIfFailStudent->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportRepeatIfFailStudent->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportRepeatIfFailStudent->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportRepeatIfFailStudent->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Repeat if failure'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
            }

            $pdf->Ln(5);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');


            /////////////////////EXCLUS SI ECHEC////////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'The Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();

            $reportExpelledIfFailStudents = $this->reportRepository->findStudentsExpelledIfFailedEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if($reportExpelledIfFailStudents == null)
            {
                $pdf->SetFont('Times', 'B', 20);
                $pdf->Cell(0, 5, utf8_decode("NO EXCLUSIVE IF FAILURE"), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("EXCLUDED IF FAILURE"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportExpelledIfFailStudents as $reportExpelledIfFailStudent) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportExpelledIfFailStudent->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportExpelledIfFailStudent->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportExpelledIfFailStudent->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportExpelledIfFailStudent->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Excluded if failure'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
            }

            $pdf->Ln(5);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');

            

            /////////////////////RATTRAPAGE////////////////////////////
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 15, $fontSize-3);

            // Entête du bulletin

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // ENTETE DE LA FICHE
            $pdf->SetFont('Times', 'B', 14);

            $pdf->Cell(0, 5, utf8_decode("END OF THE YEAR CLASS COUNCIL"), 0, 2, 'C');
            
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode('School Year : '.$classroom->getSchoolYear()->getSchoolYear()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->Ln(2);
            $pdf->SetX(18);
            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(15, 5, 'Class : ', 0, 0, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(50, 5, utf8_decode($classroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', '', 12);
            $pdf->Cell(50, 5, 'The Head Teacher : ', 0, 0, 'L');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(15, 5, utf8_decode($classroom->getPrincipalTeacher()->getFullName()), 0, 1, 'L');

            $pdf->Ln();

            $reportCatchuppedStudents = $this->reportRepository->findStudentsCatchuppedEndYear($classroom);
            $i = 1;
            $pdf->SetFont('Times', '', 10);

            if($reportCatchuppedStudents == null)
            {
                $pdf->SetFont('Times', 'B', 20);
                $pdf->Cell(0, 5, utf8_decode("NO STUDENTS AT CATCH-UP"), 0, 1, 'C');
            }else
            {
                $pdf->SetFont('Times', 'BU', 14);
                $pdf->Cell(0, 5, utf8_decode("REMEDIAL STUDENTS"), 0, 1, 'C');

                $pdf->Ln();
                ///ENETE DU TABLEAU
                $pdf = $this->header($pdf, $subSystem);
                $pdf->SetFont('Times', '', 10);
                foreach ($reportCatchuppedStudents as $reportCatchuppedStudent) 
                { 
                    if ($i % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }
                    $pdf->Cell(8, 5, utf8_decode($i), 1, 0, 'C', true);
                    $pdf->Cell(85, 5, utf8_decode($reportCatchuppedStudent->getStudent()->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(25, 5, utf8_decode(date_format($reportCatchuppedStudent->getStudent()->getBirthday(), 'd-m-Y')), 1, 0, 'C', true);
                    $pdf->Cell(15, 5, utf8_decode($reportCatchuppedStudent->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                    $pdf->Cell(20, 5, utf8_decode($this->generalService->formatMark($reportCatchuppedStudent->getMoyenne())), 1, 0, 'C', true);

                    $pdf->SetFont('Times', '', 8);
                    $pdf->Cell(33, 5, utf8_decode('Catch-up'), 1, 1, 'C', true);
                    $pdf->SetFont('Times', '', 10);
                    
                    $i++;
                }
            }

            $pdf->Ln(5);

            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, $cellHeight7, utf8_decode('Done at'.$school->getPlace().', On _ _ _ _ _ _ _ _ _  '), 0, 0, 'R');

            $pdf->Ln();
            $pdf->Cell(90, $cellHeight7, utf8_decode('The Head Teacher'), 0, 0, 'L');
            $pdf->Cell(90, $cellHeight7, utf8_decode('The President of the Class Council'), 0, 0, 'R');
        }

        return $pdf;

    }

    public function header(Pagination $pdf, SubSystem $subSystem)
    {
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(8, 5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(85, 5, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode('Né(e) le'), 1, 0, 'C', true);
            $pdf->Cell(15, 5, utf8_decode('Genre'), 1, 0, 'C', true);
            $pdf->Cell(20, 5, utf8_decode('Moy. Ann.'), 1, 0, 'C', true);
            $pdf->Cell(33, 5, utf8_decode('Observations'), 1, 1, 'C', true);
        }else
        {
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(8, 5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell(85, 5, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell(25, 5, utf8_decode('Born'), 1, 0, 'C', true);
            $pdf->Cell(15, 5, utf8_decode('Gender'), 1, 0, 'C', true);
            $pdf->Cell(20, 5, utf8_decode('Avg. Ann.'), 1, 0, 'C', true);
            $pdf->Cell(33, 5, utf8_decode('Observations'), 1, 1, 'C', true);
        }
        

        return $pdf;
    }


}
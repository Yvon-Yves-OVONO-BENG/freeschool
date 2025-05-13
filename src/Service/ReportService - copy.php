<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\Skill;
use App\Entity\School;
use App\Entity\Student;
use App\Entity\Classroom;
use App\Entity\SubSystem;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Entity\ReportElements\Row;
use App\Repository\TermRepository;
use App\Repository\SkillRepository;
use App\Repository\ReportRepository;
use App\Repository\AbsenceRepository;
use App\Entity\ReportElements\NoFooter;
use App\Entity\ReportElements\Remember;
use App\Repository\EvaluationRepository;
use App\Entity\ReportElements\Discipline;
use App\Entity\ReportElements\ParentVisa;
use App\Entity\ReportElements\ReportBody;
use App\Entity\ReportElements\StudentWork;
use App\Repository\RegistrationRepository;
use App\Entity\ReportElements\ReportFooter;
use App\Entity\ReportElements\ReportHeader;
use App\Entity\ReportElements\StudentResult;
use App\Entity\ReportElements\HeadmasterVisa;
use App\Entity\ReportElements\ClassroomProfile;
use App\Entity\ReportElements\CommiteeDecision;
use App\Entity\ReportElements\WorkAppreciation;
use App\Repository\UnrankedCoefficientRepository;
use App\Entity\ReportElements\PrincipalTeacherVisa;

class ReportService 
{
    public function __construct(
        protected GeneralService $generalService, 
        protected TermRepository $termRepository, 
        protected SkillRepository $skillRepository, 
        protected ReportRepository $reportRepository, 
        protected AbsenceRepository $absenceRepository, 
        protected EvaluationRepository $evaluationRepository, 
        protected RegistrationRepository $registrationRepository,
        protected UnrankedCoefficientRepository $unrankedCoefficientRepository, 
        )
    {}

    /**
     * Calcule les moyennes trimestrielles des élèves et les classe par ordre de merite
     *
     * @param array $studentMarkTerm
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function getRankedStudents(array $studentMarkTerm, Classroom $classroom, Term $term, int $pv = 0): array
    {
        $rankedStudents = [];
        $rankedCaterory1 = [];
        $rankedCaterory2 = [];
        $rankedCaterory3 = [];
        $allRanked= [];

        $lessons = $classroom->getLessons();
        $numberOfLessons = count($lessons );
        $numberOfStudents = count($classroom->getStudents());
        $totalCoefficient = 0;
        $totalCoefficientFirstGroup = 0;
        $totalCoefficientCategory1 = 0;
        $totalCoefficientCategory2 = 0;
        $totalCoefficientCategory3 = 0;

        foreach ($lessons  as $lesson) 
        {
            $lessonCoefficient = $lesson->getCoefficient();
            // Total des coefficients de toutes les matières
            $totalCoefficient += $lessonCoefficient;

            // Total des coefficients des matières de base
            if($lesson->getSubjectGroup()->getSubjectGroup() == ConstantsClass::SUBJECT_FIRST_GROUP)
            {
                $totalCoefficientFirstGroup += $lessonCoefficient;
            }

            switch ($lesson->getSubject()->getCategory()->getCategory()) 
            {
                case ConstantsClass::CATEGORY1:
                    $totalCoefficientCategory1 += $lessonCoefficient;
                    break;

                case  ConstantsClass::CATEGORY2:
                    $totalCoefficientCategory2 += $lessonCoefficient;
                    break;

                case  ConstantsClass::CATEGORY3:
                    $totalCoefficientCategory3 += $lessonCoefficient;
                    break;
            }
        }

        $numberOfEvaluations = ($numberOfStudents * $numberOfLessons);

        if(!empty($studentMarkTerm) && ($numberOfEvaluations == count($studentMarkTerm)))
        {
            // On parcours les notes de chaque élève pour calculer sa moyenne
            for ($i = 0; $i < $numberOfStudents; $i++) 
            { 
                $beging = $i * $numberOfLessons;
                $end = $numberOfLessons + $beging;
    
                $totalStudentCoefficient = 0;
                $totalStudentCoefficientFirstGroup = 0;
                $totalStudentMark = 0;
                $moyenne = ConstantsClass::UNRANKED_AVERAGE;
                $appreciation = '//';
    
                $totalStudentCoefficientCatgory1 = 0;
                $totalStudentMarkCategory1 = 0;
                $averageCategory1 = ConstantsClass::UNRANKED_AVERAGE;
    
                $totalStudentCoefficientCatgory2 = 0;
                $totalStudentMarkCategory2 = 0;
                $averageCategory2 = ConstantsClass::UNRANKED_AVERAGE;
    
                $totalStudentCoefficientCatgory3 = 0;
                $totalStudentMarkCategory3 = 0;
                $averageCategory3 = ConstantsClass::UNRANKED_AVERAGE;

    
                for ($j = $beging; $j < $end; $j++) 
                { 
                    // on parcoure les évaluations de l'élève pour calculer les totaux et les moyennes
                    $mark = $studentMarkTerm[$j]->getMark();
                    $coefficient = $studentMarkTerm[$j]->getLesson()->getCoefficient();
                    $subjectGroup = $studentMarkTerm[$j]->getLesson()->getSubjectGroup()->getSubjectGroup();
                    $category = $studentMarkTerm[$j]->getLesson()->getSubject()->getCategory()->getCategory();
    
                    if($mark != ConstantsClass::UNRANKED_MARK)
                    {
                        // on incrémente le total général des coefficients de l'élève
                        $totalStudentCoefficient += $coefficient;
                        $totalStudentMark += $mark * $coefficient;

                        // on incrémente le total des coefficients des matières de base de l'élève
                        if($subjectGroup == ConstantsClass::SUBJECT_FIRST_GROUP)
                        {
                            $totalStudentCoefficientFirstGroup += $coefficient;
                        }
    
                        // On calule les totaux par groupe de matières
                        switch ($category) 
                        {
                            case ConstantsClass::CATEGORY1:
                                $totalStudentCoefficientCatgory1 += $coefficient;
                                $totalStudentMarkCategory1 += $mark * $coefficient;
                                break;
        
                            case ConstantsClass::CATEGORY2:
                                $totalStudentCoefficientCatgory2 += $coefficient;
                                $totalStudentMarkCategory2 += $mark * $coefficient;
                                break;
        
                            case ConstantsClass::CATEGORY3:
                                $totalStudentCoefficientCatgory3 += $coefficient;
                                $totalStudentMarkCategory3 += $mark * $coefficient;
                                break;
                        }
                    }
    
    
                }
                
                // on recupère les heures d'absence de l'élève
                $absence = 0;
                $decision = "";
                $motif = "";

                $student = $studentMarkTerm[$beging]->getStudent();
                $sex = $studentMarkTerm[$beging]->getStudent()->getSex()->getSex();

                if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                {
                    if($studentTermAbsence = $this->absenceRepository->findOneBy(['student' => $student, 'term' => $term]))
                    {
                        $absence += $studentTermAbsence->getAbsence();
                    }

                }else
                {
                    foreach ($student->getAbsences() as $studentAbsence) 
                    {
                        if($studentAbsence)
                        {
                            $absence += $studentAbsence->getAbsence();

                        }
                    }

                    
                }

                // Elèves classés
                $unrankedCoefficient = $this->unrankedCoefficientRepository->findOneByClassroom($classroom);
                if(!is_null($unrankedCoefficient))
                {
                    $outsideCoefficient = $unrankedCoefficient->getUnrankedCoefficient();
                    $forFirstGroup = $unrankedCoefficient->isForFirstGroup();

                    if($forFirstGroup)
                    {
                       $outside =  $totalStudentCoefficientFirstGroup - ($totalCoefficientFirstGroup -  $outsideCoefficient);
                    }else
                    {
                        $outside = $totalStudentCoefficient - ($totalCoefficient - $outsideCoefficient);
                    }
                }else
                {
                    $outside = 1;
                }
                
                foreach ($student->getConseils() as $studentConseil) 
                {
                    $decision = $studentConseil->getDecision();
                    $motif = $studentConseil->getMotif();
                    
                }

                if($outside >= 0)
                {
                    if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                    {
                        $moyenne = $this->generalService->getRatio($totalStudentMark, $totalStudentCoefficient);
                    }
                    else
                    {
                        $moyenneTerm1 = $this->reportRepository->findOneBy([
                            'student' => $student,
                            'term' => 1
                        ])->getMoyenne();

                        $moyenneTerm2 = $this->reportRepository->findOneBy([
                            'student' => $student,
                            'term' => 2
                        ])->getMoyenne();

                        $moyenneTerm3 = $this->reportRepository->findOneBy([
                            'student' => $student,
                            'term' => 3
                        ])->getMoyenne();

                        if($moyenneTerm1 == -1)
                        {
                            $moyenneTerm1 = 0;
                        }

                        if($moyenneTerm2 == -1)
                        {
                            $moyenneTerm2 = 0;
                        }

                        if($moyenneTerm3 == -1)
                        {
                            $moyenneTerm3 = 0;
                        }

                        // $moyenne = $this->generalService->getRatio($totalStudentMark, $totalStudentCoefficient);
                        $moyenne = number_format((
                            number_format($moyenneTerm1, 2, ".", "") + 
                            number_format($moyenneTerm2, 2, ".", "") + 
                            number_format($moyenneTerm3, 2, ".", "")
                            )/3, 2, ".", "");

                    }

                    
                    $averageCategory1 = $this->generalService->getRatio($totalStudentMarkCategory1, $totalStudentCoefficientCatgory1);
                    $averageCategory2 = $this->generalService->getRatio($totalStudentMarkCategory2, $totalStudentCoefficientCatgory2);
                    $averageCategory3 = $this->generalService->getRatio($totalStudentMarkCategory3, $totalStudentCoefficientCatgory3);
                    $appreciation = $this->generalService->getApoAppreciation($moyenne);
                }
                    $rankedStudents[] = [
                        'student' => $student,
                        'sex' => $sex,
                        'fullName' => $student->getFullName(),
    
                        'totalStudentCoefficient' => $totalStudentCoefficient,
                        'totalClassroomCoefficient' => $totalCoefficient,
                        'totalMark' => $totalStudentMark,
                        'moyenne' => $moyenne,
                        'rang' => -1,
                        'appreciation' => $appreciation,
    
                        'totalCoefficentCategory1' => $totalCoefficientCategory1,
                        'totalStudentCoefficentCategory1' => $totalStudentCoefficientCatgory1,
                        'totalMarkCategory1' => $totalStudentMarkCategory1,
                        'averageCategory1' => $averageCategory1,
    
                        'totalCoefficentCategory2' => $totalCoefficientCategory2,
                        'totalStudentCoefficentCategory2' => $totalStudentCoefficientCatgory2,
                        'totalMarkCategory2' => $totalStudentMarkCategory2,
                        'averageCategory2' => $averageCategory2,
    
                        'totalCoefficentCategory3' => $totalCoefficientCategory3,
                        'totalStudentCoefficentCategory3' => $totalStudentCoefficientCatgory3,
                        'totalMarkCategory3' => $totalStudentMarkCategory3,
                        'averageCategory3' => $averageCategory3,
    
                        'absence' => $absence,
                        'decision' => $decision,
                        'motif' => $motif,
                    ];
                
                    // on construit les tableau pour le classement par groupe de matières
                $rankedCaterory1[] = $averageCategory1;
                $rankedCaterory2[] = $averageCategory2;
                $rankedCaterory3[] = $averageCategory3;
               
            }

            
            $decision = "";
            $motif = "";

            // Classement
            if($pv == 1)
            {
                // on classe par ordre alphabétique
                $averageColum = array_column($rankedStudents, 'fullName');
                array_multisort($averageColum, SORT_ASC, $rankedStudents);
            }else
            {
                // On classe par ordre de mérite, c-à-d par ordre décroissant des moyennes(average)
                $averageColum = array_column($rankedStudents, 'moyenne');
                array_multisort($averageColum, SORT_DESC, $rankedStudents);
            }
           

            // On classe les moyennes de chaque groupe par ordre alphabétique
            rsort( $rankedCaterory1, SORT_NUMERIC);
            rsort( $rankedCaterory2, SORT_NUMERIC);
            rsort( $rankedCaterory3, SORT_NUMERIC);
    
             // On ajoute les rangs de chaque élève
             for($k = 0; $k < $numberOfStudents; $k++)
             {
                // si le student est non classé, on set le rang à -1
                if($rankedStudents[$k]['moyenne'] != ConstantsClass::UNRANKED_AVERAGE)
                {
                    $rankedStudents[$k]['rang'] = ($k+1);

                }else
                {
                    $rangedStudents[$k]['rang'] = ConstantsClass::UNRANKED_RANK_DB;
                }
             }
     
        }else
        {
            $allStudents = $classroom->getStudents();

            foreach($allStudents as $oneStudent)
            {
                $rankedStudents[] = [
                    'student' => $oneStudent,
                    'sex' => $oneStudent->getSex()->getSex(),
    
                    'totalStudentCoefficient' => 0,
                    'totalClassroomCoefficient' => 0,
                    'totalMark' => 0,
                    'moyenne' => ConstantsClass::UNRANKED_AVERAGE,
                    'appreciation' => '/',
    
                    'totalCoefficentCategory1' => 0,
                    'totalStudentCoefficentCategory1' => 0,
                    'totalMarkCategory1' => 0,
                    'averageCategory1' => ConstantsClass::UNRANKED_AVERAGE,
    
                    'totalCoefficentCategory2' => 0,
                    'totalStudentCoefficentCategory2' => 0,
                    'totalMarkCategory2' => 0,
                    'averageCategory2' => ConstantsClass::UNRANKED_AVERAGE,
    
                    'totalCoefficentCategory3' => 0,
                    'totalStudentCoefficentCategory3' => 0,
                    'totalMarkCategory3' => 0,
                    'averageCategory3' => ConstantsClass::UNRANKED_AVERAGE,
    
                    'absence' => 0,
                    'decision' => $oneStudent->getConseils(),
                    'motif' => $oneStudent->getConseils(),

                    'rang' => ConstantsClass::UNRANKED_RANK_DB
                ];
            
                // on construit les tableau pour le classement par groupe de matières
                $rankedCaterory1[] = ConstantsClass::UNRANKED_AVERAGE;
                $rankedCaterory2[] = ConstantsClass::UNRANKED_AVERAGE;
                $rankedCaterory3[] = ConstantsClass::UNRANKED_AVERAGE;

            }
        }

        $allRanked['rankedCategory1'] = $rankedCaterory1;
        $allRanked['rankedCategory2'] = $rankedCaterory2;
        $allRanked['rankedCategory3'] = $rankedCaterory3;
        $allRanked['rankedTerm'] = $rankedStudents;

        return $allRanked;
    }


    /**
     * Retourne un tableau des lessons et de leurs skill et de leurs notes par ordre de mérite
     *
     * @param array $studentMarkTerm
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function getRankPerLesson(array $studentMarkTerm, Classroom $classroom, Term $term): array
    {
        $rankPerLesson = [];

        if(!empty($studentMarkTerm))
        {

            $numberOfLessons = count($classroom->getLessons());
            $numberOfStudents = count($classroom->getStudents());
            $numberOfEvaluations = $numberOfLessons * $numberOfStudents;
            $rankPerLesson = [];
            
            for($i = 0; $i < $numberOfLessons; $i++)
            {
                $lessonMarkAndSkill = [];
                $lesson = $studentMarkTerm[$i]->getLesson();
                $lessonMArk = [];
                for($j = $i; $j < $numberOfEvaluations; $j += $numberOfLessons)
                {
                    //On construit le tableau des notes par lesson
                    $lessonMArk[] =  $studentMarkTerm[$j]->getMark();
                }
                    // On classe par ordre de mérite, c-à-d par ordre décroissant des notes
                    rsort( $lessonMArk, SORT_NUMERIC);

                // On recupère la compétence
                if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                {
                    $skill = $this->skillRepository->findOneBy([
                        'lesson' => $lesson,
                        'term' => $term
                    ]);  
                }else
                {
                    $skill = (new Skill())->setSkill("//");
                }
    
                $lessonMarkAndSkill['skill'] = $skill->getSkill();
                $lessonMarkAndSkill['lessonName'] = $lesson->getSubject()->getSubject(); 
                $lessonMarkAndSkill['lessonMark'] = $lessonMArk;
    
                $rankPerLesson[$lesson->getId()] =  $lessonMarkAndSkill;
            }
        }

        return  $rankPerLesson;
    }


    /**
     * Retourne l'entité ClassroomProfile contenant le profil de la classe
     *
     * @param array $rankedStudents
     * @return ClassroomProfile
     */
    public function getClassroomProfile(array $rankedStudents): ClassroomProfile
    {
        $classroomProfile = new ClassroomProfile();

        if($rankedStudents)
        {
            $classroomProfile->setClassroomAverage($this->getClassroomAverage($rankedStudents))
                        ->setSuccessRate($this->getSuccesRate($rankedStudents))
                        ->setFirstAverage($rankedStudents[0]['moyenne'])
                        ->setLastAverage($this->getLastAverage($rankedStudents))
            ; 
        }
        return $classroomProfile;
    }


    /**
     * Calcule et retourne la moyenne generale de la classe
     *
     * @param array $rankedStudents
     * @return float
     */
    public function getClassroomAverage(array $rankedStudents): float
    {
        if(!empty($rankedStudents))
        {
            $numberOfRankedStudents = 0;
            $totalAverage = 0;
            foreach ($rankedStudents as $rankedStudent) 
            {
                if($rankedStudent['moyenne'] != ConstantsClass::UNRANKED_AVERAGE)
                {
                    $numberOfRankedStudents++;
                    $totalAverage += $rankedStudent['moyenne'];
                }
            }
    
            return $this->generalService->getRatio($totalAverage, $numberOfRankedStudents);

        }else
        {
            return 0;
        }
    }

    /**
     * Calcule et retourne le taux e réussite de la classe 
     *
     * @param array $rankedStudents
     * @return float
     */
    public function getSuccesRate(array $rankedStudents): float
    {
        if($rankedStudents)
        {
            $numberOfRankedStudents = 0;
            $numberOfSuccessStudents = 0;
            foreach ($rankedStudents as $rankedStudent) 
            {
                if($rankedStudent['moyenne'] != ConstantsClass::UNRANKED_AVERAGE)
                {
                    $numberOfRankedStudents++;
    
                    if($rankedStudent['moyenne'] >= 10)
                        $numberOfSuccessStudents++;
                }
            }
    
            return $this->generalService->getRatio($numberOfSuccessStudents, $numberOfRankedStudents)*100;
        }else
        {
            return 0;
        }
    }


    /**
     * Recherche et retourne la plus faible average
     *
     * @param array $rankedStudents
     * @return float
     */
    public function getLastAverage(array $rankedStudents): float
    {
       $length = count($rankedStudents) - 1;
       for($i = $length; $i >= 0; $i--)
       {
           if($rankedStudents[$i]['moyenne'] != ConstantsClass::UNRANKED_AVERAGE)
            {
                return $rankedStudents[$i]['moyenne'];
            }
       }
        return 0;
    }


    /**
     * Imprime les noms des enseignants retardataires dans la saisie
     *
     * @param array $unrecordedEvalauations
     * @return PDF
     */
    public function printUnrecordedMark(array $unrecordedEvalauations): PDF
    {

        $pdf = new PDF();

        $pdf->AddPage();
        $pdf->SetFont('Times','B',14);
        $pdf->SetFillColor(200, 200, 200);

        $pdf->Cell(0,10,"Imposssible d'imprimer les bulletiins, Car il manque les notes suivantes : ", 0, 1, 'C');
        $pdf->Ln();
        $pdf->SetFont('Times','B',11);
        // entête du tableau
        $pdf->Cell(80,10,  "Noms de l'enseignant", 1, 0, 'C',  true);
            $pdf->Cell(20,10,  'Evaluation', 1, 0, 'C',  true);
            $pdf->Cell(30,10,  'Classe', 1, 0, 'C', true);
            $pdf->Cell(60,10,  utf8_decode('Matière'), 1, 0, 'C',  true);
            $pdf->Ln();

            // Contenu du tableau
        foreach ($unrecordedEvalauations as $evaluation) 
        {
            $lesson = $evaluation['lesson'];
            $sequence = $evaluation['sequence'];
            $pdf->Cell(80,7,  utf8_decode($lesson->getTeacher()->getFullName()), 1, 0, 'L');
            $pdf->Cell(20,7,  utf8_decode($sequence->getSequence()), 1, 0, 'C');
            $pdf->Cell(30,7,  utf8_decode($lesson->getClassroom()->getClassroom()), 1, 0, 'C');
            $pdf->Cell(60,7,  utf8_decode($lesson->getSubject()->getSubject()), 1,  0, 'C');
            $pdf->Ln();
            
        }
        return $pdf;
    }
    

    /**
     * Repère et retourne l'élève et sa position
     *
     * @param array $rankedStudents
     * @param integer $idS
     * @return array
     */
    public function getStudentPositionForOneReport(array $rankedStudents, int $idS): array
    {
        $studentIndex = [];
        $length = count($rankedStudents);
        for($i = 0; $i < $length; $i++)
        {
            if($rankedStudents[$i]['student']->getId() == $idS)
            {
                $studentIndex['index'] = $i;
                $studentIndex['student'] = $rankedStudents[$i]['student'];
                break;
            }
        }
        return  $studentIndex;
    }


    /**
     * Construire le header du studentReport
     *
     * @param School $school
     * @param Classroom $classroom
     * @param Student $student
     * @param Term $term
     * @return ReportHeader
     */
    public function getStudentReportHeader(School $school, Classroom $classroom, Student $student, Term $term, SubSystem $subSystem): ReportHeader
    {  
        $studentReportHeader = new ReportHeader();

        $studentReportHeader->setSchool($school)
            ->setStudent($student)
            ->setClassroom($classroom)
        ;

        if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
        {
            if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
            {
                $studentReportHeader->setTitle('BULLETIN SCOLAIRE TRIMESTRE '.$term->getTerm());
            } 
            else 
            {
                $studentReportHeader->setTitle('REPORT TERM '.$term->getTerm());
            }
            
        }else
        {
            if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
            {
                $studentReportHeader->setTitle('BULLETIN SCOLAIRE ANNUEL');
            } 
            else 
            {
                $studentReportHeader->setTitle('ANNUAL REPORT');
            }
           
        }

        return $studentReportHeader;
    }


    /**
     * Construire le body du studentReport
     *
     * @param array $studentMarkSequence1
     * @param array $studentMarkSequence2
     * @param array $studentMarkTerm
     * @param array $rankedStudents
     * @param array $rankPerLesson
     * @param integer $index
     * @param integer $numberOfLessons
     * @param array $rankedStudentsCategory1
     * @param array $rankedStudentsCategory2
     * @param array $rankedStudentsCategory3
     * @param array $studentMarkSequence3
     * @return ReportBody
     */
    public function getStudentReportBody(array $studentMarkSequence1, array $studentMarkSequence2, array $studentMarkTerm, array $rankedStudents,  int $index, int $numberOfLessons,  int $numberOfStudents, array $rankedStudentsCategory1, array $rankedStudentsCategory2, array $studentMarkSequence3 = [], SubSystem $subSystem, Term $selectedTerm, array $rankPerLesson, array $rankedStudentsCategory3, SchoolYear $schoolYear): ReportBody
    {
        if(count($studentMarkTerm) == ($numberOfLessons*$numberOfStudents))
        {
            $reportRowGroup1 = [];
            $reportRowGroup2 = [];
            $reportRowGroup3 = [];

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                // Resumé des notes du groupe 1
                $reportSummaryGroup1 = new StudentResult();
                $reportSummaryGroup1->setName('Formation Scientifique')
                            ->setTotalStudentCoefficient($rankedStudents[$index]['totalStudentCoefficentCategory1'])
                            ->setTotalClassroomCoefficient($rankedStudents[$index]['totalCoefficentCategory1'])
                            ->setTotalMark($rankedStudents[$index]['totalMarkCategory1'])
                            ->setMoyenne($rankedStudents[$index]['averageCategory1'])
                            ->setRang($this->getIndex($rankedStudents[$index]['averageCategory1'], $rankedStudentsCategory1));

                // Resumé des notes du groupe 2
                $reportSummaryGroup2 = new StudentResult();
                $reportSummaryGroup2->setName('Formation Littéraire')
                            ->setTotalStudentCoefficient($rankedStudents[$index]['totalStudentCoefficentCategory2'])
                            ->setTotalClassroomCoefficient($rankedStudents[$index]['totalCoefficentCategory2'])
                            ->setTotalMark($rankedStudents[$index]['totalMarkCategory2'])
                            ->setMoyenne($rankedStudents[$index]['averageCategory2'])
                            ->setRang($this->getIndex($rankedStudents[$index]['averageCategory2'], $rankedStudentsCategory2));

                // Resumé des notes du groupe 3
                $reportSummaryGroup3 = new StudentResult();
                $reportSummaryGroup3->setName('Formation Humaine')
                            ->setTotalStudentCoefficient($rankedStudents[$index]['totalStudentCoefficentCategory3'])
                            ->setTotalClassroomCoefficient($rankedStudents[$index]['totalCoefficentCategory3'])
                            ->setTotalMark($rankedStudents[$index]['totalMarkCategory3'])
                            ->setMoyenne($rankedStudents[$index]['averageCategory3'])
                            ->setRang($this->getIndex($rankedStudents[$index]['averageCategory3'], $rankedStudentsCategory3));
            }else
            {
                // Resumé des notes du groupe 1
                $reportSummaryGroup1 = new StudentResult();
                $reportSummaryGroup1->setName('Scientific Training ')
                            ->setTotalStudentCoefficient($rankedStudents[$index]['totalStudentCoefficentCategory1'])
                            ->setTotalClassroomCoefficient($rankedStudents[$index]['totalCoefficentCategory1'])
                            ->setTotalMark($rankedStudents[$index]['totalMarkCategory1'])
                            ->setMoyenne($rankedStudents[$index]['averageCategory1'])
                            ->setRang($this->getIndex($rankedStudents[$index]['averageCategory1'], $rankedStudentsCategory1));

                // Resumé des notes du groupe 2
                $reportSummaryGroup2 = new StudentResult();
                $reportSummaryGroup2->setName('Literary Training ')
                            ->setTotalStudentCoefficient($rankedStudents[$index]['totalStudentCoefficentCategory2'])
                            ->setTotalClassroomCoefficient($rankedStudents[$index]['totalCoefficentCategory2'])
                            ->setTotalMark($rankedStudents[$index]['totalMarkCategory2'])
                            ->setMoyenne($rankedStudents[$index]['averageCategory2'])
                            ->setRang($this->getIndex($rankedStudents[$index]['averageCategory2'], $rankedStudentsCategory2));

                // Resumé des notes du groupe 3
                $reportSummaryGroup3 = new StudentResult();
                $reportSummaryGroup3->setName('Human Training')
                            ->setTotalStudentCoefficient($rankedStudents[$index]['totalStudentCoefficentCategory3'])
                            ->setTotalClassroomCoefficient($rankedStudents[$index]['totalCoefficentCategory3'])
                            ->setTotalMark($rankedStudents[$index]['totalMarkCategory3'])
                            ->setMoyenne($rankedStudents[$index]['averageCategory3'])
                            ->setRang($this->getIndex($rankedStudents[$index]['averageCategory3'], $rankedStudentsCategory3));
            }
                
            // On recupère la position du student dans le tableau $studentMarkTerm
            $position = $this->getPosition($studentMarkTerm, $rankedStudents, $index);

            // On calule la position de la dernière lesson du student
            $positionEnd = $position + $numberOfLessons;

            // on parcours les lessons du student pour construire les lignes de son bulletin
            for($j = $position; $j < $positionEnd; $j++)
            {
                $mark = $studentMarkTerm[$j]->getMark();
                $coef = $studentMarkSequence1[$j]->getLesson()->getCoefficient();
                $lessonId = $studentMarkSequence1[$j]->getLesson()->getId();

                if(!empty($rankPerLesson))
                {
                    $skill = $rankPerLesson[$lessonId]['skill'];
                    $rang = $this->getIndex($mark, $rankPerLesson[$lessonId]['lessonMark']);
                }else
                {
                    $skill = '/';
                    $rang = ConstantsClass::UNRANKED_RANK_DB;
                }

                // Les lignes du bulletin correspondantes aux matières
                $reportRow = new Row();
                $reportRow->setSubject($studentMarkSequence1[$j]->getLesson()->getSubject()->getSubject())
                    ->setTeacher($studentMarkSequence1[$j]->getLesson()->getTeacher()->getFullName())
                    ->setSkill($skill)
                    ->setEvaluation1($studentMarkSequence1[$j]->getMark())
                    ->setEvaluation2($studentMarkSequence2[$j]->getMark())
                    ->setMoyenne($mark)
                    ->setCoefficient($coef)
                    ->setTotal($mark*$coef)
                    ->setRang($rang)
                    ->setAppreciationFr($this->generalService->getApcAppreciationFr($mark))
                    ;
                            
                if(!empty($studentMarkSequence3))
                {
                    $reportRow->setEvaluation3($studentMarkSequence3[$j]->getMark());

                }
                
                switch ($studentMarkSequence1[$j]->getLesson()->getSubject()->getCategory()->getCategory()) 
                {
                    case ConstantsClass::CATEGORY1:
                        $reportRowGroup1[] = $reportRow;
                        break;
                    
                    case ConstantsClass::CATEGORY2:
                        $reportRowGroup2[] = $reportRow;
                        break;

                    case ConstantsClass::CATEGORY3:
                        $reportRowGroup3[] = $reportRow;
                        break;
                }
            }

            $reportBody = new ReportBody();

            return $reportBody->setRowsGroup1($reportRowGroup1)
                        ->setRowsGroup2($reportRowGroup2)
                        ->setRowsGroup3($reportRowGroup3)
                        ->setSummaryGroup1($reportSummaryGroup1)
                        ->setSummaryGroup2($reportSummaryGroup2)
                        ->setSummaryGroup3($reportSummaryGroup3);
                        
        }else
        {
            $reportBody = new ReportBody();

            return $reportBody->setRowsGroup1([])
                        ->setRowsGroup2([])
                        ->setRowsGroup3([])
                        ->setSummaryGroup1(new StudentResult())
                        ->setSummaryGroup2(new StudentResult())
                        ->setSummaryGroup3(new StudentResult());
        }
        
    }


    /**
     * Construit le reportFooter du studentRepot
     *
     * @param array $studentResults
     * @param ClassroomProfile $reportClassroomProfile
     * @param Term $term
     * @return ReportFooter
     */
    public function getStudentReportFooter(array $studentResults, ClassroomProfile $reportClassroomProfile, Term $term): ReportFooter
    {   
        // on construit la partie des résultats de l'élève
        // dd($studentResults);
        $reportResult = new StudentResult();
        $reportResult->setTotalStudentCoefficient($studentResults['totalStudentCoefficient'])
            ->setTotalClassroomCoefficient($studentResults['totalClassroomCoefficient'])
            ->setTotalMark($studentResults['totalMark'])
            ->setMoyenne($studentResults['moyenne'])
            // ->setRang(1)
            ->setRang($studentResults['rang'])
            ;

        // On construit la partie des rappels
        $reportRemember = new Remember();
        $reportRemember->setName('Rappels');
    
        switch($term->getTerm())
        {
            case 1: 
                // Pas de rappel au trimestre 1 
            break;

            case 2:
                // Moyenne et rang du trimestre 1
                $report1 = $this->reportRepository->findOneBy([
                    'student' => $studentResults['student'],
                    'term' => $this->termRepository->findOneBy(['term' => 1])
                ]);

                $reportRemember->setMoyenneTerm1($report1->getMoyenne())
                        ->setRank1($report1->getRang())
                ;
            break;

            case 3:
                // Moyenne et rang du trimestre 2
                $report2 = $this->reportRepository->findOneBy([
                    'student' => $studentResults['student'],
                    'term' => $this->termRepository->findOneBy(['term' => 2])
                ]);
                // Moyenne et rang du trimestre 1
                $report1 = $this->reportRepository->findOneBy([
                    'student' => $studentResults['student'],
                    'term' => $this->termRepository->findOneBy(['term' => 1])
                ]);

                $reportRemember->setMoyenneTerm1($report1->getMoyenne())
                        ->setRank1($report1->getRang())
                        ->setMoyenneTerm2($report2->getMoyenne())
                        ->setRank2($report2->getRang())
                ;
            break;

            case 0:
                // Moyenne et rang du trimestre 3
                $report3 = $this->reportRepository->findOneBy([
                    'student' => $studentResults['student'],
                    'term' => $this->termRepository->findOneBy(['term' => 3])
                ]);
                // Moyenne et rang du trimestre 2
                $report2 = $this->reportRepository->findOneBy([
                    'student' => $studentResults['student'],
                    'term' => $this->termRepository->findOneBy(['term' => 2])
                ]);
                // Moyenne et rang du trimestre 1
                $report1 = $this->reportRepository->findOneBy([
                    'student' => $studentResults['student'],
                    'term' => $this->termRepository->findOneBy(['term' => 1])
                ]);
    
                $reportRemember->setMoyenneTerm1($report1->getMoyenne())
                        ->setRank1($report1->getRang())
                        ->setMoyenneTerm2($report2->getMoyenne())
                        ->setRank2($report2->getRang())
                        ->setMoyenneTerm3($report3->getMoyenne())
                        ->setRank3($report3->getRang())
                ;
            break;
        }

        // On construit la partie discipline
        $reportDiscipline = new Discipline();
        $absence = $studentResults['absence'];

        $reportDiscipline->setAbsence($absence)
            ->setWarningBehaviour($this->getWarningBehaviour($absence))
            ->setBlameBehaviour($this->getBlameBehaviour($absence))
            ->setExclusion($this->getExclusion($absence))
            ->setDisciplinaryCommitee($this->getDisciplinaryCommitee($absence));
        
        // On construit la parie travail de l'élève
        $reportWork = new StudentWork();
        $moyenne = $studentResults['moyenne'];
        $reportWork->setRollOfHonour($this->getRollOfHonour($moyenne))
            ->setEncouragement($this->getEncouragement($moyenne))
            ->setCongratulation($this->getCongratulation($moyenne))
            ->setWarningWork($this->getWarningWork($moyenne))
            ->setBlameWork($this->getBlameWork($moyenne));

        // On construit la partie Appréciation du travail
        $reportWorkAppreciation = new WorkAppreciation();
        $reportWorkAppreciation->setAppreciation($this->generalService->getApoAppreciation($moyenne));

        // on construit la partie décision du conseil de classes
        $commiteeDecision = new CommiteeDecision();

        // on construit la partie Visa du proviseur
        $headmasterVisa = new HeadmasterVisa();

        // on construit la partie Visa du parent
        $parentVisa = new ParentVisa();

        // on construit la partie visa du professeur principal
        $principalTeacherVisa = new PrincipalTeacherVisa();

        // On set le reportFooter
        $studentReportFooter = new ReportFooter();
        return $studentReportFooter->setStudentResult($reportResult)
            ->setRemember($reportRemember)
            ->setClassroomProfile($reportClassroomProfile)
            ->setDiscipline($reportDiscipline)
            ->setStudentWork($reportWork)
            ->setWorkAppreciation($reportWorkAppreciation)
            ->setCommiteeDecision($commiteeDecision)
            ->setHeadmasterVisa($headmasterVisa)
            ->setParentVisa($parentVisa)
            ->setPrincipalTeacherVisa($principalTeacherVisa);

    }
    
    
    /**
     * Retourne l'index d'une valeur dana un tableau
     *
     * @param [type] $value
     * @param array $elements
     * @return integer
     */
    public function getIndex($value, array $elements): int
    {
        $length = count($elements);
 
         for ($i=0; $i < $length; $i++) 
         { 
            if($elements[$i] == $value)
            {
                return $i+1;
            }
         }
 
         return 0;
    }

   /**
    * Cherche et retourne la position de l'élève $rankedStudents[$index] dans le tableau $studentMarkTerm
    *
    * @param array $studentMarkTerm
    * @param array $rankedStudents
    * @param integer $index
    * @return integer
    */
    public function getPosition(array $studentMarkTerm, array $rankedStudents, int $index): int
    {
        $length = count($studentMarkTerm);

        for($i = 0; $i < $length; $i++)
        {
            if($studentMarkTerm[$i]->getStudent()->getId() == $rankedStudents[$index]['student']->getId())
            {
                return $i; 
            }
        }

        return -1;
    }

    /**
     * Determine s'il y a avertissement conduite
     *
     * @param integer $absence
     * @return string
     */
    public function getWarningBehaviour(int $absence): string
    {
        return ($absence >= ConstantsClass::WARNING_BAHAVIOUR) ? 'X' : '';
    }

    /**
     * Détermine s'il y a blâme conduite
     *
     * @param integer $absence
     * @return string
     */
    public function getBlameBehaviour(int $absence): string
    {
        return ($absence >=  ConstantsClass::BLAME_BAHAVIOUR) ? 'X' : '';
    }


    /**
     * Retourne les jours d'exclusion selon le nombre d'heures d'absence
     *
     * @param integer $absence
     * @return integer
     */
    public function getExclusion(int $absence): int
    {
        if($absence < ConstantsClass::EXCLUSION_3_DAYS)
            return 0;
        elseif($absence >=  ConstantsClass::EXCLUSION_3_DAYS && $absence <  ConstantsClass::EXCLUSION_5_DAYS)
            return 3;
        elseif($absence >=  ConstantsClass::EXCLUSION_5_DAYS && $absence <  ConstantsClass::EXCLUSION_8_DAYS)
            return 5;
        elseif($absence >=  ConstantsClass::EXCLUSION_8_DAYS)
            return 8;
    }


    /**
     * Détermine si l'élève est traduit ou non au conseil de discipline 
     *
     * @param integer $absence
     * @return string
     */
    public function getDisciplinaryCommitee(int $absence): string
    {
        return ($absence >= ConstantsClass::DISCIPLINARY_COMMITEE) ? 'X' : '';
    }

    /**
     * Détermine s'il y tableau d'honneur
     *
     * @param float $mark
     * @return string
     */
    public function getRollOfHonour(float $mark): string
    {
        return ($mark >=  ConstantsClass::ROLL_OF_HONOUR) ? 'X' : '';
    }

    /**
     * Détermine s'il y a encouragement
     *
     * @param float $mark
     * @return string
     */
    public function getEncouragement(float $mark): string
    {
        return ($mark >=  ConstantsClass::ENCOURAGEMENT) ? 'X' : '';
    }

    /**
     * Détermine s'il y a félicitation
     *
     * @param float $mark
     * @return string
     */
    public function getCongratulation(float $mark): string
    {
        return ($mark >=  ConstantsClass::CONGRATULATION) ? 'X' : '';
    }

    /**
     * Détermine s'il y a avertissement travail
     *
     * @param float $mark
     * @return string
     */
    public function getWarningWork(float $mark): string
    {
        return ($mark < ConstantsClass::WARNING_WORK) ? 'X' : '';
    }

    /**
     * Détermine s'il y a blâme travail
     *
     * @param float $mark
     * @return string
     */
    public function getBlameWork(float $mark): string
    {
        return ($mark < ConstantsClass::BLAME_WORK) ? 'X' : '';
    }


    public function printReport(School $school, array $allStudentReports, int $numberOfLessons, 
    Term $term, SchoolYear $schoolYear, int $numberOfStudents, int $numberOfBoys, int $numberOfGirls, 
    SubSystem $subSystem)
    {
        if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
        {
            $cellEvaluationWidth = 10;
            $cellAverageWidth = 15;
            $cellCoefficientWidth = 11;
            $cellTotalWidth = 15;
            $cellRankWidth = 11;
            $cellAppreciationWidth = 20;
            $cellSubjectWidth = 40;
            $cellSkillWidth = 56;
            
            switch($term->getTerm())
            {
                case 1:
                    $e1 = 'Eval 1';
                    $e2 = 'Eval 2';
                break;

                case 2:
                    $e1 = 'Eval 3';
                    $e2 = 'Eval 4';
                break;

                case 3:
                    $e1 = 'Eval 5';
                    $e2 = 'Eval 6';
                break;
            }
        }else
        {

            $cellEvaluationWidth = 15;
            $cellAverageWidth = 21;
            $cellCoefficientWidth = 17;
            $cellTotalWidth = 21;
            $cellRankWidth = 17;
            $cellAppreciationWidth = 25;
            $cellSubjectWidth = 42;

            $cellSkillWidth = 56;

            $e1 = 'Trim 1';
            $e2 = 'Trim 2';
            $e3 = 'Trim 3';
        }

        $pdf = new PDF();
        $fontSize = 10;

        $cellTableBodyHeight = 209/($numberOfLessons+18);
        $cellHeaderHeight = 3;
        $cellHeaderStudentHeight = 4;
        $cellTableHeaderHeight = $cellTableBodyHeight;

        $cellRecapNameWidth = 63;
        $cellRecapTotalCoefficientWidth = 33;
        $cellRecapTotalMarkWidth = 37;
        $cellRecapAverageWidth = 32;
        $cellRecapRankWidth = 23;
        
        foreach($allStudentReports as $report)
        {
            //////////////si l'élève a payé tous ses frais
            $conseil = 0;
            if($report->getReportHeader()->getStudent()->isSolvable() == 1)
            {
                foreach($report->getReportHeader()->getStudent()->getConseils() as $conseil)
                {
                    if($conseil)
                    {
                        $decision = $conseil->getDecision();
                        $motif = $conseil->getMotif();
                    }
                    else
                    {
                        $decision = "";
                        $motif = "";
                    }
                }
                
                $reportHeader = $report->getReportHeader();
                $reportBody = $report->getReportBody();
                $reportFooter = $report->getReportFooter();

                $classroom = $reportHeader->getClassroom();

                // On insère une page
                $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);

                // Entête du bulletin

                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeader($reportHeader->getSchool(), $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

                // Partie de l'entête comportant les info de l'élève
                $student = $reportHeader->getStudent();
                $pdf->SetFont('Times', 'B', $fontSize+4);
                
                $pdf->Ln();
                // $pdf->Rect(57, 45, 100, 10,'DF');
                // $pdf->AddFont('Monotype Corsiva','','makefont1.php');
                $pdf->RoundedRect(57, 45, 100, 10, 3.5, 'DF');
                $pdf->Cell(0, $cellHeaderStudentHeight+1, $reportHeader->getTitle(), 0, 1, 'C');

                $pdf->Ln();
                
                if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
                {
                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(17, $cellHeaderStudentHeight, 'Matricule : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);

                    $pdf->Cell(50, $cellHeaderStudentHeight, $student->getRegistrationNumber(), 0, 0, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(36);
                    $pdf->Cell(13, $cellHeaderStudentHeight, 'Classe : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize);

                    $pdf->Cell(20, $cellHeaderStudentHeight, utf8_decode($classroom->getClassroom()), 0, 1, 'L');
                    
                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(28, $cellHeaderStudentHeight, utf8_decode('Noms et Prénoms : '), 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize+2);

                    $pdf->Cell(35, $cellHeaderStudentHeight, utf8_decode($student->getFullName()), 0, 1, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);
                    

                    $pdf->Cell(38, $cellHeaderStudentHeight, 'Date et lieu de naissance : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);
                    
                    $pdf->Cell(35, $cellHeaderStudentHeight, $student->getBirthday()->format('d/m/Y').''.utf8_decode(' à ').''.utf8_decode($student->getBirthplace()), 0, 0, 'L');
                    $pdf->Cell(30);

                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(15, $cellHeaderStudentHeight, 'Effectif : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);

                    $pdf->Cell(35, $cellHeaderStudentHeight, utf8_decode('Garçons : ').$numberOfBoys.'    Filles : '.$numberOfGirls, 0, 1, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);
                    $pdf->Cell(13, $cellHeaderStudentHeight, 'Sexe : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);
                    $pdf->Cell(10, $cellHeaderStudentHeight, $student->getSex()->getSex(), 0, 0, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);
                    $pdf->Cell(22, $cellHeaderStudentHeight, 'Redoublant(e) : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);
                    $pdf->Cell(58, $cellHeaderStudentHeight, $student->getRepeater()->getRepeater(), 0, 0, 'L');
                    
                    $pdf->Cell(20, $cellHeaderStudentHeight, 'Total : '.$numberOfStudents, 0, 1, 'L');
                    

                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(33, $cellHeaderStudentHeight, 'Professeur Principal : ', 0, 0, 'L');
                } else 
                {
                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(30, $cellHeaderStudentHeight, 'Registration number : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);

                    $pdf->Cell(27, $cellHeaderStudentHeight, $student->getRegistrationNumber(), 0, 0, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(45);
                    $pdf->Cell(13, $cellHeaderStudentHeight, 'Class : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize);

                    $pdf->Cell(20, $cellHeaderStudentHeight, utf8_decode($classroom->getClassroom()), 0, 1, 'L');
                    
                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(28, $cellHeaderStudentHeight, utf8_decode('First and last name : '), 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize+2);

                    $pdf->Cell(35, $cellHeaderStudentHeight, utf8_decode($student->getFullName()), 0, 1, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);
                    

                    $pdf->Cell(38, $cellHeaderStudentHeight, 'Date and place of birth : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);
                    
                    $pdf->Cell(35, $cellHeaderStudentHeight, $student->getBirthday()->format('d/m/Y').''.utf8_decode(' à ').''.utf8_decode($student->getBirthplace()), 0, 0, 'L');
                    $pdf->Cell(30);

                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(15, $cellHeaderStudentHeight, 'Effective : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);

                    $pdf->Cell(35, $cellHeaderStudentHeight, utf8_decode('Boys : ').$numberOfBoys.'    Girls : '.$numberOfGirls, 0, 1, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);
                    $pdf->Cell(13, $cellHeaderStudentHeight, 'Sex : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);
                    $pdf->Cell(10, $cellHeaderStudentHeight, $student->getSex()->getSex(), 0, 0, 'L');

                    $pdf->SetFont('Times', '', $fontSize-1);
                    $pdf->Cell(22, $cellHeaderStudentHeight, 'Repeater : ', 0, 0, 'L');

                    $pdf->SetFont('Times', 'B', $fontSize-1);

                    if ($student->getRepeater()->getRepeater() == constantsClass::REPEATER_NO) 
                    {
                        $pdf->Cell(58, $cellHeaderStudentHeight, "No", 0, 0, 'L');
                    } else 
                    {
                        $pdf->Cell(58, $cellHeaderStudentHeight, "Yes", 0, 0, 'L');
                    }
                    
                    
                    
                    $pdf->Cell(20, $cellHeaderStudentHeight, 'Total : '.$numberOfStudents, 0, 1, 'L');
                    

                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell(33, $cellHeaderStudentHeight, 'Head teacher : ', 0, 0, 'L');
                }
                

                $pdf->SetFont('Times', 'B', $fontSize-1);

                $principalTeacher = $classroom->getPrincipalTeacher();

                $pdf->Cell(20, $cellHeaderStudentHeight, utf8_decode($this->generalService->getNameWithTitle($principalTeacher->getFullName(), $principalTeacher->getSex()->getSex())  ), 0, 1, 'L');
                
                $x = $pdf->GetX();
                $y = $pdf->GetY();

                if($student->getPhoto())
                {
                    $pdf->Image('images/students/'.$student->getPhoto(), 173, 45, 30, 30);
                }else
                {
                    if($student->getSex()->getSex() == 'F')
                    {
                        $pdf->Image('images/students/fille.jpg', 173, 45, 30, 30);
                    }
                    else
                    {
                        $pdf->Image('images/students/garcon.jpg', 173, 45, 30, 30);
                    }
                    
                }

                $pdf->SetXY($x, $y+2);
                $pdf->SetFont('Times', 'B', $fontSize-1);

                // CORPS DU BULLETIN

                    // Entête du tableau
                
                $pdf->Cell($cellSubjectWidth, $cellTableHeaderHeight*2/3, 'DISCIPLINES', 'LTR', 0, 'C', true);

                $x = $pdf->GetX();
                $y = $pdf->GetY();
                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Ln();

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellSubjectWidth, $cellTableHeaderHeight/3, 'Enseignants', 'LBR', 0, 'L', true);

                    $pdf->SetXY($x, $y);
                    $pdf->SetFont('Times', 'B', $fontSize);

                    if($term->getTerm() != ConstantsClass::ANNUEL_TERM) 
                    {
                        $pdf->Cell($cellSkillWidth, $cellTableHeaderHeight, utf8_decode('Compétences visées'), 1, 0, 'C', true);

                    }

                    if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-1);
                    }

                    $pdf->Cell($cellEvaluationWidth, $cellTableHeaderHeight, $e1, 1, 0, 'C', true);
                    $pdf->Cell($cellEvaluationWidth, $cellTableHeaderHeight, $e2, 1, 0, 'C', true);

                    if($term->getTerm() == ConstantsClass::ANNUEL_TERM)
                    {
                        $pdf->Cell($cellEvaluationWidth, $cellTableHeaderHeight, $e3, 1, 0, 'C', true);

                    }

                    $pdf->SetFont('Times', 'B', $fontSize);
                        
                    $pdf->Cell($cellAverageWidth, $cellTableHeaderHeight, 'Moy', 1, 0, 'C', true);
                    $pdf->Cell($cellCoefficientWidth, $cellTableHeaderHeight, 'Coef', 1, 0, 'C', true);
                    $pdf->Cell($cellTotalWidth, $cellTableHeaderHeight, 'Total', 1, 0, 'C', true);
                    $pdf->Cell($cellRankWidth, $cellTableHeaderHeight, 'Rang', 1, 0, 'C', true);

                    $pdf->SetFont('Times', 'B', $fontSize-2);
                    
                    $pdf->Cell($cellAppreciationWidth, $cellTableHeaderHeight*2/3, utf8_decode('Appréciation'), 'LTR', 2, 'L', true);

                    $pdf->SetFont('Times', 'B', $fontSize-3);

                    $pdf->Cell($cellAppreciationWidth, $cellTableHeaderHeight/3, utf8_decode('Emargement'), 'LBR', 1, 'R', true);

                }else
                {
                    $pdf->Cell($cellSubjectWidth, $cellTableHeaderHeight/3, 'Teachers', 'LBR', 0, 'L', true);

                    $pdf->SetXY($x, $y);
                    $pdf->SetFont('Times', 'B', $fontSize);

                    if($term->getTerm() != ConstantsClass::ANNUEL_TERM) 
                    {
                        $pdf->Cell($cellSkillWidth, $cellTableHeaderHeight, utf8_decode('Target skill'), 1, 0, 'C', true);

                    }

                    if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
                    {
                        $pdf->SetFont('Times', 'B', $fontSize-1);
                    }

                    $pdf->Cell($cellEvaluationWidth, $cellTableHeaderHeight, $e1, 1, 0, 'C', true);
                    $pdf->Cell($cellEvaluationWidth, $cellTableHeaderHeight, $e2, 1, 0, 'C', true);

                    if($term->getTerm() == ConstantsClass::ANNUEL_TERM)
                    {
                        $pdf->Cell($cellEvaluationWidth, $cellTableHeaderHeight, $e3, 1, 0, 'C', true);

                    }

                    $pdf->SetFont('Times', 'B', $fontSize);
                        
                    $pdf->Cell($cellAverageWidth, $cellTableHeaderHeight, 'Avg', 1, 0, 'C', true);
                    $pdf->Cell($cellCoefficientWidth, $cellTableHeaderHeight, 'Coef', 1, 0, 'C', true);
                    $pdf->Cell($cellTotalWidth, $cellTableHeaderHeight, 'Total', 1, 0, 'C', true);
                    $pdf->Cell($cellRankWidth, $cellTableHeaderHeight, 'Rank', 1, 0, 'C', true);

                    $pdf->SetFont('Times', 'B', $fontSize-2);
                    
                    $pdf->Cell($cellAppreciationWidth, $cellTableHeaderHeight*2/3, utf8_decode('Appreciation'), 'LTR', 2, 'L', true);

                    $pdf->SetFont('Times', 'B', $fontSize-3);

                    $pdf->Cell($cellAppreciationWidth, $cellTableHeaderHeight/3, utf8_decode('Registration'), 'LBR', 1, 'R', true);
                }
                

                    // Contenu du tableau
                        // Groupe 1
                $pdf = $this->displayMarkGroup($reportBody->getRowsGroup1(), $reportBody->getSummaryGroup1(), $pdf, $fontSize, $cellSubjectWidth, $cellTableBodyHeight, $term, $cellSkillWidth, $cellEvaluationWidth, $cellAverageWidth, $cellCoefficientWidth, $cellTotalWidth, $cellRankWidth, $cellAppreciationWidth, $cellRecapNameWidth, $cellRecapTotalCoefficientWidth, $cellRecapTotalMarkWidth, $cellRecapAverageWidth, $cellRecapRankWidth, $student);

                // Groupe 2
                $pdf = $this->displayMarkGroup($reportBody->getRowsGroup2(), $reportBody->getSummaryGroup2(), $pdf, $fontSize, $cellSubjectWidth, $cellTableBodyHeight, $term, $cellSkillWidth, $cellEvaluationWidth, $cellAverageWidth, $cellCoefficientWidth, $cellTotalWidth, $cellRankWidth, $cellAppreciationWidth, $cellRecapNameWidth, $cellRecapTotalCoefficientWidth, $cellRecapTotalMarkWidth, $cellRecapAverageWidth, $cellRecapRankWidth, $student);

                // Groupe 3
                $pdf = $this->displayMarkGroup($reportBody->getRowsGroup3(), $reportBody->getSummaryGroup3(), $pdf, $fontSize, $cellSubjectWidth, $cellTableBodyHeight, $term, $cellSkillWidth, $cellEvaluationWidth, $cellAverageWidth, $cellCoefficientWidth, $cellTotalWidth, $cellRankWidth, $cellAppreciationWidth, $cellRecapNameWidth, $cellRecapTotalCoefficientWidth, $cellRecapTotalMarkWidth, $cellRecapAverageWidth, $cellRecapRankWidth, $student);

                $pdf->Cell(0, 2, '', 0, 1, 'C');

                // Pied de bulletin
                $counter = 0;

                if($term->getTerm() == 1)
                {
                    $w11 = 21.5;
                    $w21 = 24;
                    $w12 = 27.5;
                    $w22 = 18;
                    $w0 = 45.5;

                    $space = 2;
                }else
                {
                    $w11 = 15;
                    $w21 = 21.8;
                    $w12 = 21;
                    $w22 = 15.8;
                    $w0 = 36.8;

                    $space = 1;
                }
                
                //  Résultats de l'élève
                $reportResult = $reportFooter->getStudentResult();
                
                $pdf->SetFont('Times', 'B', $fontSize);

                $moyenne = $reportResult->getMoyenne();
                
                if($moyenne == ConstantsClass::UNRANKED_AVERAGE)
                {
                    $totalMark = '//';
                    $moyenne = '//';
                    $rang = ConstantsClass::UNRANKED_RANK;
                }
                else
                {
                    $totalMark = $this->generalService->formatMark($reportResult->getTotalMark());
                    $moyenne = $this->generalService->formatMark($reportResult->getMoyenne());
                    // $rang = $this->generalService->formatRank($reportResult->getRang(), $student->getSex()->getSex());

                    $eleve = $this->reportRepository->findBy(
                        [
                        'student' => $student->getId(),
                        'term' => $term,
                        ]
                    );
                    // dd($eleve[0]->getRang());

                    $rang = $this->generalService->formatRank($eleve[0]->getRang(), $student->getSex()->getSex());

                }
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0, $cellTableBodyHeight, utf8_decode($reportResult->getName()), 1, 0, 'C', true);
                }
                else
                {
                    $pdf->Cell($w0, $cellTableBodyHeight, utf8_decode($reportResult->getNameEnglish()), 1, 0, 'C', true);
                }
                

                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->Ln();
                $pdf->SetFont('Times', '', $fontSize-1);
                        
                $pdf->Cell($w11, $cellTableBodyHeight, 'Total Points  ', 'L', 0, 'L');
                        
                $pdf->SetFont('Times', 'B', $fontSize-1);
                        
                $pdf->Cell($w21, $cellTableBodyHeight, $totalMark, 'R', 1, 'C');
                        
                $pdf->SetFont('Times', '', $fontSize-1);
                        
                $pdf->Cell($w11, $cellTableBodyHeight, 'Total Coefs  ', 'L', 0, 'L');
                        
                $pdf->SetFont('Times', 'B', $fontSize-1);
                        
                $pdf->Cell($w21, $cellTableBodyHeight, $reportResult->getTotalStudentCoefficient().' / '.$reportResult->getTotalClassroomCoefficient(), 'R', 1, 'C');
                            
                $pdf->SetFont('Times', '', $fontSize-1);
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w11, $cellTableBodyHeight, 'Moyenne  ', 'L', 0, 'L');
                }
                else
                {
                    $pdf->Cell($w11, $cellTableBodyHeight, 'Average  ', 'L', 0, 'L');
                }
                
                        
                $pdf->SetFont('Times', 'B', $fontSize+4);
                        
                $pdf->Cell($w21, $cellTableBodyHeight, $moyenne, 'R', 1, 'C');

                $pdf->SetFont('Times', '', $fontSize-1);
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w11, $cellTableBodyHeight, 'Rang  ', 'L', 0, 'L');
                }
                else
                {
                    $pdf->Cell($w11, $cellTableBodyHeight, 'Rank  ', 'L', 0, 'L');
                }
                
                $pdf->SetFont('Times', 'B', $fontSize+4);
                
                if($moyenne == ConstantsClass::UNRANKED_AVERAGE)
                {
                    $pdf->Cell($w21, $cellTableBodyHeight, utf8_decode("N.C") , 'R', 1, 'L');
                }else
                {
                    $eleve = $this->reportRepository->findBy(
                        [
                        'student' => $student->getId(),
                        'term' => $term,
                        ]
                    );
                    // dd($eleve[0]->getRang());
                    // $rang = $this->generalService->formatRank($reportResult->getRang(), $student->getSex()->getSex());

                    $rang = $this->generalService->formatRank($eleve[0]->getRang(), $student->getSex()->getSex());
                    
                    $pdf->Cell($w21, $cellTableBodyHeight, utf8_decode($rang) , 'R', 1, 'L');
                }
                
                
                $pdf->Cell($w0, $cellTableBodyHeight, '', 'LBR', 1, 'C');

                $pdf->setXY($x+$space, $y);
                $pdf->SetFont('Times', 'B', $fontSize-1);
                $counter++;

                // rappels
                $remember = $reportFooter->getRemember();
                
                if($term->getTerm() != 1)
                {
                    $pdf->Cell($w0, $cellTableBodyHeight*3/5, utf8_decode($remember->getName()), 'LTR', 0, 'C', true);

                    $x = $pdf->getX();
                    $y = $pdf->getY();

                    $pdf->Ln();
                    $pdf->SetFont('Times', 'B', $fontSize-3);

                    $pdf->Cell($counter*($w0+$space));

                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($w12-5, $cellTableBodyHeight*2/5, 'Trimestre  ', 'LB', 0, 'L', true);

                        $pdf->Cell($w22-2, $cellTableBodyHeight*2/5, 'Moy', 'B', 0, 'L', true);

                        $pdf->Cell(7, $cellTableBodyHeight*2/5, 'Rang', 'BR', 0, 'C', true);
                    }else
                    {
                        $pdf->Cell($w12-5, $cellTableBodyHeight*2/5, 'Term  ', 'LB', 0, 'L', true);

                        $pdf->Cell($w22-2, $cellTableBodyHeight*2/5, 'Avg', 'B', 0, 'L', true);

                        $pdf->Cell(7, $cellTableBodyHeight*2/5, 'Rank', 'BR', 0, 'C', true);
                    }
                    

                    $pdf->Ln();
                    $pdf->SetFont('Times', '', $fontSize-1);

                    $pdf->Cell($counter*($w0+$space));

                    // $pdf = $this->displayRememberContent($remember->getMoyenneTerm1(), $pdf, $w12, $w22, $cellTableBodyHeight, $fontSize, $remember->getRang1(), $student->getSex()->getSex(), 'Trim 1');

                    if($remember->getMoyenneTerm1() != ConstantsClass::UNRANKED_AVERAGE)
                    {
                        $pdf->Cell($w12-5, $cellTableBodyHeight, 'Trim 1', 'L', 0, 'L');
                                
                        $pdf->SetFont('Times', 'B', $fontSize-1);
            
                        $pdf->Cell($w22-2, $cellTableBodyHeight, $this->generalService->formatMark($remember->getMoyenneTerm1()), 0, 0, 'L');
            
                        $pdf->Cell(7, $cellTableBodyHeight, utf8_decode($this->generalService->formatRank($remember->getRank1(), $student->getSex()->getSex())), 'R', 1, 'L');
            
                    }else
                    {
                        $pdf->Cell($w12, $cellTableBodyHeight, 'Trim 1', 'L', 0, 'L');
                                
                        $pdf->SetFont('Times', 'B', $fontSize-1);
            
                        $pdf->Cell($w22, $cellTableBodyHeight, ConstantsClass::UNRANKED_RANK, 'R', 1, 'C');
            
                    }

                    $pdf->Cell($counter*($w0+$space));

                    if($term->getTerm() == 2)
                    {
                        $pdf->Cell($w0, $cellTableBodyHeight, '', 'LR', 1, 'L');
                        $pdf->Cell($counter*($w0+$space));
                        $pdf->Cell($w0, $cellTableBodyHeight, '', 'LR', 1, 'L');
                        $pdf->Cell($counter*($w0+$space));
                        $pdf->Cell($w0, $cellTableBodyHeight, '', 'LR', 1, 'L');
                        $pdf->Cell($counter*($w0+$space));
                        $pdf->Cell($w0, $cellTableBodyHeight, '', 'LBR', 1, 'L');
                        $pdf->Cell($counter*($w0+$space));
                    }

                    if($term->getTerm() == 3 || $term->getTerm() == ConstantsClass::ANNUEL_TERM)
                    {
                        $pdf->SetFont('Times', '', $fontSize-1);

                        // $pdf = $this->displayRememberContent($remember->getMoyenneTerm2(), $pdf, $w12, $w22, $cellTableBodyHeight, $fontSize, $remember->getRank2(), $student->getSex()->getSex(), 'Trim 2');

                        if($remember->getMoyenneTerm2() != ConstantsClass::UNRANKED_AVERAGE)
                        {
                            $pdf->Cell($w12-5, $cellTableBodyHeight, 'Trim 2', 'L', 0, 'L');
                                    
                            $pdf->SetFont('Times', 'B', $fontSize-1);

                            $pdf->Cell($w22-2, $cellTableBodyHeight, $this->generalService->formatMark($remember->getMoyenneTerm2()), 0, 0, 'L');

                            $pdf->Cell(7, $cellTableBodyHeight, utf8_decode($this->generalService->formatRank( $remember->getRank2(), $student->getSex()->getSex())), 'R', 1, 'L');

                        }else
                        {
                            $pdf->Cell($w12, $cellTableBodyHeight, 'Trim 2', 'L', 0, 'L');
                                    
                            $pdf->SetFont('Times', 'B', $fontSize-1);

                            $pdf->Cell($w22, $cellTableBodyHeight, ConstantsClass::UNRANKED_RANK, 'R', 1, 'C');

                        }

                        $pdf->Cell($counter*($w0+$space));

                        if($term->getTerm() == 3)
                        {
                            $pdf->Cell($w0, $cellTableBodyHeight, '', 'LR', 1, 'L');
                            $pdf->Cell($counter*($w0+$space));
                            $pdf->Cell($w0, $cellTableBodyHeight, '', 'LR', 1, 'L');
                            $pdf->Cell($counter*($w0+$space));
                            $pdf->Cell($w0, $cellTableBodyHeight, '', 'LBR', 1, 'L');
                            $pdf->Cell($counter*($w0+$space));

                        }else
                        {
                            $pdf->SetFont('Times', '', $fontSize-1);

                            // $pdf = $this->displayRememberContent($remember->getMoyenneTerm3(), $pdf, $w12, $w22, $cellTableBodyHeight, $fontSize, $remember->getRank3(), $student->getSex()->getSex(), 'Trim 3');

                            if($remember->getMoyenneTerm3() != ConstantsClass::UNRANKED_AVERAGE)
                            {
                                $pdf->Cell($w12-5, $cellTableBodyHeight, 'Trim 3', 'L', 0, 'L');
                                        
                                $pdf->SetFont('Times', 'B', $fontSize-1);

                                $pdf->Cell($w22-2, $cellTableBodyHeight, $this->generalService->formatMark($remember->getMoyenneTerm3()), 0, 0, 'L');

                                $pdf->Cell(7, $cellTableBodyHeight, utf8_decode($this->generalService->formatRank( $remember->getRank3(), $student->getSex()->getSex())), 'R', 1, 'L');

                            }else
                            {
                                $pdf->Cell($w12, $cellTableBodyHeight, 'Trim 3', 'L', 0, 'L');
                                        
                                $pdf->SetFont('Times', 'B', $fontSize-1);

                                $pdf->Cell($w22, $cellTableBodyHeight, ConstantsClass::UNRANKED_RANK, 'R', 1, 'C');

                            }

                            $pdf->Cell($counter*($w0+$space));

                            $pdf->Cell($w0, $cellTableBodyHeight, '', 'LR', 1, 'L');
                            $pdf->Cell($counter*($w0+$space));
                            $pdf->Cell($w0, $cellTableBodyHeight, '', 'LBR', 1, 'L');
                            $pdf->Cell($counter*($w0+$space));

                        }
                        
                    }
                    
                    $pdf->setXY($x+$space, $y);
                    $counter++;
                }


                // Profil de la classe
                $classroomProfile = $reportFooter->getClassroomProfile();
                $pdf->SetFont('Times', 'B', $fontSize);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0, $cellTableBodyHeight, utf8_decode($classroomProfile->getName()), 1, 0, 'C', true);

                }else
                {
                    $pdf->Cell($w0, $cellTableBodyHeight, utf8_decode($classroomProfile->getNameEnglish()), 1, 0, 'C', true);

                }

                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->Ln();
                $pdf->SetFont('Times', '', $fontSize-1);
                
                $pdf->Cell($counter*($w0+$space));
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Moy de la classe  ', 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Avg of the class  ', 'L', 0, 'L');
                }
                        
                $pdf->SetFont('Times', 'B', $fontSize-1);
                        
                $pdf->Cell($w22, $cellTableBodyHeight, $this->generalService->formatMark($classroomProfile->getClassroomAverage()), 'R', 1, 'R');
                
                $pdf->Cell($counter*($w0+$space));
                $pdf->SetFont('Times', '', $fontSize-2);
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, utf8_decode('Taux de Réussite  '), 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, utf8_decode('Sucess rate  '), 'L', 0, 'L');
                }
                
                        
                $pdf->SetFont('Times', 'B', $fontSize-1);
                        
                $pdf->Cell($w22, $cellTableBodyHeight, $this->generalService->formatMark($classroomProfile->getSuccessRate()).' %', 'R', 1, 'R');

                $pdf->Cell($counter*($w0+$space));
                $pdf->SetFont('Times', '', $fontSize-1);
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Moy du Premier  ', 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Avg of the first  ', 'L', 0, 'L');
                }
                
                        
                $pdf->SetFont('Times', 'B', $fontSize-1);
                        
                $pdf->Cell($w22, $cellTableBodyHeight, $this->generalService->formatMark($classroomProfile->getFirstAverage()), 'R', 1, 'R');

                $pdf->Cell($counter*($w0+$space));
                $pdf->SetFont('Times', '', $fontSize-1);
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Moy du Dernier  ', 'L', 0, 'L');
                }
                else
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Avg of the last  ', 'L', 0, 'L');
                }
                
                        
                $pdf->SetFont('Times', 'B', $fontSize-1);
                        
                $pdf->Cell($w22, $cellTableBodyHeight, $this->generalService->formatMark($classroomProfile->getLastAverage()), 'R', 1, 'R');
                $pdf->Cell($counter*($w0+$space));
                $pdf->Cell($w0, $cellTableBodyHeight, '', 'LBR', 1, 'L');

                $pdf->setXY($x+$space, $y);
                $counter++;

                // Discipline
                $discipline = $reportFooter->getDiscipline();
                
                $pdf->SetFont('Times', 'B', $fontSize);

                $pdf->Cell($w0, $cellTableBodyHeight, utf8_decode($discipline->getName()), 1, 0, 'C', true);

                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->Ln();
                $pdf->SetFont('Times', '', $fontSize-1);

                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Absences  ', 'L', 0, 'L');
                }
                else
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Absences  ', 'L', 0, 'L');
                }
                
                        
                $pdf->SetFont('Times', 'B', $fontSize-1);
                
                $pdf->Cell($w22, $cellTableBodyHeight, $this->generalService->formatInteger($discipline->getAbsence()).' h', 'R', 1, 'R');

                $pdf->SetFont('Times', '', $fontSize-1.5);

                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Avertissement conduite', 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Warning behavior', 'L', 0, 'L');
                }
                
                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $discipline->getWarningBehaviour());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1+3, $y1);

                $pdf->Cell(2, $cellTableBodyHeight, '', 'R', 1, 'C');
                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, utf8_decode('Blâme Conduite'), 'L', 0, 'L');
                }
                else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, utf8_decode('Blame behavior'), 'L', 0, 'L');
                }

                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $discipline->getBlameBehaviour());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1+3, $y1);

                $pdf->Cell(2, $cellTableBodyHeight, '', 'R', 1, 'C');
                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Conseil de Discipline', 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Disciplinary commitee', 'L', 0, 'L');
                }

                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $discipline->getDisciplinaryCommitee());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1+3, $y1);

                $pdf->Cell(2, $cellTableBodyHeight, '', 'R', 1, 'C');
                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Exclusions ', 'LB', 0, 'L');
                }else
                {
                    $pdf->Cell($w12, $cellTableBodyHeight, 'Expelleds ', 'LB', 0, 'L');
                }

                $pdf->SetFont('Times', 'B', $fontSize-1);
                        
                $pdf->Cell($w22, $cellTableBodyHeight, $discipline->getExclusion().' J', 'RB', 1, 'R');

                $pdf->setXY($x+$space, $y);
                $counter++;

                // Travail
                $work = $reportFooter->getStudentWork();
                $pdf->SetFont('Times', 'B', $fontSize);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0, $cellTableBodyHeight, utf8_decode($work->getName()), 1, 0, 'C', true);

                }else
                {
                    $pdf->Cell($w0, $cellTableBodyHeight, utf8_decode($work->getNameEnglish()), 1, 0, 'C', true);

                }

                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->Ln();
                $pdf->SetFont('Times', '', $fontSize-1);

                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, "Tableau d'honneur", 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, "Honor rolls", 'L', 0, 'L');
                }
                

                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $work->getRollOfHonour());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1+3, $y1);

                $pdf->Cell(2, $cellTableBodyHeight, '', 'R', 1, 'C');
                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Encouragements', 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Encouragements', 'L', 0, 'L');
                }
                

                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $work->getEncouragement());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1+3, $y1);

                $pdf->Cell(2, $cellTableBodyHeight, '', 'R', 1, 'C');
                $pdf->Cell($counter*($w0+$space));
                
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, utf8_decode('Félicitations'), 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, utf8_decode('Congratulations'), 'L', 0, 'L');
                }

                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $work->getCongratulation());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1+3, $y1);

                $pdf->Cell(2, $cellTableBodyHeight, '', 'R', 1, 'C');
                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Avertissement Travail', 'L', 0, 'L');
                }else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Warning work', 'L', 0, 'L');
                }
                

                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $work->getWarningWork());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1+3, $y1);

                $pdf->Cell(2, $cellTableBodyHeight, '', 'R', 1, 'C');
                $pdf->Cell($counter*($w0+$space));

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, utf8_decode('Blâme Travail'), 'LB', 0, 'L');
                }else
                {
                    $pdf->Cell($w0-5, $cellTableBodyHeight, 'Blame work', 'LB', 0, 'L');
                }
                

                $x1 = $pdf->GetX();
                $y1 = $pdf->GetY();

                $pdf->Rect($x1, $y1+($cellTableBodyHeight/2)-1.5, 3, 3, 'D');

                $pdf->SetFont('Times', 'B', $fontSize-1);  

                $pdf->Text($x1+0.5, $y1+($cellTableBodyHeight/2)+1.2, $work->getBlameWork());

                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->SetXY($x1, $y1);

                $pdf->Cell(5, $cellTableBodyHeight, '', 'RB', 1, 'C');
                
                $pdf->Cell(0, 2, '', 0, 1, 'C');

                // Appréciation du travail
                $w01 = 61;
                $w02 = 22;
                $w03 = 37;
                $w04 = 52;
                $w05 = 71;
                $counter = 0;
                $space = 2;

                $workAppreciation = $reportFooter->getWorkAppreciation();
                $pdf->SetFont('Times', 'B', $fontSize-1);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w01-21, $cellTableBodyHeight, utf8_decode($workAppreciation->getName()), 'LTB', 0, 'C', true);
                }else
                {
                    $pdf->Cell($w01-21, $cellTableBodyHeight, utf8_decode($workAppreciation->getNameEnglish()), 'LTB', 0, 'C', true);
                }

                $pdf->SetFont('Times', 'B', $fontSize);

                if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
                {
                    ///////////////FRANCOPHONE
                    switch ($workAppreciation->getAppreciation()) 
                    {
                        case ConstantsClass::UNRANK:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode("N.C"), 'TRB', 0, 'C');
                            
                            break;
                            
                        case ConstantsClass::FAIBLE:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::INSUFFISANT:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;
                        
                        case ConstantsClass::MEDIOCRE:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::PASSABLE:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::ASSEZ_BIEN:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::BIEN:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::TRES_BIEN:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::EXCELLENT:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::PARFAIT:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;
                        
                        
                    }
                }
                ////////////////ANGLOPHONE
                else
                {
                    switch ($workAppreciation->getAppreciation()) 
                    {
                        case ConstantsClass::UNRANK:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode("N.C"), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::WEAK:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::INSUFFICIENT:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;
                        
                        case ConstantsClass::POOR:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::FAIR:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::PRETTY_GOOD:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::GOOD:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::ALRIGHT:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            break;

                        case ConstantsClass::EXCELENT:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            
                            break;

                        case ConstantsClass::PERFECT:
                            $pdf->Cell(21, $cellTableBodyHeight, utf8_decode($workAppreciation->getAppreciation()), 'TRB', 0, 'C');
                            
                            
                            break;
                        
                        
                    }
                }

                
                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->Ln();
                $pdf->SetFont('Times', '', $fontSize-1);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w01, $cellTableBodyHeight, utf8_decode($workAppreciation->getContent()).' : _ _ _ _ _ _ _ _ _ ', 'LR', 2, 'L');
                }else
                {
                    $pdf->Cell($w01, $cellTableBodyHeight, utf8_decode($workAppreciation->getContentEnglish()).' : _ _ _ _ _ _ _ _ _ ', 'LR', 2, 'L');
                }

                $pdf->Cell($w01, $cellTableBodyHeight, '_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ ', 'LRB', 2, 'L');
                $pdf->Cell($w01, 2, '', 0, 1, 'C');

                // Visa du parent
                $parentVisa = $reportFooter->getParentVisa();
                $pdf->SetFont('Times', 'B', $fontSize-2);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w02, $cellTableBodyHeight, utf8_decode($parentVisa->getName()), 1, 0, 'C', true);

                }else
                {
                    $pdf->Cell($w02, $cellTableBodyHeight, utf8_decode($parentVisa->getNameEnglish()), 1, 0, 'C', true);
                }

                $x1 = $pdf->getX();
                $y1 = $pdf->getY();
                $pdf->Ln();
                $pdf->SetFont('Times', '', $fontSize-1);

                //for($i = 0; $i < 0; $i++)
                //{
                    //$pdf->Cell($w02, $cellTableBodyHeight, '', 'LR', 2, 'C');
                //}
                //$pdf->Cell($w02, $cellTableBodyHeight, '', 'LR', 2, 'C');
                $pdf->Cell($w02, $cellTableBodyHeight, '', 'LBR', 2, 'C');
                
                $pdf->setXY($x1+$space, $y1);

                // Visa Professeur Principal
                $principalTeacherVisa = $reportFooter->getPrincipalTeacherVisa();
                $pdf->SetFont('Times', 'B', $fontSize-3);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w03, $cellTableBodyHeight, utf8_decode($principalTeacherVisa->getName()), 1, 2, 'C', true);
                }
                else
                {
                    $pdf->Cell($w03, $cellTableBodyHeight, utf8_decode($principalTeacherVisa->getNameEnglish()), 1, 2, 'C', true);
                }
                
                for($i = 0; $i < 0; $i++)
                {
                    $pdf->Cell($w03, $cellTableBodyHeight, '', 'LR', 2, 'C');
                }
                
                //$pdf->Cell($w03, $cellTableBodyHeight, '', 'LR', 2, 'C');
                $pdf->Cell($w03, $cellTableBodyHeight, '', 'LBR', 2, 'C');

                $pdf->setXY($x+$space, $y);
                
                // Décision du conseil de classe
                
                //$decision = "";
                //$motif = "";
                // On recupère la decision du conseil de l'élève 
                if ($classroom->isIsDeliberated() && $term->getTerm() == ConstantsClass::ANNUEL_TERM) 
                {
                    switch ($student->getDecision()->getDecision()) 
                    {
                        case ConstantsClass::DECISION_PASSED:
                            $decisionConseil = "Admis en classe de " . $student->getNextClassroomName();
                            break;
                        case ConstantsClass::DECISION_REAPETED:
                            $decisionConseil = "Redouble la classe de " . $student->getNextClassroomName();
                            break;
                        case ConstantsClass::DECISION_EXPELLED:
                            $decisionConseil = "Exclu pour " . $student->getMotif();
                            break;
                        case ConstantsClass::DECISION_FINISHED:
                            $decisionConseil = "Terminé(e)";
                            break;
                        case ConstantsClass::DECISION_CATCHUPPED:
                            $decisionConseil = "Attendu au rattrapage ";
                            break;
                        case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                            $decisionConseil = "Redouble si échec" . $student->getNextClassroomName();
                            break;
                        case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                            $decisionConseil = "Exclu si échec pour " . $student->getMotif();
                            break;
                    }
                } 
                

                ////decision du conseil de classe
                $commiteeDecision = $reportFooter->getCommiteeDecision();
                $pdf->SetFont('Times', 'B', $fontSize-1);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w04, $cellTableBodyHeight, utf8_decode($commiteeDecision->getName()), 1, 0, 'C', true);

                }else
                {
                    $pdf->Cell($w04, $cellTableBodyHeight, utf8_decode($commiteeDecision->getNameEnglish()), 1, 0, 'C', true);

                }

                $x = $pdf->getX();
                $y = $pdf->getY();
                $pdf->Ln();

                $pdf->Cell($w01+$space);
                $pdf->Cell($w04, $cellTableBodyHeight, '', 'LR', 2, 'C');

                if ($classroom->isIsDeliberated() && $term->getTerm() == ConstantsClass::ANNUEL_TERM) 
                {
                    $pdf->Cell($w04, $cellTableBodyHeight, utf8_decode($decisionConseil), 'LR', 2, 'C');
                }
                else
                {
                    $pdf->SetFont('Times', 'B', $fontSize-3);
                    
                    if($conseil)
                    {
                        $decision = $conseil->getDecision();
                        $motif = $conseil->getMotif();
                    }
                    else
                    {
                        $decision = "";
                        $motif = "";
                    }

                    $pdf->Cell($w04, $cellTableBodyHeight, utf8_decode($decision.' / '.$motif), 'LR', 2, 'C');
                    
                }
                

                $pdf->Cell($w04, $cellTableBodyHeight, '', 'LR', 2, 'C');
                //for($i = 0; $i < 3; $i++)
                //{
                    //$pdf->Cell($w04, $cellTableBodyHeight, '', 'LR', 2, 'C');
                //}
                
                $pdf->Cell($w04, $cellTableBodyHeight+2, '', 'LBR', 2, 'C');

                $pdf->setXY($x+$space, $y);

                // Visa du chef d'établissement
                $headmasterVisa = $reportFooter->getHeadmasterVisa();
                $pdf->SetFont('Times', 'B', $fontSize);
                
                if($school->isPublic())
                {
                    if($school->isLycee())
                    {
                        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                        {
                            $pdf->Cell($w05, $cellTableBodyHeight,  utf8_decode($headmasterVisa->getName()), 1, 2, 'C', true);
                        }else
                        {
                            $pdf->Cell($w05, $cellTableBodyHeight,  utf8_decode($headmasterVisa->getNameEnglish()), 1, 2, 'C', true);
                        }
                        
                    }else
                    {
                        if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                        {
                            $pdf->Cell($w05, $cellTableBodyHeight,  utf8_decode($headmasterVisa->getNameDirecteur()), 1, 2, 'C', true);
                        }else
                        {
                            $pdf->Cell($w05, $cellTableBodyHeight,  utf8_decode($headmasterVisa->getNameDirecteurEnglish()), 1, 2, 'C', true);
                        }
                    }
                }else
                {
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($w05, $cellTableBodyHeight,  utf8_decode($headmasterVisa->getNamePrincipal()), 1, 2, 'C', true);
                    }else
                    {
                        $pdf->Cell($w05, $cellTableBodyHeight,  utf8_decode($headmasterVisa->getNamePrincipalEnglish()), 1, 2, 'C', true);
                    }
                }


                $pdf->Cell($w05, $cellTableBodyHeight, '', 'LR', 2, 'C');
                $pdf->SetFont('Times', '', $fontSize);

                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($w05, $cellTableBodyHeight, utf8_decode('Fait à').' '.utf8_decode($reportHeader->getSchool()->getPlace()).', Le _ _ _ _ _', 'LR', 2, 'L');
                }else
                {
                    $pdf->Cell($w05, $cellTableBodyHeight, utf8_decode('Done at').' '.utf8_decode($reportHeader->getSchool()->getPlace()).', On _ _ _ _ _', 'LR', 2, 'L');
                }

                for($i = 0; $i < 1; $i++)
                {
                    $pdf->Cell($w05, $cellTableBodyHeight, '', 'LR', 2, 'C');
                }

                //$pdf->Cell($w05, $cellTableBodyHeight, '', 'LR', 2, 'C');
                $pdf->Cell($w05, $cellTableBodyHeight+2, '', 'LBR', 1, 'C');

                if($student->getQrCode()) 
                {
                    // $pdf->Cell(25, 5, $pdf->Image('images/qrcode/'.$student->getQrCode(), 93, 270, 22, 22) , 0, 1, 'C', 0);
                }
            }

        }
            
        return $pdf;
    }


    public function displayMarkGroup(array $rowsGroup, StudentResult $reportResult,  PDF $pdf, int $fontSize, int $cellSubjectWidth, int $cellTableBodyHeight, Term $term, int $cellSkillWidth, int $cellEvaluationWidth, int $cellAverageWidth, int $cellCoefficientWidth, int $cellTotalWidth, int $cellRankWidth, int $cellAppreciationWidth, int $cellRecapNameWidth, int $cellRecapTotalCoefficientWidth, int $cellRecapTotalMarkWidth, int $cellRecapAverageWidth, int $cellRecapRankWidth, Student $student): PDF
    {
        // Notes du groupe
        foreach($rowsGroup as $reportRow)
        {
            $pdf->SetFont('Times', 'B', $fontSize-1);

            if(strlen($reportRow->getSubject()) > 19)
            {
                $pdf->SetFont('Times', 'B', $fontSize-4);
                $pdf->Cell($cellSubjectWidth, $cellTableBodyHeight*2/3, utf8_decode($reportRow->getSubject()), 'LTR', 0, 'L');
                $pdf->SetFont('Times', 'B', $fontSize-1);
            }else
            {
                $pdf->Cell($cellSubjectWidth, $cellTableBodyHeight*2/3, utf8_decode($reportRow->getSubject()), 'LTR', 0, 'L');

            }
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Ln();
            $pdf->SetFont('Times', '', $fontSize-4);
            
            $pdf->Cell($cellSubjectWidth, $cellTableBodyHeight/3, utf8_decode($reportRow->getTeacher()), 'LBR', 0, 'L');

            $pdf->SetXY($x, $y);
            $pdf->SetFont('Times', '', $fontSize-3);

            if($term->getTerm() != ConstantsClass::ANNUEL_TERM)
            {
                $skill = strtolower(utf8_decode($reportRow->getSkill()));
                if(strlen($skill) <= 53)
                {
                    $pdf->Cell($cellSkillWidth, $cellTableBodyHeight, $skill, 1, 0, 'L');

                }else
                {
                    $skill1 = substr($skill, 0, 53);
                    $skill2 = substr($skill, 53, 53);

                    $pdf->Cell($cellSkillWidth, $cellTableBodyHeight/2, $skill1, 'LTR', 0, 'L');
                    $x = $pdf->GetX();
                    $y = $pdf->GetY();
                    $pdf->Ln();
                    $x1 = $pdf->SetXY($x - $cellSkillWidth, $y + ($cellTableBodyHeight/2));
                    $pdf->Cell($cellSkillWidth, $cellTableBodyHeight/2, $skill2, 'LBR', 0, 'L');
                    $pdf->SetXY($x, $y);

                }
            }

            $pdf->SetFont('Times', '', $fontSize-1);

            $pdf->Cell($cellEvaluationWidth, $cellTableBodyHeight, $this->generalService->formatMark($reportRow->getEvaluation1()), 1, 0, 'C');
            $pdf->Cell($cellEvaluationWidth, $cellTableBodyHeight, $this->generalService->formatMark($reportRow->getEvaluation2()), 1, 0, 'C');

            if($term->getTerm() == ConstantsClass::ANNUEL_TERM)
            {
                $pdf->Cell($cellEvaluationWidth, $cellTableBodyHeight, $this->generalService->formatMark($reportRow->getEvaluation3()), 1, 0, 'C');
            }

            $pdf->SetFont('Times', 'B', $fontSize);

            $pdf->Cell($cellAverageWidth, $cellTableBodyHeight, $this->generalService->formatMark($reportRow->getMoyenne()), 1, 0, 'C', true);

            $pdf->SetFont('Times', '', $fontSize-1);

            $pdf->Cell($cellCoefficientWidth, $cellTableBodyHeight, $this->formatCoefficient($reportRow->getCoefficient()), 1, 0, 'C');

            $pdf->SetFont('Times', 'B', $fontSize);

            if($reportRow->getMoyenne() != ConstantsClass::UNRANKED_MARK)
            {
                $pdf->Cell($cellTotalWidth, $cellTableBodyHeight, $this->generalService->formatMark($reportRow->getTotal()), 1, 0, 'C');

                $pdf->SetFont('Times', '', $fontSize);
                $pdf->Cell($cellRankWidth, $cellTableBodyHeight, utf8_decode($this->generalService->formatRank($reportRow->getRang(), $student->getSex()->getSex())), 1, 0, 'C');
                $pdf->Cell($cellAppreciationWidth, $cellTableBodyHeight, utf8_decode($reportRow->getAppreciationFr()), 1, 1, 'L');
            }else
            {
                $pdf->Cell($cellTotalWidth, $cellTableBodyHeight, '/', 1, 0, 'C');

                $pdf->SetFont('Times', '', $fontSize);
                $pdf->Cell($cellRankWidth, $cellTableBodyHeight, '/', 1, 0, 'C');
                $pdf->Cell($cellAppreciationWidth, $cellTableBodyHeight, '/', 1, 1, 'L');

            }

        }

        // Recapitulatif Groupe

        $pdf->SetFont('Times', 'B', $fontSize-1);

        $pdf->Cell($cellRecapNameWidth, $cellTableBodyHeight, utf8_decode($reportResult->getName()), 'LTB', 0, 'C', true);
    
        $pdf->Cell($cellRecapTotalCoefficientWidth, $cellTableBodyHeight, 'Total Coef : '.$reportResult->getTotalStudentCoefficient().' / '.$reportResult->getTotalClassroomCoefficient(), 'TB', 0, 'C', true);

        //  $subSystem->getSubSystem() == constantsClass::FRANCOPHONE ? 'Moyenne  : ' : 'Average  : '
        if($reportResult->getMoyenne() != ConstantsClass::UNRANKED_AVERAGE)
        {
            $pdf->Cell($cellRecapTotalMarkWidth, $cellTableBodyHeight, 'Total Points : '.$this->generalService->formatMark($reportResult->getTotalMark()), 'TB', 0, 'C', true);
            $pdf->Cell($cellRecapAverageWidth, $cellTableBodyHeight, 'Moyenne  : ' .$this->generalService->formatMark($reportResult->getMoyenne()), 'TB', 0, 'C', true);
            $pdf->Cell($cellRecapRankWidth, $cellTableBodyHeight, 'Rang  : '.utf8_decode($this->generalService->formatRank($reportResult->getRang(), $student->getSex()->getSex())), 'RTB', 1, 'C', true);
        }else
        {
        //  $pdf->Cell($cellRecapTotalCoefficientWidth, $cellTableBodyHeight, 'Total Coef : //', 'TB', 0, 'C', true);
        $pdf->Cell($cellRecapTotalMarkWidth, $cellTableBodyHeight, 'Total Points : //', 'TB', 0, 'C', true);
        
        $pdf->Cell($cellRecapAverageWidth, $cellTableBodyHeight, 'Moyenne : //', 'TB', 0, 'C', true);
        $pdf->Cell($cellRecapRankWidth, $cellTableBodyHeight, 'Rang  : N.C', 'RTB', 1, 'C', true);
        
        }

        return $pdf;
    }

   
    /**
     * Affiche le rappel d'un trimestre
     *
     * @param float $moyenne
     * @param PDF $pdf
     * @param integer $w12
     * @param integer $cellTableBodyHeight
     * @param integer $fontSize
     * @param integer $w22
     * @param integer $rang
     * @param string $sex
     * @return PDF
     */
    public function displayRememberContent(Classroom $classroom, float $studentAverage, PDF $pdf, int $w12, int $w22, int $cellTableBodyHeight, int $fontSize, int $studentRank, string $studentSex, string $termName): PDF
    {
        if($studentAverage != ConstantsClass::UNRANKED_AVERAGE)
        {
            $pdf->Cell($w12-5, $cellTableBodyHeight, $termName, 'L', 0, 'L');
                    
            $pdf->SetFont('Times', 'B', $fontSize-1);

            $pdf->Cell($w22-2, $cellTableBodyHeight, $this->generalService->formatMark($studentAverage), 0, 0, 'L');

            $pdf->Cell(7.8, $cellTableBodyHeight, utf8_decode($this->generalService->formatRank( $studentRank, $studentSex)), 'R', 1, 'L');

        }else
        {
            $pdf->Cell($w12, $cellTableBodyHeight, $termName, 'L', 0, 'L');
                    
            $pdf->SetFont('Times', 'B', $fontSize-1);

            $pdf->Cell($w22+0.8, $cellTableBodyHeight, ConstantsClass::UNRANKED_RANK, 'R', 1, 'C');

        }

        return $pdf;
    }


    /**
     * Formate le coefficient
     *
     * @param float $coefficient
     * @return void
     */
    public function formatCoefficient(float $coefficient)
    {
        if(floor($coefficient) == $coefficient)
        {
            return (int)$coefficient;

        }else
        {
            return number_format($coefficient, 1);
        }
    }

    /**
     * Imprime les cartes scolaires
     */
    public function printStudentCard(array $students, School $school, SchoolYear $schoolYear, Classroom $selectedClassroom, SubSystem $subSystem): NoFooter
    {
        $pdf = new NoFooter();
        $pdf->SetAutoPageBreak(false);
        
        // on ajoute une page en portrait
        $pdf = $this->generalService->newPageNoFooter($pdf, 'P', 10, 10);

        $i = 0;

        $totalCellWidth = 90;
        $cellHeight = 3;
        $cellWidth = $totalCellWidth/3;
        $space = 7.5;

        $escape = 0;

        $xLogo = $x0Logo = 49;
        $yLogo = $y0Logo = 11;
        $logoSize = -350;

        $xFiligree = $x0Filigree = 36;
        $yFiligree = $y0Filigree = 26;
        $filigreeSize = -800;

        $xPhoto = $x0Photo = 13;
        $yPhoto = $y0Photo = 33;
        $photoSize = -220;
        
        $xYellowStar = $x0YellowStar = 53;
        $yYellowStar = $y0YellowStar = 25;
        $yellowStarSize = -1500;

        $xStamp = $x0Stamp = 30;
        $yStamp =  $y0Stamp = 45;
        $stampSize = -400;
        
        $x0 = $pdf->GetX();
        $y0 = $pdf->GetY();


        foreach ($students as $student) 
        {
            $i++;

            if($i >= 6)
            {
                $escape = $totalCellWidth + $space;
            }

            $pdf->SetFont('Times', 'B', 5);
            $x = $pdf->GetX();
            $y = $pdf->GetY();
     
            // Logo de l'établissement et filigrane et signature
            $pdf->Image('images/school/'.$school->getLogo(), $xLogo, $yLogo, $logoSize);
            $pdf->Image('images/school/'.$school->getFiligree(), $xFiligree, $yFiligree, $filigreeSize); 

            $pdf->Image('build/custom/images/signature2.png', $xStamp, $yStamp, $stampSize);

            // Photo de l'élève
            if($student->getPhoto())
            {
                $pdf->Image('images/students/'.$student->getPhoto(), $xPhoto-3, $yPhoto, 20,20);
            }else
            {
                $pdf->Image('images/students/defaultPhoto.jpg', $xPhoto-3, $yPhoto, 20,20);
            } 
     
            $pdf->setXY($x, $y);

            // partie administrative french
            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getFrenchCountry()), 0, 0, 'C');
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->Ln();

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
            $pdf->SetFont('Times', 'B', 3.5);
            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'L');
            // $pdf->SetFont('Times', 'B', 3.5);
            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
            // $pdf->SetFont('Times', 'B', 2.8);
            $pdf->Cell($cellWidth, $cellHeight, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->SetFont('Times', 'B', 5);

            $pdf->SetXY($x, $y);
            $pdf->Cell($cellWidth, $cellHeight, '', 0, 0, 'C');

            // partie administrative english
            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getEnglishCountry()), 0, 2, 'C');
            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
            $pdf->SetFont('Times', 'B', 3.5);
            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
            //  $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($cellWidth, $cellHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
            //  $pdf->SetFont('Times', 'B', 2.8);
            $pdf->Cell($cellWidth, $cellHeight, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            //  bande vert rouge jaune
            $pdf->Cell(3, $cellHeight, '', 0, 0, 'C');
            $pdf->SetFillColor(0,128,0);
            $pdf->Cell($cellWidth-2, $cellHeight, '', 0, 0, 'C', true);
            $pdf->SetFillColor(255,0,0);
            $pdf->Cell($cellWidth-2, $cellHeight, '', 0, 0, 'C', true);
            $pdf->SetFillColor(255,219,0);
            $pdf->Cell($cellWidth-2, $cellHeight, '', 0, 0, 'C', true);
            $pdf->Cell(3, $cellHeight, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Image('build/custom/images/etoile.PNG', $xYellowStar, $yYellowStar, $yellowStarSize);

            $pdf->SetFont('Times', 'B', 6.5);
            $pdf->SetTextColor(0, 0, 255);

            $pdf->SetXY($x, $y);

            // nom établissement
            $pdf->Cell($totalCellWidth, $cellHeight/3, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($totalCellWidth, $cellHeight, utf8_decode($school->getFrenchName()).' / '.utf8_decode($school->getEnglishName()), 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            // BP et téléphone
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont('Times', 'B', 4);
            $pdf->Cell($totalCellWidth, $cellHeight/3, 'B.P : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($totalCellWidth, $cellHeight/3, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->SetFont('Times', 'B', 6);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFillColor(0,127,127);
            
            // cadre titre
            $pdf->Cell($cellWidth/2+7, $cellHeight, '', 0, 0, 'C');
            $pdf->Cell($cellWidth*2-7, $cellHeight, utf8_decode("CARTE D'IDENTITE SCOLAIRE"), 0, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 4);
            $pdf->SetTextColor(0, 0, 0);

            if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
            {
                $pdf->Cell($cellWidth/2, $cellHeight, utf8_decode('Année Scolaire'), 0, 1, 'C');
            }else
            {
                $pdf->Cell($cellWidth/2, $cellHeight, utf8_decode('School Year'), 0, 1, 'C');
            }

            $pdf->SetFont('Times', 'B', 6);
            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->SetFont('Times', 'BI', 6);
            $pdf->SetTextColor(255, 255, 255);

            $pdf->Cell($cellWidth/2+7, $cellHeight, '', 0, 0, 'C');
            $pdf->Cell($cellWidth*2-7, $cellHeight, utf8_decode('SCHOOL IDENTITY CARD'), 0, 0, 'C', true);

            $pdf->SetFont('Times', 'B', 6);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($cellWidth/2, $cellHeight, utf8_decode($schoolYear->getSchoolYear()), 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->SetFont('Times', 'B', 6);
            $pdf->SetTextColor(0, 0, 0);

            // Etat civil de l'élève
            $pdf->Cell($totalCellWidth, $cellHeight/3, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($cellWidth*2/3, $cellHeight, '', 0, 0, 'R');

            if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
            {
                $pdf->Cell($cellWidth*2/3, $cellHeight, utf8_decode('Noms et prénoms  '), 0 , 0, 'L');
            } else 
            {
                $pdf->Cell($cellWidth*2/3, $cellHeight, utf8_decode('First and last name  '), 0 , 0, 'L');
            }
            
            $pdf->SetFont('Times', 'B', 6.5);
            $pdf->SetTextColor(0, 0, 255);

            $pdf->Cell($cellWidth*5/3, $cellHeight, utf8_decode($student->getFullName()), 0, 1, 'L');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->SetFont('Times', 'B', 6);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($totalCellWidth, $cellHeight/3, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($cellWidth*2/3 + $cellWidth*7/12, $cellHeight, '', 0, 0, 'R');

            if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
            {
                $pdf->Cell($cellWidth*1/3, $cellHeight, utf8_decode('Né(e) le '), 0, 0, 'L');
            } else 
            {
                $pdf->Cell($cellWidth*1/3, $cellHeight, utf8_decode('Born on '), 0, 0, 'L');
            }

            
            $pdf->Cell($cellWidth*1/3, $cellHeight, $student->getBirthday()->format('d/m/Y'), 0, 0, 'R');

            if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
            {
                $pdf->Cell($cellWidth*1/6, $cellHeight, utf8_decode(' à '), 0, 0, 'R');
            } else 
            {
                $pdf->Cell($cellWidth*1/6, $cellHeight, utf8_decode(' at '), 0, 0, 'L');
            }
            

            $pdf->Cell($cellWidth*7/12, $cellHeight, utf8_decode($student->getBirthplace()), 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($totalCellWidth, $cellHeight/3, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($cellWidth*2/3 + $cellWidth*7/12, $cellHeight, '', 0, 0, 'R');

            if ($subSystem->getSubSystem() == constantsClass::FRANCOPHONE) 
            {
                $pdf->Cell($cellWidth*1/3, $cellHeight, utf8_decode('Classe  '), 0, 0, 'L');
            } else 
            {
                $pdf->Cell($cellWidth*1/3, $cellHeight, utf8_decode('Class  '), 0, 0, 'L');
            }
           

            $pdf->SetFont('Times', 'B', 6.5);
            $pdf->SetTextColor(0, 0, 255);

            $pdf->Cell($cellWidth*2/3, $cellHeight, utf8_decode($selectedClassroom->getClassroom()), 0, 0, 'L');

            $pdf->SetFont('Times', 'B', 6);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->Cell($cellWidth*1/3, $cellHeight, utf8_decode('Contact '), 0, 0, 'L');
            $pdf->Cell($cellWidth*5/12-4, $cellHeight, utf8_decode($student->getTelephonePere() ? $student->getTelephonePere() : ""), 0, 1, 'L');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);
            $pdf->SetFont('Times', 'B', 5);

            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($cellWidth+4, $cellHeight/3, utf8_decode("Le Proviseur"), 0, 1, 'R');
                    }else
                    {
                        $pdf->Cell($cellWidth+4, $cellHeight/3, utf8_decode("The Principal"), 0, 1, 'R');
                    }
                    
                }else
                {
                    if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                    {
                        $pdf->Cell($cellWidth+4, $cellHeight/3, utf8_decode("Le Directeur"), 0, 1, 'R');
                    }else
                    {
                        $pdf->Cell($cellWidth+4, $cellHeight/3, utf8_decode("The Director"), 0, 1, 'R');
                    }
                    
                }
            }else
            {
                if($subSystem->getSubSystem() == constantsClass::FRANCOPHONE)
                {
                    $pdf->Cell($cellWidth+4, $cellHeight/3, utf8_decode("Le Principal"), 0, 1, 'R');
                }else
                {
                    $pdf->Cell($cellWidth+4, $cellHeight/3, utf8_decode("The Principal"), 0, 1, 'R');
                }
            }
            


            $pdf->SetFont('Times', 'B', 6);

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->SetFont('Times', 'B', 5);
            $pdf->Cell($cellWidth*2/3 + $cellWidth*8/12, $cellHeight, '', 0, 0, 'R');
            $pdf->SetFont('Times', 'B', 6);
            $pdf->Cell($cellWidth*4/6, $cellHeight, $subSystem->getSubSystem() == constantsClass::FRANCOPHONE ? utf8_decode('Matricule '): "Register number", 0, 0, 'R');
            $pdf->Cell($cellWidth*5/6, $cellHeight, utf8_decode($student->getRegistrationNumber()), 0, 1, 'L');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            $pdf->Cell($totalCellWidth, $cellHeight/3, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            // espace vide pour rattraper les eux cellules remontées
            $pdf->Cell($totalCellWidth/3, $cellHeight/3, '', 0, 1, 'R');
            $pdf->Cell($totalCellWidth, $cellHeight, '', 0, 1, 'R');
            // fin espace pour rattrapper les deux cellules remontées


            $pdf->Cell($totalCellWidth, $space, '', 0, 1, 'C');

            $pdf = $this->generalService->escapeNoFooter($pdf, $escape);

            // on repositionne les coordonnées pour les images
            $xToAdd = 51+$space;
            $escapeToAdd = $totalCellWidth + $space;

            if($i%5 != 0)
            {
                $yLogo += $xToAdd;
                $yFiligree += $xToAdd;
                $yPhoto += $xToAdd;
                $yYellowStar += $xToAdd;
                $yStamp += $xToAdd;
            }
            
            if($i%5 == 0 && $i%10 != 0)
            {
                $x0 += $escapeToAdd;
                $xLogo += $escapeToAdd;
                $xFiligree += $escapeToAdd;
                $xPhoto += $escapeToAdd;
                $xYellowStar += $escapeToAdd;
                $xStamp += $escapeToAdd;

                $yLogo = $y0Logo;
                $yFiligree = $y0Filigree;
                $yPhoto = $y0Photo;
                $yYellowStar = $y0YellowStar;
                $yStamp = $y0Stamp;

                $pdf->SetXY($x0, $y0);
            }
            
            if($i == 10 && count($students) > 10)
            {
                // on ajoute une page en portrait
                $pdf =  $this->generalService->newPageNoFooter($pdf, 'P', 10, 10);
               
                $i = 0;

                $totalCellWidth = 90;
                $cellHeight = 3;
                $cellWidth = $totalCellWidth/3;
                $space = 8;
        
                $escape = 0;
        
                $xLogo = $x0Logo = 49;
                $yLogo = $y0Logo = 11;
                $logoSize = -350;
        
                $xFiligree = $x0Filigree = 36;
                $yFiligree = $y0Filigree = 26;
                $filigreeSize = -800;
        
                $xPhoto = $x0Photo = 13;
                $yPhoto = $y0Photo = 33;
                $photoSize = -225; 
                
                $xYellowStar = $x0YellowStar = 53;
                $yYellowStar = $y0YellowStar = 25;
                $yellowStarSize = -1500;
        
                $xStamp = $x0Stamp = 30;
                $yStamp =  $y0Stamp = 43;
                $stampSize = -400;
                
                $x0 = $pdf->GetX();
                $y0 = $pdf->GetY();
            }

        }

        /////////////////VERSO////////////////////////
        // on ajoute une page en paysage
        $pdf =  $this->generalService->newPageNoFooter($pdf, 'P', 10, 10);

        $xLog = $x0Log = 43;
        $yLog = $y0Log = 23;
        $logoSize = -150;

        $hauteur = 297;
        $largeur = 210;


        // Logo de l'établissement
        for ($i=0; $i < 5; $i++) 
        { 
            $pdf->Image('images/school/'.$school->getLogo(), $xLog, $yLog, $logoSize);
            $pdf->Image('images/school/'.$school->getLogo(), $xLog+93, $yLog, $logoSize);
            $yLog = $yLog + 56;
        }


        return $pdf;
    }
   

    /**
     * Recupère les limites des coefficients/notes par niveau
     */
    public function getUnrankedCoefficientCycle(SchoolYear $schoolYear): array
    {

        $unrankedCoefficientCycle['level1'] = null;
        $unrankedCoefficientCycle['level2'] = null;
        $unrankedCoefficientCycle['level3'] = null;
        $unrankedCoefficientCycle['level4'] = null;
        $unrankedCoefficientCycle['level5'] = null;
        $unrankedCoefficientCycle['level6'] = null;
        $unrankedCoefficientCycle['level7'] = null;

        $outsideCoefficient = $this->unrankedCoefficientRepository->findOneForClassroomsLevel(1, $schoolYear);
        if(!empty($outsideCoefficient))
        {
            $unrankedCoefficientCycle['level1'] = $outsideCoefficient[0];
        }

        $outsideCoefficient = $this->unrankedCoefficientRepository->findOneForClassroomsLevel(2, $schoolYear);
        if(!empty($outsideCoefficient))
        {
            $unrankedCoefficientCycle['level2'] = $outsideCoefficient[0];
        }

        $outsideCoefficient = $this->unrankedCoefficientRepository->findOneForClassroomsLevel(3, $schoolYear);
        if(!empty($outsideCoefficient))
        {
            $unrankedCoefficientCycle['level3'] = $outsideCoefficient[0];
        }

        $outsideCoefficient = $this->unrankedCoefficientRepository->findOneForClassroomsLevel(4, $schoolYear);
        if(!empty($outsideCoefficient))
        {
            $unrankedCoefficientCycle['level4'] = $outsideCoefficient[0];
        }
        
        $outsideCoefficient = $this->unrankedCoefficientRepository->findOneForClassroomsLevel(5, $schoolYear);
        if(!empty($outsideCoefficient))
        {
            $unrankedCoefficientCycle['level5'] = $outsideCoefficient[0];
        }

        $outsideCoefficient = $this->unrankedCoefficientRepository->findOneForClassroomsLevel(6, $schoolYear);
        if(!empty($outsideCoefficient))
        {
            $unrankedCoefficientCycle['level6'] = $outsideCoefficient[0];
        }

        $outsideCoefficient = $this->unrankedCoefficientRepository->findOneForClassroomsLevel(7, $schoolYear);
        if(!empty($outsideCoefficient))
        {
            $unrankedCoefficientCycle['level7'] = $outsideCoefficient[0];
        }

        return $unrankedCoefficientCycle;
    }

    /**
     * Récupère les titres des niveaux
     */
    public function getLevelsName(): array
    {
        $levelsName['level1'] = ConstantsClass::LEVEL_1;
        $levelsName['level2'] = ConstantsClass::LEVEL_2;
        $levelsName['level3'] = ConstantsClass::LEVEL_3;
        $levelsName['level4'] = ConstantsClass::LEVEL_4;
        $levelsName['level5'] = ConstantsClass::LEVEL_5;
        $levelsName['level6'] = ConstantsClass::LEVEL_6;
        $levelsName['level7'] = ConstantsClass::LEVEL_7;

        return $levelsName;
    }


}
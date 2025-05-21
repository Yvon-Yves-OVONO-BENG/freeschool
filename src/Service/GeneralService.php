<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\School;
use App\Entity\Student;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\SubSystem;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Repository\SexRepository;
use App\Entity\ReportElements\PDF;
use App\Repository\LessonRepository;
use App\Repository\AbsenceRepository;
use App\Repository\StudentRepository;
use App\Repository\DecisionRepository;
use App\Entity\ReportElements\NoFooter;
use App\Entity\ReportElements\Pagination;
use Symfony\Component\HttpFoundation\RequestStack;

class GeneralService 
{
    public function __construct(
        protected RequestStack $request,
        protected SexRepository $sexRepository, 
        protected LessonRepository $lessonRepository, 
        protected StudentRepository $studentRepository, 
        protected AbsenceRepository $absenceRepository, 
        protected DecisionRepository $decisionRepository, 
        )
    {}

     /**
     * Retourne une appréciation APO selon une note
     *
     * @param float $mark
     * @return string
     */
    public function getApoAppreciation(float $mark): string
    {
        if($mark == ConstantsClass::UNRANKED_AVERAGE)
        {
            return '//';
        }

        if($mark != ConstantsClass::UNRANKED_MARK)
        {
            $mySession =  $this->request->getSession();

            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
            {
                if($mark < 10)
                    return ConstantsClass::CNA_FR;
                    
                elseif($mark >= 10 && $mark < 12)
                    return ConstantsClass::CMA;
                    
                elseif($mark >= 12 && $mark < 14)
                    return ConstantsClass::CA_FR;

                elseif($mark >= 14 && $mark < 16)
                    return ConstantsClass::CBA;
                    
                elseif($mark >= 16 && $mark < 20)
                    return ConstantsClass::CTBA;
                    
            }else
            {
                if($mark < 10)
                    return ConstantsClass::CNA_EN;
                    
                elseif($mark >= 10 && $mark < 12)
                    return ConstantsClass::CAA;
                    
                elseif($mark >= 12 && $mark < 14)
                    return ConstantsClass::CA_EN;

                elseif($mark >= 14 && $mark < 16)
                    return ConstantsClass::CWA;
                    
                elseif($mark >= 16 && $mark < 20)
                    return ConstantsClass::CVWA;
            }

        }else
        {
            return '//';
        }

        return '//';
    } 

    /**
     * Entete de mes tableaux des meilleurs élèves
     *
     * @param Pagination $pdf
     * @param integer $fontSize
     * @param integer $cellTableClassroom
     * @param integer $cellTableHeight
     * @param integer $cellTablePresence
     * @param integer $cellTableObservation
     * @param integer $cellTablePresence3
     * @param SubSystem $subSystem
     * @return Pagination
     */
    public function getTableHeaderPagination(Pagination $pdf, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTablePresence, int $cellTableObservation, int $cellTablePresence3, SubSystem $subSystem): Pagination
    {
        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->SetFont('Times', 'B', $fontSize+1);
            $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence+45 , $cellTableHeight*1.5, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, 'Sexe', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, 'Moyenne', 1, 0, 'C', true);
            $pdf->Cell($cellTableClassroom, $cellTableHeight*1.5, 'Classes', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight*1.5, utf8_decode('Date de naissance'), 1, 1, 'C', true);
        } 
        else 
        {
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence+45 , $cellTableHeight*1.5, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, 'Sex', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, 'Average', 1, 0, 'C', true);
            $pdf->Cell($cellTableClassroom, $cellTableHeight*1.5, 'Class', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence , $cellTableHeight*1.5, utf8_decode('Date of birth'), 1, 1, 'C', true);
        }
        
        return $pdf;
    }

    /**
     * Undocumented function
     *
     * @param Pagination $pdf
     * @param integer $fontSize
     * @param integer $cellTableClassroom
     * @param integer $cellTableHeight
     * @param integer $cellTablePresence
     * @param integer $cellTableObservation
     * @param integer $cellTablePresence3
     * @param SubSystem $subSystem
     * @return Pagination
     */
    public function getTableHeaderPaginationFirstPerClass(Pagination $pdf, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTablePresence, int $cellTableObservation, int $cellTablePresence3, SubSystem $subSystem): Pagination
    {
        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->SetFont('Times', 'B', $fontSize+1);
            $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellTableClassroom, $cellTableHeight*1.5, 'Classes', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence+80 , $cellTableHeight*1.5, utf8_decode('Noms et prénoms'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, 'Sexe', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, 'Moyenne', 1, 1, 'C', true);
        } 
        else 
        {
            $pdf->SetFont('Times', 'B', $fontSize+2);
            $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode('N°'), 1, 0, 'C', true);
            $pdf->Cell($cellTableClassroom, $cellTableHeight*1.5, 'Class', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence+80 , $cellTableHeight*1.5, utf8_decode('Lastnames and firstnames'), 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, 'Sex', 1, 0, 'C', true);
            $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, 'Average', 1, 1, 'C', true);
        }
        
        return $pdf;
    }


    /**
     * fonction qui me retourne les lignes de mes tableaux
     *
     * @param Pagination $pdf
     * @param integer $fontSize
     * @param integer $cellTableClassroom
     * @param integer $cellTableHeight
     * @param integer $cellTablePresence
     * @param SchoolYear $schoolYear
     * @param array $students
     * @return Pagination
     */
    public function ligneDeMesTableauxMeilleursEleves(Pagination $pdf, int $fontSize, int $cellTableClassroom, int $cellTableHeight, int $cellTablePresence, SchoolYear $schoolYear, array $students): Pagination
    {
        $numero = 1;
        foreach ($students as $topFiveStudent) 
        {   
            if ($numero % 2 == 1) 
            {
                $pdf->SetFillColor(255,255,255);
            }else
            {
                $pdf->SetFillColor(224,235,255);
            }

            if ($topFiveStudent->getStudent()->getSchoolYear()->getSchoolYear() == $schoolYear->getSchoolYear() && $topFiveStudent->getStudent()->getSubSystem()->getSubSystem() == ConstantsClass::FRANCOPHONE) 
            {
                $pdf->SetFont('Times', '', $fontSize-1);
                $pdf->Cell($cellTableClassroom-15 , $cellTableHeight*1.5, utf8_decode($numero), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence+45 , $cellTableHeight*1.5, utf8_decode($topFiveStudent->getStudent()->getFullName()), 1, 0, 'L', true);
                $pdf->Cell($cellTablePresence-20 , $cellTableHeight*1.5, utf8_decode($topFiveStudent->getStudent()->getSex()->getSex()), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence-10 , $cellTableHeight*1.5, utf8_decode(number_format($topFiveStudent->getMoyenne(), 2)), 1, 0, 'C', true);
                $pdf->Cell($cellTableClassroom , $cellTableHeight*1.5, utf8_decode($topFiveStudent->getStudent()->getClassroom()->getClassroom()), 1, 0, 'C', true);
                $pdf->Cell($cellTablePresence , $cellTableHeight*1.5, utf8_decode(date_format($topFiveStudent->getStudent()->getBirthday(),'d/m/Y')), 1, 1, 'C', true);
                
                
                $numero ++;
            }
        
        }

        return $pdf;
    }


    /**
     * Calcule la moyenne en fonction du total des notes et des coefficients
     *
     * @param float $totalMark
     * @param float $totalCoefficient
     * @return float
     */
    public function getRatio(float $totalMark, float $totalCoefficient): float
    {
        if($totalCoefficient != 0)
        {
            return $totalMark / $totalCoefficient;
        }

        return ConstantsClass::UNRANKED_AVERAGE;
    }

    public function getFormatRatio(float $value, float $total): string
    {
        if($total != 0)
        {
            $ratio = ($value/$total)*100;

            if($ratio < 100)
            {
                return str_pad(number_format($ratio, 2), 5, '0', STR_PAD_LEFT);

            }elseif($ratio == 100)
            {
                return (string)number_format($ratio, 0);
            }else
            {
                return (string)number_format($ratio, 2);
            }
        }

        return '/';
    }


    /**
     * Formate les notes pour affichage
     *
     * @param float $mark
     * @return string
     */
    public function formatMark(float $mark):string
    {
        $formatedMark = "/";
        
        if( ($mark != ConstantsClass::UNRANKED_MARK) &&  ($mark != ConstantsClass::UNRANKED_AVERAGE))
        {
            if($mark < 100)
            {
                $formatedMark = str_pad(number_format($mark, 2), 5, '0', STR_PAD_LEFT);

            }elseif($mark == 100)
            {
                $formatedMark = number_format($mark, 0);
            }else
            {
                $formatedMark = number_format($mark, 2);

            }
        }
        
        return $formatedMark;
        
    }


    /**
     * Ajoute 0 devant tout entier inférieur à 10
     *
     * @param integer $number
     * @return void
     */
    public function formatInteger(int $number)
    {
        return ($number < 10) ? '0'.$number : $number;
    }


    /**
     * Retourne une appréciation APC selon une note
     *
     * @param float $mark
     * @return string
     */
    public function getApcAppreciationFr(float $mark): string
    {
        if($mark == ConstantsClass::UNRANKED_AVERAGE)
        {
            return 'N.C';
        }

        if($mark != ConstantsClass::UNRANKED_MARK)
        {
            if($mark > 0 && $mark < 10)
                return 'CNA';
            elseif($mark >= 10 && $mark < 12)
                return 'CMA';
            elseif($mark >= 12 && $mark < 14)
                return 'CA';
            elseif($mark >= 14 && $mark < 16)
                return 'CBA';
            elseif($mark >= 16 && $mark <= 20)
                return 'CTBA';
        }
        return '//';
    }

    /**
     * Retourne une appréciation APC selon une note
     *
     * @param float $mark
     * @return string
     */
    public function getApcAppreciationEn(float $mark): string
    {
        if($mark == ConstantsClass::UNRANKED_AVERAGE)
        {
            return 'N.C';
        }

        if($mark != ConstantsClass::UNRANKED_MARK)
        {
            if($mark > 0 && $mark < 10)
                return 'CNA';
            elseif($mark >= 10 && $mark < 12)
                return 'CAA';
            elseif($mark >= 12 && $mark < 14)
                return 'CA';
            elseif($mark >= 14 && $mark < 16)
                return 'CWA';
            elseif($mark >= 16 && $mark <= 20)
                return 'CVWA';
        }
        return '//';
    }

    public function getCote(float $mark): string
    {
        if($mark == ConstantsClass::UNRANKED_AVERAGE)
        {
            return 'N.C';
        }

        if($mark != ConstantsClass::UNRANKED_MARK)
        {
            if($mark > 0 && $mark < 10)
                return 'D';
            elseif($mark >= 10 && $mark < 12)
                return 'C';
            elseif($mark >= 12 && $mark < 14)
                return 'C+';
            elseif($mark >= 14 && $mark < 15)
                return 'B';
            elseif($mark >= 15 && $mark < 16)
                return 'B+';
            elseif($mark >= 16 && $mark < 18)
                return 'A';
            elseif($mark >= 18 && $mark <= 20)
                return 'A+';
        }
        return '//';
    }

    /**
     * Calcule la note trimestrielle en fonction de deux notes séquentielles
     *
     * @param float $mark1
     * @param float $mark2
     * @return float
     */
    public function getMarkTerm(float $mark1, float $mark2): float
    {
        if(($mark1 == ConstantsClass::UNRANKED_MARK))
        {
            return $mark2;

        }elseif(($mark2 == ConstantsClass::UNRANKED_MARK))
        {
            return $mark1;

        }else
        {
            return ($mark1 + $mark2)/2;
        }
    }

    /**
     * Calcule les notes trimestrielles en fonction des notes séquentielles
     *
     * @param array $studentMarkSequence1
     * @param array $studentMarkSequence2
     * @return array
     */
    public function getStudentMarkTerm(array $studentMarkSequence1, array $studentMarkSequence2): array 
    {
        $studentMarkTerm = [];
        $clonner = function($object){return clone $object;};
        $studentMarkTerm = array_map($clonner, $studentMarkSequence1);
        $length1 = count($studentMarkSequence1);
        $length2 = count($studentMarkSequence2);

        if($length1 == $length2)
        {
            for($i = 0; $i < $length1; $i++)
            {
                $studentMarkTerm[$i]->setMark($this->getMarkTerm($studentMarkSequence1[$i]->getMark(), $studentMarkSequence2[$i]->getMark()));
            }

        }

        return $studentMarkTerm;
    }

     /**
     * Calcule et retourne la note annuelle à partir des notes trimestrielles
     *
     * @param array $studentMarkTerm1
     * @param array $studentMarkTerm2
     * @param array $studentMarkTerm3
     * @return array
     */
    public function getAnnualMarks(array $studentMarkTerm1, array $studentMarkTerm2 = [], array $studentMarkTerm3 = []): array
    {
        $length1 = count($studentMarkTerm1);
        $length2 = count($studentMarkTerm2);
        $length3 = count($studentMarkTerm3);
 
        $studentMarkAnnual = [];
 
        $clonner = function($object){return clone $object;};
 
        if($length1 != 0)
        {
            if($length2 == 0 && $length3 == 0)
            {
                $studentMarkAnnual = array_map($clonner, $studentMarkTerm1);
 
            }elseif($length2 != 0 && $length3 == 0)
            {
                $studentMarkAnnual = $this->getStudentMarkTerm($studentMarkTerm1, $studentMarkTerm2);
            
            }elseif($length2 == 0 && $length3 != 0)
            {
                $studentMarkAnnual = $this->getStudentMarkTerm($studentMarkTerm1, $studentMarkTerm3);

            }else
            {
                $studentMarkAnnual = array_map($clonner, $studentMarkTerm1);
        
                for ($i=0; $i < $length1; $i++) 
                {   
                    $mark1 = $studentMarkTerm1[$i]->getMark();
                    $mark2 = $studentMarkTerm2[$i]->getMark();
                    $mark3 = $studentMarkTerm3[$i]->getMark();
        
                    if($mark1 == ConstantsClass::UNRANKED_MARK)
                    {
                        $termMark = $this->getMarkTerm($mark2, $mark3);
                    }
                    elseif($mark2 == ConstantsClass::UNRANKED_MARK )
                    {
                        $termMark = $this->getMarkTerm($mark1, $mark3);
                    }
                    elseif($mark3 == ConstantsClass::UNRANKED_MARK)
                    {
                        $termMark = $this->getMarkTerm($mark1, $mark2);
                    }
                    elseif(($mark1 != ConstantsClass::UNRANKED_MARK) && ($mark2 != ConstantsClass::UNRANKED_MARK) && ($mark3 != ConstantsClass::UNRANKED_MARK))
                    {
                        $termMark = ($mark1 + $mark2 + $mark3)/3;
                    }
                        
                    $studentMarkAnnual[$i]->setMark($termMark);
                }
             }
        }
 
        return $studentMarkAnnual;
    }

    /**
     * Construit l'entête partie adminisrative en français
     *
     * @param School $school
     * @param PDF $pdf
     * @param float $wHeader
     * @param float $cellHeaderHeight
     * @param integer $fontSize
     * @param SchoolYear $schoolYear
     * @return PDF
     */
    public function getAdministrativeHeader(School $school, PDF $pdf, float $cellHeaderHeight, int $fontSize, SchoolYear $schoolYear): PDF
    {
        $wHeader = 50;
        $cellHeaderHeight /= 1.2; 

        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountry()) , 0, 0, 'C');

            $x = $pdf->getX();
            $y = $pdf->getY();

            $pdf->Ln();

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            $pdf->SetFont('Times', 'B', $fontSize);
            if(strlen($school->getFrenchName()) > 30)
            {
                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                $pdf->SetFont('Times', 'B', $fontSize);
                
            }else
            {
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                
            }

            $pdf->SetFont('Times', '', $fontSize-3);
            
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            // $pdf->Ln();

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize);

            $pdf->Cell($wHeader, 3, utf8_decode('Année Scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

            $pdf->setXY($x, $y);

            $rightPosition = -60;
            $pdf->SetX($rightPosition);
            $pdf->setFont('Times', '', $fontSize-3);

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountry()) , 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            $pdf->SetFont('Times', 'B', $fontSize);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
    
            $pdf->SetFont('Times', '', $fontSize-3);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'PO Box: '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            // $pdf->Ln();

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->SetX($rightPosition);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode('School Year : '.$schoolYear->getSchoolYear()), 0, 1, 'C');


            $pdf->Ln();
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            
            $pdf->Image('images/school/'.$school->getLogo(), 92, 7, -200);

            $pdf->Image('images/school/'.$school->getFiligree(), 40, 90, -100);  
            $pdf->setXY($x, $y);

        return $pdf;

    }

    /**
     * Construit l'entête partie adminisrative en français Emploid du temps
     *
     * @param School $school
     * @param PDF $pdf
     * @param float $wHeader
     * @param float $cellHeaderHeight
     * @param integer $fontSize
     * @param SchoolYear $schoolYear
     * @return PDF
     */
    public function getAdministrativeHeaderEmploiDuTemps(School $school, PDF $pdf, float $cellHeaderHeight, int $fontSize, SchoolYear $schoolYear): PDF
    {
        $wHeader = 50;
        $cellHeaderHeight /= 1.2; 

        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountry()) , 0, 0, 'C');

            $x = $pdf->getX();
            $y = $pdf->getY();

            $pdf->Ln();

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            $pdf->SetFont('Times', 'B', $fontSize);
            if(strlen($school->getFrenchName()) > 30)
            {
                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                $pdf->SetFont('Times', 'B', $fontSize);
                
            }else
            {
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                
            }

            $pdf->SetFont('Times', '', $fontSize-3);
            
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            // $pdf->Ln();

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize);

            $pdf->Cell($wHeader, 3, utf8_decode('Année Scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

            $pdf->setXY($x, $y);

            $rightPosition = -60;
            $pdf->SetX($rightPosition);
            $pdf->setFont('Times', '', $fontSize-3);

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountry()) , 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            $pdf->SetFont('Times', 'B', $fontSize);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
    
            $pdf->SetFont('Times', '', $fontSize-3);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'PO Box: '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            // $pdf->Ln();

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->SetX($rightPosition);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode('School Year : '.$schoolYear->getSchoolYear()), 0, 1, 'C');


            $pdf->Ln();
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            
            $pdf->Image('images/school/'.$school->getLogo(), 135, 7, -200);

            $pdf->Image('images/school/'.$school->getFiligree(), 40, 90, -100);  
            $pdf->setXY($x, $y);

        return $pdf;

    }

    /**
     * Construit l'entête partie adminisrative en français
     *
     * @param School $school
     * @param Pagination $pdf
     * @param float $wHeader
     * @param float $cellHeaderHeight
     * @param integer $fontSize
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    public function getAdministrativeHeaderPagination(School $school, Pagination $pdf, float $cellHeaderHeight, int $fontSize, SchoolYear $schoolYear): Pagination
    {
        $wHeader = 50;
        $cellHeaderHeight /= 1.2; 

        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountry()) , 0, 0, 'C');

            $x = $pdf->getX();
            $y = $pdf->getY();

            $pdf->Ln();

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            $pdf->SetFont('Times', 'B', $fontSize);
            if(strlen($school->getFrenchName()) > 30)
            {
                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                $pdf->SetFont('Times', 'B', $fontSize);
                
            }else
            {
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                
            }

            $pdf->SetFont('Times', '', $fontSize-3);
            
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            // $pdf->Ln();

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize);

            $pdf->Cell($wHeader, 3, utf8_decode('Année Scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

            $pdf->setXY($x, $y);

            $rightPosition = -60;
            $pdf->SetX($rightPosition);
            $pdf->setFont('Times', '', $fontSize-3);

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountry()) , 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            $pdf->SetFont('Times', 'B', $fontSize);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
    
            $pdf->SetFont('Times', '', $fontSize-3);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'PO Box: '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            // $pdf->Ln();

            $pdf->Ln();
            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->SetX($rightPosition);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode('School Year : '.$schoolYear->getSchoolYear()), 0, 1, 'C');


            $pdf->Ln();
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            
            $pdf->Image('images/school/'.$school->getLogo(), 90, 7, -180);

            $pdf->Image('images/school/'.$school->getFiligree(), 40, 90, -100);  
            $pdf->setXY($x, $y);

        return $pdf;

    }

    public function getAdministrativeHeaderTicket(School $school, Pagination $pdf, float $cellHeaderHeight, int $fontSize, SchoolYear $schoolYear): Pagination
    {
        $wHeader = 50;
        $cellHeaderHeight /= 1.2; 

        $pdf->SetFont('Times', '', $fontSize-3);
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountry()) , 0, 0, 'C');

            $x = $pdf->getX();
            $y = $pdf->getY();

            $pdf->Ln();

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            $pdf->SetFont('Times', 'B', $fontSize);
            if(strlen($school->getFrenchName()) > 30)
            {
                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                $pdf->SetFont('Times', 'B', $fontSize);
                
            }else
            {
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                
            }

            $pdf->SetFont('Times', '', $fontSize-3);
            
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', $fontSize);

            $pdf->Cell($wHeader, 3, utf8_decode('Année Scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

            $pdf->setXY($x, $y);

            $rightPosition = -60;
            $pdf->SetX($rightPosition);
            $pdf->setFont('Times', '', $fontSize-3);

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountry()) , 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            $pdf->SetFont('Times', 'B', $fontSize);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
    
            $pdf->SetFont('Times', '', $fontSize-3);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'PO Box: '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->SetX($rightPosition);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode('School Year : '.$schoolYear->getSchoolYear()), 0, 1, 'C');


            $pdf->Ln();
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            
            $pdf->Image('images/school/'.$school->getLogo(), 130, 15, -150);
            $pdf->Image('images/school/'.$school->getFiligree(), 80, 80, -90);  
            $pdf->setXY($x, $y);

        return $pdf;

    }

    
    public function getAdministrativeHeaderQuitus(School $school, Pdf $pdf, float $cellHeaderHeight, int $fontSize, SchoolYear $schoolYear): Pdf
    {
        $wHeader = 50;
        $cellHeaderHeight /= 1.2; 

        $pdf->SetFont('Times', '', $fontSize-3);
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountry()) , 0, 0, 'C');

            $x = $pdf->getX();
            $y = $pdf->getY();

            $pdf->Ln();

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            $pdf->SetFont('Times', 'B', $fontSize);
            if(strlen($school->getFrenchName()) > 30)
            {
                $pdf->SetFont('Times', 'B', $fontSize-2);
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                $pdf->SetFont('Times', 'B', $fontSize);
                
            }else
            {
                $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
                
            }

            $pdf->SetFont('Times', '', $fontSize-3);
            
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

            // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', $fontSize);

            $pdf->Cell($wHeader, 3, utf8_decode('Année Scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

            $pdf->setXY($x, $y);

            $rightPosition = -60;
            $pdf->SetX($rightPosition);
            $pdf->setFont('Times', '', $fontSize-3);

            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountry()) , 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishRegion()), 0, 2, 'C');
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-4);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishDivision()), 0, 2, 'C');
            $pdf->SetFont('Times', '', $fontSize-3);
            $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            $pdf->SetFont('Times', 'B', $fontSize);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
    
            $pdf->SetFont('Times', '', $fontSize-3);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMotto()), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
    
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'PO Box: '.utf8_decode($school->getPobox()).'  Tel : '.$school->getTelephone(), 0, 2, 'C');
            // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', $fontSize);
            $pdf->SetX($rightPosition);
    
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode('School Year : '.$schoolYear->getSchoolYear()), 0, 1, 'C');


            $pdf->Ln();
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            
            $pdf->Image('images/school/'.$school->getLogo(), 130, 15, -150);
            $pdf->Image('images/school/'.$school->getFiligree(), 80, 80, -90);  
            $pdf->setXY($x, $y);

        return $pdf;

    }


    /**
     * Zone admistrative de la page
     *
     * @param PDF $pdf
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return PDF
     */
    public function statisticAdministrativeHeader(PDF $pdf, School $school, SchoolYear $schoolYear): PDF
    {
        $wHeader = 50;
        $cellHeaderHeight = 3;
        $x = $pdf->getX();
        $y = $pdf->getY();

        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountry()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchRegion()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchDivision()), 0, 2, 'C');
        $pdf->SetFont('Times', '', 7);
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

        $pdf->SetFont('Times', 'B', 10);

        if(strlen($school->getFrenchName()) > 30)
        {
            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
        }else
        {
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');

        }

        $pdf->SetFont('Times', '', 7);
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getFrenchMotto()), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.$school->getPobox().'  Tel : '.$school->getTelephone(), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');

        $pdf->Cell($wHeader,3,'',0,2,'C');
        $pdf->SetFont('Times', 'B', 10);

        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode('Année scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

        $pdf->SetXY($x, $y);
        $pdf->SetX(-55);
        $pdf->SetFont('Times', '', 7);

        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountry()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishRegion()), 0, 2, 'C');
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->SetFont('Times', '', 6);
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishDivision()), 0, 2, 'C');
        $pdf->SetFont('Times', '', 7);
        $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');

        $pdf->SetFont('Times', 'B', 10);

        if(strlen($school->getFrenchName()) > 30)
        {
            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
        }else
        {
            $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');

        }

        $pdf->SetFont('Times', '', 7);
        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode($school->getEnglishMotto()), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.$school->getPobox().'  Tel : '.$school->getTelephone(), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');

        $pdf->Cell($wHeader,3,'',0,2,'C');
        $pdf->SetFont('Times', 'B', 10);

        $pdf->Cell($wHeader, $cellHeaderHeight, utf8_decode('Année scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

        $pdf->Ln(2);
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // Logo de l'établissement et filigrane

        $pdf->Image('images/school/'.$school->getLogo(), 125, 7, -100);
        $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);  

        $pdf->setXY($x, $y);

        return $pdf;
    }


    /**
     * Zone admistrative de la page
     *
     * @param Pagination $pdf
     * @param School $school
     * @param SchoolYear $schoolYear
     * @return Pagination
     */
    public function statisticAdministrativeHeaderPagination(Pagination $pdf, School $school, SchoolYear $schoolYear): Pagination
    {
        $wHeader = 50;
        $cellHeaderHeight = 3;
        $x = $pdf->getX();
        $y = $pdf->getY();

        $pdf->SetFont('Times', '', 10);
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchCountry()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchCountryMotto()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchMinister()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchRegion()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchDivision()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');

        $pdf->SetFont('Times', 'B', 10);

        if(strlen($school->getFrenchName()) > 30)
        {
            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');
        }else
        {
            $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchName()), 0, 2, 'C');

        }

        $pdf->SetFont('Times', '', 8);
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getFrenchMotto()), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.$school->getPobox().'  Tel : '.$school->getTelephone(), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');

        $pdf->Cell($wHeader,3,'',0,2,'C');
        $pdf->SetFont('Times', 'B', 10);

        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode('Année scolaire : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

        $pdf->SetXY($x, $y);
        $pdf->SetX(-100);

        $pdf->SetFont('Times', '', 10);
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishCountry()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishCountryMotto()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishMinister()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishRegion()), 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishDivision()), 0, 2, 'C');
        $pdf->SetFont('Times', '', 7);
        $pdf->Cell($wHeader+50, $cellHeaderHeight, '**********', 0, 2, 'C');

        $pdf->SetFont('Times', 'B', 10);

        if(strlen($school->getFrenchName()) > 30)
        {
            $pdf->SetFont('Times', 'B', 7);
            $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');
        }else
        {
            $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishName()), 0, 2, 'C');

        }

        $pdf->SetFont('Times', '', 8);
        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode($school->getEnglishMotto()), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, '**********', 0, 2, 'C');
            
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'B.P : '.$school->getPobox().'  Tel : '.$school->getTelephone(), 0, 2, 'C');
        // $pdf->Cell($wHeader, $cellHeaderHeight, 'E-mail : '.utf8_decode($school->getEmail()), 0, 2, 'C');

        $pdf->Cell($wHeader,3,'',0,2,'C');
        $pdf->SetFont('Times', 'B', 10);

        $pdf->Cell($wHeader+50, $cellHeaderHeight, utf8_decode('School Year : '.$schoolYear->getSchoolYear()), 0, 2, 'C');

        $pdf->Ln(2);
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        // Logo de l'établissement et filigrane

        $pdf->Image('images/school/'.$school->getLogo(), 125, 7, -100);
        $pdf->Image('images/school/'.$school->getFiligree(), 90, 69, -100);  

        $pdf->setXY($x, $y);

        $pdf->Ln(5);
        return $pdf;
    }


    /**
     * Retourne un nom précédé de M. ou Mme selon le sexe
     *
     * @param string $name
     * @param string $sex
     * @return string
     */
    public function getNameWithTitle(string $name, string $sex): string
    {
        return $sex == 'M' ? 'M. '.$name : 'Mme '.$name;
    }


    /**
     * Retourne les enseignants d'une classe en dehors du professeur principal
     *
     * @param Teacher $principalTeacher
     * @param Classroom $classroom
     * @return array
     */
    public function getOtherTeachers(Teacher $principalTeacher, Classroom $classroom): array
    {
        $allTeacherLessons = $this->lessonRepository->findOtherTeachers($principalTeacher, $classroom);

        $distinctTeachers = [];

        if(!empty($allTeacherLessons))
        {
            $firstTeacherLesson = $allTeacherLessons[0];
            $firstTeacher = $firstTeacherLesson->getTeacher();
            $distinctTeachers[] = $firstTeacher;

            foreach ($allTeacherLessons as $teacherLesson) 
            {
                $teacher = $teacherLesson->getTeacher();

                if($teacher->getId() != $firstTeacher->getId())
                {
                    $distinctTeachers[] = $teacher;

                    $firstTeacher = $teacher;
                }
            }
        }

        return $distinctTeachers;
    }


    /**
     * Calcule et retourne le nombre de d'élèves ayant une moyenne >= 10
     *
     * @param array $classifiedStudents
     * @return integer
     */
    public function getNumberOfSuccedStudents(array $classifiedStudents): int
    {
        $numberOfSuccedStudents = 0;

        if(!empty($classifiedStudents))
        {
            foreach ($classifiedStudents as $report) 
            {
                if($report->getMoyenne() >= 10)
                {
                    $numberOfSuccedStudents++;
                }
            }
        }

        return $numberOfSuccedStudents;
    }


    /**
     * Recupère les 5 premiers
     *
     * @param array $classifiedStudents
     * @return array
     */
    public function getFirst5(array $classifiedStudents): array
    {
        $first5 = [];

        if(!empty($classifiedStudents))
        {
            $counter = 0;
            foreach ($classifiedStudents as $student) 
            {
                $first5[] = $student;
                $counter++;

                if($counter == 5)
                {
                    break;
                }
            }
        }

        return $first5;
    }

    /**
     * On recupère les 5 derniers
     *
     * @param array $classifiedStudents
     * @return array
     */
    public function getLast5(array $classifiedStudents): array
    {
        $last5 = [];
        $size = count($classifiedStudents);

        if($size != 0)
        {
            if($size > 5)
            {
                for($i = $size-1; $i >= $size-5; $i--)
                {
                    $last5[] = $classifiedStudents[$i];
                }
            }else
            {
                for($i = $size-1; $i >= 0; $i--)
                {
                    $last5[] = $classifiedStudents[$i];
                }
            }
        }
        
        return $last5;
    }


    /**
     * Recupère les élèves ayant au moins un tableau d'honneur
     *
     * @param array $allReports
     * @return array
     */
    public function getBest(array $allReports): array
    {
        $best = [];

        if(!empty($allReports))
        {
            foreach ($allReports as $report) 
            {
                if($report->getReportFooter()->getStudentResult()->getMoyenne() >= ConstantsClass::ROLL_OF_HONOUR)
                {
                    $best[] = $report;
                }
            }

        }

        return $best;
    }

    /**
     * Retourne le total des best par sexe et par rubrique
     *
     * @param array $best
     * @return array
     */
    public function getBestTotal(array $best): array
    {
        $boysTH = 0;
        $boysENC = 0;
        $boysFEL = 0;
        $girlsTH = 0;
        $girlsENC = 0;
        $girlsFEL = 0;

        foreach ($best as $report) 
        {
            $studentWork = $report->getReportFooter()->getStudentWork();
            $sex = $report->getReportHeader()->getStudent()->getSex()->getSex();
            $TH = $studentWork->getRollOfHonour();
            $ENC = $studentWork->getEncouragement();
            $FEL = $studentWork->getCongratulation();

            if ($sex == 'F') 
            {
                if ($TH == 'X') 
                {
                    $girlsTH++;
                }
                if ($ENC == 'X') 
                {
                    $girlsENC++;
                }
                if ($FEL == 'X') 
                {
                    $girlsFEL++;
                }
            }else
            {
                if ($TH == 'X') 
                {
                    $boysTH++;
                }
                if ($ENC == 'X') 
                {
                    $boysENC++;
                }
                if ($FEL == 'X') 
                {
                    $boysFEL++;
                }
            }

        }
            return ['girlsTH'=> $girlsTH, 
                    'girlsENC'=> $girlsENC,
                    'girlsFEL'=> $girlsFEL,
                    'boysTH'=> $boysTH,
                    'boysENC'=> $boysENC,
                    'boysFEL'=> $boysFEL
                    ];
    }


    /**
     * Retourne les mauvais en travail et en discipline
     *
     * @param array $allReports
     * @return array
     */
    public function getBad(array $allReports): array
    {
        $bad = [];

        foreach ($allReports as $report) 
        {
            $average = $report->getReportFooter()->getStudentResult()->getMoyenne();
            $absence = $report->getReportFooter()->getDiscipline()->getAbsence();

            if((($average <= ConstantsClass::WARNING_WORK) || ($absence >= ConstantsClass::WARNING_BAHAVIOUR)) && ($average != ConstantsClass::UNRANKED_AVERAGE))
            {
                $bad[] = $report;
            }
        }

        return $bad;
    }

    /**
     * Retourne le total des bad par sexe et par rubrique
     *
     * @param array $bad
     * @return array
     */
    public function getTotalBad(array $bad): array 
    {
        $boysAC = 0;
        $boysBC = 0;
        $boysAT = 0;
        $boysBT = 0;
        $boysEXT = 0;
        $boysCD = 0;
        $boysEclusion3 = 0;
        $boysEclusion5 = 0;
        $boysEclusion8 = 0;

        $girlsAC = 0;
        $girlsBC = 0;
        $girlsAT = 0;
        $girlsBT = 0;
        $girlsEXT = 0;
        $girlsCD = 0;
        $girlsEclusion3 = 0;
        $girlsEclusion5 = 0;
        $girlsEclusion8 = 0;


        foreach ($bad as $report) 
        {
            $studentWork = $report->getReportFooter()->getStudentWork();
            $studentDiscipline = $report->getReportFooter()->getDiscipline();
            $AC = $studentDiscipline->getWarningBehaviour();
            $BC = $studentDiscipline->getBlameBehaviour();
            $AT = $studentWork->getWarningWork();
            $BT = $studentWork->getBlameWork();
            $EXT = $studentDiscipline->getExclusion();
            $CD =  $studentDiscipline->getDisciplinaryCommitee();
            $sex = $report->getReportHeader()->getStudent()->getSex()->getSex(); 

            if ($sex == 'F') 
            {
                if ($AC == 'X') 
                {
                    $girlsAC++;
                }
                if ($BC == 'X') 
                {
                    $girlsBC++;
                }
                if ($AT == 'X') 
                {
                    $girlsAT++;
                }
                if ($BT == 'X') 
                {
                    $girlsBT++;
                }
                if ($EXT) 
                {
                    $girlsEXT++;

                    switch ($EXT) 
                    {
                        case 3:
                            $girlsEclusion3++;
                            break;
                        case 5:
                            $girlsEclusion5++;
                            break;
                        case 8:
                            $girlsEclusion8++;
                            break;
                    }
                }
                if ($CD == 'X') 
                {
                    $girlsCD++;
                }
            }else
            {
                if ($AC == 'X') 
                {
                    $boysAC++;
                }
                if ($BC == 'X') 
                {
                    $boysBC++;
                }
                if ($AT == 'X') 
                {
                    $boysAT++;
                }
                if ($BT == 'X') 
                {
                    $boysBT++;
                }
                if ($EXT) 
                {
                    $boysEXT++;

                     switch ($EXT) 
                    {
                        case 3:
                            $boysEclusion3++;
                            break;
                        case 5:
                            $boysEclusion5++;
                            break;
                        case 8:
                            $boysEclusion8++;
                            break;
                    }
                }
                if ($CD == 'X') 
                {
                    $boysCD++;
                }
            }

        }

        return ['girlsAC'=> $girlsAC, 
                'girlsBC'=> $girlsBC,
                'girlsAT'=> $girlsAT,
                'girlsBT'=> $girlsBT,
                'girlsEXT'=> $girlsEXT,
                'girlsCD'=> $girlsCD,
                'boysAC'=> $boysAC, 
                'boysBC'=> $boysBC,
                'boysAT'=> $boysAT,
                'boysBT'=> $boysBT,
                'boysEXT'=> $boysEXT,
                'boysCD'=> $boysCD,
                'boysEclusion3'=> $boysEclusion3,
                'boysEclusion5'=> $boysEclusion5,
                'boysEclusion8'=> $boysEclusion8,
                'girlsEclusion3'=> $girlsEclusion3,
                'girlsEclusion5'=> $girlsEclusion5,
                'girlsEclusion8'=> $girlsEclusion8
            ];
    }

    public function getAllAbsence(array $allReports): array 
    {
        $absenceBoys = 0;
        $absenceGirls = 0;

        foreach ($allReports as $report) 
        {
            $absence = $report->getReportFooter()->getDiscipline()->getAbsence();
             $sex = $report->getReportHeader()->getStudent()->getSex()->getSex(); 

             if($absence)
             {
                if($sex == 'F')
                {
                   $absenceGirls += $absence; 
                }else
                {
                    $absenceBoys += $absence;
                }
             }
        }

        return ['absenceBoys' => $absenceBoys, 
                'absenceGirls' => $absenceGirls
                ];
    }
   
 
    /**
     * Insère une page en portrait
     *
     * @param PDF $pdf
     * @param integer $fontSize
     * @param integer $leftMargin
     * @return PDF
     */
    public function newPage(PDF $pdf, string $orientation, int $leftMargin, int $fontSize): PDF
    {
        $pdf->addPage($orientation);
        $pdf->SetLeftMargin($leftMargin);
        $pdf->setFont('Times', '', $fontSize);
        $pdf->setTextColor(0, 0, 0);
        $pdf->SetFillColor(200);

        return $pdf ;
    }

    /**
     * Insère une page en portrait avec pagination
     *
     * @param Pagination $pdf
     * @param integer $fontSize
     * @param integer $leftMargin
     * @return Pagination
     */
    public function newPagePagination(Pagination $pdf, string $orientation, int $leftMargin, int $fontSize): Pagination
    {
        $pdf->addPage($orientation);
        $pdf->SetLeftMargin($leftMargin);
        $pdf->setFont('Times', '', $fontSize);
        $pdf->setTextColor(0, 0, 0);
        $pdf->SetFillColor(200);

        return $pdf ;
    }


    /**
     * Insère une page en portrait sans footer
     *
     * @param NoFooter $pdf
     * @param integer $fontSize
     * @param integer $leftMargin
     * @return NoFooter
     */
    public function newPageNoFooter(NoFooter $pdf, string $orientation, int $leftMargin, int $fontSize): NoFooter
    {
        $pdf->addPage($orientation);
        $pdf->SetLeftMargin($leftMargin);
        $pdf->setFont('Times', '', $fontSize);
        $pdf->setTextColor(0, 0, 0);
        $pdf->SetFillColor(200);

        return $pdf ;
    }

    public function newPageArial(PDF $pdf, string $orientation, int $leftMargin, int $fontSize): PDF
    {
        $pdf->addPage($orientation);
        $pdf->SetLeftMargin($leftMargin);
        $pdf->setFont('Arial', '', $fontSize);
        $pdf->setTextColor(0, 0, 0);
        $pdf->SetFillColor(200);

        return $pdf ;
    }


     /**
     * Entête de la fiche statistique
     *
     * @param PDF $pdf
     * @param string $title
     * @param string $termName
     * @param string $perClassOrSubject
     * @param School $school
     * @param string $statisticConcerned
     * @return PDF
     */
    public function staisticSlipHeader(PDF $pdf, string $title, string $termName, School $school, string $perClassOrSubject = '', string $statisticConcerned = ''): PDF
    {
        $pdf->SetFont('Times', 'B', 16);
        $pdf->Cell(0, 3, $title, 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(80, 3, $termName, 0, 0, 'C');
        $pdf->Cell(105, 3, '', 0, 0, 'C');
        // $pdf->Cell(105, 3, $school->getFrenchName(), 0, 0, 'C');

        if($statisticConcerned)
        {
            $pdf->Cell(25, 3, $perClassOrSubject.' : ', 0, 0, 'R');
            if(strlen($statisticConcerned) > 17)
            {
                $pdf->SetFont('Times', 'B', 10);
            }
            $pdf->Cell(40, 3, utf8_decode($statisticConcerned), 0, 1, 'L');
        }
        $pdf->Ln(2);

        return $pdf;
    }


     /**
     * Entête de la fiche statistique
     *
     * @param Pagination $pdf
     * @param string $title
     * @param string $termName
     * @param string $perClassOrSubject
     * @param School $school
     * @param string $statisticConcerned
     * @return Pagination
     */
    public function staisticSlipHeaderPagination(Pagination $pdf, string $title, string $termName, School $school, string $perClassOrSubject = '', string $statisticConcerned = ''): Pagination
    {
        $pdf->SetFont('Times', 'B', 16);
        $pdf->Cell(0, 3, $title, 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(270/2, 3, $termName, 0, 0, 'C');

        $pdf->Cell(270/2, 3, "DISCIPLINE : ".$statisticConcerned ? $statisticConcerned: "//", 0, 0, 'C');

        $pdf->Cell(105, 3, '', 0, 0, 'C');
        // $pdf->Cell(105, 3, $school->getFrenchName(), 0, 0, 'C');

        if($statisticConcerned)
        {
            $pdf->Cell(25, 3, $perClassOrSubject.' : ', 0, 0, 'R');
            if(strlen($statisticConcerned) > 17)
            {
                $pdf->SetFont('Times', 'B', 10);
            }
            $pdf->Cell(40, 3, utf8_decode($statisticConcerned), 0, 1, 'L');
        }
        $pdf->Ln(2);

        return $pdf;
    }

    public function titreFicheSyntheseDeLaCouvertureDesHeuresEtProgrammesEnseignementParMatiere(Pagination $pdf, string $title, string $termName, School $school, string $perClassOrSubject = '', string $statisticConcerned = ''): Pagination
    {
        $pdf->SetFont('Times', 'B', 14);
        $pdf->Cell(0, 3, $title, 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(0, 3, $termName, 0, 0, 'C');

        $pdf->Cell(270/2, 3, "DISCIPLINE : ".$statisticConcerned ? $statisticConcerned: "//", 0, 0, 'C');

        $pdf->Cell(105, 3, '', 0, 0, 'C');
        // $pdf->Cell(105, 3, $school->getFrenchName(), 0, 0, 'C');

        if($statisticConcerned)
        {
            $pdf->Cell(25, 3, $perClassOrSubject.' : ', 0, 0, 'R');
            if(strlen($statisticConcerned) > 17)
            {
                $pdf->SetFont('Times', 'B', 10);
            }
            $pdf->Cell(40, 3, utf8_decode($statisticConcerned), 0, 1, 'L');
        }
        $pdf->Ln(2);

        return $pdf;
    }


    /**
     * fonction qui afiche l'enête de la fiche de reference er pv
     *
     * @param Pagination $pdf
     * @param string $title
     * @param string $termName
     * @param School $school
     * @param string $perClassOrSubject
     * @param string $statisticConcerned
     * @return Pagination
     */
    public function staisticSlipHeaderRegisterPagination(Pagination $pdf, string $title, string $termName, School $school, string $perClassOrSubject = '', string $statisticConcerned = ''): Pagination
    {
        $pdf->SetFont('Times', 'B', 16);
        $pdf->Cell(0, 3, $title, 0, 1, 'C');
        $pdf->Ln(2);
        $pdf->SetFont('Times', 'B', 12);
        $pdf->Cell(270/2, 3, $termName, 0, 0, 'C');
        
        $pdf->Cell(270/2, 3, $statisticConcerned ? "Classe : ".$statisticConcerned: "//", 0, 0, 'C');

        $pdf->Cell(105, 3, '', 0, 0, 'C');
        // $pdf->Cell(105, 3, $school->getFrenchName(), 0, 0, 'C');

        if($statisticConcerned)
        {
            $pdf->Cell(25, 3, $perClassOrSubject.' : ', 0, 0, 'R');
            if(strlen($statisticConcerned) > 17)
            {
                $pdf->SetFont('Times', 'B', 10);
            }
            $pdf->Cell(40, 3, utf8_decode($statisticConcerned), 0, 1, 'L');
        }
        $pdf->Ln(2);

        return $pdf;
    }
    

    /**
     * Formate le rang
     *
     * @param integer $rank
     * @param string $sex
     * @return string
     */
    public function formatRank(int $rank, string $sex): string
    {
        $mySession = $this->request->getSession();
        $subSystem = $mySession->get('subSystem');

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {        
            if($rank == 1)
            {
                if($sex == 'M')
                {
                    return $rank.'er';
                }else
                {
                    return $rank.'ère';
                }
            }
        }else
        {
            if($rank == 1)
            {
                return $rank.'st';
                
            }elseif ($rank == 2) 
            {
                return $rank.'nd';
            }elseif ($rank == 3) 
            {
                return $rank.'rd';
            }elseif ($rank == 21) 
            {
                return $rank.'st';
            }elseif ($rank == 22) 
            {
                return $rank.'nd';
            }elseif ($rank == 23) 
            {
                return $rank.'rd';
            }elseif ($rank == 31) 
            {
                return $rank.'st';
            }elseif ($rank == 32) 
            {
                return $rank.'nd';
            }elseif ($rank == 33) 
            {
                return $rank.'rd';
            }elseif ($rank == 41) 
            {
                return $rank.'st';
            }elseif ($rank == 42) 
            {
                return $rank.'nd';
            }elseif ($rank == 43) 
            {
                return $rank.'rd';
            }elseif ($rank == 51) 
            {
                return $rank.'st';
            }elseif ($rank == 52) 
            {
                return $rank.'nd';
            }elseif ($rank == 53) 
            {
                return $rank.'rd';
            }elseif ($rank == 61) 
            {
                return $rank.'st';
            }elseif ($rank == 62) 
            {
                return $rank.'nd';
            }elseif ($rank == 63) 
            {
                return $rank.'rd';
            }elseif ($rank == 71) 
            {
                return $rank.'st';
            }elseif ($rank == 72) 
            {
                return $rank.'nd';
            }elseif ($rank == 73) 
            {
                return $rank.'rd';
            }elseif ($rank == 81) 
            {
                return $rank.'st';
            }elseif ($rank == 82) 
            {
                return $rank.'nd';
            }elseif ($rank == 83) 
            {
                return $rank.'rd';
            }elseif ($rank == 91) 
            {
                return $rank.'st';
            }elseif ($rank == 92) 
            {
                return $rank.'nd';
            }elseif ($rank == 93) 
            {
                return $rank.'rd';
            }
        }

        if($rank == ConstantsClass::UNRANKED_RANK_DB)
        {
            return  ConstantsClass::UNRANKED_RANK;
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        { 
            return $rank.'e';
        }else
        {
            return $rank.'th';
        }
    }

    /**
     * Laisse un espace vertical entre les rubriques au pied de page des bulletins
     */
    public function escape(PDF $pdf, float $escape): PDF
    {
        if($escape != 0)
        {
            $pdf->Cell($escape);
        }
        return $pdf;
    }


    /**
     * Laisse un espace vertical entre les rubriques au pied de page des bulletins
     */
    public function escapeNoFooter(NoFooter $pdf, float $escape): NoFooter
    {
        if($escape != 0)
        {
            $pdf->Cell($escape);
        }
        return $pdf;
    }


    /**
     * Retourne le nombre d'heures dues dans une classe par trimestre
     */
    public function getNumberOfTermHours(Classroom $classroom, string $sex = null, int $term = 0): int
    {
        $numberOfWeekHours = 0;
        $numberOfStudents = 0;

        $lessons = $classroom->getLessons();

        if($sex == 'M')
        {
            $numberOfStudents = $this->getNumberOfBoys($classroom);

        }elseif($sex == 'F')
        {
            $numberOfStudents = $this->getNumberOfGirls($classroom);
        }else
        {
            $numberOfStudents = $this->getNumberOfStudents($classroom);
        }


        foreach ($lessons as $lesson) 
        {
            $numberOfWeekHours += $lesson->getWeekHours();
        }
        
        $numberOfStudentsHours = $numberOfWeekHours * $numberOfStudents * ConstantsClass::WEEKS_PER_TERM;
        
        if($term == 0)
        {
            return $numberOfStudentsHours*3;
        }

        return $numberOfStudentsHours;
    }


    /**
     * Effectif des garçons d'une classe
     */
    public function getNumberOfBoys(Classroom $classroom): int 
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'M'])
        ]));
    }

    /**
     * Effectif des redoublants
     */
    public function getNumberOfRepeaters(Classroom $classroom): int 
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'repeater' => 0
        ]));
    }


    /**
     * Effectif des filles d'une classe
     */
    public function getNumberOfGirls(Classroom $classroom): int 
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'F'])
        ]));
    }

    /**
     * Effectif total d'une classes
     */
    public function getNumberOfStudents(Classroom $classroom)
    {
        return count($classroom->getStudents());
    }

    
    /**
     * Nombre de garçons admis d'une classe
     */
    public function getNumberOfPassedBoys(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'M']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_PASSED)
        ]));
    }

     /**
     * Nombre de garçons redoublants d'une classe
     */
    public function getNumberOfRepeatedBoys(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'M']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_REAPETED)
        ]));
    }

     /**
     * Nombre de garçons exclus d'une classe
     */
    public function getNumberOfExpelledBoys(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'M']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_EXPELLED)
        ]));
    }

     /**
     * Nombre de garçons démissionnaire d'une classe
     */
    public function getNumberOfResignedBoys(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'M']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_RESIGNED)
        ]));
    }


    /**
     * Nombre de filles admises d'une classe
     */
    public function getNumberOfPassedGirls(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'F']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_PASSED)
        ]));
    }

     /**
     * Nombre de filles redoublantes d'une classe
     */
    public function getNumberOfRepeatedGirls(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'F']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_REAPETED)
        ]));
    }

     /**
     * Nombre de filles exclues d'une classe
     */
    public function getNumberOfExpelledGirls(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'F']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_EXPELLED)
        ]));
    }

    /**
     * Nombre de filles démissionnaires d'une classe
     */
    public function getNumberOfResignedGirls(Classroom $classroom)
    {
        return count($this->studentRepository->findBy([
            'classroom' => $classroom,
            'sex' => $this->sexRepository->findOneBy(['sex' => 'F']),
            'decision' => $this->decisionRepository->findOneByDecision(ConstantsClass::DECISION_RESIGNED)
        ]));
    }

    /**
     * Calucle l'age d'un élève
     */
    public function getStudentAge(Student $student): int
    {
        $birthdate = $student->getBirthday();
        $now = new \DateTime('now');

        $age = (int)date_parse($now->format('Y-m-d'))['year'] - (int)date_parse($birthdate->format('Y-m-d'))['year'];

        if($age < 10)
        {
            return 1;

        }elseif($age > 20)
        {
            return -1;
        }

        return $age;
    }

    /**
     * Nombre de cours programmés dans une classe
     */
    public function getNumberOfLessons(Classroom $classroom)
    {
        return count($classroom->getLessons());
    }

    /**
     * Recupère le nombre total des absences dans une classe pour un trimestre donné
     */
    public function getNumberOfAbsences(Term $term, Classroom $classroom, string $sex): int
    {
        $numberOfAbsences= 0;
        $absences = $this->absenceRepository->findAbsencesBySex($term, $classroom, $sex);

        foreach ($absences as $absence) 
        {
            $numberOfAbsences += $absence->getAbsence();
        }

        return $numberOfAbsences;

    }

    /**
     * 
     */
    public function doAt(PDF $pdf, School $school, int $cellHeaderHeight2) : PDF 
    {
        $pdf->Ln();
        $pdf->Ln();
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Times', 'B', 12);
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Fait à '.$school->getPlace().' le _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');

            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(124, $cellHeaderHeight2, utf8_decode('Le Proviseur'), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(124, $cellHeaderHeight2, utf8_decode('Le Direteur'), 0, 1, 'R');
                }
            }else
            {
                $pdf->Cell(124, $cellHeaderHeight2, utf8_decode('Le Principal'), 0, 1, 'R');
            }
        }else
        {
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Done at '.$school->getPlace().' on _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');
            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(120, $cellHeaderHeight2, utf8_decode('The Principal'), 0, 1, 'R');
                }else
                {
                    $pdf->Cell(120, $cellHeaderHeight2, utf8_decode('The Director'), 0, 1, 'R');
                }
            }else
            {
                $pdf->Cell(120, $cellHeaderHeight2, utf8_decode('The Principal'), 1, 1, 'R');
            }
        }
            
        return $pdf;
    }

    /**
     * 
     */
    public function doAtPagination(Pagination $pdf, School $school, int $cellHeaderHeight2) : Pagination 
    {
        $pdf->Ln();
        $pdf->Ln();
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Times', 'B', 12);
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Fait à '.$school->getPlace().' le _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');

            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(196, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
                    $pdf->Cell(124, $cellHeaderHeight2, utf8_decode('Le Proviseur'), 0, 1, 'L');
                }else
                {
                    $pdf->Cell(196, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
                    $pdf->Cell(124, $cellHeaderHeight2, utf8_decode('Le Directeur'), 0, 1, 'L');
                }
            }else
            {
                $pdf->Cell(196, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
                $pdf->Cell(124, $cellHeaderHeight2, utf8_decode('Le Principal'), 0, 1, 'L');
            }
        }else
        {
            $pdf->Cell(0, $cellHeaderHeight2, utf8_decode('Done at '.$school->getPlace().' on _ _ _ _ _ _ _ _ _ _ _ _ _ _ _'), 0, 1, 'R');
            if($school->isPublic())
            {
                if($school->isLycee())
                {
                    $pdf->Cell(196, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
                    $pdf->Cell(120, $cellHeaderHeight2, utf8_decode('The Principal'), 0, 1, 'L');
                }else
                {
                    $pdf->Cell(196, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
                    $pdf->Cell(120, $cellHeaderHeight2, utf8_decode('The Director'), 0, 1, 'L');
                }
            }else
            {
                $pdf->Cell(196, $cellHeaderHeight2, utf8_decode(''), 0, 0, 'R');
                $pdf->Cell(120, $cellHeaderHeight2, utf8_decode('The Principal'), 1, 1, 'L');
            }
        }
            
        return $pdf;
    }

    ////////////////////////
    
    public function getHeaderStudentList(Pagination $pdf, School $school, Classroom $classroom): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('ETAT DES PAIEMENTS DES ELEVES SOLVABLES'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, utf8_decode(""), 0, 0, 'L');
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Classe : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('PAYMENT STSUS OF SOLVENTS STUDENTS'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, utf8_decode(""), 0, 0, 'L');
            // $pdf->Cell(100, 5, utf8_decode($school->getFrenchName()), 0, 0, 'L');
            $pdf->Cell(90, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
            $pdf->Ln();
        }

        return $pdf;
    }

    public function getHeaderStudentListInsolvable(Pagination $pdf, School $school, Classroom $classroom): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('ETAT DES PAIEMENTS DES ELEVES INSOLVABLES'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, "", 0, 0, 'L');
            $pdf->Cell(90, 5, 'Classe : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode('STATUS OF PAYMENTOF INSOLVENTS STUDENTS'), 0, 2, 'C');
            $pdf->Ln(2);
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(100, 5, "", 0, 0, 'L');
            $pdf->Cell(90, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 0, 2, 'C');
        }
        $pdf->Ln();

        return $pdf;
    }

    public function getTableHeaderStudentList(Pagination $pdf, int $cellHeaderHeight, int $numberWith, int $fullNameWith, int $feesWith, int $totalWith, int $apeeFees, int $computerFees, int $medicalBookletFees, int $cleanSchoolFees, int $photoFees, int $stampFees, Classroom $classroom): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetX(30);
            $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith+10, $cellHeaderHeight*2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Cell($feesWith+10, $cellHeaderHeight, utf8_decode('APEE'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Informatique'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Livret Med.'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);

            $pdf->Cell($totalWith+5, $cellHeaderHeight*2,  utf8_decode('Montant à payer'), 1, 0, 'C', true);
            $pdf->Ln();

            $pdf->SetY($pdf->GetY()-$cellHeaderHeight);
            
            $pdf->SetX(30);
            $pdf->Cell($numberWith + $fullNameWith+10);
            $pdf->Cell($feesWith+10, $cellHeaderHeight/2, $apeeFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $computerFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $medicalBookletFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $cleanSchoolFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $photoFees.' F CFA', 'LBR', 0, 'C', true);
            
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 8);
            $pdf->SetX(30);
            $pdf->Cell($numberWith + $fullNameWith+10);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Avance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Reste'), 1, 0, 'C', true);
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetX(30);
            $pdf->Cell($numberWith, $cellHeaderHeight*2, 'No', 1, 0, 'C', true);
            $pdf->Cell($fullNameWith+10, $cellHeaderHeight*2, utf8_decode('First and last names'), 1, 0, 'C', true);
            $pdf->Cell($feesWith+10, $cellHeaderHeight, utf8_decode('PTA'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('IT'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Med. Book.'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Clean School'), 'LTR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight, utf8_decode('Photo'), 'LTR', 0, 'C', true);

            $pdf->Cell($totalWith+5, $cellHeaderHeight*2,  utf8_decode('Amount to be paid'), 1, 0, 'C', true);
            $pdf->Ln();

            $pdf->SetY($pdf->GetY()-$cellHeaderHeight);
            
            $pdf->SetX(30);
            $pdf->Cell($numberWith + $fullNameWith+10);
            $pdf->Cell($feesWith+10, $cellHeaderHeight/2, $apeeFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $computerFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $medicalBookletFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $cleanSchoolFees.' F CFA', 'LBR', 0, 'C', true);
            $pdf->Cell($feesWith+2, $cellHeaderHeight/2, $photoFees.' F CFA', 'LBR', 0, 'C', true);
            
            $pdf->Ln();

            $pdf->SetFont('Times', 'B', 8);
            $pdf->SetX(30);
            $pdf->Cell($numberWith + $fullNameWith+10);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+10)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Advance'), 1, 0, 'C', true);
            $pdf->Cell(($feesWith+2)/2, $cellHeaderHeight/2, utf8_decode('Rest'), 1, 0, 'C', true);
        }

        $pdf->Ln();
        $pdf->SetFont('Times', '', 10);
        return $pdf;
    }


    public function getHeaderStudentSheet(PDF $pdf, SchoolYear $schoolYear): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Rect(57, 45, 100, 15,'DF');
            $pdf->Cell(0, 5, utf8_decode('SCOLARITE'), 0, 2, 'C');
            $pdf->Cell(0, 5, utf8_decode("DOSSIER DE L'ELEVE"), 0, 2, 'C');
            $pdf->Ln(-1);
            $pdf->SetFont('Arial', 'BI', 8);
            $pdf->Cell(190, 5, utf8_decode("Année Scolaire : ".$schoolYear->getSchoolYear()), 0, 0, 'C');
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Rect(57, 45, 100, 15,'DF');
            $pdf->Cell(0, 5, utf8_decode('SCHOOLING'), 0, 2, 'C');
            $pdf->Cell(0, 5, utf8_decode("STUDENT'S FOLDER"), 0, 2, 'C');
            $pdf->Ln(-1);
            $pdf->SetFont('Arial', 'BI', 8);
            $pdf->Cell(190, 5, utf8_decode("School Year : ".$schoolYear->getSchoolYear()), 0, 0, 'C');
            $pdf->Ln();
        }

        return $pdf;
    }

}
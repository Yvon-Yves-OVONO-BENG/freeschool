<?php

namespace App\Service;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Entity\Teacher;
use App\Repository\DutyRepository;
use App\Repository\TermRepository;
use App\Repository\StudentRepository;
use App\Repository\TeacherRepository;
use App\Repository\SequenceRepository;
use App\Repository\EvaluationRepository;
use App\Repository\HistoriqueTeacherRepository;
use DateTime;
use Symfony\Component\HttpFoundation\RequestStack;

class HistoriqueAbsenceTeacherService
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
        protected HistoriqueTeacherRepository $historiqueTeacherRepository,
        )
    {}

    /**
     * fiche d'assiduité
     *
     * @param School $school
     * @param SchoolYear $schoolYear
     * @param Teacher $teacher
     * @param array $historiques
     * @param integer $printAll
     * @return PDF
     */
    public function printHistoricAttendance(School $school, SchoolYear $schoolYear, Teacher $teacher = null, array $historiques = [], int $periode = 0, DateTime $dateDebut = null, DateTime $dateFin = null): PDF
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new PDF();

        if ($teacher) 
        {
            $attendances = $this->historiqueTeacherRepository->findBy([
                'teacher' => $teacher
            ]);

            $mySession =  $this->request->getSession();

            if($mySession)
            {
                $subSystem = $mySession->get('subSystem');
            }

            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
            
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
            $pdf->Ln(5);
            // Entête de la liste
            $pdf = $this->getHeaderAttendance($pdf, $teacher);

            // entête du tableau
            $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2);

            // contenu du tableau
            $pdf->SetFont('Times', '', $fontSize);
            $numero = 0;

            $pdf = $this->contenuTableau($pdf, $numero, $cellBodyHeight2, $fontSize, $attendances);

        } 

        if($historiques && $periode == 0)
        {
            foreach ($historiques as $historique) 
            {
                $teacher = $this->teacherRepository->find($historique['id']);
                
                $attendances = $this->historiqueTeacherRepository->findBy([
                    'teacher' => $teacher
                ]);

                $mySession =  $this->request->getSession();

                if($mySession)
                {
                    $subSystem = $mySession->get('subSystem');
                }

                // On insère une page
                $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                
                // Administrative Header
                $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                
                $pdf->Ln(5);
                // Entête de la liste
                $pdf = $this->getHeaderAttendance($pdf, $teacher);

                // entête du tableau
                $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2);

                // contenu du tableau
                $pdf->SetFont('Times', '', $fontSize);
                $numero = 0;

                $pdf = $this->contenuTableau($pdf, $numero, $cellBodyHeight2, $fontSize, $attendances);


            }
        }

        if (count($historiques) > 0) 
        {
            if($historiques && $periode == 1)
            {
                foreach ($historiques as $historique) 
                {
                    $teacher = $this->teacherRepository->find($historique['id']);
                    
                    $attendances = $this->historiqueTeacherRepository->findBy([
                        'teacher' => $teacher
                    ]);

                    $mySession =  $this->request->getSession();

                    if($mySession)
                    {
                        $subSystem = $mySession->get('subSystem');
                    }

                    // On insère une page
                    $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                    
                    // Administrative Header
                    $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
                    
                    $pdf->Ln(5);
                    
                    $pdf->SetFont('Times', 'B', $fontSize-1);
                    $pdf->Cell(180, $cellBodyHeight2, utf8_decode("ASSIDUITE DE LA PERIODE DU / ATTENDANCE FOR THE ".date_format($dateDebut, 'd-m-Y')." AU ".date_format($dateFin, 'd-m-Y')), 1, 1, 'C', true);
                    $pdf->Ln(5);

                    // Entête de la liste
                    $pdf = $this->getHeaderAttendance($pdf, $teacher);

                    // entête du tableau
                    $pdf = $this->getTableHeaderStudentList($pdf, $cellHeaderHeight2);

                    // contenu du tableau
                    $pdf->SetFont('Times', '', $fontSize);
                    $numero = 0;

                    $pdf = $this->contenuTableau($pdf, $numero, $cellBodyHeight2, $fontSize, $attendances);


                }
            }
        }
        elseif(count($historiques) == 0)
        {
            // On insère une page
            $pdf = $this->generalService->newPage($pdf, 'P', 15, $fontSize-3);
                    
            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
            $pdf->Ln(5);
            
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(0, $cellHeaderHeight2, "PAS D'ASSIDUITE", 0, 2, 'C', true);
            $pdf->SetFont('Times', 'BI', 10);
            $pdf->Cell(0, $cellHeaderHeight2, "NO ATTENDANCE", 0, 2, 'C', true);
        }
        
        
        $pdf = $this->generalService->doAt($pdf, $school, $cellHeaderHeight2);
        
        return $pdf;
        
    }

    /////CONTENU DU TABLEAU
    public function contenuTableau(PDF $pdf, int $numero, int $cellBodyHeight2, int $fontSize,  array $attendances): PDF
    {
        $nombreHeureSeq1 = 0;
        $nombreHeureSeq2 = 0;
        $nombreHeureSeq3 = 0;
        $nombreHeureSeq4 = 0;
        $nombreHeureSeq5 = 0;
        $nombreHeureSeq6 = 0;

        ///
        $historicSeq1 = [];
        $historicSeq2 = [];
        $historicSeq3 = [];
        $historicSeq4 = [];
        $historicSeq5 = [];
        $historicSeq6 = [];
        

        foreach($attendances as $attendance)
        {
            switch ($attendance->getSequence()->getSequence()) 
            {
                case 1:
                    $nombreHeureSeq1 = $nombreHeureSeq1 + $attendance->getNombreHeure();
                    $historicSeq1[] = $attendance;
                    break;
                case 2:
                    $nombreHeureSeq2 = $nombreHeureSeq2 + $attendance->getNombreHeure();
                    $historicSeq2[] = $attendance;
                    break;

                case 3:
                    $nombreHeureSeq3 += $attendance->getNombreHeure();
                    $historicSeq3[] = $attendance;
                    break;

                case 4:
                    $nombreHeureSeq4 += $attendance->getNombreHeure();
                    $historicSeq4[] = $attendance;
                    break;

                case 5:
                    $nombreHeureSeq5 += $attendance->getNombreHeure();
                    $historicSeq5[] = $attendance;
                    break;

                case 6:
                    $nombreHeureSeq6 += $attendance->getNombreHeure();
                    $historicSeq6[] = $attendance;
                    break;
            }
        }

        //sequence 1
        if (count($historicSeq1) > 0) 
        {
            foreach ($historicSeq1 as $attendance) 
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
                $pdf->Cell(25, $cellBodyHeight2, utf8_decode($attendance->getClassroom()->getClassroom()), 1, 0, 'C', true);

                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getDay()->getDay())), 1, 0, 'C', true);
                $pdf->Cell(20, $cellBodyHeight2, (utf8_decode($attendance->getSequence()->getSequence())), 1, 0, 'C', true);
                $pdf->Cell(35, $cellBodyHeight2, (utf8_decode($attendance->getHeureDebut()." - ".$attendance->getHeureFin())), 1, 0, 'C', true);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getNombreHeure())), 1, 0, 'C', true);
                $pdf->Cell(40, $cellBodyHeight2, (utf8_decode($attendance->getSubject()->getSubject())), 1, 1, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
            }

            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(115, $cellBodyHeight2, utf8_decode('Total séquence 1'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellBodyHeight2, utf8_decode($nombreHeureSeq1.' Heure / hours'), 1, 1, 'C', true);
            $pdf->SetFont('Times', '', $fontSize-1);
        }
        

        //sequence 2
        if (count($historicSeq2) > 0) 
        {
            foreach ($historicSeq2 as $attendance) 
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
                $pdf->Cell(25, $cellBodyHeight2, utf8_decode($attendance->getClassroom()->getClassroom()), 1, 0, 'C', true);

                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getDay()->getDay())), 1, 0, 'C', true);
                $pdf->Cell(20, $cellBodyHeight2, (utf8_decode($attendance->getSequence()->getSequence())), 1, 0, 'C', true);
                $pdf->Cell(35, $cellBodyHeight2, (utf8_decode($attendance->getHeureDebut()." - ".$attendance->getHeureFin())), 1, 0, 'C', true);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getNombreHeure())), 1, 0, 'C', true);
                $pdf->Cell(40, $cellBodyHeight2, (utf8_decode($attendance->getSubject()->getSubject())), 1, 1, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
            }

            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(115, $cellBodyHeight2, utf8_decode('Total séquence 2'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellBodyHeight2, utf8_decode($nombreHeureSeq2.' Heure / hours'), 1, 1, 'C', true);
             $pdf->SetFont('Times', '', $fontSize-1);
        }
        

        //sequence 3
        if (count($historicSeq3) > 0) 
        {
            foreach ($historicSeq3 as $attendance) 
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
                $pdf->Cell(25, $cellBodyHeight2, utf8_decode($attendance->getClassroom()->getClassroom()), 1, 0, 'C', true);

                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getDay()->getDay())), 1, 0, 'C', true);
                $pdf->Cell(20, $cellBodyHeight2, (utf8_decode($attendance->getSequence()->getSequence())), 1, 0, 'C', true);
                $pdf->Cell(35, $cellBodyHeight2, (utf8_decode($attendance->getHeureDebut()." - ".$attendance->getHeureFin())), 1, 0, 'C', true);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getNombreHeure())), 1, 0, 'C', true);
                $pdf->Cell(40, $cellBodyHeight2, (utf8_decode($attendance->getSubject()->getSubject())), 1, 1, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
            }

            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(115, $cellBodyHeight2, utf8_decode('Total séquence 3'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellBodyHeight2, utf8_decode($nombreHeureSeq3.' Heure / hours'), 1, 1, 'C', true);
            $pdf->SetFont('Times', '', $fontSize-1);
        }
        

        //sequence 4
        if (count($historicSeq4) > 0) 
        {
            foreach ($historicSeq4 as $attendance) 
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
                $pdf->Cell(25, $cellBodyHeight2, utf8_decode($attendance->getClassroom()->getClassroom()), 1, 0, 'C', true);

                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getDay()->getDay())), 1, 0, 'C', true);
                $pdf->Cell(20, $cellBodyHeight2, (utf8_decode($attendance->getSequence()->getSequence())), 1, 0, 'C', true);
                $pdf->Cell(35, $cellBodyHeight2, (utf8_decode($attendance->getHeureDebut()." - ".$attendance->getHeureFin())), 1, 0, 'C', true);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getNombreHeure())), 1, 0, 'C', true);
                $pdf->Cell(40, $cellBodyHeight2, (utf8_decode($attendance->getSubject()->getSubject())), 1, 1, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
            }

            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(115, $cellBodyHeight2, utf8_decode('Total séquence 4'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellBodyHeight2, utf8_decode($nombreHeureSeq4.' Heure / hours'), 1, 1, 'C', true);
            $pdf->SetFont('Times', '', $fontSize-1);

        }
        
        //sequence 5
        if (count($historicSeq5) > 0) 
        {
            foreach ($historicSeq5 as $attendance) 
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
                $pdf->Cell(25, $cellBodyHeight2, utf8_decode($attendance->getClassroom()->getClassroom()), 1, 0, 'C', true);

                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getDay()->getDay())), 1, 0, 'C', true);
                $pdf->Cell(20, $cellBodyHeight2, (utf8_decode($attendance->getSequence()->getSequence())), 1, 0, 'C', true);
                $pdf->Cell(35, $cellBodyHeight2, (utf8_decode($attendance->getHeureDebut()." - ".$attendance->getHeureFin())), 1, 0, 'C', true);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getNombreHeure())), 1, 0, 'C', true);
                $pdf->Cell(40, $cellBodyHeight2, (utf8_decode($attendance->getSubject()->getSubject())), 1, 1, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
            }

            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(115, $cellBodyHeight2, utf8_decode('Total séquence 5'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellBodyHeight2, utf8_decode($nombreHeureSeq5.' Heure / hours'), 1, 1, 'C', true);
            $pdf->SetFont('Times', '', $fontSize-1);
        }
        
        //sequence 6
        if (count($historicSeq6) > 0) 
        {
            foreach ($historicSeq6 as $attendance) 
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
                $pdf->Cell(25, $cellBodyHeight2, utf8_decode($attendance->getClassroom()->getClassroom()), 1, 0, 'C', true);

                $pdf->SetFont('Times', '', $fontSize-2);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getDay()->getDay())), 1, 0, 'C', true);
                $pdf->Cell(20, $cellBodyHeight2, (utf8_decode($attendance->getSequence()->getSequence())), 1, 0, 'C', true);
                $pdf->Cell(35, $cellBodyHeight2, (utf8_decode($attendance->getHeureDebut()." - ".$attendance->getHeureFin())), 1, 0, 'C', true);
                $pdf->Cell(25, $cellBodyHeight2, (utf8_decode($attendance->getNombreHeure())), 1, 0, 'C', true);
                $pdf->Cell(40, $cellBodyHeight2, (utf8_decode($attendance->getSubject()->getSubject())), 1, 1, 'C', true);
                $pdf->SetFont('Times', '', $fontSize-1);
            }

            $pdf->SetFont('Times', 'B', 10);
            $pdf->SetFillColor(200,200,200);
            $pdf->Cell(115, $cellBodyHeight2, utf8_decode('Total séquence 6'), 1, 0, 'C', true);
            $pdf->Cell(65, $cellBodyHeight2, utf8_decode($nombreHeureSeq6.' Heure / hours'), 1, 1, 'C', true);
            
        }
        
        ////////TOTAL
        $pdf->SetFont('Times', 'B', 10);
        $pdf->SetFillColor(200,200,200);
        $pdf->Cell(115, $cellBodyHeight2, utf8_decode('TOTAL'), 1, 0, 'C', true);
        $pdf->Cell(65, $cellBodyHeight2, utf8_decode(
            $nombreHeureSeq1 + $nombreHeureSeq2 + 
            $nombreHeureSeq3 + $nombreHeureSeq4 + 
            $nombreHeureSeq5 + $nombreHeureSeq6.' Heure / hours'), 1, 1, 'C', true);

        
        return $pdf;
    }


    /**
     * entête de la fiche
     *
     * @param PDF $pdf
     * @param Teacher $teacher
     * @return PDF
     */
    public function getHeaderAttendance(PDF $pdf, Teacher $teacher): PDF
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        $pdf->SetFont('Times', 'B', 14);
        
        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->Cell(90, 5, "FICHE D'ASSIDUITE DE ", 0, 0, 'R');
        } else 
        {
            $pdf->Cell(90, 5, "SHEET ATTENDANCE OF ", 0, 0, 'C');
        }

        $pdf->Cell(0, 5, utf8_decode($teacher->getFullName()), 0, 1, 'L');

        $pdf->SetFont('Times', 'B', 10);
        if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE) 
        {
            $pdf->Cell(90, 5, "GRADE / CONTACT : ", 0, 0, 'R');
        } else 
        {
            $pdf->Cell(90, 5, "RANK / CONTACT : ", 0, 0, 'C');
        }
        $pdf->Cell(0, 5, utf8_decode(($teacher->getGrade() ? $teacher->getGrade()->getGrade() : "" )." / ".($teacher->getPhoneNumber() ? $teacher->getPhoneNumber() : "")), 0, 2, 'L');

        $pdf->Ln(2);
        
        $pdf->Cell(35, 5, '', 0, 0, 'C');
        $pdf->Ln();

        $pdf->SetFont('Times', 'B', 12);
        return $pdf;
    }


    /**
     * Entête du tableau de la fiche d'assiduité
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
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Classe'), 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Jour'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Séquence'), 1, 0, 'C', true);
            $pdf->Cell(35, $cellHeaderHeight2, utf8_decode('Plage'), 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Nbre Heure'), 1, 0, 'C', true);
            $pdf->Cell(40, $cellHeaderHeight2, utf8_decode('Matière'), 1, 0, 'C', true);
            $pdf->Ln();
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(10, $cellHeaderHeight2, 'No', 1, 0, 'C', true); 
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Classroom'), 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Day'), 1, 0, 'C', true);
            $pdf->Cell(20, $cellHeaderHeight2, utf8_decode('Sequence'), 1, 0, 'C', true);
            $pdf->Cell(35, $cellHeaderHeight2, utf8_decode('Plage'), 1, 0, 'C', true);
            $pdf->Cell(25, $cellHeaderHeight2, utf8_decode('Numb. Hours'), 1, 0, 'C', true);
            $pdf->Cell(40, $cellHeaderHeight2, utf8_decode('Subject'), 1, 0, 'C', true);
            $pdf->Ln();
        }

        return $pdf;
    }


    
}
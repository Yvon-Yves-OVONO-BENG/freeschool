<?php

namespace App\Service;

use App\Entity\School;
use App\Entity\Student;
use App\Entity\SchoolYear;
use App\Entity\EtatDepense;
use App\Entity\EtatFinance;
use App\Entity\ConstantsClass;
use App\Service\GeneralService;
use App\Entity\ReportElements\PDF;
use App\Repository\FeesRepository;
use App\Repository\RegistrationRepository;
use Symfony\Component\HttpFoundation\RequestStack;

class PrintFicheEleveService 
{
    public function __construct(
        protected RequestStack $request,
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
     * @return PDF
     */
    public function print(Student $student, School $school, SchoolYear $schoolYear): PDF
    {
        $fontSize = 9;
        $cellHeaderHeight1 = 3;

        $pdf = new PDF();

        // On insère une page
        $pdf = $this->generalService->newPage($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeader($school, $pdf, $cellHeaderHeight1, $fontSize, $schoolYear);

        $pdf = $this->generalService->getHeaderStudentSheet($pdf, $schoolYear);

        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }

        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            ///IDENTIFICATION
            $pdf->Cell(90, 5, utf8_decode("I. IDENTIFICATION"), 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(70, 5, utf8_decode("Numéro WHATSAPP COURS DIGITALISES :"), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode($student->getNumeroWhatsapp() ? $student->getNumeroWhatsapp() : "_ _ _  _ _  _ _  _ _"), 0, 1, 'L');

            
            $pdf->Cell(0, 5, utf8_decode("NOM(S) ET PRÉNOM(S) : ".$student->getFullName()), 0, 1, 'L');
            $pdf->Cell(80, 5, utf8_decode("CLASSE : ".$student->getClassroom()->getClassroom()), 0, 0, 'L');
        
            $pdf->Cell(60, 5, utf8_decode('REDOUBLANT : '), 0, 0, 'L');
            $pdf->Cell(40, 5, utf8_decode('NON REDOUBLANT : '), 0, 1, 'L');


            /////REDOUBLANT
            if ($student->getRepeater()->getRepeater() == ConstantsClass::REPEATER_YES) 
            {
                $pdf->Cell(25, 5, $pdf->Image('build/custom/images/checked.png', 117, 73.5, 5, 5) , 0, 1, 'C', 0);
                $pdf->Cell(25, 5, $pdf->Image('build/custom/images/unchecked.png', 184, 73.5, 5, 5) , 0, 1, 'C', 0);
                
            }else
            {
                $pdf->Cell(25, 5, $pdf->Image('build/custom/images/unchecked.png', 117, 73.5, 5, 5) , 0, 1, 'C', 0);
                $pdf->Cell(25, 5, $pdf->Image('build/custom/images/checked.png', 184, 73.5, 5, 5) , 0, 1, 'C', 0);
                
            }
            
            $pdf->Ln(-10);
            $pdf->Cell(0, 5, utf8_decode("DATE ET LIEU DE NAISSANCE : ". date_format($student->getBirthday(), "d-m-Y")." à ".utf8_decode($student->getBirthplace())), 0, 1, 'L');

            $pdf->Cell(20, 5, utf8_decode('SEXE : '), 0, 0, 'L');
            $pdf->Cell(40, 5, utf8_decode('MASCULIN : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('FEMININ : '), 0, 0, 'L');

            ////SEXE
            if ($student->getSex()->getSex() == ConstantsClass::SEX_M) 
            {
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 51, 83.5, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 87, 83.5, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 51, 83.5, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 87, 83.5, 5, 5) , 0, 0, 'C', 0);
                
            }

            $pdf->Cell(60, 5, utf8_decode("PAYS D'ORIGINE : ".($student->getCountry() ? $student->getCountry()->getCountry() : "")), 0, 1, 'L');

            //////AUTOCHTONE
            $pdf->Cell(35, 5, utf8_decode('AUTOCHTONE ? : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('OUI : '), 0, 0, 'L');
            $pdf->Cell(15, 5, utf8_decode('NON : '), 0, 0, 'L');

            if ($student->isAutochtone()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 51, 89, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 87, 89, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 55, 89, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 76, 89, 5, 5) , 0, 0, 'C', 0);
                
            }

            //////DEPLACEE
            $pdf->Cell(70, 5, utf8_decode('DEPLACE DU NORD-OUEST / SUD-OUEST : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('OUI : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('NON : '), 0, 0, 'L');

            if ($student->getMovement()) 
            {
                if ($student->getMovement()->getMovement() == ConstantsClass::MOVEMENT_NOSO) 
                {
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 173, 88, 5, 5) , 0, 1, 'C', 0);
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 192, 88, 5, 5) , 0, 1, 'C', 0);
                    
                }else
                {
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 173, 88, 5, 5) , 0, 1, 'C', 0);
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 192, 88, 5, 5) , 0, 1, 'C', 0);
                    
                }
            }else
            {
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 173, 88, 5, 5) , 0, 1, 'C', 0);
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 192, 88, 5, 5) , 0, 1, 'C', 0);
            }
                

            //////HANDICAPEE
            $pdf->Ln(-5);
            $pdf->Cell(30, 5, utf8_decode('HANDICAPE(E) ? : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('OUI : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('NON : '), 0, 0, 'L');

            if ($student->getHandicap()) 
            {
                if ($student->getHandicap()->getHandicap() == ConstantsClass::HANDICAPED_YES) 
                {
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 94, 5, 5) , 0, 0, 'C', 0);
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 72, 94, 5, 5) , 0, 0, 'C', 0);

                    $pdf->Cell(30, 5, utf8_decode('SI OUI, TYPE HANDICAPE : '.$student->getHandicap()->getHandicap()), 0, 0, 'L');
                
                    
                }else
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 94, 5, 5) , 0, 0, 'C', 0);
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 94, 5, 5) , 0, 0, 'C', 0);

                    $pdf->Cell(30, 5, utf8_decode('SI OUI, TYPE HANDICAPE : _ _ _ _ _ _ R.A.S _ _ _ _ _ _  '), 0, 1, 'L');
                
                    
                }
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 94, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 94, 5, 5) , 0, 0, 'C', 0);

                $pdf->Cell(30, 5, utf8_decode('SI OUI, TYPE HANDICAPE : _ _ _ _ _ _ R.A.S _ _ _ _ _ _ _ _ '), 0, 1, 'L');
            }
            

            //////REFUGIE
            $pdf->Ln(1);
            $pdf->Cell(30, 5, utf8_decode('REFUGIE(E) ? : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('OUI : '), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode('NON : '), 0, 0, 'L');

            if ($student->getMovement()) 
            {
                if ($student->getMovement()->getMovement() == ConstantsClass::MOVEMENT_REFFUGIE) 
                {
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 100, 5, 5) , 0, 0, 'C', 0);
                    $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 72, 100, 5, 5) , 0, 0, 'C', 0);

                    $pdf->Cell(30, 5, utf8_decode('SI OUI, NUMERO HCR : '.$student->getNumeroHcr()), 0, 1, 'L');
                
                    
                }else
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 100, 5, 5) , 0, 0, 'C', 0);
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 100, 5, 5) , 0, 0, 'C', 0);

                    $pdf->Cell(30, 5, utf8_decode('SI OUI, NUMERO HCR : _ _ _ _ _ _ R.A.S _ _ _ _ _ _  '), 0, 1, 'L');
                
                    
                }
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 100, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 100, 5, 5) , 0, 0, 'C', 0);

                $pdf->Cell(30, 5, utf8_decode('SI OUI, NUMERO HCR : _ _ _ _ _ _ _ _ R.A.S _ _ _ _ _ _ _ _  '), 0, 1, 'L');
            }
            

            ////PERE
            $pdf->Ln(1);
            $pdf->Cell(30, 5, utf8_decode("NOM(S) DU PÈRE : "), 0, 0, 'L');
            $pdf->Cell(145, 5, utf8_decode($student->getFatherName() ? $student->getFatherName() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

            $pdf->Cell(25, 5, utf8_decode("PROFESSION : "), 0, 0, 'L');
            $pdf->Cell(80, 5, utf8_decode($student->getProfessionPere() ? $student->getProfessionPere() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 0, 'L');

            $pdf->Cell(43, 5, utf8_decode("ADRESSE DU PÈRE (TEL)  : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode($student->getTelephonePere() ? $student->getTelephonePere() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

            ////MERE
            $pdf->Ln(1);
            $pdf->Cell(35, 5, utf8_decode("NOM(S) DE LA MÈRE : "), 0, 0, 'L');
            $pdf->Cell(145, 5, utf8_decode($student->getMotherName() ? $student->getMotherName() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 1, 'L');

            $pdf->Cell(25, 5, utf8_decode("PROFESSION : "), 0, 0, 'L');
            $pdf->Cell(80, 5, utf8_decode($student->getProfessionMere() ? $student->getProfessionmere() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 0, 'L');

            $pdf->Cell(43, 5, utf8_decode("ADRESSE DU MÈRE (TEL)  : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode($student->getTelephoneMere() ? $student->getTelephoneMere() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

            ////TUTEUR
            $pdf->Ln(1);
            $pdf->Cell(35, 5, utf8_decode("NOM(S) DU TUTEUR  : "), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode($student->getTuteur() ? $student->getTuteur() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

            $pdf->Cell(48, 5, utf8_decode("ADRESSE DU TUTEUR (TEL)  : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode($student->getTelephoneTuteur() ? $student->getTelephoneTuteur() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 1, 'L');

            ////PERSONNE A CONTACTER EN CAS D'URGENCE
            $pdf->Ln(1);
            $pdf->Cell(79, 5, utf8_decode("PERSONNE A CONTACTER EN CAS D'URGENCE : "), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode($student->getPersonneAContacterEnCasUergence() ? $student->getPersonneAContacterEnCasUergence() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

            $pdf->Cell(10, 5, utf8_decode("/TEL : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode($student->getTelephoneTuteur() ? $student->getTelephoneTuteur() : "_ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');


            ///////////////////SCOLARITE
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("II. SCOLARITE"), 0, 1, 'L');

            //////DATE ENTREE AU LYCEE
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(90, 5, utf8_decode("DATE DE LA PREMERE INSCRIPTION AU : ".$school->getFrenchName()), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode($student->getDatePremiereEntreeEtablissementAt() ? date_format( $student->getDatePremiereEntreeEtablissementAt(), "d-m-Y") : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

            $pdf->Cell(18, 5, utf8_decode("/CLASSE : "), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode($student->getClasseEntree() ? $student->getClasseEntree()->getClassroom() : "_ _ _ _ _ "), 0, 1, 'L');

            //////ETABLISSEMENT AN DERNIER
            $pdf->Cell(90, 5, utf8_decode("ETABLISSEMENT FREQUENTE L'AN DERNIER : "), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode($student->getDatePremiereEntreeEtablissementAt() ? date_format( $student->getDatePremiereEntreeEtablissementAt(), "d-m-Y") : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

            $pdf->Cell(18, 5, utf8_decode("/OPERATEUR : "), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode($student->getOperateur() ? $student->getOperateur()->getOperateur() : "_ _ _ _ _ "), 0, 1, 'L');

            //////MODE ADMISSION
            $pdf->Cell(95, 5, utf8_decode("MODE D'ADMISSION AU ".$school->getFrenchName()." : "), 0, 1, 'L');
            $pdf->Cell(50, 5, utf8_decode("CONCOURS : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("PERMUTATION : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("TRANSFERT : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("RECRUTEMENT : "), 0, 1, 'L');


            ////////////////////LES CHECKBOX
            if ($student->getModeAdmission()) 
            {   
                if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_CONCOURS) 
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 33, 163, 5, 5) , 0, 0, 'C', 0);
                    
                }else
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 33, 163, 5, 5) , 0, 0, 'C', 0); 
                }

                if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_PERMUTATION) 
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 88, 163, 5, 5) , 0, 0, 'C', 0);
                    
                }else
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 88, 163, 5, 5) , 0, 0, 'C', 0); 
                }

                if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_TRANSFERT) 
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 134, 163, 5, 5) , 0, 0, 'C', 0);
                    
                }else
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 134, 163, 5, 5) , 0, 0, 'C', 0); 
                }

                if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_RECRUTEMENT) 
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 189, 163, 5, 5) , 0, 1, 'C', 0);
                    
                }else
                {
                    $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 189, 163, 5, 5) , 0, 1, 'C', 0); 
                }

                
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 33, 163, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 88, 163, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 134, 163, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 189, 163, 5, 5) , 0, 1, 'C', 0);
                
            }


            /////////////DOSSIER MEDICAL
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("III. DOSSIER MEDICAL : "), 0, 1, 'L');
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(40, 5, utf8_decode("ALLERGIE ? :  OUI :      "), 0, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode("NON : "), 0, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode("APTITUDE AU SPORT : APTE : "), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode("INAPTE : "), 0, 0, 'L');

            /////////////////LES CHECKBOX
            ////////////DREPANOCYTOSE
            if ($student->isDrepanocytose()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 42, 178, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 62, 178, 5, 5) , 0, 0, 'C', 0); 

                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 42, 178, 5, 5) , 0, 0, 'C', 0); 
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 62, 178, 5, 5) , 0, 0, 'C', 0); 
            }

            //////////////APTE
            if ($student->isApte()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 34, 183, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 74, 183, 5, 5) , 0, 1, 'C', 0); 

                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 160, 178, 5, 5) , 0, 0, 'C', 0); 
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 186, 178, 5, 5) , 0, 1, 'C', 0); 
            }

            
            
            //////////////////////////////MALADIE CRHONIQUES
            $pdf->Cell(80, 5, utf8_decode("MALADIE CHRONIQUE : ASTHME ? :  OUI :      "), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode("NON : "), 0, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode("TEST COVID-19 : POSITIF : "), 0, 0, 'L');
            $pdf->Cell(20, 5, utf8_decode("NEGATIF : "), 0, 0, 'L');

            /////////////////LES CHECKBOX
            ////////////ASTHME
            if ($student->isAsthme()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 78, 183, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 101, 183, 5, 5) , 0, 0, 'C', 0); 

                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 78, 183, 5, 5) , 0, 0, 'C', 0); 
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 101, 183, 5, 5) , 0, 0, 'C', 0); 
            }

            //////////////COVID
            if ($student->isCovid()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 154, 183, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 190, 183, 5, 5) , 0, 1, 'C', 0); 

                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 154, 183, 5, 5) , 0, 0, 'C', 0); 
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 190, 183, 5, 5) , 0, 1, 'C', 0); 
            }

            $pdf->Cell(0, 5, utf8_decode("AUTRES : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 1, 'L');

            //////////////////////////////ALLERGIES
            $pdf->Cell(40, 5, utf8_decode("ALLERGIE ? :  OUI :      "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("NON : "), 0, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode("SI OUI, A QUOI ? : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

            /////////////////LES CHECKBOX
            ////////////ALLERGIE
            if ($student->isAsthme()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 43, 183, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 63, 183, 5, 5) , 0, 1, 'C', 0); 

                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 43, 193, 5, 5) , 0, 0, 'C', 0); 
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 63, 193, 5, 5) , 0, 1, 'C', 0); 
            }

            ////////////////GROUPE SANGUIN ET RESHUS
            $pdf->Cell(90, 5, utf8_decode("GROUPE SANGUIN : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');
            $pdf->Cell(60, 5, utf8_decode("RESHUS : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');


            /////////////// IDENTIFICATION D’UNE CONNAISSANCE DANS L’ETABLISSEMENT
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("IV. CHOIX OBLIGATOIRE / ACTIVITES POST ET PERIS SCOLAIRE"), 0, 1, 'L');

            //////LES CLUBS
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(50, 5, utf8_decode("CLUB BILINGUE : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("CLUB MULTICULTUREL : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("CLUB SCIENTIFIQUE : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("CLUB JOURNAL : "), 0, 1, 'L');

            $pdf->Cell(50, 5, utf8_decode("CLUB ENVIRONNEMENT : "), 0, 0, 'L');
            $pdf->Cell(30, 5, utf8_decode("CLUB LV II : "), 0, 0, 'L');
            $pdf->Cell(70, 5, utf8_decode("CLUB SANTE ET CROIX ROUGE : "), 0, 0, 'L');
            $pdf->Cell(50, 5, utf8_decode("CLUB RETHORIQUE : "), 0, 1, 'L');
            $pdf->Cell(0, 5, utf8_decode("AUTRES : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');


            ////////////////////////LES CHECKBOX
            ////////////CLUB BILINGUE
            if ($student->isClubBilingue()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 40, 218, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 40, 218, 5, 5) , 0, 0, 'C', 0); 
            }

            ////////////CLUB MULTICULTUREL
            if ($student->isClubMulticulturel()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 101, 218, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 101, 218, 5, 5) , 0, 0, 'C', 0); 
            }

            ////////////CLUB SCIENTIFIQUE
            if ($student->isClubMulticulturel()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 146, 218, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 146, 218, 5, 5) , 0, 0, 'C', 0); 
            }

            ////////////CLUB JOURNAL
            if ($student->isClubJournal()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 190, 218, 5, 5) , 0, 1, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 190, 218, 5, 5) , 0, 1, 'C', 0); 
            }


            ////////////CLUB ENVIRONNEMENT
            if ($student->isClubEnvironnement()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 52, 223, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 52, 223, 5, 5) , 0, 0, 'C', 0); 
            }

            ////////////LV II
            if ($student->isClubLv2()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 181, 223, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 81, 223, 5, 5) , 0, 0, 'C', 0); 
            }

            ////////////CLUB SANTE ET CROIX ROUGE
            if ($student->isClubSante()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 146, 223, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 146, 223, 5, 5) , 0, 0, 'C', 0); 
            }

            ////////////CLUB RETHORIQUE
            if ($student->isClubRethorique()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 196, 223, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 196, 223, 5, 5) , 0, 0, 'C', 0); 
            }


            /////////////// IDENTIFICATION D’UNE CONNAISSANCE DANS L’ETABLISSEMENT
            $pdf->Ln();
            $pdf->SetFont('Arial', 'B', 12);
            $pdf->Cell(0, 5, utf8_decode("V. IDENTIFICATION D'UNE CONNAISSANCE DANS L'ETABLISSEMENT"), 0, 1, 'L');

            //////LES CLUBS
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(40, 5, utf8_decode("FRERE : "), 0, 0, 'L');
            $pdf->Cell(40, 5, utf8_decode("SOEUR : "), 0, 0, 'L');
            $pdf->Cell(40, 5, utf8_decode("ENSEIGNANT : "), 0, 0, 'L');
            $pdf->Cell(15, 5, utf8_decode("AUTRES : "), 0, 0, 'L');
            $pdf->Cell(40, 5, utf8_decode($student->getAutreConnaisanceEtablissement() ? $student->getAutreConnaisanceEtablissement() : " _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

            $pdf->Cell(35, 5, utf8_decode("NOMS ET PRENOMS : "), 0, 0, 'L');
            $pdf->Cell(100, 5, utf8_decode($student->getNomPersonneEtablissement() ? $student->getNomPersonneEtablissement() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

            $pdf->Cell(8, 5, utf8_decode("TEL : "), 0, 0, 'L');
            $pdf->Cell(30, 5, utf8_decode($student->getTelephonePersonneEtablissement() ? $student->getTelephonePersonneEtablissement(): " _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');
            $pdf->Ln();
            $pdf->Cell(0, 5, utf8_decode("DATE, SIGNATURE NOMS ET PRENOMS"), 0, 0, 'R');


            ///////////////////LES CHECKBOX
            ////////////FRERE
            if ($student->isFrere()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 25, 248, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 25, 248, 5, 5) , 0, 0, 'C', 0); 
            }


            ////////////SOEUR
            if ($student->isSoeur()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 66, 248, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 66, 248, 5, 5) , 0, 0, 'C', 0); 
            }


            ////////////ENSEIGNANT
            if ($student->isEnseignant()) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 115, 248, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 115, 248, 5, 5) , 0, 0, 'C', 0); 
            }
        }else
        {
            ///IDENTIFICATION
        $pdf->Cell(90, 5, utf8_decode("I. IDENTIFICATION"), 0, 0, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(70, 5, utf8_decode("WHATSAPP NUMBER DIGITAL LESSONS :"), 0, 0, 'L');
        $pdf->Cell(70, 5, utf8_decode($student->getNumeroWhatsapp() ? $student->getNumeroWhatsapp() : "_ _ _  _ _  _ _  _ _"), 0, 1, 'L');

        
        $pdf->Cell(0, 5, utf8_decode("FIRST AND LAST NAMES : ".$student->getFullName()), 0, 1, 'L');
        $pdf->Cell(80, 5, utf8_decode("CLASS : ".$student->getClassroom()->getClassroom()), 0, 0, 'L');
    
        $pdf->Cell(60, 5, utf8_decode('REPEATING : '), 0, 0, 'L');
        $pdf->Cell(40, 5, utf8_decode('NOT REPEATING : '), 0, 1, 'L');


        /////REDOUBLANT
        if ($student->getRepeater()->getRepeater() == ConstantsClass::REPEATER_YES) 
        {
            $pdf->Cell(25, 5, $pdf->Image('build/custom/images/checked.png', 117, 73.5, 5, 5) , 0, 1, 'C', 0);
            $pdf->Cell(25, 5, $pdf->Image('build/custom/images/unchecked.png', 184, 73.5, 5, 5) , 0, 1, 'C', 0);
               
        }else
        {
            $pdf->Cell(25, 5, $pdf->Image('build/custom/images/unchecked.png', 117, 73.5, 5, 5) , 0, 1, 'C', 0);
            $pdf->Cell(25, 5, $pdf->Image('build/custom/images/checked.png', 184, 73.5, 5, 5) , 0, 1, 'C', 0);
               
        }
        
        $pdf->Ln(-10);
        $pdf->Cell(0, 5, utf8_decode("DATE AND PLACE OF BIRTH: ". date_format($student->getBirthday(), "d-m-Y")." at ".utf8_decode($student->getBirthplace())), 0, 1, 'L');

        $pdf->Cell(20, 5, utf8_decode('SEX : '), 0, 0, 'L');
        $pdf->Cell(40, 5, utf8_decode('MALE : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('FEMALE : '), 0, 0, 'L');

        ////SEXE
        if ($student->getSex()->getSex() == ConstantsClass::SEX_M) 
        {
            $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 51, 83.5, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 87, 83.5, 5, 5) , 0, 0, 'C', 0);
               
        }else
        {
            $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 51, 83.5, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 87, 83.5, 5, 5) , 0, 0, 'C', 0);
               
        }

        $pdf->Cell(60, 5, utf8_decode("PAYS D'ORIGINE : ".$student->getCountry()->getCountry()), 0, 1, 'L');

        //////AUTOCHTONE
        $pdf->Cell(35, 5, utf8_decode('AUTOCHTONE ? : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('YES : '), 0, 0, 'L');
        $pdf->Cell(15, 5, utf8_decode('NO : '), 0, 0, 'L');

        if ($student->isAutochtone()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 51, 89, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 87, 89, 5, 5) , 0, 0, 'C', 0);
               
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 55, 89, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 76, 89, 5, 5) , 0, 0, 'C', 0);
               
        }

        //////DEPLACEE
        $pdf->Cell(70, 5, utf8_decode('MOVED FROM NORTH-WEST / SOUTH-WEST : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('  YES : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('NO : '), 0, 0, 'L');

        if ($student->getMovement()) 
        {
            if ($student->getMovement()->getMovement() == ConstantsClass::MOVEMENT_NOSO) 
            {
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 173, 88, 5, 5) , 0, 1, 'C', 0);
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 192, 88, 5, 5) , 0, 1, 'C', 0);
                
            }else
            {
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 173, 88, 5, 5) , 0, 1, 'C', 0);
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 192, 88, 5, 5) , 0, 1, 'C', 0);
                
            }
        }else
        {
            $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 173, 88, 5, 5) , 0, 1, 'C', 0);
            $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 192, 88, 5, 5) , 0, 1, 'C', 0);
        }
            

        //////HANDICAPEE
        $pdf->Ln(-5);
        $pdf->Cell(30, 5, utf8_decode('DISABLED ? : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('YES : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('NO : '), 0, 0, 'L');

        if ($student->getHandicap()) 
        {
            if ($student->getHandicap()->getHandicap() == ConstantsClass::HANDICAPED_YES) 
            {
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 94, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 72, 94, 5, 5) , 0, 0, 'C', 0);

                $pdf->Cell(30, 5, utf8_decode('IF YES, HANDICAPE TYPE : '.$student->getHandicap()->getHandicap()), 0, 0, 'L');
            
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 94, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 94, 5, 5) , 0, 0, 'C', 0);

                $pdf->Cell(30, 5, utf8_decode('IF YES, HANDICAPE TYPE  : _ _ _ _ _ _ R.A.S _ _ _ _ _ _  '), 0, 1, 'L');
            
                
            }
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 94, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 94, 5, 5) , 0, 0, 'C', 0);

            $pdf->Cell(30, 5, utf8_decode('IF YES, HANDICAPE TYPE  : _ _ _ _ _ _ R.A.S _ _ _ _ _ _ _ _ '), 0, 1, 'L');
        }
        

        //////REFUGIE
        $pdf->Ln(1);
        $pdf->Cell(30, 5, utf8_decode('REFUGEE ? : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('YES : '), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode('NO : '), 0, 0, 'L');

        if ($student->getMovement()) 
        {
            if ($student->getMovement()->getMovement() == ConstantsClass::MOVEMENT_REFFUGIE) 
            {
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 100, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(15, 5, $pdf->Image('build/custom/images/checked.png', 72, 100, 5, 5) , 0, 0, 'C', 0);

                $pdf->Cell(30, 5, utf8_decode("IF YES, HCR'S NUMBER : ".$student->getNumeroHcr()), 0, 1, 'L');
            
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 100, 5, 5) , 0, 0, 'C', 0);
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 100, 5, 5) , 0, 0, 'C', 0);

                $pdf->Cell(30, 5, utf8_decode("IF YES, HCR'S NUMBER  : _ _ _ _ _ _ R.A.S _ _ _ _ _ _  "), 0, 1, 'L');
            
                
            }
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 50, 100, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 72, 100, 5, 5) , 0, 0, 'C', 0);

            $pdf->Cell(30, 5, utf8_decode("IF YES, HCR'S NUMBER  : _ _ _ _ _ _ R.A.S _ _ _ _ _ _  "), 0, 1, 'L');
        }
        

        ////PERE
        $pdf->Ln(1);
        $pdf->Cell(40, 5, utf8_decode("NAME OF THE FATHER : "), 0, 0, 'L');
        $pdf->Cell(145, 5, utf8_decode($student->getFatherName() ? $student->getFatherName() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

        $pdf->Cell(25, 5, utf8_decode("OCCUPATION : "), 0, 0, 'L');
        $pdf->Cell(80, 5, utf8_decode($student->getProfessionPere() ? $student->getProfessionPere() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 0, 'L');

        $pdf->Cell(50, 5, utf8_decode("FATHER'S ADDRESS (PHONE)  : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode($student->getTelephonePere() ? $student->getTelephonePere() : "_ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

        ////MERE
        $pdf->Ln(1);
        $pdf->Cell(40, 5, utf8_decode("NAME OF THE MOTHER : "), 0, 0, 'L');
        $pdf->Cell(145, 5, utf8_decode($student->getMotherName() ? $student->getMotherName() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 1, 'L');

        $pdf->Cell(25, 5, utf8_decode("OCCUPATION : "), 0, 0, 'L');
        $pdf->Cell(80, 5, utf8_decode($student->getProfessionMere() ? $student->getProfessionmere() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 0, 'L');

        $pdf->Cell(50, 5, utf8_decode("MOTHER'S ADDRESS (PHONE)  : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode($student->getTelephoneMere() ? $student->getTelephoneMere() : " _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

        ////TUTEUR
        $pdf->Ln(1);
        $pdf->Cell(35, 5, utf8_decode("GUARDIAN NAME  : "), 0, 0, 'L');
        $pdf->Cell(70, 5, utf8_decode($student->getTuteur() ? $student->getTuteur() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

        $pdf->Cell(55, 5, utf8_decode("GUARDIAN'S ADDRESS (PHONE)  : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode($student->getTelephoneTuteur() ? $student->getTelephoneTuteur() : "_ _ _ _ _ _ _ _ _ _ _ _ "), 0, 1, 'L');

        ////PERSONNE A CONTACTER EN CAS D'URGENCE
        $pdf->Ln(1);
        $pdf->Cell(79, 5, utf8_decode("PERSON TO CONTACT IN CASE OF EMERGENCY : "), 0, 0, 'L');
        $pdf->Cell(65, 5, utf8_decode($student->getPersonneAContacterEnCasUergence() ? $student->getPersonneAContacterEnCasUergence() : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

        $pdf->Cell(15, 5, utf8_decode("/PHONE : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode($student->getTelephoneTuteur() ? $student->getTelephoneTuteur() : "_ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');


        ///////////////////SCOLARITE
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, utf8_decode("II. SCHOOLING"), 0, 1, 'L');

        //////DATE ENTREE AU LYCEE
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(90, 5, utf8_decode("DATE OF THE FIRST REGISTRATION : ".$school->getEnglishName()), 0, 0, 'L');
        $pdf->Cell(70, 5, utf8_decode($student->getDatePremiereEntreeEtablissementAt() ? date_format( $student->getDatePremiereEntreeEtablissementAt(), "d-m-Y") : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

        $pdf->Cell(18, 5, utf8_decode("/CLASS : "), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode($student->getClasseEntree() ? $student->getClasseEntree()->getClassroom() : "_ _ _ _ _ "), 0, 1, 'L');

        //////ETABLISSEMENT AN DERNIER
        $pdf->Cell(67, 5, utf8_decode("ESTABLISHMENT ATTENDED LAST YEAR : "), 0, 0, 'L');
        $pdf->Cell(70, 5, utf8_decode($student->getDatePremiereEntreeEtablissementAt() ? date_format( $student->getDatePremiereEntreeEtablissementAt(), "d-m-Y") : "_ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

        $pdf->Cell(25, 5, utf8_decode("/OPERATOR : "), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode($student->getOperateur() ? $student->getOperateur()->getOperateur() : "_ _ _ _ _ "), 0, 1, 'L');

        //////MODE ADMISSION
        $pdf->Cell(95, 5, utf8_decode("MODE OF ADMISSION OF ".$school->getFrenchName()." : "), 0, 1, 'L');
        $pdf->Cell(50, 5, utf8_decode("COMPETITION : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("PERMUTATION : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("TRANSFER : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("RECRUITMENT : "), 0, 1, 'L');


        ////////////////////LES CHECKBOX
        if ($student->getModeAdmission()) 
        {   
            if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_CONCOURS) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 40, 163, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 40, 163, 5, 5) , 0, 0, 'C', 0); 
            }

            if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_PERMUTATION) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 88, 163, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 88, 163, 5, 5) , 0, 0, 'C', 0); 
            }

            if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_TRANSFERT) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 134, 163, 5, 5) , 0, 0, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 134, 163, 5, 5) , 0, 0, 'C', 0); 
            }

            if ($student->getModeAdmission()->getModeAdmission() == ConstantsClass::MODE_ADMISSION_RECRUTEMENT) 
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 189, 163, 5, 5) , 0, 1, 'C', 0);
                
            }else
            {
                $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 189, 163, 5, 5) , 0, 1, 'C', 0); 
            }

            
               
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 40, 163, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 88, 163, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 134, 163, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 189, 163, 5, 5) , 0, 1, 'C', 0);
            
        }


        /////////////DOSSIER MEDICAL
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, utf8_decode("III. MEDICAL FOLDER : "), 0, 1, 'L');
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 5, utf8_decode("ALLERGY ? :  YES :      "), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode("NO : "), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode("FITNESS FOR SPORT : APT : "), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode("UNFIT : "), 0, 0, 'L');

        /////////////////LES CHECKBOX
        ////////////DREPANOCYTOSE
        if ($student->isDrepanocytose()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 42, 178, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 62, 178, 5, 5) , 0, 0, 'C', 0); 

            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 42, 178, 5, 5) , 0, 0, 'C', 0); 
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 62, 178, 5, 5) , 0, 0, 'C', 0); 
        }

        //////////////APTE
        if ($student->isApte()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 34, 183, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 74, 183, 5, 5) , 0, 1, 'C', 0); 

            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 160, 178, 5, 5) , 0, 0, 'C', 0); 
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 186, 178, 5, 5) , 0, 1, 'C', 0); 
        }

        
        
        //////////////////////////////MALADIE CRHONIQUES
        $pdf->Cell(80, 5, utf8_decode("CHRONIC DESEASE : ASTHMA ? :  YES :      "), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode("NO : "), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode("TEST COVID-19 : POSITIVE : "), 0, 0, 'L');
        $pdf->Cell(20, 5, utf8_decode("NEGATIVE : "), 0, 0, 'L');

        /////////////////LES CHECKBOX
        ////////////ASTHME
        if ($student->isAsthme()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 78, 183, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 101, 183, 5, 5) , 0, 0, 'C', 0); 

            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 78, 183, 5, 5) , 0, 0, 'C', 0); 
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 101, 183, 5, 5) , 0, 0, 'C', 0); 
        }

        //////////////COVID
        if ($student->isCovid()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 156, 183, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 190, 183, 5, 5) , 0, 1, 'C', 0); 

            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 156, 183, 5, 5) , 0, 0, 'C', 0); 
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 190, 183, 5, 5) , 0, 1, 'C', 0); 
        }

        $pdf->Cell(0, 5, utf8_decode("OTHERS : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ "), 0, 1, 'L');

        //////////////////////////////ALLERGIES
        $pdf->Cell(40, 5, utf8_decode("ALLERGY ? :  YES :      "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("NO : "), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode("IF YES, TO WHAT ? : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

        /////////////////LES CHECKBOX
        ////////////ALLERGIE
        if ($student->isAsthme()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 43, 183, 5, 5) , 0, 0, 'C', 0);
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 63, 183, 5, 5) , 0, 1, 'C', 0); 

            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 43, 193, 5, 5) , 0, 0, 'C', 0); 
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 63, 193, 5, 5) , 0, 1, 'C', 0); 
        }

        ////////////////GROUPE SANGUIN ET RESHUS
        $pdf->Cell(90, 5, utf8_decode("BLOOD GROUP : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');
        $pdf->Cell(60, 5, utf8_decode("RESHUS : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');


        /////////////// IDENTIFICATION D’UNE CONNAISSANCE DANS L’ETABLISSEMENT
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, utf8_decode("IV. MANDATORY CHOICE / AFTER-SCHOOL ACTIVITIES"), 0, 1, 'L');

        //////LES CLUBS
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(50, 5, utf8_decode("BILINGUAL CLUB : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("MULTICULTURAL CLUB : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("SCIENCE CLUB : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("NEWSPAPER CLUB : "), 0, 1, 'L');

        $pdf->Cell(50, 5, utf8_decode("ENVIRONMENT CLUB : "), 0, 0, 'L');
        $pdf->Cell(30, 5, utf8_decode("LV II CLUB : "), 0, 0, 'L');
        $pdf->Cell(70, 5, utf8_decode(" HEALTH CLUB AND RED CROSS : "), 0, 0, 'L');
        $pdf->Cell(50, 5, utf8_decode("RETHORICAL CLUB : "), 0, 1, 'L');
        $pdf->Cell(0, 5, utf8_decode("OTHERS : _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');


        ////////////////////////LES CHECKBOX
        ////////////CLUB BILINGUE
        if ($student->isClubBilingue()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 42, 218, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 42, 218, 5, 5) , 0, 0, 'C', 0); 
        }

        ////////////CLUB MULTICULTUREL
        if ($student->isClubMulticulturel()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 101, 218, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 101, 218, 5, 5) , 0, 0, 'C', 0); 
        }

        ////////////CLUB SCIENTIFIQUE
        if ($student->isClubMulticulturel()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 146, 218, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 146, 218, 5, 5) , 0, 0, 'C', 0); 
        }

        ////////////CLUB JOURNAL
        if ($student->isClubJournal()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 195, 218, 5, 5) , 0, 1, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 195, 218, 5, 5) , 0, 1, 'C', 0); 
        }


        ////////////CLUB ENVIRONNEMENT
        if ($student->isClubEnvironnement()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 52, 223, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 52, 223, 5, 5) , 0, 0, 'C', 0); 
        }

        ////////////LV II
        if ($student->isClubLv2()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 181, 223, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 81, 223, 5, 5) , 0, 0, 'C', 0); 
        }

        ////////////CLUB SANTE ET CROIX ROUGE
        if ($student->isClubSante()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 146, 223, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 146, 223, 5, 5) , 0, 0, 'C', 0); 
        }

        ////////////CLUB RETHORIQUE
        if ($student->isClubRethorique()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 196, 223, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 196, 223, 5, 5) , 0, 0, 'C', 0); 
        }


        /////////////// IDENTIFICATION D’UNE CONNAISSANCE DANS L’ETABLISSEMENT
        $pdf->Ln();
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(0, 5, utf8_decode("V. IDENTIFICATION OF AN ACQUAINTANCE IN THE ESTABLISHMENT"), 0, 1, 'L');

        //////LES CLUBS
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->Cell(40, 5, utf8_decode("BROTHER : "), 0, 0, 'L');
        $pdf->Cell(40, 5, utf8_decode("SISTER : "), 0, 0, 'L');
        $pdf->Cell(40, 5, utf8_decode("TEACHER : "), 0, 0, 'L');
        $pdf->Cell(15, 5, utf8_decode("OTHER : "), 0, 0, 'L');
        $pdf->Cell(40, 5, utf8_decode($student->getAutreConnaisanceEtablissement() ? $student->getAutreConnaisanceEtablissement() : " _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');

        $pdf->Cell(40, 5, utf8_decode("FIRST AND LAST NAME : "), 0, 0, 'L');
        $pdf->Cell(92, 5, utf8_decode($student->getNomPersonneEtablissement() ? $student->getNomPersonneEtablissement() : " _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 0, 'L');

        $pdf->Cell(15, 5, utf8_decode("PHONE : "), 0, 0, 'L');
        $pdf->Cell(30, 5, utf8_decode($student->getTelephonePersonneEtablissement() ? $student->getTelephonePersonneEtablissement(): " _ _ _ _ _ _ _ _ _ _ _ _ _ _ _ _"), 0, 1, 'L');
        $pdf->Ln();
        $pdf->Cell(0, 5, utf8_decode("DATE, SIGNATURE FIRST AND LAST NAME"), 0, 0, 'R');


        ///////////////////LES CHECKBOX
        ////////////FRERE
        if ($student->isFrere()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 30, 248, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 30, 248, 5, 5) , 0, 0, 'C', 0); 
        }


        ////////////SOEUR
        if ($student->isSoeur()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 66, 248, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 66, 248, 5, 5) , 0, 0, 'C', 0); 
        }


        ////////////ENSEIGNANT
        if ($student->isEnseignant()) 
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/checked.png', 115, 248, 5, 5) , 0, 0, 'C', 0);
            
        }else
        {
            $pdf->Cell(5, 5, $pdf->Image('build/custom/images/unchecked.png', 115, 248, 5, 5) , 0, 0, 'C', 0); 
        }
        }


        return $pdf;
    }


}
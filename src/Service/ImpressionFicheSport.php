<?php

namespace App\Service;
use Fpdf\Fpdf;
use App\Repository\SchoolRepository;

class ImpressionFicheSport extends FPDF
{

    public function __construct(protected SchoolRepository $etablissementRepository)
    {}

    public function impresionFiche(array $eleves, $classe): Fpdf
    {
        $pdf = new Fpdf();
        foreach ($eleves as $eleve) {
            $pdf->addPage('P');
            $pdf->SetLeftMargin(10);
            $pdf->SetX(10);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(35, 4, "MINESEC/IGE", 0, 0, 'C');
            $pdf->Cell(80, 4, "FICHE INDIVIDUELLE DES EPREUVES PRATIQUES D'E.P.S. AUX EXAMENS DECC", 0, 1, 'L');
            $pdf->SetFont('Arial', 'I', 9);
            $pdf->Cell(0, 4, "INDIVIDUAL INFORMATION ON THE S.P.E. DECC PRACTICAL EXAMINATION", 0, 1, 'C');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(95, 8, "SESSION ", 0, 0, 'R');
            $pdf->SetFont('Arial', 'BI', 9);
            $pdf->Cell(8, 8, "2024", 0, 1, 'C');

            if($eleve->getPhoto())
            {
                $pdf->Image('images/students/'.$eleve->getPhoto(), 25, 20, 30, 40);
            }else
            {
                if($eleve->getSex()->getSex() == 'F')
                {
                    $pdf->Image('images/students/fille.jpg', 25, 20, 30, 40);
                }
                else
                {
                    $pdf->Image('images/students/garcon.jpg', 15, 52, 25, 25);
                }
                
            }

            $pdf->SetX(65);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(10, 8, "Nom /", 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(12, 8, "Name :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(80, 8, strtoupper(utf8_decode($eleve->getFullName())), 0, 1, 'L');

            $pdf->SetX(65);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(15, 5,utf8_decode("Né(e) le /"), 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(15, 5, "Born on :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(20, 5, date_format($eleve->getBirthday(), 'd/m/Y'), 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(5, 5,utf8_decode("à /"), 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(5, 5, "at :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(80, 5, strtoupper(utf8_decode($eleve->getBirthplace())), 0, 1, 'L');

            $pdf->SetX(65);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(10, 5,utf8_decode("Sexe /"), 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(10, 5, "Sex :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(20, 5, $eleve->getSex()->getSex()=='F' ? utf8_decode("Féminin"):"Masculin", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(10, 5,utf8_decode("EPS /"), 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(10, 5, "SPE :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(10, 5, "Apte", 0, 1, 'L');

            $pdf->SetX(65);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(33, 5,utf8_decode("Sous-Centre d'Examen /"), 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(32, 5, "Accommodation Center :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(35, 5, "YAOUNDE LYCEE ODZA", 0, 1, 'L');

            $pdf->SetX(65);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(15, 5,"Examen /", 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(17, 5, "Examination:", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(20, 5, "BEPC", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(20, 5,utf8_decode("Série/Option /"), 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(20, 5, "Series/Trade :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            
            switch($eleve->getClassroom()->getId())
            {
                case 144 :
                $pdf->Cell(10, 5, "ALL", 0, 1, 'L');
                break;

                case 146 :
                $pdf->Cell(10, 5, "ESP", 0, 1, 'L');
                break;

                case 147 :
                $pdf->Cell(10, 5, "ESP", 0, 1, 'L');
                break;

                case 148 :
                $pdf->Cell(10, 5, "ITA", 0, 1, 'L');
                break;

                case 181 :
                $pdf->Cell(10, 5, "CHI", 0, 1, 'L');
                break;


            }
            

            $pdf->SetX(65);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(22, 5,"Etablissement /", 0, 0, 'L');
            $pdf->SetFont('Arial', 'I', 8);
            $pdf->Cell(12, 5, "School :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(35, 5, "LYCEE ODZA", 0, 1, 'L');

            $pdf->Image('logo/fond.png', 67, 60, 100, 40);

            $pdf->Ln(5);
            $pdf->SetX(10);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(0, 5,"CETTE FICHE EST GRATUITE", 0, 1, 'C');

            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 7);
            $pdf->Cell(57, 5,"Nom du responsable du stade et signature", 0, 0, 'L');
            $pdf->SetFont('Arial', 'IB', 9);
            $pdf->Cell(50, 5, "THIS FORM IS FREE OF CHARGE", 0, 1, 'L');
            $pdf->SetX(18);
            $pdf->SetFont('Arial', 'I', 7);
            $pdf->Cell(50, 3, "Name of the person in charge of the stadium and", 0, 1, 'L');
            $pdf->SetX(18);
            $pdf->Cell(50, 3, "signature", 0, 1, 'C');

            $pdf->Ln(2);

            $pdf->SetX(17);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(57, 4,"   -------------------------------------------------------", 'LTR', 0, 'L');
            $pdf->Cell(85, 4,"", 0, 0, 'L');
            $pdf->Cell(30, 4,utf8_decode("N° Anonymat"), 'LRT', 1, 'C');

            $pdf->SetX(17);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(57, 4,"   -------------------------------------------------------", 'LR', 0, 'L');
            $pdf->Cell(85, 4,"", 0, 0, 'L');
            $pdf->Cell(30, 4,utf8_decode(""), 'LR', 1, 'L');

            $pdf->SetX(17);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(57, 4,"", 'LRB', 0, 'L');
            $pdf->Cell(85, 4,"", 0, 0, 'L');
            $pdf->Cell(30, 4,"----------------------", 'LRB', 1, 'C');

            $pdf->Image('logo/ciseaux.png', 11, 91, 8, 8);

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(0, 2,"--------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------", 0, 1, 'C');

            $pdf->SetX(17);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(57, 4,"", 0, 0, 'L');
            $pdf->Cell(85, 4,"", 0, 0, 'L');
            $pdf->Cell(30, 4,utf8_decode("N° Anonymat"), 'LRT', 1, 'C');

            $pdf->SetX(17);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(20, 4,"Examen /", 0, 0, 'R');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(20, 4,"Examination :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(17, 4,"BEPC", 0, 0, 'L');
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(40, 4,"SESSION ", 0, 0, 'R');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(45, 4,"2024", 0, 0, 'L');
            $pdf->Cell(30, 4,utf8_decode(""), 'LR', 1, 'L');

            $pdf->SetX(24);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(20, 4,utf8_decode("Série/Option /"), 0, 0, 'R');
            $pdf->SetFont('Arial', '', 9);
            $pdf->Cell(23, 4,"Series/Trade :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);

            // $pdf->Cell(17, 4,$eleve->getClassroom()->getClassroom(), 0, 0, 'L');
            switch($eleve->getClassroom()->getId())
            {
                case 144 :
                $pdf->Cell(17, 4, "ALL", 0, 0, 'L');
                break;

                case 146 :
                $pdf->Cell(17, 4, "ESP", 0, 0, 'L');
                break;

                case 147 :
                $pdf->Cell(17, 4, "ESP", 0, 0, 'L');
                break;

                case 148 :
                $pdf->Cell(17, 4, "ITA", 0, 0, 'L');
                break;

                case 181 :
                $pdf->Cell(17, 4, "CHI", 0, 0, 'L');
                break;


            }

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(20, 4,"Sexe /", 0, 0, 'R');
            $pdf->SetFont('Arial', '', 8);
            $pdf->Cell(10, 4,"Sex :", 0, 0, 'L');
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(45, 4,$eleve->getSex()->getSex()=='F' ?utf8_decode("Féminin"):"Masculin", 0, 0, 'L');
            $pdf->Cell(30, 4,"----------------------", 'LRB', 1, 'C');

            $pdf->Ln(3);
            $pdf->SetX(50);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(60, 6,utf8_decode("EPREUVES PRATIQUES D'E.P.S. /"), 0, 0, 'R');
            $pdf->SetFont('Arial', 'BI', 9);
            $pdf->Cell(50, 6,utf8_decode("PRACTICE OF S.P.E."), 0, 1, 'L');

            $pdf->Ln(3);
            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"EPREUVES /",'LRT' , 0, 'C');
            $pdf->Cell(30, 3,"CHOIX DE L'ELEVE /", 'LRT', 0, 'C');
            $pdf->Cell(30, 3,"PERFORMANCE", 'LRT', 0, 'C');
            $pdf->Cell(20, 3,"SIGNATURE", 'LRT', 0, 'C');
            $pdf->Cell(15, 3,"NOTE /", 'LRT', 0, 'C');
            $pdf->Cell(40, 3,utf8_decode("Traité par/Treated by :"), 'LRT', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"DISCIPLINE",'LR' , 0, 'C');
            $pdf->Cell(30, 2,"CHOICE OF THE STUDENT", 'LR', 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(20, 2,"", 'LR', 0, 'C');
            $pdf->Cell(15, 2,"MARK", 'LR', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'LRB' , 0, 'C');
            $pdf->Cell(30, 2,"3/4", 'LRB', 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(20, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,"", 'LRB', 1, 'C');


            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"1-COURSE DE VITESSE / SPRINT",'LRT' , 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(20, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(15, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(40, 3,utf8_decode("M ............................................................."), 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"60m - 80m - 100m",'LR' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(20, 2,"", 'LR', 0, 'C');
            $pdf->Cell(15, 2,"", 'LR', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"OU/OR",'LR' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(20, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,".................................................................", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"D'ENDURANCE / ENDURANCE",'LR' , 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(20, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(15, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(40, 3,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"300m - 600m - 800m",'LR' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(20, 2,"", 'LR', 0, 'C');
            $pdf->Cell(15, 2,"", 'LR', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'LRB' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(20, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"2-SAUT EN HAUTEUR /",'LR' , 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(20, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(15, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(40, 3,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"HIGH JUMP",'LR' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(20, 2,"", 'LR', 0, 'C');
            $pdf->Cell(15, 2,"", 'LR', 0, 'C');
            $pdf->Cell(40, 2,utf8_decode("Fait à / Done at : ..................................."), 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'LRB' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(20, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'L');


            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"3-LANCER DE POIDS / SHOT PUT",'LR' , 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(20, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(15, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(40, 3,"Le / On : .................................................", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"2kgs - 3kgs - 4kgs",'LR' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(20, 2,"", 'LR', 0, 'C');
            $pdf->Cell(15, 2,"", 'LR', 0, 'C');
            $pdf->Cell(40, 2,"Signature", 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'LRB' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(20, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"4-GYMNASTIQUE AU SOL",'LR' , 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(30, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(20, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(15, 3,"", 'LRT', 0, 'C');
            $pdf->Cell(40, 3,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"GROUND GYMNASTIC",'LR' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(30, 2,"", 'LR', 0, 'C');
            $pdf->Cell(20, 2,"", 'LR', 0, 'C');
            $pdf->Cell(15, 2,"", 'LR', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'LRB' , 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(30, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(20, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"TOTAL",'LT' , 0, 'L');
            $pdf->Cell(30, 3,"", 'T', 0, 'C');
            $pdf->Cell(30, 3,"", 'T', 0, 'C');
            $pdf->Cell(20, 3,"", 'T', 0, 'C');
            $pdf->Cell(15, 3,"", 'RLT', 0, 'C');
            $pdf->Cell(40, 3,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'L' , 0, 'C');
            $pdf->Cell(30, 2,"",0, 0, 'C');
            $pdf->Cell(30, 2,"", 0, 0, 'C');
            $pdf->Cell(20, 2,"", 0, 0, 'C');
            $pdf->Cell(15, 2,"/60", 'LR', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'LB' , 0, 'C');
            $pdf->Cell(30, 2,"", 'B', 0, 'C');
            $pdf->Cell(30, 2,"", 'B', 0, 'C');
            $pdf->Cell(20, 2,"", 'B', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'L');


            $pdf->SetX(15);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(40, 3,"MOYENNE / AVERAGE",'LT' , 0, 'L');
            $pdf->Cell(30, 3,"", 'T', 0, 'C');
            $pdf->Cell(30, 3,"", 'T', 0, 'C');
            $pdf->Cell(20, 3,"", 'T', 0, 'C');
            $pdf->Cell(15, 3,"", 'RLT', 0, 'C');
            $pdf->Cell(40, 3,"", 'LR', 1, 'L');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'L' , 0, 'C');
            $pdf->Cell(30, 2,"",0, 0, 'C');
            $pdf->Cell(30, 2,"", 0, 0, 'C');
            $pdf->Cell(20, 2,"", 0, 0, 'C');
            $pdf->Cell(15, 2,"/20", 'LR', 0, 'C');
            $pdf->Cell(40, 2,"", 'LR', 1, 'C');

            $pdf->SetX(15);
            $pdf->Cell(40, 2,"",'LB' , 0, 'C');
            $pdf->Cell(30, 2,"", 'B', 0, 'C');
            $pdf->Cell(30, 2,"", 'B', 0, 'C');
            $pdf->Cell(20, 2,"", 'B', 0, 'C');
            $pdf->Cell(15, 2,"", 'LRB', 0, 'C');
            $pdf->Cell(40, 2,"", 'LRB', 1, 'L');


            $pdf->Ln(4);
            $pdf->SetX(50);
            $pdf->SetFont('Arial', 'B', 9);
            $pdf->Cell(60, 6,utf8_decode("DETAILS DE L'EPREUVE DE GYMNASTIQUE AU SOL /"), 0, 0, 'R');
            $pdf->SetFont('Arial', 'BI', 9);
            $pdf->Cell(50, 6,utf8_decode("DETAILS OF GROUND GYMNASTIC"), 0, 1, 'L');

            $pdf->Ln(4);
            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(33, 8,"ELEMENT (1)",1 , 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (2)", 1, 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (3)", 1, 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (4)", 1, 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (5)",1, 1, 'C');

            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(33, 8,"",1 , 0, 'C');
            $pdf->Cell(33, 8,"", 1, 0, 'C');
            $pdf->Cell(33, 8,"", 1, 0, 'C');
            $pdf->Cell(33, 8,"", 1, 0, 'C');
            $pdf->Cell(33, 8,"",1, 1, 'C');

            $pdf->Ln(2);
            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(33, 8,"ELEMENT (6)",1 , 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (7)", 1, 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (8)", 1, 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (9)", 1, 0, 'C');
            $pdf->Cell(33, 8,"ELEMENT (10)",1, 1, 'C');

            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(33, 8,"",1 , 0, 'C');
            $pdf->Cell(33, 8,"", 1, 0, 'C');
            $pdf->Cell(33, 8,"", 1, 0, 'C');
            $pdf->Cell(33, 8,"", 1, 0, 'C');
            $pdf->Cell(33, 8,"",1, 1, 'C');

            $pdf->Ln(3);
            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(70, 8,"(D) DIFFICULTES COMBINAISONS / DIFFICULTY",1 , 0, 'L');
            $pdf->Cell(10, 8,"/10", 1, 0, 'R');
            $pdf->Cell(5, 8,"", 0, 0, 'C');
            $pdf->Cell(70, 8,"(EXC) EXECUTION CORRECTE / (CE) CORRECT EXECUTION",1 , 0, 'L');
            $pdf->Cell(10, 8,"/5", 1, 1, 'R');

            $pdf->Ln(2);
            $pdf->SetX(20);
            $pdf->SetFont('Arial', 'B', 6);
            $pdf->Cell(70, 8,"(ES) EXIGENCES SPECIFIQUES / (SR) SPECIFIC REQUIREMENT",1 , 0, 'L');
            $pdf->Cell(10, 8,"/3", 1, 0, 'R');
            $pdf->Cell(5, 8,"", 0, 0, 'C');
            $pdf->Cell(70, 8,"RECEPTION",1 , 0, 'L');
            $pdf->Cell(10, 8,"/2", 1, 1, 'R');


            $pdf->Ln(5);
            $pdf->SetX(70);
            $pdf->SetFont('Arial', 'B', 8);
            $pdf->Cell(50, 8,"NOTE TOTAL / TOTAL MARK",1 , 0, 'L');
            $pdf->Cell(20, 8,"/20", 1, 1, 'R');


        }
        $pdf->AliasNbPages();
        return $pdf;
    }
}

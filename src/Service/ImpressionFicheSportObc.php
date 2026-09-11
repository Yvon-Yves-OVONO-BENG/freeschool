<?php

namespace App\Service;

use App\Entity\Classroom;
use App\Entity\School;
use App\Entity\SchoolYear;
use App\Entity\Student;
use Fpdf\Fpdf;

class ImpressionFicheSportObc
{
    /**
     * @param Student[] $students
     */
    public function impressionFiche(
        array $students,
        Classroom $classroom,
        SchoolYear $schoolYear,
        ?School $school = null
    ): Fpdf {
        $pdf = new Fpdf('P', 'mm', 'A4');
        $pdf->SetMargins(7, 4, 7);
        $pdf->SetAutoPageBreak(false);
        $sessionYear = $this->sessionYear($schoolYear);

        foreach ($students as $student) {
            $pdf->AddPage('P');
            $this->drawHeader($pdf);
            $this->drawIdentity($pdf, $student, $classroom, $school, $sessionYear);
            $this->drawPracticalTests($pdf);
            $this->drawGymnasticsDetails($pdf, $sessionYear);
        }

        $pdf->AliasNbPages();

        return $pdf;
    }

    private function drawHeader(Fpdf $pdf): void
    {
        $pdf->SetTextColor(20, 28, 42);
        $pdf->SetFont('Arial', '', 6.2);
        $this->multiText(
            $pdf,
            8,
            4,
            64,
            3.05,
            "REPUBLIQUE DU CAMEROUN\nPaix - Travail - Patrie\n----------------\nOFFICE DU BACCALAUREAT DU CAMEROUN\nDIRECTION\nDIVISION DES EXAMENS",
            'C'
        );

        $this->multiText(
            $pdf,
            138,
            4,
            64,
            3.05,
            "REPUBLIC OF CAMEROON\nPeace - Work - Fatherland\n----------------\nOFFICE DU BACCALAUREAT DU CAMEROUN\nDIRECTORATE\nDEPARTMENT OF EXAMINATIONS",
            'C'
        );

        $logo = $this->firstExistingFile([
            'logo/obc.png',
            'public/logo/obc.png',
            'images/logo/obc.png',
            'public/images/logo/obc.png',
        ]);

        if ($logo !== null) {
            $pdf->Image($logo, 94, 3.5, 22, 19);
        } else {
            $this->drawObcBadge($pdf);
        }

        $pdf->SetFillColor(30, 41, 59);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 9.2);
        $pdf->SetXY(76.5, 25);
        $pdf->Cell(57, 7, 'EXAMENS  OBC', 0, 1, 'C', true);

        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Arial', 'B', 10.5);
        $pdf->SetXY(8, 35);
        $pdf->Cell(
            194,
            7,
            $this->latin1("FICHE INDIVIDUELLE DES EPREUVES PRATIQUES D'E.P.S. AUX EXAMENS OFFICIELS"),
            0,
            1,
            'C'
        );
    }

    private function drawObcBadge(Fpdf $pdf): void
    {
        $pdf->SetDrawColor(15, 118, 87);
        $pdf->SetLineWidth(.6);
        $pdf->Rect(94, 3.5, 22, 19);
        $pdf->SetFillColor(0, 135, 81);
        $pdf->Rect(95.2, 4.8, 6.5, 16.4, 'F');
        $pdf->SetFillColor(252, 209, 22);
        $pdf->Rect(101.7, 4.8, 6.6, 16.4, 'F');
        $pdf->SetFillColor(206, 17, 38);
        $pdf->Rect(108.3, 4.8, 6.5, 16.4, 'F');
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY(94.5, 10);
        $pdf->Cell(21, 5, 'O B C', 0, 0, 'C');
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(.2);
    }

    private function drawIdentity(
        Fpdf $pdf,
        Student $student,
        Classroom $classroom,
        ?School $school,
        string $sessionYear
    ): void {
        $photoX = 8;
        $photoY = 48;
        $photoW = 31;
        $photoH = 37;
        $this->drawStudentPhoto($pdf, $student, $photoX, $photoY, $photoW, $photoH);

        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(45, 47);
        $pdf->Cell(18, 6, 'SESSION', 0, 0, 'L');
        $pdf->Cell(21, 6, $sessionYear, 0, 1, 'C');
        $pdf->Line(63, 53, 84, 53);

        $schoolName = $school ? $school->getFrenchName() : 'ETABLISSEMENT';
        $examCenter = strtoupper((string) $schoolName);
        $series = $this->seriesFromClassroom($classroom);
        $exam = (int) $classroom->getLevel()->getLevel() === 6 ? 'PROBATOIRE' : 'BACCALAUREAT';
        $sex = $student->getSex() && $student->getSex()->getSex() === 'F' ? 'FEMININ' : 'MASCULIN';
        $birthday = $student->getBirthday() ? $student->getBirthday()->format('d/m/Y') : '';

        $this->labelLine($pdf, 45, 55, 79, 201, 'Nom(s) et Prenom(s):', strtoupper((string) $student->getFullName()), 8.7);
        $this->labelLine($pdf, 45, 63, 62, 106, 'Ne(e) le:', $birthday, 8.3);
        $this->labelLine($pdf, 109, 63, 127, 201, 'A (lieu):', strtoupper((string) $student->getBirthplace()), 8.3);
        $this->labelLine($pdf, 45, 71, 58, 96, 'Sexe:', $sex, 8.3);
        $this->labelLine($pdf, 100, 71, 128, 201, 'Serie/Specialite:', $series, 8.3);
        $this->labelLine($pdf, 45, 79, 76, 201, "Centre d'examen:", $examCenter, 7.8);

        $this->labelLine($pdf, 45, 88, 96, 158, "Responsable de l'examen:", '', 8);
        $this->labelLine($pdf, 45, 96, 61, 158, 'Examen:', $exam, 8.3);

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetXY(8, 87);
        $pdf->Cell(31, 4, $this->latin1("N° matricule"), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 7.2);
        $pdf->SetXY(8, 91);
        $pdf->Cell(31, 4, $this->latin1((string) $student->getRegistrationNumber()), 0, 0, 'C');

        $pdf->Rect(163, 88, 38, 20);
        $pdf->SetFont('Arial', 'B', 7.1);
        $pdf->SetXY(164, 92);
        $pdf->MultiCell(36, 4, $this->latin1("N° d'ordre du candidat\n(1)"), 0, 'C');

        $pdf->SetFont('Arial', 'B', 8.2);
        $pdf->SetXY(45, 106);
        $pdf->Cell(113, 5, 'CETTE FICHE EST GRATUITE', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 6.2);
        $pdf->SetXY(42, 111);
        $pdf->Cell(
            119,
            4,
            $this->latin1("(remplir l'entete, ne pas la detacher, ne porter aucune mention)"),
            0,
            1,
            'C'
        );

        $pdf->SetDrawColor(15, 23, 42);
        for ($x = 8; $x <= 201; $x += 3) {
            $pdf->Line($x, 120, $x + .8, 120.8);
            $pdf->Line($x + .8, 120, $x, 120.8);
        }
    }

    private function drawPracticalTests(Fpdf $pdf): void
    {
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Arial', 'B', 10.5);
        $pdf->SetXY(7, 123);
        $pdf->Cell(196, 7, $this->latin1("EPREUVES PRATIQUES D'E.P.S"), 0, 1, 'C');

        $x = 7;
        $y = 131;
        $widths = [48, 52, 30, 40, 26];
        $headerHeight = 13;
        $headers = [
            'Epreuves',
            $this->latin1("Choix de l'eleve (3 epreuves sur les 4)"),
            'Performance',
            $this->latin1("Signature chef d'atelier"),
            'Note',
        ];

        $cursorX = $x;
        foreach ($headers as $index => $header) {
            $this->boxedText($pdf, $cursorX, $y, $widths[$index], $headerHeight, $header, 6.7, 'B', 'C');
            $cursorX += $widths[$index];
        }

        $rows = [
            $this->latin1("1- COURSE de vitesse ou d'endurance-vitesse"),
            $this->latin1('2- SAUT en Hauteur'),
            $this->latin1('3- LANCER de Poids'),
            $this->latin1('4- GYMNASTIQUE au sol'),
        ];
        $rowHeight = 8.5;
        $y += $headerHeight;

        foreach ($rows as $row) {
            $cursorX = $x;
            foreach ($widths as $index => $width) {
                $this->boxedText(
                    $pdf,
                    $cursorX,
                    $y,
                    $width,
                    $rowHeight,
                    $index === 0 ? $row : '',
                    6.6,
                    $index === 0 ? 'B' : '',
                    $index === 0 ? 'L' : 'C'
                );
                $cursorX += $width;
            }
            $y += $rowHeight;
        }

        $labelWidth = array_sum($widths) - $widths[4];
        $this->boxedText($pdf, $x, $y, $labelWidth, 6, 'Total', 7.5, 'B', 'L');
        $this->boxedText($pdf, $x + $labelWidth, $y, $widths[4], 6, '/60', 7.5, 'B', 'R');
        $y += 6;
        $this->boxedText($pdf, $x, $y, $labelWidth, 6, 'MOYENNE', 7.5, 'B', 'L');
        $this->boxedText($pdf, $x + $labelWidth, $y, $widths[4], 6, '/20', 7.5, 'B', 'R');

        $processedX = 7;
        $processedY = 193;
        $processedW = 196;
        $processedH = 25;
        $pdf->Rect($processedX, $processedY, $processedW, $processedH);
        $pdf->Line(73, $processedY, 73, $processedY + $processedH);
        $pdf->Line(139, $processedY, 139, $processedY + $processedH);
        $pdf->SetFont('Arial', 'B', 7.4);
        $pdf->SetXY(9, 196);
        $pdf->Cell(62, 4, $this->latin1('Traite par : M/Mme'), 0, 1, 'C');
        $pdf->Line(12, 206, 69, 206);
        $pdf->SetXY(75, 196);
        $pdf->Cell(62, 4, $this->latin1('Fait a'), 0, 1, 'C');
        $pdf->Line(78, 206, 134, 206);
        $pdf->SetXY(75, 208);
        $pdf->Cell(62, 4, $this->latin1('Le'), 0, 1, 'C');
        $pdf->Line(92, 215, 121, 215);
        $pdf->SetXY(141, 196);
        $pdf->Cell(60, 5, 'Signature', 0, 1, 'C');
    }

    private function drawGymnasticsDetails(Fpdf $pdf, string $sessionYear): void
    {
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->SetXY(7, 221);
        $pdf->Cell(196, 7, $this->latin1("DETAILS DE L'EPREUVE DE GYMNASTIQUE AU SOL"), 0, 1, 'C');

        $x = 8;
        $y = 229;
        $cellW = 38.8;

        for ($row = 0; $row < 2; $row++) {
            for ($column = 0; $column < 5; $column++) {
                $this->boxedText(
                    $pdf,
                    $x + ($column * $cellW),
                    $y,
                    $cellW,
                    5,
                    $this->latin1('Elements ('.($column + 1).')'),
                    6.8,
                    'B',
                    'C'
                );
                $this->boxedText($pdf, $x + ($column * $cellW), $y + 5, $cellW, 8, '', 7, '', 'C');
            }
            $y += 15;
        }

        $this->boxedText($pdf, 8, 261, 49, 6, $this->latin1('(D) Difficulte de combinaison'), 6.4, 'B', 'L');
        $this->boxedText($pdf, 57, 261, 15, 6, '/10', 7, 'B', 'R');
        $this->boxedText($pdf, 8, 267, 49, 6, $this->latin1('(ES) Exigences specifiques'), 6.4, 'B', 'L');
        $this->boxedText($pdf, 57, 267, 15, 6, '/3', 7, 'B', 'R');

        $this->boxedText($pdf, 139, 261, 48, 6, $this->latin1('(EXC) Execution correcte'), 6.4, 'B', 'L');
        $this->boxedText($pdf, 187, 261, 15, 6, '/15', 7, 'B', 'R');
        $this->boxedText($pdf, 139, 267, 48, 6, $this->latin1('(R) Reception'), 6.4, 'B', 'L');
        $this->boxedText($pdf, 187, 267, 15, 6, '/2', 7, 'B', 'R');

        $this->boxedText($pdf, 78, 261, 42, 12, 'NOTE TOTALE', 7.6, 'B', 'L');
        $this->boxedText($pdf, 120, 261, 13, 12, '/20', 7.6, 'B', 'R');

        $pdf->SetFont('Arial', '', 6.6);
        $pdf->SetXY(144, 281);
        $pdf->Cell(58, 3, $this->latin1('Edite le : '.date('d/m/').$sessionYear), 0, 0, 'R');
    }

    private function drawStudentPhoto(
        Fpdf $pdf,
        Student $student,
        float $x,
        float $y,
        float $width,
        float $height
    ): void {
        $photo = $student->getPhoto();
        $photoPath = $photo ? $this->firstExistingFile([
            'images/students/'.$photo,
            'public/images/students/'.$photo,
        ]) : null;

        if ($photoPath !== null && @getimagesize($photoPath) !== false) {
            $pdf->Image($photoPath, $x, $y, $width, $height);
            $pdf->Rect($x, $y, $width, $height);

            return;
        }

        $pdf->SetFillColor(241, 245, 249);
        $pdf->SetDrawColor(100, 116, 139);
        $pdf->Rect($x, $y, $width, $height, 'DF');
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('Arial', 'B', 15);
        $pdf->SetXY($x, $y + 11);
        $pdf->Cell($width, 8, $this->initials((string) $student->getFullName()), 0, 1, 'C');
        $pdf->SetFont('Arial', '', 6.5);
        $pdf->SetXY($x, $y + 23);
        $pdf->Cell($width, 5, 'PHOTO', 0, 1, 'C');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetDrawColor(0, 0, 0);
    }

    private function labelLine(
        Fpdf $pdf,
        float $labelX,
        float $y,
        float $valueX,
        float $endX,
        string $label,
        string $value,
        float $fontSize
    ): void {
        $pdf->SetFont('Arial', 'B', 8.2);
        $pdf->SetXY($labelX, $y);
        $pdf->Cell($valueX - $labelX, 5, $this->latin1($label), 0, 0, 'L');
        $pdf->SetFont('Arial', '', $fontSize);
        $pdf->SetXY($valueX, $y);
        $pdf->Cell($endX - $valueX, 5, $this->latin1($value), 0, 0, 'L');
        $pdf->Line($valueX, $y + 5, $endX, $y + 5);
    }

    private function boxedText(
        Fpdf $pdf,
        float $x,
        float $y,
        float $width,
        float $height,
        string $text,
        float $fontSize,
        string $style,
        string $align
    ): void {
        $pdf->Rect($x, $y, $width, $height);
        $pdf->SetFont('Arial', $style, $fontSize);
        $pdf->SetXY($x + 1, $y + 1);

        if (strlen($text) > 28) {
            $pdf->MultiCell($width - 2, max(3, ($height - 2) / 2), $text, 0, $align);
        } else {
            $pdf->Cell($width - 2, max(3, $height - 2), $text, 0, 0, $align);
        }
    }

    private function multiText(
        Fpdf $pdf,
        float $x,
        float $y,
        float $width,
        float $lineHeight,
        string $text,
        string $align
    ): void {
        $pdf->SetXY($x, $y);
        $pdf->MultiCell($width, $lineHeight, $this->latin1($text), 0, $align);
    }

    private function seriesFromClassroom(Classroom $classroom): string
    {
        $name = strtoupper(trim((string) $classroom->getClassroom()));
        $series = preg_replace(
            '/^(TERMINALE|TLE|PREMIERE|1ERE|1RE|LOWER 6|UPPER 6)[\s_-]*/i',
            '',
            $name
        );

        return trim((string) $series) !== '' ? trim((string) $series) : $name;
    }

    private function sessionYear(SchoolYear $schoolYear): string
    {
        preg_match_all('/(?:19|20)\\d{2}/', (string) $schoolYear->getSchoolYear(), $matches);

        if (!empty($matches[0])) {
            $years = $matches[0];

            return (string) end($years);
        }

        return (string) $schoolYear->getSchoolYear();
    }

    /**
     * @param string[] $paths
     */
    private function firstExistingFile(array $paths): ?string
    {
        foreach ($paths as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function initials(string $fullName): string
    {
        $parts = preg_split('/\s+/', trim($fullName)) ?: [];
        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= strtoupper(substr($part, 0, 1));
        }

        return $initials !== '' ? $initials : '--';
    }

    private function latin1(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'windows-1252//TRANSLIT', $text);

            if ($converted !== false) {
                return $converted;
            }
        }

        return utf8_decode($text);
    }
}

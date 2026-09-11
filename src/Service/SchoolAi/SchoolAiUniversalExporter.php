<?php

namespace App\Service\SchoolAi;

use Dompdf\Dompdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use RuntimeException;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Exporte hors du dossier public. Les fichiers sont servis par une route
 * authentifiée et supprimés automatiquement après 24 heures.
 */
class SchoolAiUniversalExporter
{
    private const ALLOWED_FORMATS = ['pdf', 'xlsx', 'docx'];

    public function __construct(private KernelInterface $kernel)
    {
    }

    /**
     * Retourne uniquement le nom interne du fichier, jamais un chemin public.
     */
    public function export(array $formatted, string $format): ?string
    {
        $format = strtolower(trim($format));
        if (!in_array($format, self::ALLOWED_FORMATS, true)) {
            return null;
        }

        $directory = $this->exportDirectory();
        $this->ensureDirectory($directory);
        $this->cleanupExpiredFiles($directory);

        $filename = 'assistant_ia_' . date('Ymd_His') . '_' . bin2hex(random_bytes(16)) . '.' . $format;
        $path = $directory . DIRECTORY_SEPARATOR . $filename;

        match ($format) {
            'pdf' => $this->exportPdf($formatted, $path),
            'xlsx' => $this->exportXlsx($formatted, $path),
            'docx' => $this->exportDocx($formatted, $path),
        };

        if (!is_file($path) || filesize($path) === 0) {
            throw new RuntimeException("Le fichier demandé n'a pas pu être généré.");
        }

        return $filename;
    }

    public function resolveFile(string $filename): ?string
    {
        if (
            !preg_match(
                '/^assistant_ia_\d{8}_\d{6}_[a-f0-9]{32}\.(pdf|xlsx|docx)$/',
                $filename
            )
        ) {
            return null;
        }

        $path = $this->exportDirectory() . DIRECTORY_SEPARATOR . $filename;

        return is_file($path) ? $path : null;
    }

    public function downloadName(string $filename): string
    {
        $extension = strtolower((string) pathinfo($filename, PATHINFO_EXTENSION));

        return 'freeschool_ai_' . date('Y-m-d') . '.' . $extension;
    }

    private function exportPdf(array $data, string $path): void
    {
        $html = '<!doctype html><html lang="fr"><head><meta charset="UTF-8"><style>'
            . 'body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#17233d}'
            . '.title{font-size:20px;font-weight:bold;margin-bottom:8px;color:#173f73}'
            . '.context{color:#5d6b82;margin:4px 0}.summary{background:#eef4ff;padding:10px;'
            . 'border-left:4px solid #2878d0;margin:12px 0}table{border-collapse:collapse;width:100%}'
            . 'th,td{border:1px solid #d8e0ea;padding:6px;text-align:left;vertical-align:top}'
            . 'th{background:#173f73;color:white}tr:nth-child(even){background:#f7f9fc}'
            . '</style></head><body>';
        $html .= '<div class="title">' . $this->escape($data['title'] ?? 'FreeSchool AI') . '</div>';
        if (!empty($data['schoolYear'])) {
            $html .= '<p class="context"><strong>Année scolaire :</strong> '
                . $this->escape($data['schoolYear']) . '</p>';
        }
        if (!empty($data['question'])) {
            $html .= '<p class="context"><strong>Question :</strong> '
                . $this->escape($data['question']) . '</p>';
        }
        $html .= '<div class="summary">' . $this->escape($data['summary'] ?? '') . '</div>';
        $html .= $this->htmlTable($data);
        $html .= '</body></html>';

        $dompdf = new Dompdf(['isRemoteEnabled' => false]);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        if (file_put_contents($path, $dompdf->output(), LOCK_EX) === false) {
            throw new RuntimeException("Impossible d'enregistrer le PDF.");
        }
    }

    private function exportXlsx(array $data, string $path): void
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('FreeSchool AI');
        $sheet->setCellValue('A1', $this->safeSpreadsheetValue($data['title'] ?? 'FreeSchool AI'));
        $sheet->setCellValue('A2', $this->safeSpreadsheetValue($data['summary'] ?? ''));
        if (!empty($data['schoolYear'])) {
            $sheet->setCellValue('A3', 'Année scolaire : ' . $this->safeSpreadsheetValue($data['schoolYear']));
        }

        $rowIndex = 5;
        $columns = $data['columns'] ?? [];
        $labels = $data['columnLabels'] ?? [];
        foreach ($columns as $index => $column) {
            $sheet->setCellValueByColumnAndRow(
                $index + 1,
                $rowIndex,
                $this->safeSpreadsheetValue($labels[$column] ?? $column)
            );
        }

        $lastColumn = max(1, count($columns));
        $sheet->getStyleByColumnAndRow(1, $rowIndex, $lastColumn, $rowIndex)
            ->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');
        $sheet->getStyleByColumnAndRow(1, $rowIndex, $lastColumn, $rowIndex)
            ->getFill()
            ->setFillType('solid')
            ->getStartColor()
            ->setARGB('FF173F73');

        ++$rowIndex;
        foreach (($data['rows'] ?? []) as $row) {
            foreach ($columns as $index => $column) {
                $sheet->setCellValueByColumnAndRow(
                    $index + 1,
                    $rowIndex,
                    $this->safeSpreadsheetValue($row[$column] ?? '')
                );
            }
            ++$rowIndex;
        }

        foreach (range(1, $lastColumn) as $columnIndex) {
            $sheet->getColumnDimensionByColumn($columnIndex)->setAutoSize(true);
        }

        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();
    }

    private function exportDocx(array $data, string $path): void
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection([
            'orientation' => 'landscape',
            'marginTop' => 900,
            'marginBottom' => 900,
            'marginLeft' => 900,
            'marginRight' => 900,
        ]);
        $section->addTitle((string) ($data['title'] ?? 'FreeSchool AI'), 1);
        if (!empty($data['schoolYear'])) {
            $section->addText('Année scolaire : ' . $data['schoolYear'], ['bold' => true]);
        }
        if (!empty($data['question'])) {
            $section->addText('Question : ' . $data['question']);
        }
        $section->addText((string) ($data['summary'] ?? ''), ['bold' => true]);

        $columns = $data['columns'] ?? [];
        $labels = $data['columnLabels'] ?? [];
        if ($columns !== []) {
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'CBD5E1',
                'cellMargin' => 80,
            ]);
            $table->addRow();
            foreach ($columns as $column) {
                $table->addCell(2500, ['bgColor' => '173F73'])
                    ->addText((string) ($labels[$column] ?? $column), ['bold' => true, 'color' => 'FFFFFF']);
            }
            foreach (($data['rows'] ?? []) as $row) {
                $table->addRow();
                foreach ($columns as $column) {
                    $table->addCell(2500)->addText((string) ($row[$column] ?? ''));
                }
            }
        }

        IOFactory::createWriter($phpWord, 'Word2007')->save($path);
    }

    private function htmlTable(array $data): string
    {
        $columns = $data['columns'] ?? [];
        $labels = $data['columnLabels'] ?? [];
        if ($columns === []) {
            return '';
        }

        $html = '<table><thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th>' . $this->escape($labels[$column] ?? $column) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        foreach (($data['rows'] ?? []) as $row) {
            $html .= '<tr>';
            foreach ($columns as $column) {
                $html .= '<td>' . $this->escape($row[$column] ?? '') . '</td>';
            }
            $html .= '</tr>';
        }

        return $html . '</tbody></table>';
    }

    private function exportDirectory(): string
    {
        return $this->kernel->getProjectDir() . '/var/assistant-ia';
    }

    private function ensureDirectory(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0770, true) && !is_dir($directory)) {
            throw new RuntimeException("Le dossier d'export de l'assistant IA est inaccessible.");
        }
    }

    private function cleanupExpiredFiles(string $directory): void
    {
        $cutoff = time() - 86400;
        foreach (glob($directory . '/assistant_ia_*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
            }
        }
    }

    private function safeSpreadsheetValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_match('/^[=+\-@]/', $value) ? "'" . $value : $value;
    }

    private function escape(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

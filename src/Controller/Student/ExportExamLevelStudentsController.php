<?php

namespace App\Controller\Student;

use App\Entity\ConstantsClass;
use App\Repository\ClassroomRepository;
use App\Repository\SchoolRepository;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class ExportExamLevelStudentsController extends AbstractController
{
    #[Route('/export/exam-level-students', name: 'export_exam_level_students')]
    public function exportExamLevelStudents(
        Request $request,
        SchoolRepository $schoolRepository,
        ClassroomRepository $classroomRepository
    ): Response {
        $session = $request->getSession();
        $session->set('ajout', null);
        $session->set('suppression', null);
        $session->set('miseAjour', null);
        $session->set('saisiNotes', null);

        $schoolYear = $session->get('schoolYear');
        $subSystem = $session->get('subSystem');

        if ($schoolYear === null || $subSystem === null) {
            return $this->redirectToRoute('app_logout');
        }

        $school = $schoolRepository->findOneBy(['schoolYear' => $schoolYear]);
        $education = $school?->getEducation()?->getEducation();
        $levelName = $education === ConstantsClass::TECHNICAL_EDUCATION
            ? ConstantsClass::LEVEL_4_TECH
            : ConstantsClass::LEVEL_4;

        $classrooms = $classroomRepository->findExamLevelClassrooms($schoolYear, $subSystem, $levelName);
        $spreadsheet = $this->createSpiderWorkbook($levelName, $school?->getFrenchName() ?? $school?->getEnglishName() ?? '');
        $sheet = $spreadsheet->getSheetByName($this->safeSheetTitle('SPIDER '.$levelName)) ?? $spreadsheet->getActiveSheet();

        $row = 5;
        $index = 1;

        foreach ($classrooms as $classroom) {
            $students = $classroom->getStudents()->toArray();
            usort($students, static function ($left, $right): int {
                return strcmp($left->getFullName(), $right->getFullName());
            });

            foreach ($students as $student) {
                $birthday = $student->getBirthday();
                $identity = sprintf(
                    '%s né(e) le %s à %s',
                    $student->getFullName(),
                    $birthday ? $birthday->format('d/m/Y') : '',
                    $student->getBirthplace() ?? ''
                );

                $sheet->setCellValue('A'.$row, $index++);
                $sheet->setCellValue('B'.$row, trim($identity));
                $sheet->setCellValue('AG'.$row, '');
                $sheet->setCellValue('AH'.$row, '');
                $sheet->setCellValue('AI'.$row, '');
                $sheet->setCellValue('AJ'.$row, '');
                $sheet->setCellValue('AK'.$row, '');
                $row++;
            }
        }

        $lastRow = max(5, $row - 1);
        $sheet->getStyle('A3:AK'.$lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle('A3:AK'.$lastRow)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle('A3:AK'.$lastRow)->getAlignment()->setWrapText(true);

        $fileName = sprintf('export_spider_%s_%s.xlsx', str_replace(['/', ' '], ['-', '_'], $levelName), date('Ymd_His'));

        return new StreamedResponse(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    private function createSpiderWorkbook(string $levelName, string $schoolName): Spreadsheet
    {
        $templatePath = $this->getParameter('kernel.project_dir').'/public/templates/spider_exam_template.xlsx';

        if (is_file($templatePath)) {
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getSheetByName('ALL') ?? $spreadsheet->getActiveSheet();

            for ($index = $spreadsheet->getSheetCount() - 1; $index >= 0; $index--) {
                $title = $spreadsheet->getSheet($index)->getTitle();

                if (!in_array($title, ['COUVERTURE', 'DERNIERE_PAGE', $sheet->getTitle()], true)) {
                    $spreadsheet->removeSheetByIndex($index);
                }
            }

            $sheet->setTitle($this->safeSheetTitle('SPIDER '.$levelName));
            $spreadsheet->setActiveSheetIndexByName($sheet->getTitle());
            $this->clearTemplateRows($sheet, 5, max(120, $sheet->getHighestDataRow()));

            if ($schoolName !== '') {
                $sheet->setCellValue('D2', $schoolName);
            }

            return $spreadsheet;
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle($this->safeSheetTitle('SPIDER '.$levelName));
        $sheet->setCellValue('B2', "NOM DE L'ETABLISSEMENT :");
        $sheet->setCellValue('D2', $schoolName);
        $sheet->setCellValue('A3', 'N°');
        $sheet->setCellValue('B3', 'Noms et Prénoms, date et lieu de naissance');
        $sheet->setCellValue('C3', 'Matières et Notes Annuelles');
        $sheet->setCellValue('AG3', 'Total Général');
        $sheet->setCellValue('AH3', 'Moyenne Annuelle');
        $sheet->setCellValue('AI3', 'Moy 1er Trimestre');
        $sheet->setCellValue('AJ3', 'Moyenne 2ème Trimestre');
        $sheet->setCellValue('AK3', 'Moyenne 3ème Trimestre');

        return $spreadsheet;
    }

    private function clearTemplateRows(Worksheet $sheet, int $startRow, int $endRow): void
    {
        for ($row = $startRow; $row <= $endRow; $row++) {
            for ($columnIndex = 1; $columnIndex <= 37; $columnIndex++) {
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($columnIndex).$row, null);
            }
        }
    }

    private function safeSheetTitle(string $title): string
    {
        $title = str_replace(['\\', '/', '?', '*', '[', ']', ':'], '-', $title);

        return substr($title, 0, 31);
    }
}

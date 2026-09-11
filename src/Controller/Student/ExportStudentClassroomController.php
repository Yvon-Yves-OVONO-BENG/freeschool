<?php

namespace App\Controller\Student;

use ZipArchive;
use App\Entity\Classroom;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ExportStudentClassroomController extends AbstractController
{
    #[Route('/export/eleves/download', name: 'export_eleves_download')]
    public function exportDownload(Request $request, EntityManagerInterface $em): StreamedResponse
    {
        $session = $request->getSession();

        $schoolYear = $session->get('schoolYear');
        $subSystem = $session->get('subSystem');

        $classrooms = $em->getRepository(Classroom::class)->findBy([
            'schoolYear' => $schoolYear,
            'subSystem' => $subSystem,
        ]);

        // ✅ Créer un fichier ZIP temporaire valide
        $zipFile = tempnam(sys_get_temp_dir(), 'export_eleves_');
        unlink($zipFile); // supprime le fichier vide généré par tempnam
        $zipFile .= '.zip';

        $zip = new ZipArchive();
        if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Impossible de créer le fichier ZIP');
        }

        foreach ($classrooms as $classroom) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle($classroom->getClassroom());

            // En-têtes
            $sheet->setCellValue('A1', 'N°');
            $sheet->setCellValue('B1', 'NIU');
            $sheet->setCellValue('C1', 'Nom');
            $sheet->setCellValue('D1', 'Notes');

            // Élèves
            $students = $classroom->getStudents();
            $row = 2;
            $index = 1;

            foreach ($students as $student) {
                $sheet->setCellValue('A' . $row, $index++);
                $sheet->setCellValue('B' . $row, $student->getRegistrationNumber());
                $sheet->setCellValue('C' . $row, $student->getFullName());
                $sheet->setCellValue('D' . $row, 0);
                $row++;
            }

            // Fichier Excel temporaire
            $tempExcel = tempnam(sys_get_temp_dir(), 'classe_') . '.xlsx';
            $writer = new Xlsx($spreadsheet);
            $writer->save($tempExcel);

            // Ajouter au ZIP
            $zip->addFile($tempExcel, $classroom->getClassroom() . '.xlsx');

            // Supprimer le fichier temporaire après fermeture du ZIP
            register_shutdown_function(static fn() => @unlink($tempExcel));
        }

        $zip->close();

        // Téléchargement du ZIP
        return new StreamedResponse(function () use ($zipFile) {
            readfile($zipFile);
            @unlink($zipFile);
        }, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="eleves_par_classe.zip"',
        ]);
    }
}

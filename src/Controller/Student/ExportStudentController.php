<?php

namespace App\Controller\Student;

use App\Entity\ConstantsClass;
use App\Repository\ClassroomRepository;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ExportStudentController extends AbstractController
{
    #[Route('/export-student/{parametre}/{slugClassroom}', name: 'export_student')]
    public function exportStudent(Request $request, ClassroomRepository $classroomRepository, $parametre = "", $slugClassroom = ""): Response
    {
        $mySession = $request->getSession();

        $mySession->set('ajout',null);
        $mySession->set('suppression', null);
        $mySession->set('miseAjour', null);
        $mySession->set('saisiNotes', null);
        
        if($mySession)
        {
            $schoolYear = $mySession->get('schoolYear');
            $subSystem = $mySession->get('subSystem');

        }else 
        {
            return $this->redirectToRoute("app_logout");
        }

        
        if ($request->request->has('studentsList')) 
        {
            //je récupère ma classe
            $classroom = $classroomRepository->findOneBy(['slug' => $request->request->get('slugClassroom')]);
        } 
        else 
        {
            //je récupère ma classe
            $classroom = $classroomRepository->findOneBy(['slug' => $slugClassroom]);
        }
        
        // Récupérer les eleves d'une classe donnée
        $students = $classroom->getStudents()->toArray();
        

        usort($students, function($a, $b) 
        {
            return strcmp($a->getFullName(), $b->getFullName());
        });

        
        // Créer une feuille Excel
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        if ($parametre == 'notes') 
        {
            if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
            {
                $sheet->setTitle(utf8_decode('Importation ').$classroom->getClassroom());
            }
            else 
            {
                $sheet->setTitle('Import notes of in the '.$classroom->getClassroom());
            }
                
            // En-tête
            $sheet->setCellValue('A1', 'N°');
            $sheet->setCellValue('B1', 'NIU');
            $sheet->setCellValue('C1', 'Nom');
            $sheet->setCellValue('D1', 'Notes');

            // Contenu
            $row = 2;
            $index = 1;
            foreach ($students as $student) 
            {
                $sheet->setCellValue("A{$row}", $index++);
                $sheet->setCellValue("B{$row}", $student->getRegistrationNumber()); 
                $sheet->setCellValue("C{$row}", $student->getFullName()); 
                $row++;
            }
        } 
        else 
        {
            if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
            {
                $sheet->setTitle(utf8_decode('Liste des eleves_').$classroom->getClassroom());
            }
            else 
            {
                $sheet->setTitle('List student of in the '.$classroom->getClassroom());
            }
                
            // En-tête

            $sheet->setCellValue('A1', 'N°');
            $sheet->setCellValue('B1', 'NIU');

            if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
            {
                $sheet->setCellValue('C1', 'Nom');
                $sheet->setCellValue('D1', 'Genre');
                $sheet->setCellValue('E1', 'Date naissance');
                $sheet->setCellValue('F1', 'Lieu');
            }
            else
            {
                $sheet->setCellValue('C1', 'Nom');
                $sheet->setCellValue('D1', 'Gender');
                $sheet->setCellValue('E1', 'Birthday');
                $sheet->setCellValue('F1', 'Birthplace');
            }
            
            // Contenu
            $row = 2;
            $index = 1;
            foreach ($students as $student) 
            {
                $sheet->setCellValue("A{$row}", $index++);
                $sheet->setCellValue("B{$row}", $student->getRegistrationNumber()); 
                $sheet->setCellValue("C{$row}", $student->getFullName()); 
                $sheet->setCellValue("D{$row}", $student->getSex()->getSex()); 
                $sheet->setCellValue("E{$row}", $student->getBirthday()); 
                $sheet->setCellValue("F{$row}", $student->getBirthplace()); 
                $row++;
            }
        }
        

        // Préparer la réponse HTTP
        $writer = new Xlsx($spreadsheet);
        $response = new StreamedResponse(function() use ($writer) 
        {
            $writer->save('php://output');
        });


        if ($parametre == 'notes') 
        {
            if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
            {
                $disposition = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'Importation_des_notes_de_la_'.$classroom->getClassroom().'.xlsx'
                );
            }
            else
            {
                $disposition = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'Import_notes_of_in_the'.$classroom->getClassroom().'.xlsx'
                );
            }
        }
        else 
        {
            if ($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE ) 
            {
                $disposition = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'Liste_des_eleves_de_la_'.$classroom->getClassroom().'.xlsx'
                );
            }
            else
            {
                $disposition = $response->headers->makeDisposition(
                    ResponseHeaderBag::DISPOSITION_ATTACHMENT,
                    'List_students_of_in_the'.$classroom->getClassroom().'.xlsx'
                );
            }
        }

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $disposition);

        return $response;

    }
}

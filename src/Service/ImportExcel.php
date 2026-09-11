<?php

namespace App\Service;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class ImportExcel
{
    public function import(string $filePath): array
    {
        // Charger le fichier Excel
        $spreadsheet = IOFactory::load($filePath);
        
        // Accéder à la première feuille
        $sheet = $spreadsheet->getActiveSheet();
        
        // Parcourir les lignes
        $data = [];
        foreach ($sheet->getRowIterator(2) as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);

            foreach ($cellIterator as $cell) {
                if (Date::isDateTime($cell)) {
                    $dateValue = Date::excelToDateTimeObject($cell->getValue());
                    $rowData[] = $dateValue->format('Y-m-d');
                } else {
                    $rowData[] = $cell->getValue();
                }
                
            }
            $data[] = $rowData;
        }

        return $data;
    }
}

<?php

namespace App\Service;

class StrService 
{
    public function strToUpper(string $value): string
    {
         // Tableau utilisé pour rempplacer les carctères spéciaux 
        // et éviter les problèmes d'encodage dans la BD
        $specialCaracters = ['à', 'â', 'ë', 'é', 'è', 'ê', 'É', 'È', 'Ê', 'Ë', 'ç','Ç', 'î', 'ï', 'Î', 'Ï'];
        $replaceCaracters = ['a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'c', 'c', 'i', 'i', 'i', 'i'];

        // on enlève les carctères speciaux et on met en majuscule
        return strtoupper(str_replace($specialCaracters, $replaceCaracters, $value));
        
    }
}
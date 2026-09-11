<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

class AppExtension extends AbstractExtension 
{
    #filtre qui permet de segmenter un nombre en bloc de 3 commençant pas ma froite
    public function getFilters()
    {
        return [
            new TwigFilter('age', [$this, 'calculeAge']),
            new TwigFilter('sum', [$this, 'arraySum']),
            new TwigFilter('number_format', [$this, 'numberFormat']),
            new TwigFilter('truncate_decimal', [$this, 'truncateDecimal']),
        ];

    }

    public function numberFormat($number)
    {
        #j'utilise la fonction
        return number_format($number, 0, '', ' ');
    }

    #filtre qui permet de calculer l'âge
    public function calculeAge(\DateTimeInterface $dateNaissance): int
    {
        $aujourdhui = new \DateTime();
        $age = $aujourdhui->diff($dateNaissance)->y;

        return $age;
    }

    //pour calculer la somme des éléments dans un tableau twig 
    public function arraySum(array $values): float
    {
        return array_sum($values);
    }

    public function truncateDecimal($number, int $decimals = 2): float
    {
        $factor = pow(10, $decimals);
        return floor($number * $factor) / $factor;
    }

}
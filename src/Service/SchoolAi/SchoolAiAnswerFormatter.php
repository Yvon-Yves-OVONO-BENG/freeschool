<?php

namespace App\Service\SchoolAi;

class SchoolAiAnswerFormatter
{
    public function format(array $execution): array
    {
        $rows = is_array($execution['rows'] ?? null) ? $execution['rows'] : [];
        $plan = is_array($execution['plan'] ?? null) ? $execution['plan'] : [];
        $title = trim((string) ($plan['title'] ?? 'Résultat FreeSchool AI'));
        $question = trim((string) ($plan['question'] ?? ''));
        $schoolYear = trim((string) ($plan['schoolYear'] ?? ''));
        $type = (string) ($execution['type'] ?? 'list');

        if ($rows === []) {
            return [
                'title' => $title,
                'question' => $question,
                'schoolYear' => $schoolYear,
                'summary' => $schoolYear !== ''
                    ? "Aucun résultat trouvé pour l'année scolaire {$schoolYear}."
                    : 'Aucun résultat ne correspond à cette question.',
                'columns' => [],
                'columnLabels' => [],
                'rows' => [],
            ];
        }

        $columns = array_keys($rows[0]);
        $summary = match ($type) {
            'aggregate' => $this->aggregateSummary($rows[0], $plan),
            'group' => sprintf(
                '%d groupe%s trouvé%s%s.',
                count($rows),
                count($rows) > 1 ? 's' : '',
                count($rows) > 1 ? 's' : '',
                $schoolYear !== '' ? " pour {$schoolYear}" : ''
            ),
            default => sprintf(
                '%d résultat%s affiché%s%s.',
                count($rows),
                count($rows) > 1 ? 's' : '',
                count($rows) > 1 ? 's' : '',
                $schoolYear !== '' ? " pour {$schoolYear}" : ''
            ),
        };

        $columnLabels = [];
        foreach ($columns as $column) {
            $columnLabels[$column] = $this->humanLabel((string) $column);
        }

        return [
            'title' => $title,
            'question' => $question,
            'schoolYear' => $schoolYear,
            'summary' => $summary,
            'columns' => $columns,
            'columnLabels' => $columnLabels,
            'rows' => $rows,
        ];
    }

    private function aggregateSummary(array $row, array $plan): string
    {
        $operation = (string) ($plan['operation'] ?? 'count');
        $value = $row['value'] ?? reset($row);
        $schoolYear = trim((string) ($plan['schoolYear'] ?? ''));
        $label = match ($operation) {
            'count' => 'Le nombre total trouvé est',
            'sum' => 'La somme demandée est',
            'avg' => 'La moyenne demandée est',
            'min' => 'La plus petite valeur trouvée est',
            'max' => 'La plus grande valeur trouvée est',
            default => 'Le résultat est',
        };
        $context = $schoolYear !== '' ? " pour l'année scolaire {$schoolYear}" : '';

        return $label . $context . ' : ' . $this->humanValue($value, $operation === 'count') . '.';
    }

    public function humanValue(mixed $value, bool $integerExpected = false): string
    {
        if ($value === null || $value === '') {
            return '—';
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }
        if (is_bool($value)) {
            return $value ? 'Oui' : 'Non';
        }
        if (is_numeric($value)) {
            $number = (float) $value;
            $decimals = $integerExpected || floor($number) === $number ? 0 : 2;

            return number_format($number, $decimals, ',', ' ');
        }

        return (string) $value;
    }

    private function humanLabel(string $column): string
    {
        $known = [
            'label' => 'Libellé',
            'value' => 'Valeur',
            'fullName' => 'Nom complet',
            'registrationNumber' => 'Matricule',
            'classroom' => 'Classe',
            'subject' => 'Matière',
            'mark' => 'Note',
            'schoolFees' => 'Pension',
            'apeeFees' => 'Frais APEE',
            'computerFees' => 'Frais informatiques',
            'montant' => 'Montant',
            'createdAt' => 'Date',
        ];

        if (isset($known[$column])) {
            return $known[$column];
        }

        $label = str_replace('_', ' ', $column);
        $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $label) ?? $label;
        $label = preg_replace('/\s+/', ' ', $label) ?? $label;

        return ucfirst(trim($label));
    }
}

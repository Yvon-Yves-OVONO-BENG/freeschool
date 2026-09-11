<?php

namespace App\Service\SchoolAi;

use RuntimeException;

/**
 * Transforme une question naturelle en plan de lecture strict.
 *
 * Le modèle est utilisé en premier lorsqu'il est configuré. Le moteur local
 * sert de repli pour les demandes scolaires courantes et non l'inverse.
 */
class SchoolAiPlanParser
{
    private const OPERATIONS = [
        'list',
        'count',
        'sum',
        'avg',
        'min',
        'max',
        'group_count',
        'group_sum',
        'group_avg',
    ];

    private const FORMATS = ['html', 'pdf', 'xlsx', 'docx'];

    public function __construct(
        private SchoolAiSchemaCatalog $catalog,
        private SchoolAiOpenAiClient $openAiClient
    ) {
    }

    public function isAiConfigured(): bool
    {
        return $this->openAiClient->isConfigured();
    }

    /**
     * Retourne un plan sûr. L'année passée ici est toujours celle de la session.
     */
    public function parse(
        string $question,
        ?string $forcedFormat = null,
        ?string $selectedSchoolYear = null
    ): array {
        $format = $this->normalizeFormat($forcedFormat ?: $this->detectFormat($question));
        $llmFailure = null;

        if ($this->openAiClient->isConfigured()) {
            try {
                $schema = $this->catalog->schemaSummaryForQuestion($question);
                $plan = $this->openAiClient->createQueryPlan($question, $schema, $format);

                if (is_array($plan)) {
                    return $this->enforceSelectedSchoolYear(
                        $this->normalizePlan($plan, $question, $format, 'openai'),
                        $selectedSchoolYear
                    );
                }
            } catch (RuntimeException $exception) {
                $llmFailure = $exception;
            }
        }

        $rulePlan = $this->parseWithRules($question, $format);
        if ($rulePlan !== null) {
            return $this->enforceSelectedSchoolYear(
                $this->normalizePlan($rulePlan, $question, $format, 'rules'),
                $selectedSchoolYear
            );
        }

        if ($llmFailure !== null) {
            throw $llmFailure;
        }

        throw new RuntimeException(
            "Le mode IA complet n'est pas configuré. Ajoutez OPENAI_API_KEY dans .env.local, "
            . "puis reformulez la question."
        );
    }

    private function parseWithRules(string $question, string $format): ?array
    {
        $normalized = $this->catalog->normalize($question);
        $entity = $this->detectRuleEntity($question, $normalized);

        if ($entity === null) {
            return null;
        }

        $shortName = $entity['shortName'];
        $operation = $this->detectOperation($normalized, $shortName);
        $aggregateField = $this->aggregateField($entity, $normalized, $operation);
        $filters = $this->detectFilters($question, $normalized, $shortName);
        $groupBy = $this->detectGroupBy($normalized, $shortName);
        $having = null;
        $orderBy = null;
        $limit = 100;

        if ($groupBy !== null) {
            $operation = match ($operation) {
                'sum' => 'group_sum',
                'avg', 'min', 'max' => 'group_avg',
                default => 'group_count',
            };
        }

        $isRanking = preg_match('/\b(classement|top|meilleur|meilleure|premier|premiere)\b/u', $normalized);
        if ($isRanking && in_array($shortName, ['Evaluation', 'Report'], true)) {
            $operation = 'group_avg';
            $aggregateField = $shortName === 'Report' ? 'moyenne' : 'mark';
            $groupBy = 'student.fullName';
            $orderBy = ['path' => 'value', 'direction' => 'DESC'];
            $limit = $this->detectTopLimit($normalized) ?? 10;
        }

        if (in_array($operation, ['group_count', 'group_sum', 'group_avg'], true)) {
            $orderBy ??= ['path' => 'value', 'direction' => 'DESC'];
            $having = $this->detectHaving($normalized);
        } elseif ($operation === 'list') {
            $orderBy = $this->defaultOrderBy($entity);
        }

        return [
            'entity' => $shortName,
            'operation' => $operation,
            'aggregateField' => $aggregateField,
            'select' => $entity['displayFields'] ?? [],
            'filters' => $filters,
            'groupBy' => $groupBy,
            'having' => $having,
            'orderBy' => $orderBy,
            'limit' => $limit,
            'format' => $format,
            'title' => $this->ruleTitle($shortName, $operation),
        ];
    }

    private function detectRuleEntity(string $question, string $normalized): ?array
    {
        if (
            preg_match('/\b(moyenne generale|moyennes generales|rang|rangs|bulletin|bulletins)\b/u', $normalized)
            || (
                preg_match('/\b(classement|top)\b/u', $normalized)
                && !preg_match('/\b(note|notes|matiere)\b/u', $normalized)
            )
        ) {
            return $this->catalog->getEntityByShortName('Report')
                ?? $this->catalog->getEntityByShortName('Evaluation');
        }

        if (preg_match('/\b(note|notes|moyenne|meilleur)\b/u', $normalized)) {
            return $this->catalog->getEntityByShortName('Evaluation');
        }

        if (preg_match('/\b(eleve|eleves|apprenant|apprenants|student|students|effectif)\b/u', $normalized)) {
            return $this->catalog->getEntityByShortName('Student');
        }

        if (preg_match('/\b(enseignant|enseignants|professeur|professeurs|personnel)\b/u', $normalized)) {
            return $this->catalog->getEntityByShortName('Teacher');
        }

        if (preg_match('/\b(depense|depenses|charge|charges|sortie caisse)\b/u', $normalized)) {
            return $this->catalog->getEntityByShortName('Depense');
        }

        if (preg_match('/\b(paiement|paiements|pension|scolarite|inscription|inscriptions)\b/u', $normalized)) {
            return $this->catalog->getEntityByShortName('Registration');
        }

        if (preg_match('/\b(absence enseignant|absences enseignants)\b/u', $normalized)) {
            return $this->catalog->getEntityByShortName('AbsenceTeacher');
        }

        if (preg_match('/\b(absence|absences)\b/u', $normalized)) {
            return $this->catalog->getEntityByShortName('Absence');
        }

        return $this->catalog->findEntityForQuestion($question);
    }

    private function detectOperation(string $normalized, string $entity): string
    {
        if (preg_match('/\b(combien|nombre|effectif|compte|total des|total d)\b/u', $normalized)) {
            return 'count';
        }

        if (preg_match('/\b(moyenne|average)\b/u', $normalized)) {
            return 'avg';
        }

        if (preg_match('/\b(plus grande|meilleure note|maximum|maximale)\b/u', $normalized)) {
            return 'max';
        }

        if (preg_match('/\b(plus petite|plus faible|minimum|minimale)\b/u', $normalized)) {
            return 'min';
        }

        $financialEntity = in_array($entity, ['Registration', 'Depense', 'EtatFinance', 'Fees'], true);
        if ($financialEntity && preg_match('/\b(somme|montant total|total paye|total)\b/u', $normalized)) {
            return 'sum';
        }

        return 'list';
    }

    private function aggregateField(array $entity, string $normalized, string $operation): ?string
    {
        if ($operation === 'count' || $operation === 'list') {
            return null;
        }

        if ($entity['shortName'] === 'Evaluation') {
            return 'mark';
        }

        $preferred = [];
        if (str_contains($normalized, 'apee')) {
            $preferred[] = 'apeeFees';
        }
        if (str_contains($normalized, 'informatique')) {
            $preferred[] = 'computerFees';
        }
        if (preg_match('/\b(pension|scolarite)\b/u', $normalized)) {
            $preferred[] = 'schoolFees';
        }
        if (preg_match('/\b(depense|montant)\b/u', $normalized)) {
            $preferred = array_merge($preferred, ['montant', 'amount']);
        }

        foreach ($preferred as $field) {
            if (isset($entity['fields'][$field])) {
                return $field;
            }
        }

        return $this->firstNumericField($entity);
    }

    private function detectFilters(string $question, string $normalized, string $entity): array
    {
        $filters = [];

        if (preg_match('/\b(fille|filles|feminin|female)\b/u', $normalized)) {
            $path = $this->sexPath($entity);
            if ($path !== null) {
                $filters[] = ['path' => $path, 'operator' => 'like', 'value' => 'F'];
            }
        }

        if (preg_match('/\b(garcon|garcons|masculin|male)\b/u', $normalized)) {
            $path = $this->sexPath($entity);
            if ($path !== null) {
                $filters[] = ['path' => $path, 'operator' => 'like', 'value' => 'M'];
            }
        }

        if (
            preg_match(
                '/\b(6e|6eme|5e|5eme|4e|4eme|3e|3eme|2nde|seconde|1ere|premiere|tle|terminale)(?:\s+[a-z0-9]+)?\b/ui',
                $question,
                $match
            )
        ) {
            $path = $this->classroomPath($entity);
            if ($path !== null) {
                $filters[] = [
                    'path' => $path,
                    'operator' => 'like',
                    'value' => trim($match[0]),
                ];
            }
        }

        $subjects = [
            'mathematique' => 'Math',
            'maths' => 'Math',
            'math' => 'Math',
            'chimie' => 'Chimie',
            'physique' => 'Physique',
            'francais' => 'Français',
            'anglais' => 'Anglais',
            'informatique' => 'Informatique',
            'histoire' => 'Histoire',
            'geographie' => 'Géographie',
        ];
        foreach ($subjects as $needle => $value) {
            if (!preg_match('/\b' . preg_quote($needle, '/') . '\b/u', $normalized)) {
                continue;
            }
            $path = $this->subjectPath($entity);
            if ($path !== null) {
                $filters[] = ['path' => $path, 'operator' => 'like', 'value' => $value];
            }
            break;
        }

        if (preg_match('/\b([1-3])(?:er|e|eme)?\s+trimestre\b/u', $normalized, $match)) {
            $path = $this->termPath($entity);
            if ($path !== null) {
                $filters[] = ['path' => $path, 'operator' => '=', 'value' => (int) $match[1]];
            }
        }

        if (preg_match('/\bsequence\s*([1-6])\b/u', $normalized, $match)) {
            $path = $this->sequencePath($entity);
            if ($path !== null) {
                $filters[] = ['path' => $path, 'operator' => '=', 'value' => (int) $match[1]];
            }
        }

        return $filters;
    }

    private function detectGroupBy(string $normalized, string $entity): ?string
    {
        $mapping = [
            '/\bpar classe\b/u' => $this->classroomPath($entity),
            '/\bpar (sexe|genre)\b/u' => $this->sexPath($entity),
            '/\bpar (matiere|discipline)\b/u' => $this->subjectPath($entity),
            '/\bpar (trimestre|term)\b/u' => $this->termPath($entity),
            '/\bpar sequence\b/u' => $this->sequencePath($entity),
        ];

        foreach ($mapping as $pattern => $path) {
            if ($path !== null && preg_match($pattern, $normalized)) {
                return $path;
            }
        }

        return null;
    }

    private function detectHaving(string $normalized): ?array
    {
        if (
            preg_match(
                '/\b(?:moyenne|valeur)?\s*(superieure ou egale|au moins|>=|superieure|plus de|>)\s*(\d+(?:[.,]\d+)?)\b/u',
                $normalized,
                $match
            )
        ) {
            $operator = in_array($match[1], ['superieure ou egale', 'au moins', '>='], true) ? '>=' : '>';

            return ['operator' => $operator, 'value' => (float) str_replace(',', '.', $match[2])];
        }

        if (
            preg_match(
                '/\b(?:moyenne|valeur)?\s*(inferieure ou egale|au plus|<=|inferieure|moins de|<)\s*(\d+(?:[.,]\d+)?)\b/u',
                $normalized,
                $match
            )
        ) {
            $operator = in_array($match[1], ['inferieure ou egale', 'au plus', '<='], true) ? '<=' : '<';

            return ['operator' => $operator, 'value' => (float) str_replace(',', '.', $match[2])];
        }

        return null;
    }

    private function normalizePlan(array $plan, string $question, string $format, string $source): array
    {
        $entityName = trim((string) ($plan['entity'] ?? ''));
        if ($entityName === '' || $this->catalog->getEntityByShortName($entityName) === null) {
            throw new RuntimeException("L'IA n'a pas identifié une donnée scolaire autorisée.");
        }

        $operation = strtolower(trim((string) ($plan['operation'] ?? 'list')));
        if (!in_array($operation, self::OPERATIONS, true)) {
            throw new RuntimeException("L'opération demandée n'est pas autorisée.");
        }

        $select = array_values(array_filter(
            is_array($plan['select'] ?? null) ? $plan['select'] : [],
            static fn ($path) => is_string($path) && trim($path) !== ''
        ));
        $filters = array_values(array_filter(
            is_array($plan['filters'] ?? null) ? $plan['filters'] : [],
            static fn ($filter) => is_array($filter) && is_string($filter['path'] ?? null)
        ));

        return [
            'question' => $question,
            'entity' => $entityName,
            'operation' => $operation,
            'aggregateField' => is_string($plan['aggregateField'] ?? null)
                ? trim($plan['aggregateField'])
                : null,
            'select' => array_slice($select, 0, 12),
            'filters' => array_slice($filters, 0, 12),
            'groupBy' => is_string($plan['groupBy'] ?? null) ? trim($plan['groupBy']) : null,
            'having' => is_array($plan['having'] ?? null) ? $plan['having'] : null,
            'orderBy' => is_array($plan['orderBy'] ?? null) ? $plan['orderBy'] : null,
            'limit' => max(1, min((int) ($plan['limit'] ?? 100), 500)),
            'format' => $format,
            'title' => trim((string) ($plan['title'] ?? 'Résultat FreeSchool AI')),
            'source' => $source,
        ];
    }

    /**
     * Supprime toute année éventuellement proposée par la question ou le
     * modèle, puis réinjecte exclusivement l'année de la session.
     */
    private function enforceSelectedSchoolYear(array $plan, ?string $selectedSchoolYear): array
    {
        if ($selectedSchoolYear === null || trim($selectedSchoolYear) === '') {
            throw new RuntimeException(
                "Aucune année scolaire active n'a été trouvée. Reconnectez-vous en choisissant une année."
            );
        }

        $plan['filters'] = array_values(array_filter(
            $plan['filters'],
            function (array $filter): bool {
                $path = str_replace(
                    ['_', ' '],
                    '',
                    $this->catalog->normalize((string) ($filter['path'] ?? ''))
                );

                return !str_contains($path, 'schoolyear') && !str_contains($path, 'anneescolaire');
            }
        ));

        $scopePath = $this->catalog->schoolYearScopePath((string) $plan['entity']);
        if ($scopePath !== null) {
            $yearLabelPath = $scopePath === 'id' ? 'schoolYear' : $scopePath . '.schoolYear';
            $plan['filters'][] = [
                'path' => $yearLabelPath,
                'operator' => '=',
                'value' => trim($selectedSchoolYear),
                'context' => true,
            ];
        }

        $plan['schoolYear'] = trim($selectedSchoolYear);

        return $plan;
    }

    private function normalizeFormat(string $format): string
    {
        $format = strtolower(trim($format));

        return in_array($format, self::FORMATS, true) ? $format : 'html';
    }

    private function detectFormat(string $question): string
    {
        $normalized = $this->catalog->normalize($question);
        if (str_contains($normalized, 'pdf')) {
            return 'pdf';
        }
        if (str_contains($normalized, 'word') || str_contains($normalized, 'docx')) {
            return 'docx';
        }
        if (str_contains($normalized, 'excel') || str_contains($normalized, 'xlsx')) {
            return 'xlsx';
        }

        return 'html';
    }

    private function firstNumericField(array $entity): ?string
    {
        foreach ($entity['fields'] as $field => $info) {
            if (
                in_array($info['type'], ['integer', 'bigint', 'smallint', 'float', 'decimal'], true)
                && $field !== 'id'
            ) {
                return $field;
            }
        }

        return null;
    }

    private function defaultOrderBy(array $entity): ?array
    {
        foreach (['fullName', 'name', 'classroom', 'subject', 'createdAt', 'id'] as $field) {
            if (isset($entity['fields'][$field])) {
                return ['path' => $field, 'direction' => $field === 'createdAt' ? 'DESC' : 'ASC'];
            }
        }

        return null;
    }

    private function detectTopLimit(string $normalized): ?int
    {
        if (preg_match('/\btop\s*(\d{1,3})\b/u', $normalized, $match)) {
            return max(1, min((int) $match[1], 100));
        }

        return null;
    }

    private function ruleTitle(string $entity, string $operation): string
    {
        $labels = [
            'Student' => 'Élèves',
            'Teacher' => 'Enseignants',
            'Evaluation' => 'Notes et moyennes',
            'Report' => 'Moyennes générales et rangs',
            'Registration' => 'Paiements scolaires',
            'Depense' => 'Dépenses',
            'Absence' => 'Absences des élèves',
            'AbsenceTeacher' => 'Absences des enseignants',
            'Classroom' => 'Classes',
            'Subject' => 'Matières',
        ];
        $prefix = str_starts_with($operation, 'group_') ? 'Analyse par groupe — ' : '';

        return $prefix . ($labels[$entity] ?? 'Résultat scolaire');
    }

    private function sexPath(string $entity): ?string
    {
        return match ($entity) {
            'Student', 'Teacher' => 'sex.sex',
            'Evaluation', 'Registration', 'Absence', 'Conseil', 'Report' => 'student.sex.sex',
            'AbsenceTeacher', 'HistoriqueTeacher' => 'teacher.sex.sex',
            default => null,
        };
    }

    private function classroomPath(string $entity): ?string
    {
        return match ($entity) {
            'Student' => 'classroom.classroom',
            'Classroom' => 'classroom',
            'Evaluation', 'Registration', 'Absence', 'Conseil', 'Report' => 'student.classroom.classroom',
            'Lesson', 'Progress', 'TimeTable' => 'classroom.classroom',
            default => null,
        };
    }

    private function subjectPath(string $entity): ?string
    {
        return match ($entity) {
            'Evaluation' => 'lesson.subject.subject',
            'Lesson', 'Progress', 'TimeTable' => 'subject.subject',
            'Subject' => 'subject',
            default => null,
        };
    }

    private function termPath(string $entity): ?string
    {
        return match ($entity) {
            'Evaluation' => 'sequence.term.term',
            'Absence', 'Conseil', 'Report' => 'term.term',
            'Sequence' => 'term.term',
            'Term' => 'term',
            default => null,
        };
    }

    private function sequencePath(string $entity): ?string
    {
        return match ($entity) {
            'Evaluation' => 'sequence.sequence',
            'Sequence' => 'sequence',
            default => null,
        };
    }
}

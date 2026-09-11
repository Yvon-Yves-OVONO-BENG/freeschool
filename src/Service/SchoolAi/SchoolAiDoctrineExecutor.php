<?php

namespace App\Service\SchoolAi;

use App\Entity\SchoolYear;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * Exécuteur Doctrine strictement en lecture seule.
 *
 * La portée année scolaire est ajoutée ici avec l'identifiant de la session,
 * indépendamment du plan produit par le modèle. Elle ne peut donc pas être
 * retirée ou remplacée par une question utilisateur.
 */
class SchoolAiDoctrineExecutor
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

    private const FILTER_OPERATORS = [
        '=',
        '!=',
        'like',
        '>',
        '<',
        '>=',
        '<=',
        'between',
        'in',
        'is_null',
        'is_not_null',
    ];

    private EntityManagerInterface $em;

    public function __construct(ManagerRegistry $registry, private SchoolAiSchemaCatalog $catalog)
    {
        $this->em = $registry->getManager();
    }

    public function execute(array $plan, SchoolYear $selectedSchoolYear): array
    {
        $entity = $this->catalog->getEntityByShortName((string) ($plan['entity'] ?? ''));
        if ($entity === null) {
            throw new RuntimeException("La donnée demandée n'est pas autorisée.");
        }

        $operation = strtolower((string) ($plan['operation'] ?? 'list'));
        if (!in_array($operation, self::OPERATIONS, true)) {
            throw new RuntimeException("L'opération demandée n'est pas autorisée.");
        }

        if ($selectedSchoolYear->getId() === null) {
            throw new RuntimeException("L'année scolaire de connexion n'est pas valide.");
        }

        $rootClass = $entity['class'];
        $qb = $this->em->createQueryBuilder()->from($rootClass, 'e');
        $joins = [];

        $this->applyMandatorySchoolYearScope(
            $qb,
            $rootClass,
            $entity['shortName'],
            $selectedSchoolYear,
            $joins
        );
        $this->applyFilters($qb, $rootClass, $plan['filters'] ?? [], $joins);

        if (in_array($operation, ['count', 'sum', 'avg', 'min', 'max'], true)) {
            $this->applyAggregateSelect($qb, $rootClass, $operation, $plan, $joins);
            $rows = $this->normalizeRows($qb->getQuery()->getScalarResult());

            return [
                'type' => 'aggregate',
                'rows' => $rows,
                'rowCount' => count($rows),
                'plan' => $plan,
            ];
        }

        if (in_array($operation, ['group_count', 'group_sum', 'group_avg'], true)) {
            $aggregateExpression = $this->applyGroupSelect($qb, $rootClass, $operation, $plan, $joins);
            $this->applyHaving($qb, $aggregateExpression, $plan['having'] ?? null);
            $this->applyGroupOrder($qb, $plan['orderBy'] ?? null);
            $qb->setMaxResults($this->safeLimit($plan));
            $rows = $this->normalizeRows($qb->getQuery()->getScalarResult());

            return [
                'type' => 'group',
                'rows' => $rows,
                'rowCount' => count($rows),
                'plan' => $plan,
            ];
        }

        $select = is_array($plan['select'] ?? null) && $plan['select'] !== []
            ? $plan['select']
            : ($entity['displayFields'] ?? ['id']);
        $this->applyListSelect($qb, $rootClass, $select, $joins);
        $this->applyListOrder($qb, $rootClass, $plan['orderBy'] ?? null, $joins);
        $qb->setMaxResults($this->safeLimit($plan));
        $rows = $this->normalizeRows($qb->getQuery()->getScalarResult());

        return [
            'type' => 'list',
            'rows' => $rows,
            'rowCount' => count($rows),
            'plan' => $plan,
        ];
    }

    private function applyMandatorySchoolYearScope(
        QueryBuilder $qb,
        string $rootClass,
        string $entityName,
        SchoolYear $schoolYear,
        array &$joins
    ): void {
        $scopePath = $this->catalog->schoolYearScopePath($entityName);
        if ($scopePath === null) {
            return;
        }

        $resolved = $this->resolvePath($rootClass, $scopePath, $qb, $joins);
        $qb->andWhere($resolved['dql'] . ' = :__schoolAiYearId')
            ->setParameter('__schoolAiYearId', $schoolYear->getId());
    }

    private function applyFilters(QueryBuilder $qb, string $rootClass, array $filters, array &$joins): void
    {
        foreach (array_slice($filters, 0, 12) as $index => $filter) {
            if (!is_array($filter) || trim((string) ($filter['path'] ?? '')) === '') {
                continue;
            }

            $operator = strtolower(trim((string) ($filter['operator'] ?? '=')));
            if (!in_array($operator, self::FILTER_OPERATORS, true)) {
                throw new RuntimeException('Opérateur de filtre non autorisé.');
            }

            $resolved = $this->resolvePath(
                $rootClass,
                trim((string) $filter['path']),
                $qb,
                $joins
            );
            $parameter = '__schoolAiFilter' . $index;
            $value = $this->normalizeValue($filter['value'] ?? null, $resolved['type']);

            if ($operator === 'is_null') {
                $qb->andWhere($resolved['dql'] . ' IS NULL');
                continue;
            }
            if ($operator === 'is_not_null') {
                $qb->andWhere($resolved['dql'] . ' IS NOT NULL');
                continue;
            }
            if ($operator === 'like') {
                $qb->andWhere('LOWER(' . $resolved['dql'] . ') LIKE :' . $parameter)
                    ->setParameter($parameter, '%' . mb_strtolower((string) $value) . '%');
                continue;
            }
            if ($operator === 'in') {
                if (!is_array($value) || $value === []) {
                    $qb->andWhere('1 = 0');
                    continue;
                }
                $qb->andWhere($resolved['dql'] . ' IN (:' . $parameter . ')')
                    ->setParameter($parameter, array_slice($value, 0, 100));
                continue;
            }
            if ($operator === 'between') {
                if (!is_array($value) || count($value) < 2) {
                    throw new RuntimeException('Le filtre "between" attend deux valeurs.');
                }
                $qb->andWhere(
                    $resolved['dql'] . ' BETWEEN :' . $parameter . 'a AND :' . $parameter . 'b'
                )
                    ->setParameter($parameter . 'a', $value[0])
                    ->setParameter($parameter . 'b', $value[1]);
                continue;
            }

            $qb->andWhere($resolved['dql'] . ' ' . $operator . ' :' . $parameter)
                ->setParameter($parameter, $value);
        }
    }

    private function applyAggregateSelect(
        QueryBuilder $qb,
        string $rootClass,
        string $operation,
        array $plan,
        array &$joins
    ): void {
        if ($operation === 'count') {
            $qb->select('COUNT(DISTINCT e.id) AS value');

            return;
        }

        $field = trim((string) ($plan['aggregateField'] ?? ''));
        if ($field === '') {
            throw new RuntimeException("Aucun champ numérique n'a été identifié pour ce calcul.");
        }

        $resolved = $this->resolvePath($rootClass, $field, $qb, $joins);
        $this->assertNumericType($resolved['type']);
        $qb->select(strtoupper($operation) . '(' . $resolved['dql'] . ') AS value');
    }

    private function applyGroupSelect(
        QueryBuilder $qb,
        string $rootClass,
        string $operation,
        array $plan,
        array &$joins
    ): string {
        $groupPath = trim((string) ($plan['groupBy'] ?? ''));
        if ($groupPath === '') {
            throw new RuntimeException("Aucun regroupement n'a été identifié.");
        }

        $group = $this->resolvePath($rootClass, $groupPath, $qb, $joins);
        $qb->select($group['dql'] . ' AS label')->groupBy($group['dql']);

        if ($operation === 'group_count') {
            $expression = 'COUNT(DISTINCT e.id)';
            $qb->addSelect($expression . ' AS value');

            return $expression;
        }

        $field = trim((string) ($plan['aggregateField'] ?? ''));
        if ($field === '') {
            throw new RuntimeException("Aucun champ numérique n'a été identifié pour ce regroupement.");
        }

        $metric = $this->resolvePath($rootClass, $field, $qb, $joins);
        $this->assertNumericType($metric['type']);
        $function = $operation === 'group_sum' ? 'SUM' : 'AVG';
        $expression = $function . '(' . $metric['dql'] . ')';
        $qb->addSelect($expression . ' AS value');

        return $expression;
    }

    private function applyHaving(QueryBuilder $qb, string $aggregateExpression, mixed $having): void
    {
        if (!is_array($having)) {
            return;
        }

        $operator = trim((string) ($having['operator'] ?? ''));
        if (!in_array($operator, ['=', '!=', '>', '<', '>=', '<='], true)) {
            throw new RuntimeException('Opérateur de seuil non autorisé.');
        }

        $value = $having['value'] ?? null;
        if (!is_numeric($value)) {
            throw new RuntimeException('Le seuil du regroupement doit être numérique.');
        }

        $qb->having($aggregateExpression . ' ' . $operator . ' :__schoolAiHaving')
            ->setParameter('__schoolAiHaving', (float) $value);
    }

    private function applyListSelect(QueryBuilder $qb, string $rootClass, array $select, array &$joins): void
    {
        $expressions = [];
        $usedAliases = [];

        foreach (array_slice($select, 0, 12) as $path) {
            if (!is_string($path) || trim($path) === '') {
                continue;
            }

            $path = trim($path);
            $resolved = $this->resolvePath($rootClass, $path, $qb, $joins);
            $baseAlias = trim((string) preg_replace('/[^a-zA-Z0-9_]/', '_', $path), '_');
            $baseAlias = $baseAlias !== '' ? $baseAlias : 'value';
            $alias = $baseAlias;
            $suffix = 2;

            while (isset($usedAliases[$alias])) {
                $alias = $baseAlias . '_' . $suffix++;
            }
            $usedAliases[$alias] = true;
            $expressions[] = $resolved['dql'] . ' AS ' . $alias;
        }

        if ($expressions === []) {
            $expressions[] = 'e.id AS id';
        }

        $qb->distinct()->select(implode(', ', $expressions));
    }

    private function applyListOrder(
        QueryBuilder $qb,
        string $rootClass,
        mixed $orderBy,
        array &$joins
    ): void {
        if (!is_array($orderBy) || trim((string) ($orderBy['path'] ?? '')) === '') {
            return;
        }

        $resolved = $this->resolvePath(
            $rootClass,
            trim((string) $orderBy['path']),
            $qb,
            $joins
        );
        $qb->orderBy($resolved['dql'], $this->direction($orderBy['direction'] ?? 'ASC'));
    }

    private function applyGroupOrder(QueryBuilder $qb, mixed $orderBy): void
    {
        if (!is_array($orderBy)) {
            return;
        }

        $path = strtolower(trim((string) ($orderBy['path'] ?? '')));
        if (!in_array($path, ['label', 'value'], true)) {
            throw new RuntimeException('Le tri du regroupement doit utiliser "label" ou "value".');
        }

        $qb->orderBy($path, $this->direction($orderBy['direction'] ?? 'ASC'));
    }

    private function resolvePath(string $rootClass, string $path, QueryBuilder $qb, array &$joins): array
    {
        $this->catalog->assertPathAllowed($rootClass, $path);
        $segments = explode('.', $path);
        $currentClass = $rootClass;
        $alias = 'e';

        foreach ($segments as $index => $segment) {
            $metadata = $this->em->getClassMetadata($currentClass);
            $last = $index === count($segments) - 1;

            if ($metadata->hasAssociation($segment)) {
                $joinKey = $alias . '_' . $segment;
                if (!isset($joins[$joinKey])) {
                    $joinAlias = 'j' . count($joins);
                    $qb->leftJoin($alias . '.' . $segment, $joinAlias);
                    $joins[$joinKey] = $joinAlias;
                }

                $alias = $joins[$joinKey];
                $currentClass = $metadata->getAssociationTargetClass($segment);
                if ($last) {
                    return [
                        'dql' => $alias . '.id',
                        'class' => $currentClass,
                        'field' => 'id',
                        'type' => 'integer',
                    ];
                }
                continue;
            }

            if ($metadata->hasField($segment) && $last) {
                return [
                    'dql' => $alias . '.' . $segment,
                    'class' => $currentClass,
                    'field' => $segment,
                    'type' => $metadata->getTypeOfField($segment),
                ];
            }

            throw new RuntimeException('Chemin de donnée invalide.');
        }

        throw new RuntimeException('Chemin de donnée vide.');
    }

    private function assertNumericType(string $type): void
    {
        if (!in_array($type, ['integer', 'bigint', 'smallint', 'float', 'decimal'], true)) {
            throw new RuntimeException('Le calcul demandé doit utiliser un champ numérique.');
        }
    }

    private function normalizeValue(mixed $value, string $type): mixed
    {
        if (is_array($value)) {
            return array_map(fn ($item) => $this->normalizeValue($item, $type), $value);
        }

        return match ($type) {
            'integer', 'smallint', 'bigint' => is_numeric($value) ? (int) $value : $value,
            'float', 'decimal' => is_numeric($value) ? (float) $value : $value,
            'boolean' => $this->normalizeBoolean($value),
            default => $value,
        };
    }

    private function normalizeBoolean(mixed $value): mixed
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = strtolower(trim((string) $value));
        if (in_array($normalized, ['1', 'true', 'oui', 'yes'], true)) {
            return true;
        }
        if (in_array($normalized, ['0', 'false', 'non', 'no'], true)) {
            return false;
        }

        return $value;
    }

    private function normalizeRows(array $rows): array
    {
        foreach ($rows as &$row) {
            foreach ($row as &$value) {
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:s');
                } elseif (is_bool($value)) {
                    $value = $value ? 'Oui' : 'Non';
                }
            }
            unset($value);
        }
        unset($row);

        return $rows;
    }

    private function safeLimit(array $plan): int
    {
        return max(1, min((int) ($plan['limit'] ?? 100), 500));
    }

    private function direction(mixed $direction): string
    {
        return strtoupper((string) $direction) === 'DESC' ? 'DESC' : 'ASC';
    }
}

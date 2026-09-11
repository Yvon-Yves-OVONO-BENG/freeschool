<?php

namespace App\Service\SchoolAi;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * Catalogue en lecture seule des entités et chemins Doctrine autorisés.
 *
 * Ce service constitue la liste blanche partagée par le prompt et l'exécuteur.
 * Une proposition du modèle ne peut donc jamais accéder à User, aux secrets ou
 * à une relation collection arbitraire.
 */
class SchoolAiSchemaCatalog
{
    private EntityManagerInterface $em;

    /** @var array<string, array>|null */
    private ?array $catalog = null;

    private array $excludedEntities = [
        'App\\Entity\\User' => true,
        'App\\Entity\\UserLog' => true,
        'App\\Entity\\Browser' => true,
        'App\\Entity\\DeviceType' => true,
        'App\\Entity\\OperatingSystem' => true,
        'App\\Entity\\AiQueryHistory' => true,
        'App\\Entity\\QuestionSecrete' => true,
        'App\\Entity\\ReponseQuestion' => true,
        'App\\Entity\\NextYear' => true,
        'App\\Entity\\Verrou' => true,
        'App\\Entity\\VerrouInsolvable' => true,
        'App\\Entity\\VerrouReport' => true,
        'App\\Entity\\VerrouSequence' => true,
    ];

    public function __construct(ManagerRegistry $registry)
    {
        $this->em = $registry->getManager();
    }

    public function getCatalog(): array
    {
        if ($this->catalog !== null) {
            return $this->catalog;
        }

        $items = [];
        foreach ($this->em->getMetadataFactory()->getAllMetadata() as $metadata) {
            /** @var ClassMetadata $metadata */
            $class = $metadata->getName();
            if (!$this->isClassAllowed($class)) {
                continue;
            }

            $short = $metadata->getReflectionClass()->getShortName();
            $fields = [];

            foreach ($metadata->getFieldNames() as $field) {
                if (!$this->isFieldAllowed($field)) {
                    continue;
                }

                $fields[$field] = [
                    'type' => $metadata->getTypeOfField($field),
                    'column' => $metadata->getColumnName($field),
                    'aliases' => $this->fieldAliases($field),
                ];
            }

            $associations = [];
            foreach ($metadata->getAssociationNames() as $association) {
                $target = $metadata->getAssociationTargetClass($association);
                if (!$this->isClassAllowed($target) || !$metadata->isSingleValuedAssociation($association)) {
                    continue;
                }

                $associations[$association] = [
                    'target' => $target,
                    'targetShortName' => substr($target, strrpos($target, '\\') + 1),
                    'aliases' => $this->fieldAliases($association),
                ];
            }

            $items[$class] = [
                'class' => $class,
                'shortName' => $short,
                'key' => $this->normalize($short),
                'aliases' => array_values(array_unique($this->defaultAliases($short))),
                'fields' => $fields,
                'associations' => $associations,
                'displayFields' => $this->displayFields($short, $fields, $associations),
            ];
        }

        return $this->catalog = $items;
    }

    public function findEntityForQuestion(string $question): ?array
    {
        $normalizedQuestion = ' ' . $this->normalize($question) . ' ';
        $best = null;
        $bestScore = 0;

        foreach ($this->getCatalog() as $entity) {
            $score = $this->scoreEntity($entity, $normalizedQuestion);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $entity;
            }
        }

        return $bestScore > 0 ? $best : null;
    }

    public function getEntityByShortName(string $name): ?array
    {
        $needle = $this->normalize($name);
        foreach ($this->getCatalog() as $entity) {
            if ($this->normalize($entity['shortName']) === $needle || $entity['key'] === $needle) {
                return $entity;
            }
        }

        return null;
    }

    public function normalize(string $value): string
    {
        $value = preg_replace('/([a-z])([A-Z])/', '$1 $2', trim($value)) ?? $value;
        $value = mb_strtolower($value);
        $value = strtr($value, [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            'œ' => 'oe',
        ]);
        $value = preg_replace('/[^a-z0-9]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    /**
     * Envoie au modèle uniquement les entités utiles à la question, avec un
     * socle scolaire commun. Cela réduit le coût et améliore la précision.
     */
    public function schemaSummaryForQuestion(string $question, int $maxEntities = 32): string
    {
        $catalog = array_values($this->getCatalog());
        $normalizedQuestion = ' ' . $this->normalize($question) . ' ';
        $core = [
            'Student',
            'Evaluation',
            'Classroom',
            'Teacher',
            'Lesson',
            'Subject',
            'Registration',
            'Absence',
            'AbsenceTeacher',
            'Report',
            'SchoolYear',
            'Sequence',
            'Term',
            'Sex',
        ];

        usort($catalog, function (array $left, array $right) use ($normalizedQuestion, $core): int {
            $leftScore = $this->scoreEntity($left, $normalizedQuestion)
                + (in_array($left['shortName'], $core, true) ? 2 : 0);
            $rightScore = $this->scoreEntity($right, $normalizedQuestion)
                + (in_array($right['shortName'], $core, true) ? 2 : 0);

            return $rightScore <=> $leftScore;
        });

        $lines = [];
        foreach (array_slice($catalog, 0, max(1, min($maxEntities, 50))) as $entity) {
            $fields = [];
            foreach ($entity['fields'] as $name => $info) {
                $fields[] = $name . ':' . $info['type'];
            }

            $relations = [];
            foreach ($entity['associations'] as $name => $association) {
                $relations[] = $name . '->' . $association['targetShortName'];
            }

            $lines[] = sprintf(
                '%s | champs=[%s] | relations_vers_un=[%s]',
                $entity['shortName'],
                implode(', ', $fields),
                implode(', ', $relations)
            );
        }

        return implode("\n", $lines);
    }

    public function schemaSummary(int $maxEntities = 80): string
    {
        return $this->schemaSummaryForQuestion('', $maxEntities);
    }

    /**
     * Retourne un chemin se terminant par SchoolYear (ou id pour SchoolYear).
     * Seules les relations ManyToOne/OneToOne sont traversées.
     */
    public function schoolYearScopePath(string $entityName): ?string
    {
        $entity = $this->getEntityByShortName($entityName);
        if ($entity === null) {
            return null;
        }

        if ($entity['shortName'] === 'SchoolYear') {
            return 'id';
        }

        $rootClass = $entity['class'];
        $queue = [[$rootClass, '', 0]];
        $visited = [$rootClass => true];

        while ($queue !== []) {
            [$class, $prefix, $depth] = array_shift($queue);
            $metadata = $this->em->getClassMetadata($class);

            foreach ($metadata->getAssociationNames() as $association) {
                if (!$metadata->isSingleValuedAssociation($association)) {
                    continue;
                }

                $target = $metadata->getAssociationTargetClass($association);
                if (!$this->isClassAllowed($target)) {
                    continue;
                }

                $path = $prefix === '' ? $association : $prefix . '.' . $association;
                if ($target === 'App\\Entity\\SchoolYear') {
                    return $path;
                }

                if ($depth < 3 && !isset($visited[$target])) {
                    $visited[$target] = true;
                    $queue[] = [$target, $path, $depth + 1];
                }
            }
        }

        return null;
    }

    /**
     * Valide un chemin avant même que Doctrine ne construise le DQL.
     */
    public function assertPathAllowed(string $rootClass, string $path): void
    {
        $segments = array_values(array_filter(explode('.', trim($path)), static fn ($v) => $v !== ''));
        if ($segments === [] || count($segments) > 6 || !$this->isClassAllowed($rootClass)) {
            throw new RuntimeException('Chemin de donnée non autorisé.');
        }

        $currentClass = $rootClass;
        foreach ($segments as $index => $segment) {
            $metadata = $this->em->getClassMetadata($currentClass);
            $last = $index === count($segments) - 1;

            if ($metadata->hasAssociation($segment)) {
                if (!$metadata->isSingleValuedAssociation($segment)) {
                    throw new RuntimeException('Les relations multiples ne sont pas autorisées dans une question IA.');
                }

                $target = $metadata->getAssociationTargetClass($segment);
                if (!$this->isClassAllowed($target)) {
                    throw new RuntimeException('Cette relation est protégée.');
                }

                $currentClass = $target;
                continue;
            }

            if ($metadata->hasField($segment) && $last && $this->isFieldAllowed($segment)) {
                return;
            }

            throw new RuntimeException('Champ ou relation non autorisé : ' . $path);
        }
    }

    public function isClassAllowed(string $class): bool
    {
        return str_starts_with($class, 'App\\Entity\\') && !isset($this->excludedEntities[$class]);
    }

    public function isFieldAllowed(string $field): bool
    {
        $normalized = strtolower($field);

        foreach (['password', 'token', 'secret', 'credential', 'salt'] as $needle) {
            if (str_contains($normalized, $needle)) {
                return false;
            }
        }

        return !in_array($normalized, ['plainpassword', 'reponse'], true);
    }

    private function scoreEntity(array $entity, string $normalizedQuestion): int
    {
        $score = 0;
        foreach ($entity['aliases'] as $alias) {
            if ($alias !== '' && str_contains($normalizedQuestion, ' ' . $alias . ' ')) {
                $score += mb_strlen($alias) > 4 ? 10 : 4;
            }
        }

        foreach ($entity['fields'] as $info) {
            foreach ($info['aliases'] as $alias) {
                if ($alias !== '' && str_contains($normalizedQuestion, ' ' . $alias . ' ')) {
                    $score += 2;
                }
            }
        }

        return $score;
    }

    private function defaultAliases(string $shortName): array
    {
        $normalized = $this->normalize($shortName);
        $compact = str_replace(' ', '', $normalized);
        $map = [
            'student' => ['student', 'students', 'eleve', 'eleves', 'apprenant', 'apprenants'],
            'classroom' => ['classroom', 'classrooms', 'classe', 'classes', 'salle de classe'],
            'evaluation' => ['evaluation', 'evaluations', 'note', 'notes', 'mark', 'marks', 'moyenne'],
            'report' => ['bulletin', 'bulletins', 'moyenne generale', 'moyennes generales', 'rang', 'classement'],
            'lesson' => ['lesson', 'cours', 'enseignement', 'lecon', 'matiere enseignee'],
            'subject' => ['subject', 'subjects', 'matiere', 'matieres', 'discipline'],
            'teacher' => ['teacher', 'teachers', 'enseignant', 'enseignants', 'professeur', 'professeurs', 'personnel'],
            'absence' => ['absence eleve', 'absences eleves', 'absence', 'absences'],
            'absenceteacher' => ['absence enseignant', 'absences enseignants'],
            'registration' => ['registration', 'inscription', 'inscriptions', 'paiement', 'paiements', 'pension', 'scolarite'],
            'fees' => ['fees', 'frais exigibles', 'frais', 'pension', 'scolarite'],
            'depense' => ['depense', 'depenses', 'sortie caisse', 'charges'],
            'etatfinance' => ['etat financier', 'finance', 'finances', 'recette'],
            'schoolyear' => ['school year', 'annee scolaire', 'annees scolaires', 'annee'],
            'term' => ['term', 'trimestre', 'trimestres'],
            'sequence' => ['sequence', 'sequences'],
            'sex' => ['sex', 'sexe', 'genre'],
            'level' => ['level', 'niveau', 'niveaux'],
            'cycle' => ['cycle', 'cycles'],
            'department' => ['department', 'departement', 'departements'],
            'conseil' => ['conseil', 'conseils de classe', 'decision conseil'],
            'progress' => ['progression', 'couverture programme', 'programme couvert'],
            'diploma' => ['diplome', 'diplomes'],
            'grade' => ['grade', 'grades'],
            'timetable' => ['emploi du temps', 'horaire', 'horaires'],
        ];

        return array_merge([$normalized, $compact], $map[$compact] ?? []);
    }

    private function fieldAliases(string $field): array
    {
        $normalized = $this->normalize($field);
        $compact = str_replace(' ', '', $normalized);
        $aliases = [$normalized, $compact];
        $map = [
            'fullname' => ['nom', 'nom complet', 'eleve', 'enseignant', 'full name'],
            'firstname' => ['prenom'],
            'lastname' => ['nom'],
            'mark' => ['note', 'notes', 'moyenne', 'point', 'points'],
            'moyenne' => ['moyenne generale', 'moyenne', 'average'],
            'rang' => ['rang', 'classement', 'position'],
            'montant' => ['montant', 'prix', 'somme', 'depense'],
            'amount' => ['montant', 'prix', 'somme'],
            'schoolfees' => ['pension', 'frais scolarite', 'scolarite'],
            'apeefees' => ['apee', 'frais apee'],
            'computerfees' => ['frais informatique', 'informatique'],
            'paidamount' => ['montant paye', 'paye', 'versement'],
            'remainingamount' => ['reste', 'impaye', 'solde'],
            'schoolyear' => ['annee scolaire', 'annee'],
            'classroom' => ['classe'],
            'subject' => ['matiere'],
            'sex' => ['sexe', 'genre', 'fille', 'garcon'],
            'createdat' => ['date creation', 'cree le', 'date'],
            'updatedat' => ['date modification'],
            'birthday' => ['date naissance', 'age'],
            'registrationnumber' => ['matricule'],
            'absence' => ['nombre absence', 'heures absence'],
            'absenceteacher' => ['nombre absence enseignant'],
            'coefficient' => ['coefficient', 'coef'],
            'weekhours' => ['heures semaine', 'volume horaire'],
        ];

        return array_values(array_unique(array_merge($aliases, $map[$compact] ?? [])));
    }

    private function displayFields(string $shortName, array $fields, array $associations): array
    {
        $preferredByEntity = [
            'Student' => ['fullName', 'registrationNumber', 'classroom.classroom', 'sex.sex'],
            'Teacher' => ['fullName', 'administrativeNumber', 'sex.sex', 'duty.duty'],
            'Evaluation' => [
                'student.fullName',
                'student.classroom.classroom',
                'lesson.subject.subject',
                'sequence.sequence',
                'mark',
            ],
            'Report' => [
                'student.fullName',
                'student.classroom.classroom',
                'term.term',
                'moyenne',
                'rang',
            ],
            'Registration' => ['student.fullName', 'student.classroom.classroom', 'schoolFees', 'apeeFees', 'computerFees'],
            'Absence' => ['student.fullName', 'student.classroom.classroom', 'term.term', 'absence'],
            'AbsenceTeacher' => ['teacher.fullName', 'absenceTeacher'],
            'Classroom' => ['classroom'],
            'Lesson' => ['teacher.fullName', 'subject.subject', 'classroom.classroom', 'coefficient', 'weekHours'],
            'Subject' => ['subject'],
            'Depense' => ['motif', 'montant', 'createdAt'],
            'School' => ['frenchName', 'englishName', 'place', 'telephone', 'email'],
        ];

        $result = [];
        foreach (($preferredByEntity[$shortName] ?? []) as $path) {
            $root = explode('.', $path)[0];
            if (isset($fields[$root]) || isset($associations[$root])) {
                $result[] = $path;
            }
        }

        foreach (['fullName', 'name', 'frenchName', 'englishName', 'classroom', 'subject', 'mark', 'montant', 'amount', 'createdAt'] as $field) {
            if (isset($fields[$field]) && !in_array($field, $result, true)) {
                $result[] = $field;
            }
        }

        foreach (array_keys($fields) as $field) {
            if (count($result) >= 8) {
                break;
            }
            if (!in_array($field, $result, true) && $field !== 'id') {
                $result[] = $field;
            }
        }

        return $result !== [] ? $result : ['id'];
    }
}

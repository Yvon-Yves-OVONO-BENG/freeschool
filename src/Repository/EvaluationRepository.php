<?php

namespace App\Repository;

use App\Entity\Term;
use App\Entity\Lesson;
use App\Entity\Student;
use App\Entity\Subject;
use App\Entity\Sequence;
use App\Entity\Classroom;
use App\Entity\Cycle;
use App\Entity\SubSystem;
use App\Entity\Evaluation;
use App\Entity\Level;
use App\Entity\School;
use App\Entity\SchoolYear;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Evaluation>
 *
 * @method Evaluation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Evaluation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Evaluation[]    findAll()
 * @method Evaluation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Evaluation::class);
    }

    public function save(Evaluation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Evaluation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Fonction qui retourne les 5 meilleurs élèves par matière
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Term $term
     * @return array
     */
    public function findSchoolTopFiveStudentsSubject(SchoolYear $schoolYear, SubSystem $subSystem, Subject $subject): array 
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('(SUM(e.mark)/6) AS moyenne')
                ->from(Evaluation::class, 'e')
                ->innerJoin(Student::class, 'st')
                ->addSelect('st')
                ->andWhere('st.id = e.student')
                ->innerJoin(Lesson::class, 'l')
                ->andWhere('l.id = e.lesson')
                ->innerJoin(Subject::class, 'sb')
                ->andWhere('sb.id = l.subject')
                ->innerJoin(Classroom::class, 'cl')
                ->andWhere('cl.id = st.classroom')
                ->andWhere('st.supprime = 0')
                ->andWhere('l.subject = :subject')
                ->andWhere('st.schoolYear = :schoolYear')
                ->andWhere('st.subSystem = :subSystem')
                ->setParameters([
                    'schoolYear' => $schoolYear,
                    'subSystem' => $subSystem,
                    'subject' => $subject,
                ])
                ->groupBy('st.fullName')
                ->orderBy('SUM(e.mark)/6', 'DESC')
                ->setMaxResults(5)
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    /**
     * les 5 meilleurs élèves par matière et par trimestre
     *
     * @param [type] $subjectId
     * @param [type] $term
     * @return void
     */
    public function getTop5StudentsBySubjectAndTerm(SchoolYear $schoolYear, SubSystem $subSystem, int $subjectId, string $term): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select('s.id AS student_id, s.fullName AS fullName, subj.id AS subject_id, subj.subject AS subject, (AVG(e.mark)) AS moyenne, cl.classroom AS classroom, sexe.sex AS sex, s.birthday AS dateNaissance')
            ->join('e.student', 's')
            ->join('e.lesson', 'l')
            ->join('l.subject', 'subj')
            ->join('e.sequence', 'seq')
            ->join('s.classroom', 'cl')
            ->join('s.sex', 'sexe')
            ->where('subj.id = :subjectId')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('s.subSystem = :subSystem')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ])
            ->groupBy('s.id, subj.id')
            ->orderBy('moyenne', 'DESC')
            ->setMaxResults(5);

        if ($term === '1') 
        {
            $qb->andWhere('seq.id IN (:sequences)')
                ->setParameter('sequences', [1, 2]); // Séquences 1 et 2 pour Trimestre 1
        } 
        elseif ($term === '2') 
        {
            $qb->andWhere('seq.id IN (:sequences)')
                ->setParameter('sequences', [3, 4]); // Séquences 3 et 4 pour Trimestre 2
        } 
        elseif ($term === '3') 
        {
            $qb->andWhere('seq.id IN (:sequences)')
                ->setParameter('sequences', [5, 6]); // Séquences 5 et 6 pour Trimestre 3
        } 
        elseif ($term === '0') 
        {
            $qb->andWhere('seq.id IN (:sequences)')
                ->setParameter('sequences', [1, 2, 3, 4, 5, 6]); // Toutes les séquences pour l'annuel
        }

        $qb->setParameter('subjectId', $subjectId);

        return $qb->getQuery()->getResult();
    }

    /**
     * 5 meilleurs élèves par matière et par cycle
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Cycle $cycle
     * @param integer $subjectId
     * @param string $term
     * @return array
     */
    public function findTopStudentsByCycleAndSubjectAndTrimester(SchoolYear $schoolYear, SubSystem $subSystem, int $cycle, int $subjectId, int $trimester): array
    {
        // Déterminer les évaluations du trimestre
        $evaluationIds = [];
        if ($trimester === 1) {
            $evaluationIds = [1, 2];
        } elseif ($trimester === 2) {
            $evaluationIds = [3, 4];
        } elseif ($trimester === 3) {
            $evaluationIds = [5, 6];
        }

        $query = $this->getEntityManager()->createQuery(
            'SELECT s.id AS studentId, AVG(e.mark) AS moyenne, s.fullName AS fullName, s.birthday AS dateNaissance, sex.sex AS sexe, c.classroom AS classroom
            FROM App\Entity\Evaluation e
            JOIN e.student s
            JOIN s.classroom c
            JOIN c.level l
            JOIN e.lesson lsn
            JOIN lsn.subject sub
            JOIN s.schoolYear sc
            JOIN s.subSystem sb
            JOIN s.sex sex
            WHERE l.id BETWEEN :levelStart AND :levelEnd
            AND sub.id = :subjectId
            AND e.sequence IN (:evaluationIds)
            AND sc = :schoolYear
            AND sb = :subSystem
            GROUP BY s.id
            ORDER BY moyenne DESC'
        );

        // Définir les paramètres en fonction du cycle
        $query->setParameter('levelStart', $cycle === 1 ? 1 : 5);
        $query->setParameter('levelEnd', $cycle === 1 ? 4 : 7);
        $query->setParameter('subjectId', $subjectId);
        $query->setParameter('evaluationIds', $evaluationIds);
        $query->setParameter('schoolYear', $schoolYear);
        $query->setParameter('subSystem', $subSystem);

        // Limiter les résultats aux 5 meilleurs élèves
        $query->setMaxResults(5);

        return $query->getResult();
    }

    /**
     * 5 meilleurs elèves par niveau par matière et par trimestre function
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param [type] $levelId
     * @param [type] $subjectId
     * @param [type] $term
     * @return void
     */
    public function findTop5StudentsByLevelSubjectAndTrimester(SchoolYear $schoolYear, SubSystem $subSystem, $levelId, $subjectId, $term)
    {
        // Map des trimestres et des séquences correspondantes
        $sequences = match ($term) {
            1 => [1, 2],
            2 => [3, 4],
            3 => [5, 6],
            default => throw new \InvalidArgumentException('Trimestre invalide.'),
        };

        // Construction de la requête DQL
        return $this->createQueryBuilder('e')
            ->select('s.id AS studentId, AVG(e.mark) AS moyenne, s.fullName AS fullName, sex.sex AS sexe, s.birthday AS dateNaissance, c.classroom')
            ->join('e.student', 's')
            ->join('s.sex', 'sex')
            ->join('s.classroom', 'c')
            ->join('c.level', 'l')
            ->join('e.lesson', 'le')
            ->join('le.subject', 'sub')
            ->where('l.id = :levelId')
            ->andWhere('sub.id = :subjectId')
            ->andWhere('e.sequence IN (:sequences)')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('s.subSystem = :subSystem')
            ->setParameter('levelId', $levelId)
            ->setParameter('subjectId', $subjectId)
            ->setParameter('sequences', $sequences)
            ->setParameter('schoolYear', $schoolYear)
            ->setParameter('subSystem', $subSystem)
            ->groupBy('s.id')
            ->orderBy('moyenne', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }

    /**
     * les 5 meilleurs élèves par classe par matière et par trimestre
     *
     * @param [type] $classId
     * @param [type] $subjectId
     * @param [type] $trimester
     * @return void
     */
    public function findTop5StudentsBySubjectClassAndTrimester(SchoolYear $schoolYear, SubSystem $subSystem, $subjectId, $classId, $term)
    {
        // Mapping des séquences correspondant au trimestre
        $sequences = match ($term) {
            1 => [1, 2],
            2 => [3, 4],
            3 => [5, 6],
            default => throw new \InvalidArgumentException('Trimestre invalide.'),
        };

        // Requête DQL
        return $this->createQueryBuilder('e')
            ->select('s.id AS studentId, AVG(e.mark) AS moyenne, s.fullName AS fullName, sex.sex AS sexe, s.birthday AS dateNaissance, c.classroom')
            ->join('e.student', 's')
            ->join('s.sex', 'sex')
            ->join('s.classroom', 'c')
            ->join('e.lesson', 'le')
            ->join('le.subject', 'sub')
            ->where('c.id = :classId')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('s.subSystem = :subSystem')
            ->andWhere('sub.id = :subjectId')
            ->andWhere('e.sequence IN (:sequences)')
            ->setParameter('classId', $classId)
            ->setParameter('subjectId', $subjectId)
            ->setParameter('sequences', $sequences)
            ->setParameter('schoolYear', $schoolYear)
            ->setParameter('subSystem', $subSystem)
            ->groupBy('s.id')
            ->orderBy('moyenne', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }


    /**
     * fonction qui retourne les 5 meilleurs élèves du niveau par matière
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Subject $subject
     * @return array
     */
    public function findTopFiveStudentsLevelAndSubject(SchoolYear $schoolYear, SubSystem $subSystem, Level $level, Subject $subject): array 
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('(SUM(e.mark)/6) AS moyenne')
                ->from(Evaluation::class, 'e')
                ->innerJoin(Student::class, 'st')
                ->addSelect('st')
                ->andWhere('st.id = e.student')
                ->innerJoin(Lesson::class, 'l')
                ->andWhere('l.id = e.lesson')
                ->innerJoin(Subject::class, 'sb')
                ->andWhere('sb.id = l.subject')
                ->innerJoin(Classroom::class, 'cl')
                ->innerJoin(Level::class, 'lv')
                ->andWhere('lv.id = cl.level')
                ->andWhere('cl.id = st.classroom')
                ->andWhere('cl.level = :level')
                ->andWhere('l.subject = :subject')
                ->andWhere('st.schoolYear = :schoolYear')
                ->andWhere('st.subSystem = :subSystem')
                ->andWhere('st.supprime = 0')
                ->setParameters([
                    'schoolYear' => $schoolYear,
                    'subSystem' => $subSystem,
                    'subject' => $subject,
                    'level' => $level,
                ])
                ->groupBy('st.fullName')
                ->orderBy('SUM(e.mark)/6', 'DESC')
                ->setMaxResults(5)
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }


    /**
     * Fonction qui retourne les 5 meileurs élèves par matièreet par classe
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Subject $subject
     * @param Classroom $classroom
     * @return array
     */
    public function findTopFiveStudentsSubjectAndClassroom(SchoolYear $schoolYear, SubSystem $subSystem, 
    Subject $subject, Classroom $classroom): array 
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('(SUM(e.mark)/6) AS moyenne')
                ->from(Evaluation::class, 'e')
                ->innerJoin(Student::class, 'st')
                ->addSelect('st')
                ->andWhere('st.id = e.student')
                ->innerJoin(Lesson::class, 'l')
                ->andWhere('l.id = e.lesson')
                ->innerJoin(Subject::class, 'sb')
                ->andWhere('sb.id = l.subject')
                ->andWhere('st.classroom = :classroom')
                ->andWhere('l.subject = :subject')
                ->andWhere('st.schoolYear = :schoolYear')
                ->andWhere('st.subSystem = :subSystem')
                ->andWhere('st.supprime = 0')
                ->setParameters([
                    'schoolYear' => $schoolYear,
                    'subSystem' => $subSystem,
                    'subject' => $subject,
                    'classroom' => $classroom,
                ])
                ->groupBy('st.fullName')
                ->orderBy('SUM(e.mark)/6', 'DESC')
                ->setMaxResults(5)
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }


    /**
     * fonction qui retourne les 5 meilurs élèves par cycle et par matière
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Cycle $cycle
     * @param Subject $subject
     * @return array
     */
    public function findTopFiveStudentsByCycleAndSubject(SchoolYear $schoolYear, SubSystem $subSystem, 
    Cycle $cycle, Subject $subject): array 
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('(SUM(e.mark)/6) AS moyenne')
                ->from(Evaluation::class, 'e')
                ->innerJoin(Student::class, 'st')
                ->innerJoin(Classroom::class, 'cl')
                ->innerJoin(Lesson::class, 'l')
                ->innerJoin(Subject::class, 'sb')
                ->addSelect('st')
                ->andWhere('st.id = e.student')
                ->andWhere('cl.id = st.classroom')
                ->innerJoin('cl.level', 'lv')
                ->innerJoin('lv.cycle', 'cy')
                ->andWhere('l.id = e.lesson')
                ->andWhere('sb.id = l.subject')
                ->andWhere('lv.cycle = :cycle')
                ->andWhere('l.subject = :subject')
                ->andWhere('st.schoolYear = :schoolYear')
                ->andWhere('st.subSystem = :subSystem')
                ->andWhere('st.supprime = 0')
                ->setParameters([
                    'schoolYear' => $schoolYear,
                    'subSystem' => $subSystem,
                    'subject' => $subject,
                    'cycle' => $cycle,
                ])
                ->groupBy('st.fullName')
                ->orderBy('SUM(e.mark)/6', 'DESC')
                ->setMaxResults(5)
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    /**
     * Recupère les evaluations d'une sequence et d'une lesson donnée
     *
     * @param integer $sequence
     * @param integer $lesson
     * @return void
     */
    public function findEvaluations(int $sequence, int $lesson): array 
    {
        return $this->createQueryBuilder('e')
            ->where('e.sequence = :sequence')
            ->andWhere('e.lesson = :lesson')
            ->setParameters([
                'sequence' => $sequence,
                'lesson' => $lesson
                ])
            ->innerJoin('e.student', 's')
            ->addSelect('s')
            ->orderBy('s.fullName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recupère les notes des élèves de la classe donnée à la séquence donnée
     *
     * @param Sequence $sequence
     * @param Classroom $classroom
     * @return array
     */
    public function findEvaluationForReport(Sequence $sequence, Classroom $classroom): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.sequence = :sequence')
            ->innerJoin('e.student', 'st')
            ->innerJoin('e.lesson', 'ls')
            ->addSelect('st')
            ->addSelect('ls')
            ->andWhere('st.classroom = :classroom')
            ->innerJoin('st.sex', 'sx')
            ->addSelect('sx')
            ->innerJoin('st.repeater', 'rp')
            ->addSelect('rp')
            ->innerJoin('ls.subject', 'sb')
            ->innerJoin('ls.teacher', 'tc')
            ->addSelect('sb')
            ->addSelect('tc')
            ->innerJoin('sb.category', 'ct')
            ->addSelect('ct')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'sequence' => $sequence,
                'classroom' => $classroom
            ])
            ->orderBy('e.student', 'ASC')
            ->addOrderBy('e.lesson', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Recupère les notes d'une matière donnée à une sequence donnée dans une classe
     *
     * @param Sequence $sequence
     * @param Classroom $classroom
     * @param Subject $subject
     * @return array
     */
    public function findSubjectEvaluation(Sequence $sequence, Classroom $classroom, Subject $subject): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sequence = :sequence')
            ->innerJoin('e.lesson', 'ls')
            ->addSelect('ls')
            ->andWhere('ls.subject = :subject')
            ->innerJoin('e.student', 'st')
            ->addSelect('st')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'sequence' => $sequence,
                'subject' => $subject,
                'classroom' => $classroom
            ])
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Recupère la note de l'évaluation 6 pour modifier pour l'enseignement technique
     *
     * @param Sequence $sequence
     * @param Student $student
     * @param Subject $subject
     * @return array
     */
    public function findEvaluation(Sequence $sequence, Student $student, Subject $subject): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.sequence = :sequence')
            ->innerJoin('e.lesson', 'ls')
            ->innerJoin('e.student', 's')
            ->addSelect('ls')
            ->andWhere('ls.subject = :subject')
            ->andWhere('e.student = :student')
            ->setParameters([
                'sequence' => $sequence,
                'subject' => $subject,
                'student' => $student
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Fonction qui retourne les évaluations d'une leçon
     *
     * @param Lesson $lesson
     * @return array
     */
    public function findLessonEvaluations(Lesson $lesson): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.lesson = :lesson')
            ->innerJoin('e.sequence', 'sq')
            ->addSelect('sq')
            ->innerJoin('e.student', 'st')
            ->addSelect('st')
            ->andWhere('st.supprime = 0')
            ->setParameter('lesson', $lesson)
            ->orderBy('st.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Fonction qui retourne les statistique d'une évaluation
     *
     * @param integer $sequence
     * @param integer $lesson
     * @return array
     */
    public function getEvaluationStatistics(int $sequenceId, int $lessonId): array
    {
        $qb = $this->createQueryBuilder('e')
            ->select(
                'MIN(CASE WHEN sex.sex = :female THEN e.mark ELSE 0 END) AS minMarkGirls',
                'MIN(CASE WHEN sex.sex = :male THEN e.mark ELSE 0 END) AS minMarkBoys',

                'MAX(CASE WHEN sex.sex = :female THEN e.mark ELSE 0 END) AS maxMarkGirls',
                'MAX(CASE WHEN sex.sex = :male THEN e.mark ELSE 0 END) AS maxMarkBoys',
                'MIN(e.mark) AS minMarkClass',
                'MAX(e.mark) AS maxMarkClass'
            )
            ->join('e.student', 's')
            ->join('s.sex', 'sex')
            ->where('e.sequence = :sequence')
            ->andWhere('e.lesson = :lesson')
            ->setParameters([
                'sequence' => $sequenceId,
                'lesson' => $lessonId,
                'female' => 'F',
                'male' => 'M',
                ]);

        return $qb->getQuery()->getResult();
    }

    public function getEvaluationStatisticsRaw(int $sequenceId, int $lessonId): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = <<<SQL
            SELECT
                MIN(CASE WHEN sex.sex = 'F' THEN e.mark END) AS minMarkGirls,
                MAX(CASE WHEN sex.sex = 'F' THEN e.mark END) AS maxMarkGirls,
                MIN(CASE WHEN sex.sex = 'M' THEN e.mark END) AS minMarkBoys,
                MAX(CASE WHEN sex.sex = 'M' THEN e.mark END) AS maxMarkBoys,
                MIN(e.mark) AS minMarkClass,
                MAX(e.mark) AS maxMarkClass
            FROM evaluation e
            JOIN student s ON e.student_id = s.id
            JOIN sex ON s.sex_id = sex.id
            WHERE e.sequence_id = :sequenceId
            AND e.lesson_id = :lessonId
        SQL;

        $stmt = $conn->prepare($sql);
        $result = $stmt->executeQuery([
            'sequenceId' => $sequenceId,
            'lessonId' => $lessonId,
        ]);

        return $result->fetchAssociative();
    }

    /**
     * fonction qui retourne le nombre d'évaluation par élève d'une classe
     *
     * @param [type] $schoolYear
     * @param [type] $sequence
     * @param [type] $classroom
     * @return void
     */
    public function getEvaluationsBySequenceAndClasse($schoolYear, $sequence, $classroom)
    {
        $qb = $this->createQueryBuilder('e')
            ->select('s.fullName as fullName', 's.id as id', 'COUNT(sb.id) as nbEvaluations', 's.slug as slugStudent', 'c.id as classeId', 'c.slug as slugClassroom', 'IDENTITY(e.sequence) as sequenceId')
            ->innerJoin('e.student', 's')
            ->innerJoin('s.classroom', 'c')
            ->innerJoin('e.lesson', 'l')
            ->innerJoin('l.subject', 'sb')
            ->where('s.classroom = :classroom')
            ->andWhere('e.sequence = :sequence')
            ->andWhere('c.schoolYear = :schoolYear')
            ->setParameter('classroom', $classroom)
            ->setParameter('sequence', $sequence)
            ->setParameter('schoolYear', $schoolYear)
            ->orderBy('s.fullName')
            ->groupBy('s.id');

        return $qb->getQuery()->getResult();
    }


    public function getEvaluationsByTrimestreAndClasse(SchoolYear $schoolYear, int $trimestre, int $classeId)
    {
        $sequenceMapping = [
            1 => [1, 2], // Trimestre 1 -> Séquences 1 et 2
            2 => [3, 4], // Trimestre 2 -> Séquences 3 et 4
            3 => [5, 6], // Trimestre 3 -> Séquences 5 et 6
        ];

        if (!array_key_exists($trimestre, $sequenceMapping)) {
            throw new \InvalidArgumentException('Trimestre invalide.');
        }

        $sequences = $sequenceMapping[$trimestre];

        return $this->createQueryBuilder('e')
            ->select('st.fullName as fullName', 'st.id as id', 'st.slug as slugStudent', 'c.id as classeId', 'c.slug as slugClassroom',  'IDENTITY(e.sequence) as sequenceId', 'COUNT(e.id) as nbEvaluations')
            ->innerJoin('e.student', 'st')
            ->innerJoin('e.lesson', 'l')
            ->innerJoin('l.classroom', 'c')
            ->where('c.id = :classeId')
            ->andWhere('e.sequence IN (:sequences)')
            ->andWhere('c.schoolYear = :schoolYear')
            ->setParameter('classeId', $classeId)
            ->setParameter('sequences', $sequences)
            ->setParameter('schoolYear', $schoolYear)
            ->groupBy('st.id, e.sequence')
            ->orderBy('st.fullName, e.sequence')
            ->getQuery()
            ->getResult();
    }


    /**
     * fonction retourne les évaluations d'un élève un trimestre
     *
     * @param [type] $student
     * @param [type] $trimestre
     * @return void
     */
    public function getEvaluationsByEleveAndTrimestre($student, $trimestre)
    {
        $sequenceMapping = [
            1 => [1, 2],
            2 => [3, 4],
            3 => [5, 6],
        ];

        if (!isset($sequenceMapping[$trimestre])) {
            throw new \InvalidArgumentException('Trimestre invalide.');
        }

        return $this->createQueryBuilder('e')
            ->select('IDENTITY(e.lesson) as lessonId', 'IDENTITY(e.sequence) as sequenceId', 'e.mark', 'sb.id as subjectId', 'sb.subject as subject')
            ->innerJoin('e.lesson','l')
            ->innerJoin('l.subject','sb')
            ->where('e.student = :student')
            ->andWhere('l.id = e.lesson')
            ->andWhere('sb.id = l.subject')
            ->andWhere('e.sequence IN (:sequences)')
            ->setParameter('student', $student)
            ->setParameter('sequences', $sequenceMapping[$trimestre])
            ->getQuery()
            ->getResult();
    }


    /**
     * fonction retourne les évaluations d'un élève d'une séquence donnée
     *
     * @param [type] $student
     * @param [type] $sequence
     * @return void
     */
    public function getEvaluationsByEleveAndSequence($student, $sequence)
    {
        return $this->createQueryBuilder('e')
            ->select('e.id as evaluationId, IDENTITY(e.lesson) as lessonId', 'IDENTITY(e.sequence) as sequenceId', 'e.mark', 'sb.id as subjectId', 'sb.subject as subject')
            ->innerJoin('e.lesson','l')
            ->innerJoin('l.subject','sb')
            ->where('e.student = :student')
            ->andWhere('l.id = e.lesson')
            ->andWhere('sb.id = l.subject')
            ->andWhere('e.sequence = :sequences')
            ->setParameter('student', $student)
            ->setParameter('sequences', $sequence)
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return Evaluation[] Returns an array of Evaluation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Evaluation
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

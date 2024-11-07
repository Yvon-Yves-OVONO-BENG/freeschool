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


    public function findLessonEvaluations(Lesson $lesson): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.lesson = :lesson')
            ->innerJoin('e.sequence', 'sq')
            ->addSelect('sq')
            ->innerJoin('e.student', 'st')
            ->addSelect('st')
            ->setParameter('lesson', $lesson)
            ->orderBy('st.fullName')
            ->getQuery()
            ->getResult()
        ;
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

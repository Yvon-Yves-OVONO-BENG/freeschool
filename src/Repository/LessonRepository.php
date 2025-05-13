<?php

namespace App\Repository;

use App\Entity\Level;
use App\Entity\Lesson;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Lesson>
 *
 * @method Lesson|null find($id, $lockMode = null, $lockVersion = null)
 * @method Lesson|null findOneBy(array $criteria, array $orderBy = null)
 * @method Lesson[]    findAll()
 * @method Lesson[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LessonRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Lesson::class);
    }

    public function save(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Lesson $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Recupère tous les cours d'un enseignant donné
     */
    public function findTeacherLessons(Teacher $teacher): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.teacher = :teacher')
            ->setParameter('teacher', $teacher)
            ->innerJoin('l.teacher', 'tc')
            ->innerJoin('l.subject', 'sb')
            ->innerJoin('l.classroom', 'cl')
            ->innerJoin('cl.level', 'lv')
            ->innerJoin('tc.grade', 'gr')
            ->addSelect('gr')
            ->addSelect('lv')
            ->addSelect('tc')
            ->addSelect('sb')
            ->addSelect('cl')
            ->orderBy('lv.level')
            ->addOrderBy('cl.classroom')
            ->getQuery()
            ->getResult()
        ;
    }

     /**
     * Recupère toutes les classes d'un enseignant donné
     */
    public function findClassroom(Teacher $teacher):array
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('c.classroom AS classroom')
                ->from(Classroom::class, 'c')
                ->innerJoin(Lesson::class, 'l')
                // ->innerJoin(Teacher::class, 't')
                ->andWhere('l.classroom = c.id')
                ->andWhere('l.teacher = :teacher')
                ->setParameter('teacher', $teacher)
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }


    /**
     * Undocumented function
     *
     * @param Classroom|null $classroom
     * @return array
     */
    public function reportClassroom(?Classroom $classroom = null):array
    {
        $query = $this->em->createQuery(
            'SELECT t, s, l
            FROM App\Entity\Lesson l
            JOIN l.teacher t
            JOIN l.classroom c
            JOIN l.subject s
            WHERE c.id = :classroom_id'
        );
        
        if($classroom != null)
        {
            $query->setParameter('classroom_id', $classroom->getId());
        }
        
        return $query->execute();
    }


    /**
     * Recupère tous les cours à afficher
     */
    public function findAllToDisplay(Classroom $classroom, SubSystem $subSystem, bool $forUnrecordedMark = false): array
    {
        $qb = $this->createQueryBuilder('l')
            ->where('l.classroom = :classroom')
            ->andWhere('l.subSystem = :subSystem')
            ->setParameters(['classroom' => $classroom, 'subSystem' => $subSystem])
            ->innerJoin('l.teacher', 'tc')
            ->innerJoin('l.subject', 'sb')
            ->innerJoin('l.classroom', 'cl')
            ->innerJoin('sb.category', 'ct')
            ->addSelect('tc')
            ->addSelect('sb')
            ->addSelect('cl')
            ->addSelect('ct')
        ;

        if($forUnrecordedMark == true)
        {
            $qb->orderBy('tc.fullName');
        }else
        {
            $qb->orderBy('sb.subject');
        }

        return $qb->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recupère tous les cours de l'année en cours d'un niveau donné
     */
    public function findAllForLevel(Level $level, SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.classroom', 'cl')
            ->innerJoin('cl.level', 'lv')
            ->innerJoin('l.teacher', 'tc')
            ->innerJoin('cl.subSystem', 'ss')
            ->addSelect('cl')
            ->addSelect('lv')
            ->addSelect('tc')
            ->addSelect('ss')
            ->where('cl.schoolYear = :schoolYear')
            ->andWhere('cl.level = :level')
            ->andWhere('cl.subSystem = :subSystem')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'level' => $level,
                'subSystem' => $subSystem,
            ])
            ->orderBy('tc.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recupère les cours d'un enseignant dans une classe
     */
    public function findTeacherLessonsInClassroom(Classroom $classroom, Teacher $teacher): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.teacher', 'tc')
            ->addSelect('tc')
            ->innerJoin('l.subject', 'sb')
            ->addSelect('sb')
            ->innerJoin('tc.grade', 'gr')
            ->addSelect('gr')
            ->innerJoin('tc.duty', 'dt')
            ->addSelect('dt')
            ->where('l.teacher = :teacher')
            ->andWhere('l.classroom = :classroom')
            ->setParameters([
                'teacher' => $teacher,
                'classroom' => $classroom
            ])
            ->orderBy('sb.subject')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Recupère les autres enseignants d'une classe en dehors du professeur principal
     */
    public function findOtherTeachers(Teacher $principalTeacher, Classroom $classroom): array
    {
        return $this->createQueryBuilder('l')
            ->where('l.classroom = :classroom')
            ->andWhere('l.teacher != :principalTeacher')
            ->innerJoin('l.teacher', 'tc')
            ->addSelect('tc')
            ->setParameters([
                'classroom' => $classroom,
                'principalTeacher' => $principalTeacher
            ])
            ->orderBy('tc.fullName')
            ->addOrderBy('tc.id')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recupère toutes les lessons de l'année en cours
     */
    public function findAllLessonsOfSchoolYear(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.classroom', 'cl')
            // ->innerJoin('cl.level', 'lv')
            ->innerJoin('l.teacher', 'tc')
            ->innerJoin('l.subSystem', 'ss')
            // ->addSelect('lv')
            ->addSelect('cl')
            ->addSelect('tc')
            ->addSelect('ss')
            ->andWhere('cl.schoolYear = :schoolYear')
            ->andWhere('cl.subSystem = :subSystem')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                ])
            ->orderBy('tc.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

       //////////nombre de classe d'un niveau ///////////////////
       public function getNbreClasseParCycle(SchoolYear $schoolYear)
       {
           $queryBuilder = $this->em->createQueryBuilder();
           $queryBuilder
                   ->select('c.classroom, lv.level')
                   ->from(Lesson::class, 'l')
                   ->innerJoin(Classroom::class, 'c')
                   ->innerJoin(Level::class, 'lv')
                   ->andWhere('l.classroom = c.id')
                   ->andWhere('lv.id = c.level')
                   ->andWhere('c.schoolYear = :schoolYear')
                   ->setParameter('schoolYear', $schoolYear)
                   ->groupBy('c.classroom')
                   ;
   
           $query = $queryBuilder->getQuery();
   
           return $query->execute();
       }


    /**
     * Recupère toutes les lessons de l'année en cours d'une discipline
     */
    public function findAllLessonsForDiscilineOfSchoolYear(Subject $subject, SchoolYear $schoolYear): array
    {
        return $this->createQueryBuilder('l')
            ->innerJoin('l.classroom', 'cl')
            ->innerJoin('cl.level', 'lv')
            ->innerJoin('l.teacher', 'tc')
            ->innerJoin('l.subject', 'sb')
            ->addSelect('lv')
            ->addSelect('cl')
            ->addSelect('tc')
            ->addSelect('sb')
            ->andWhere('cl.schoolYear = :schoolYear')
            ->andWhere('sb.subject = :subject')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subject' => $subject,
                ])
            ->orderBy('tc.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    ///////LES ENSEIGNANTS D'UNE CLASSE
    public function findTeachersPerClassroom(SchoolYear $schoolYear, Classroom $classroom): array 
    {
        return $this->createQueryBuilder('l')
            ->andwhere('l.teacher = t.id')
            ->andwhere('t.schoolYear = :schoolYear')
            ->andwhere('l.classroom = :classroom')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'classroom' => $classroom
                ])
            ->innerJoin('l.teacher', 't')
            ->innerJoin('l.classroom', 'c')
            ->addSelect('t')
            ->addSelect('c')
            ->orderBy('t.fullName')
            ->getQuery()
            ->getResult()    
        ;

    }


    /**
     * releve d'un élève à une séquence
     *
     * @param integer $studentId
     * @param integer $sequenceId
     * @return array
     */
    public function getEvaluationsByStudentAndSequence(int $studentId, int $sequenceId): array
    {
        return $this->getEntityManager()->createQuery(
            '
            SELECT s.subject AS subjectName, e.mark AS mark, t.fullName as teacher
            FROM App\Entity\Lesson l
            INNER JOIN l.subject s
            INNER JOIN l.teacher t
            LEFT JOIN App\Entity\Evaluation e WITH e.lesson = l.id AND e.student = :studentId AND e.sequence = :sequenceId
            WHERE l.classroom = (
                SELECT c.id
                FROM App\Entity\Classroom c
                INNER JOIN c.students st
                WHERE st.id = :studentId
                AND st.supprime = 0
            )
            ORDER BY s.subject ASC
            '
        )
        ->setParameter('studentId', $studentId)
        ->setParameter('sequenceId', $sequenceId)
        ->getResult();
    }


    /**
     * Relevés de note des élèves d'une classe à une séquence donnée
     *
     * @param integer $classroomId
     * @param integer $sequenceId
     * @return array
     */
    public function getTranscriptsByClassAndSequence(int $classroomId, int $sequenceId): array
    {
        $entityManager = $this->getEntityManager();

        // Étape 1 : Récupérer les données pour chaque élève, matière, et note
        $results = $entityManager->createQuery(
            '
            SELECT st.id AS studentId, st.fullName AS fullName, st.emailParent as emailParent,
                st.registrationNumber AS registrationNumber,
                sub.subject AS subject, e.mark AS mark, t.fullName as teacher
            FROM App\Entity\Lesson l
            INNER JOIN l.subject sub
            INNER JOIN l.teacher t
            INNER JOIN l.classroom c WITH c.id = :classroomId
            LEFT JOIN App\Entity\Evaluation e WITH e.lesson = l.id AND e.sequence = :sequenceId
            LEFT JOIN e.student st
            WHERE c.id = :classroomId
            AND st.supprime = 0
            ORDER BY st.fullName ASC, sub.subject ASC
            '
        )
        ->setParameter('classroomId', $classroomId)
        ->setParameter('sequenceId', $sequenceId)
        ->getResult();

        // Étape 2 : Réorganiser les données pour éviter la répétition des noms d’élèves
        $groupedData = [];
        foreach ($results as $row) {
            $studentId = $row['studentId'];
            $studentName = $row['fullName'];
            $registrationNumber = $row['registrationNumber'];
            $emailParent = $row['emailParent'];
            $subjectName = $row['subject'];
            $mark = $row['mark'];
            $teacher = $row['teacher'];

            if (!isset($groupedData[$studentId])) {
                $groupedData[$studentId] = [
                    'studentName' => $studentName,
                    'registrationNumber' => $registrationNumber,
                    'emailParent' => $emailParent,
                    'subjects' => []
                ];
            }

            $groupedData[$studentId]['subjects'][] = [
                'subjectName' => $subjectName,
                'registrationNumber' => $registrationNumber,
                'teacher' => $teacher,
                'mark' => $mark
            ];
        }

        return $groupedData;
    }



    /**
     * Undocumented function
     *
     * @param integer $studentId
     * @param integer $trimester
     * @return array
     */
    public function getEvaluationsByStudentAndTrimester(int $studentId, int $trimester): array
    {
        $sequenceRange = match ($trimester) {
            1 => [1, 2],
            2 => [3, 4],
            3 => [5, 6],
            default => throw new \InvalidArgumentException('Trimestre invalide'),
        };

        return $this->getEntityManager()->createQuery(
            '
            SELECT s.subject AS subjectName, t.fullName as teacher,
                MAX(CASE WHEN e.sequence = :sequence1 THEN e.mark ELSE 0 END) AS evaluation1,
                MAX(CASE WHEN e.sequence = :sequence2 THEN e.mark ELSE 0 END) AS evaluation2
            FROM App\Entity\Lesson l
            INNER JOIN l.subject s
            INNER JOIN l.teacher t
            LEFT JOIN App\Entity\Evaluation e WITH e.lesson = l.id AND e.student = :studentId AND e.sequence IN (:sequence1, :sequence2)
            WHERE l.classroom = (
                SELECT c.id
                FROM App\Entity\Classroom c
                INNER JOIN c.students st
                WHERE st.id = :studentId
                AND st.supprime = 0
            )
            GROUP BY s.id
            ORDER BY s.subject ASC
            '
        )
        ->setParameter('studentId', $studentId)
        ->setParameter('sequence1', $sequenceRange[0])
        ->setParameter('sequence2', $sequenceRange[1])
        ->getResult();
    }


    /**
     * Relevé de notes de la classe par trimestre
     *
     * @param integer $classroomId
     * @param integer $trimester
     * @return array
     */
    public function getSubjectsWithGradesByClassAndTrimester($classroomId, $trimester)
    {
        $sequenceRange = match ($trimester) {
            1 => [1, 2],
            2 => [3, 4],
            3 => [5, 6],
            default => throw new \InvalidArgumentException('Trimestre invalide'),
        };

        $em = $this->getEntityManager();

        $query = $em->createQuery(
            '
            SELECT s.subject AS subjectName, st.id AS studentId, 
                st.registrationNumber AS registrationNumber,
                st.fullName AS studentName, st.emailParent as emailParent, t.fullName as teacher,
                MAX(CASE WHEN e.sequence = :sequence1 THEN e.mark ELSE 0 END) AS evaluation1,
                MAX(CASE WHEN e.sequence = :sequence2 THEN e.mark ELSE 0 END) AS evaluation2
            FROM App\Entity\Lesson l
            INNER JOIN l.subject s
            INNER JOIN l.teacher t
            INNER JOIN l.classroom c
            LEFT JOIN App\Entity\Evaluation e WITH e.lesson = l.id AND e.sequence IN (:sequence1, :sequence2)
            LEFT JOIN e.student st
            WHERE c.id = :classroomId
            AND st.classroom = c.id
            AND st.supprime = 0
            GROUP BY st.fullName, s.id
            ORDER BY s.subject ASC
            '
        )
        ->setParameter('classroomId', $classroomId)
        ->setParameter('sequence1', $sequenceRange[0])
        ->setParameter('sequence2', $sequenceRange[1]);

        $results = $query->getResult();

        // Transformer les données pour éviter les doublons sur les étudiants
        $report = [];
        foreach ($results as $row) 
        {
            $studentId = $row['studentId'];

            if (!isset($report[$studentId])) {
                $report[$studentId] = [
                    'studentId' => $studentId,
                    'studentName' => $row['studentName'],
                    'registrationNumber' => $row['registrationNumber'],
                    'emailParent' => $row['emailParent'],
                    'subjects' => [],
                ];
            }

            $report[$studentId]['subjects'][] = [
                'studentId' => $studentId,
                    'subjectName' => $row['subjectName'],
                    'registrationNumber' => $row['registrationNumber'],
                    'teacher' => $row['teacher'],
                    'evaluation1' => $row['evaluation1'],
                    'evaluation2' => $row['evaluation2'],
            ];
        }

        return $report;
    
    }



    /**
     * relevé anneul d'un élève
     *
     * @param [type] $studentId
     * @return void
     */
    public function getAnnualReportByStudent($studentId)
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
            'SELECT 
                st.id AS studentId,
                st.fullName AS studentName,
                st.registrationNumber AS registrationNumber,
                s.subject AS subjectName,
                t.fullName AS teacher,
                COALESCE(MAX(CASE WHEN e.sequence = 1 THEN e.mark ELSE 0 END), 0) AS eval1,
                COALESCE(MAX(CASE WHEN e.sequence = 2 THEN e.mark ELSE 0 END), 0) AS eval2,
                COALESCE(MAX(CASE WHEN e.sequence = 3 THEN e.mark ELSE 0 END), 0) AS eval3,
                COALESCE(MAX(CASE WHEN e.sequence = 4 THEN e.mark ELSE 0 END), 0) AS eval4,
                COALESCE(MAX(CASE WHEN e.sequence = 5 THEN e.mark ELSE 0 END), 0) AS eval5,
                COALESCE(MAX(CASE WHEN e.sequence = 6 THEN e.mark ELSE 0 END), 0) AS eval6
            FROM App\Entity\Lesson l
            INNER JOIN l.subject s
            INNER JOIN l.classroom c
            INNER JOIN l.teacher t
            LEFT JOIN App\Entity\Evaluation e WITH e.lesson = l.id AND e.student = :studentId
            LEFT JOIN e.student st
            WHERE st.id = :studentId
            AND st.supprime = 0
            GROUP BY s.id
            ORDER BY s.subject ASC' 
        );

        $query->setParameter('studentId', $studentId);

        return $query->getResult();
    }

    /**
     * releve de notes pour la classe annuel
     *
     * @param [type] $studentId
     * @return void
     */
    public function getAnnualReportByClassroom($classroomId): array
    {
        $em = $this->getEntityManager();

        $query = $em->createQuery(
            'SELECT 
                st.id AS studentId,
                st.fullName AS studentName,
                st.registrationNumber AS registrationNumber,
                st.emailParent AS emailParent,
                s.subject AS subjectName,
                t.fullName AS teacher,
                COALESCE(MAX(CASE WHEN e.sequence = 1 THEN e.mark ELSE 0 END), 0) AS eval1,
                COALESCE(MAX(CASE WHEN e.sequence = 2 THEN e.mark ELSE 0 END), 0) AS eval2,
                COALESCE(MAX(CASE WHEN e.sequence = 3 THEN e.mark ELSE 0 END), 0) AS eval3,
                COALESCE(MAX(CASE WHEN e.sequence = 4 THEN e.mark ELSE 0 END), 0) AS eval4,
                COALESCE(MAX(CASE WHEN e.sequence = 5 THEN e.mark ELSE 0 END), 0) AS eval5,
                COALESCE(MAX(CASE WHEN e.sequence = 6 THEN e.mark ELSE 0 END), 0) AS eval6
            FROM App\Entity\Lesson l
            INNER JOIN l.subject s
            INNER JOIN l.classroom c
            INNER JOIN l.teacher t
            LEFT JOIN App\Entity\Evaluation e WITH e.lesson = l.id
            LEFT JOIN e.student st
            WHERE c.id = :classroomId
            AND st.supprime = 0
            AND st.classroom = c.id
            GROUP BY st.fullName, s.id
            ORDER BY s.subject ASC' 
        );

        $query->setParameter('classroomId', $classroomId);

        // return $query->getResult();

        $results = $query->getResult();

        // Transformer les données pour éviter les doublons sur les étudiants
        $report = [];
        foreach ($results as $row) 
        {
            $studentId = $row['studentId'];

            if (!isset($report[$studentId])) {
                $report[$studentId] = [
                    'studentId' => $studentId,
                    'studentName' => $row['studentName'],
                    'registrationNumber' => $row['registrationNumber'],
                    'emailParent' => $row['emailParent'],
                    'subjects' => [],
                ];
            }

            $report[$studentId]['subjects'][] = [
                'studentId' => $studentId,
                    'subjectName' => $row['subjectName'],
                    'registrationNumber' => $row['registrationNumber'],
                    'teacher' => $row['teacher'],
                    'eval1' => $row['eval1'],
                    'eval2' => $row['eval2'],
                    'eval3' => $row['eval3'],
                    'eval4' => $row['eval4'],
                    'eval5' => $row['eval5'],
                    'eval6' => $row['eval6']
            ];
        }

        return $report;
    }

//    /**
//     * @return Lesson[] Returns an array of Lesson objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('l.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Lesson
//    {
//        return $this->createQueryBuilder('l')
//            ->andWhere('l.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

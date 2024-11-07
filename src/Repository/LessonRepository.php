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

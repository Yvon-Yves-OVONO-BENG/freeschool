<?php

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\Classroom;
use App\Entity\Progress;
use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Classroom|null find($id, $lockMode = null, $lockVersion = null)
 * @method Classroom|null findOneBy(array $criteria, array $orderBy = null)
 * @method Classroom[]    findAll()
 * @method Classroom[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClassroomRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Classroom::class);
    }

    // Liste des classes attachées à un surveillant
    public function findSupervisorClassrooms(Teacher $teacher, SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('c')
            ->where('c.supervisor = :teacher')
            ->andWhere('c.schoolYear = :schoolYear')
            ->andWhere('c.subSystem = :subSystem')
            ->setParameters([
                'teacher' => $teacher,
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ])
            ->innerJoin('c.level', 'l')
            ->addSelect('l')
            ->orderBy('l.level', 'ASC')
            ->addOrderBy('c.classroom', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // Liste des classes attachées à un censeur
    public function findCensorClassrooms(Teacher $teacher, SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('c')
            ->where('c.censor = :teacher')
            ->andWhere('c.schoolYear = :schoolYear')
            ->andWhere('c.subSystem = :subSystem')
            ->setParameters([
                'teacher' => $teacher,
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ])
            ->innerJoin('c.level', 'l')
            ->addSelect('l')
            ->orderBy('l.level', 'ASC')
            ->addOrderBy('c.classroom', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    // Liste de toutes les classes enregistrées à afficher
    public function findAllToDisplay(SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('c')
            ->where('c.schoolYear = :schoolYear')
            ->andWhere('c.subSystem = :subSystem')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->innerJoin('c.level', 'lv')
            ->innerJoin('lv.cycle', 'cy')
            ->leftJoin('c.principalTeacher', 'pp')
            ->leftJoin('c.supervisor', 'sg')
            ->leftJoin('c.censor', 'cs')
            ->leftJoin('c.counsellor', 'co')
            ->addSelect('lv')
            ->addSelect('cy')
            ->addSelect('pp')
            ->addSelect('sg')
            ->addSelect('cs')
            ->addSelect('co')
            ->orderBy('lv.level')
            ->addOrderBy('c.classroom')
            ->getQuery()
            ->getResult()
        ;
    }

    // Liste des classes à afficher dans un select
    public function findForSelect(SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('c')
            ->where('c.schoolYear = :schoolYear')
            ->andWhere('c.subSystem = :subSystem')
            ->setParameters([
                'schoolYear' => $schoolYear, 
                'subSystem' => $subSystem
                ])
            ->innerJoin('c.level', 'lv')
            ->addSelect('lv')
            ->orderBy('lv.level')
            ->addOrderBy('c.classroom')
            ->getQuery()
            ->getResult()    
        ;
    }
    
    // Liste des classes à afficher dans un formulaire
    public function findForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('c')
            ->where('c.schoolYear = :schoolYear')
            ->andWhere('c.subSystem = :subSystem')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->innerJoin('c.level', 'lv')
            ->addSelect('lv')
            ->orderBy('lv.level')
            ->addOrderBy('c.classroom')
            ;

    }

    /**
     * Recupère les clases de second cycle (level > 4)
     */
    public function findClassroomCycle2(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.schoolYear = :schoolYear')
            ->andWhere('c.subSystem = :subSystem')
            ->innerJoin('c.level', 'lv')
            ->addSelect('lv')
            ->andWhere('lv.level > :level')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'level' => 4,
                'subSystem' => $subSystem,
            ])
            ->orderBy('lv.level')
            ->addOrderBy('c.classroom')
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
     * je sélectionne les classes où un enseignant dispense les cours
     */
    public function findClassroomPerTeacher(Teacher $teacher)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('c')
                ->from(Classroom::class, 'c')
                ->innerJoin(Lesson::class, 'l')
                ->andWhere('l.classroom = c.id')
                ->andWhere('l.teacher = :teacher')
                ->setParameter('teacher', $teacher)
                ;
        return $queryBuilder ;
    }


    /**
     * je sélectionne les classes où une matière est dispensée
     */
    public function findClassroomPerSubject(Subject $subject): array 
    {
        // return $this->
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('c')
                ->from(Classroom::class, 'c')
                ->innerJoin(Progress::class, 'p')
                ->andWhere('p.classroom = c.id')
                ->andWhere('p.subject = :subject')
                ->setParameter('subject', $subject)
                ;
        
        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    // /**
    //  * @return Classroom[] Returns an array of Classroom objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Classroom
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

<?php

namespace App\Repository;

use App\Entity\Term;
use App\Entity\Absence;
use App\Entity\Classroom;
use App\Entity\Student;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Absence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Absence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Absence[]    findAll()
 * @method Absence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Absence::class);
    }

    public function findAbsences(Term $term, Classroom $classroom): array 
    {
        return $this->createQueryBuilder('a')
            ->where('a.term = :term')
            ->innerJoin('a.student', 's')
            ->addSelect('s')
            ->andWhere('s.classroom = :classroom')
            ->setParameters([
                'term' => $term,
                'classroom' => $classroom
            ])
            ->orderBy('s.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAbsencesEndYear(Classroom $classroom): array 
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.student', 's')
            ->addSelect('s')
            ->andWhere('s.classroom = :classroom')
            ->setParameters([
                'classroom' => $classroom
            ])
            ->orderBy('s.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findAbsencesBySex(Term $term, Classroom $classroom, string $sex): array 
    {
        $qb = $this->createQueryBuilder('a')
            ->innerJoin('a.student', 's')
            ->addSelect('s')
            ->innerJoin('s.sex', 'sx')
            ->addSelect('sx')
            ->andWhere('s.classroom = :classroom')
            ->andWhere('sx.sex = :sex')
            ->setParameter('classroom', $classroom)
            ->setParameter('sex', $sex)
        ;
        
        if($term->getTerm() != 0)
        {
            $qb->andwhere('a.term = :term')
                ->setParameter('term', $term)
            ;

        }
        return $qb->getQuery()
            ->getResult()
        ;
    }

    // public function findClassroomAbsences(Classroom $classroom)
    // {
    //     $queryBuilder = $this->em->createQueryBuilder();
    //     $queryBuilder
    //             ->select('a.absence AS absences')
    //             ->from(Absence::class, 'a')
    //             ->innerJoin(Student::class, 's')
    //             ->innerJoin(Classroom::class, 'c')
    //             ->innerJoin(Term::class, 't')
    //             // ->andWhere('a.student = s.id')
    //             // ->andWhere('s.classroom = c.id')
    //             // ->andWhere('a.term = t.id')
    //             ->where('s.classroom = :classroom')
    //             ->setParameter('classroom', $classroom)
    //             ;

    //     $query = $queryBuilder->getQuery();

    //     return $query->execute();
    // }

    public function findClassroomAbsences(int $classroomId)
    {
        return $this->createQueryBuilder('a')
        ->select('a','s','c')
                ->innerJoin('a.student', 's')
                ->innerJoin('s.classroom', 'c')
                ->andwhere('c.id = :classroomId')
                ->setParameter('classroomId', $classroomId)
                ->getQuery()
                ->getResult()
                ;

        
    }


    //////////SOMME DES HEURES D'ABSENCE PAR ELEVE ///////////////////
    public function getSumAbsencePerStudent(Classroom $classroom)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('SUM(a.absence) AS sommeHeure, s.fullName AS student')
                ->from(Absence::class, 'a')
                ->innerJoin(Student::class, 's')
                ->innerJoin(Classroom::class, 'c')
                ->andWhere('s.id = a.student')
                ->andWhere('c.id = s.classroom')
                ->andWhere('s.classroom = :classroom')
                ->setParameter('classroom', $classroom)
                ->groupBy('student')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    // /**
    //  * Recupère les absences des élèves d'une classe donnée
    //  *
    //  * @param Classroom $classroom
    //  * @return array
    //  */
    // public function findClassroomAbsences(Classroom $classroom): array
    // {
    //     return $this->createQueryBuilder('a')
    //         ->innerJoin('a.student', 'st')
    //         ->addSelect('st')
    //         ->where('st.classroom = :classroom')
    //         ->setParameter('classroom', $classroom)
    //         ->innerJoin('a.term', 'tm')
    //         ->addSelect('tm')
    //         ->orderBy('st.fullName')
    //         ->getQuery()
    //         ->getResult()
    //     ;
    // }




    // /**
    //  * @return Absence[] Returns an array of Absence objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Absence
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

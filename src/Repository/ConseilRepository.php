<?php

namespace App\Repository;

use App\Entity\Term;
use App\Entity\Conseil;
use App\Entity\Student;
use App\Entity\Classroom;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Conseil>
 *
 * @method Conseil|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conseil|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conseil[]    findAll()
 * @method Conseil[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ConseilRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conseil::class);
    }

    public function save(Conseil $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Conseil $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findConseils(Term $term, Classroom $classroom): array 
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

    public function findConseilsEndYear(Classroom $classroom): array 
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

    public function findConseilsBySex(Term $term, Classroom $classroom, string $sex): array 
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

    public function findClassroomConseils(Classroom $classroom)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('a.conseil AS conseils')
                ->from(Conseil::class, 'a')
                ->innerJoin(Student::class, 's')
                ->innerJoin(Classroom::class, 'c')
                ->innerJoin(Term::class, 't')
                ->andWhere('a.student = s.id')
                ->andWhere('s.classroom = c.id')
                ->andWhere('a.term = t.id')
                ->where('s.classroom = :classroom')
                ->setParameter('classroom', $classroom)
                ;

        $query = $queryBuilder->getQuery();

        // return $query->execute();
    }


    //////////SOMME DES HEURES D'ABSENCE PAR ELEVE ///////////////////
    public function getSumConseilPerStudent(Classroom $classroom)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('SUM(a.conseil) AS sommeHeure, s.fullName AS student')
                ->from(Conseil::class, 'a')
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

//    /**
//     * @return Conseil[] Returns an array of Conseil objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Conseil
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

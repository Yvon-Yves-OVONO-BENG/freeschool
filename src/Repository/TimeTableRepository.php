<?php

namespace App\Repository;

use App\Entity\Classroom;
use App\Entity\TimeTable;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<TimeTable>
 *
 * @method TimeTable|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimeTable|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimeTable[]    findAll()
 * @method TimeTable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimeTableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeTable::class);
    }

    public function save(TimeTable $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TimeTable $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Recupère tous les cours à afficher
     */
    public function findAllToDisplay(Classroom $classroom): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.classroom = :classroom')
            ->setParameter('classroom', $classroom)
            // ->innerJoin('t.teacher', 'tc')
            ->innerJoin('t.subject', 'sb')
            ->innerJoin('t.classroom', 'cl')
            ->innerJoin('t.day', 'd')
            // ->addSelect('tc')
            ->addSelect('sb')
            ->addSelect('cl')
            ->addSelect('d')
            ->getQuery()
            ->getResult()
        ;

        // if($forUnrecordedMark == true)
        // {
        //     $qb->orderBy('tc.fullName');
        // }

        // return $qb->getQuery()
            
        ;
    }

//    /**
//     * @return TimeTable[] Returns an array of TimeTable objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TimeTable
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

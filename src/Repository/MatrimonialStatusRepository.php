<?php

namespace App\Repository;

use App\Entity\MatrimonialStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method MatrimonialStatus|null find($id, $lockMode = null, $lockVersion = null)
 * @method MatrimonialStatus|null findOneBy(array $criteria, array $orderBy = null)
 * @method MatrimonialStatus[]    findAll()
 * @method MatrimonialStatus[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MatrimonialStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MatrimonialStatus::class);
    }

    // /**
    //  * @return MatrimonialStatus[] Returns an array of MatrimonialStatus objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('m.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?MatrimonialStatus
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

<?php

namespace App\Repository;

use App\Entity\Handicap;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Handicap|null find($id, $lockMode = null, $lockVersion = null)
 * @method Handicap|null findOneBy(array $criteria, array $orderBy = null)
 * @method Handicap[]    findAll()
 * @method Handicap[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HandicapRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Handicap::class);
    }

    // /**
    //  * @return Handicap[] Returns an array of Handicap objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Handicap
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

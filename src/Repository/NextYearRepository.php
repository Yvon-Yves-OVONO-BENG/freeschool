<?php

namespace App\Repository;

use App\Entity\NextYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NextYear|null find($id, $lockMode = null, $lockVersion = null)
 * @method NextYear|null findOneBy(array $criteria, array $orderBy = null)
 * @method NextYear[]    findAll()
 * @method NextYear[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NextYearRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NextYear::class);
    }

    // /**
    //  * @return NextYear[] Returns an array of NextYear objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NextYear
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

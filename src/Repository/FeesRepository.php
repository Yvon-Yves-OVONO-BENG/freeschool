<?php

namespace App\Repository;

use App\Entity\Fees;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Fees|null find($id, $lockMode = null, $lockVersion = null)
 * @method Fees|null findOneBy(array $criteria, array $orderBy = null)
 * @method Fees[]    findAll()
 * @method Fees[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FeesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fees::class);
    }

    // /**
    //  * @return Fees[] Returns an array of Fees objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('f.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Fees
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

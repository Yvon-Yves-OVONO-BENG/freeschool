<?php

namespace App\Repository;

use App\Entity\Repeater;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Repeater|null find($id, $lockMode = null, $lockVersion = null)
 * @method Repeater|null findOneBy(array $criteria, array $orderBy = null)
 * @method Repeater[]    findAll()
 * @method Repeater[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RepeaterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Repeater::class);
    }

    // /**
    //  * @return Repeater[] Returns an array of Repeater objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Repeater
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

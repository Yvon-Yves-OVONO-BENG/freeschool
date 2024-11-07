<?php

namespace App\Repository;

use App\Entity\Verrou;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Verrou|null find($id, $lockMode = null, $lockVersion = null)
 * @method Verrou|null findOneBy(array $criteria, array $orderBy = null)
 * @method Verrou[]    findAll()
 * @method Verrou[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerrouRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Verrou::class);
    }

    // /**
    //  * @return Verrou[] Returns an array of Verrou objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('v.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Verrou
    {
        return $this->createQueryBuilder('v')
            ->andWhere('v.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

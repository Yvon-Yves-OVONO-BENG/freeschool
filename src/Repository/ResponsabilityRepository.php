<?php

namespace App\Repository;

use App\Entity\Responsability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Responsability|null find($id, $lockMode = null, $lockVersion = null)
 * @method Responsability|null findOneBy(array $criteria, array $orderBy = null)
 * @method Responsability[]    findAll()
 * @method Responsability[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ResponsabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Responsability::class);
    }

    // /**
    //  * @return Responsability[] Returns an array of Responsability objects
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
    public function findOneBySomeField($value): ?Responsability
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

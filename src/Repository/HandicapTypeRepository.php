<?php

namespace App\Repository;

use App\Entity\HandicapType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HandicapType|null find($id, $lockMode = null, $lockVersion = null)
 * @method HandicapType|null findOneBy(array $criteria, array $orderBy = null)
 * @method HandicapType[]    findAll()
 * @method HandicapType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HandicapTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HandicapType::class);
    }

    // /**
    //  * @return HandicapType[] Returns an array of HandicapType objects
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
    public function findOneBySomeField($value): ?HandicapType
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

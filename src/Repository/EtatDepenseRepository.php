<?php

namespace App\Repository;

use App\Entity\EtatDepense;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EtatDepense|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtatDepense|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtatDepense[]    findAll()
 * @method EtatDepense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtatDepenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtatDepense::class);
    }

    // /**
    //  * @return EtatDepense[] Returns an array of EtatDepense objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EtatDepense
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

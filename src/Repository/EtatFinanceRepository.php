<?php

namespace App\Repository;

use App\Entity\EtatFinance;
use App\Entity\SchoolYear;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EtatFinance|null find($id, $lockMode = null, $lockVersion = null)
 * @method EtatFinance|null findOneBy(array $criteria, array $orderBy = null)
 * @method EtatFinance[]    findAll()
 * @method EtatFinance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EtatFinanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EtatFinance::class);
    }

    // /**
    //  * @return EtatFinance[] Returns an array of EtatFinance objects
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
    public function findOneBySomeField($value): ?EtatFinance
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

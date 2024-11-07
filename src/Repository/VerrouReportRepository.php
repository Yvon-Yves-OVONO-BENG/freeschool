<?php

namespace App\Repository;

use App\Entity\VerrouReport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method VerrouReport|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerrouReport|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerrouReport[]    findAll()
 * @method VerrouReport[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerrouReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerrouReport::class);
    }

    // /**
    //  * @return VerrouReport[] Returns an array of VerrouReport objects
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
    public function findOneBySomeField($value): ?VerrouReport
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

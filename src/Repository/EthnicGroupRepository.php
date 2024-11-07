<?php

namespace App\Repository;

use App\Entity\EthnicGroup;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EthnicGroup|null find($id, $lockMode = null, $lockVersion = null)
 * @method EthnicGroup|null findOneBy(array $criteria, array $orderBy = null)
 * @method EthnicGroup[]    findAll()
 * @method EthnicGroup[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EthnicGroupRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EthnicGroup::class);
    }

    // /**
    //  * @return EthnicGroup[] Returns an array of EthnicGroup objects
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
    public function findOneBySomeField($value): ?EthnicGroup
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

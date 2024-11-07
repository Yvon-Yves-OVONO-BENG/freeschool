<?php

namespace App\Repository;

use App\Entity\Subdivision;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Subdivision|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subdivision|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subdivision[]    findAll()
 * @method Subdivision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubdivisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Subdivision::class);
    }

    // /**
    //  * @return Subdivision[] Returns an array of Subdivision objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Subdivision
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

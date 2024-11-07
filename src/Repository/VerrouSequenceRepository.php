<?php

namespace App\Repository;

use App\Entity\VerrouSequence;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method VerrouSequence|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerrouSequence|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerrouSequence[]    findAll()
 * @method VerrouSequence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerrouSequenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerrouSequence::class);
    }

    // /**
    //  * @return VerrouSequence[] Returns an array of VerrouSequence objects
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
    public function findOneBySomeField($value): ?VerrouSequence
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

<?php

namespace App\Repository;

use App\Entity\Sequence;
use App\Entity\SchoolYear;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Sequence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sequence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sequence[]    findAll()
 * @method Sequence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SequenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sequence::class);
    }

    public function findForMark(bool $term1IsLocked, bool $term2IsLocked, bool $term3IsLocked, bool $sequence1IsLocked, bool $sequence2IsLocked, bool $sequence3IsLocked, bool $sequence4IsLocked, bool $sequence5IsLocked, bool $sequence6IsLocked): array 
    {
        $qb = $this->createQueryBuilder('s');

        if($term1IsLocked == true)
        {
            $qb->andWhere('s.sequence != :sequence1')
                ->andWhere('s.sequence != :sequence2')
                ->setParameter('sequence1', 1)
                ->setParameter('sequence2', 2)
            ;
        }

        if($term2IsLocked == true)
        {
            $qb->andWhere('s.sequence != :sequence3')
                ->andWhere('s.sequence != :sequence4')
                ->setParameter('sequence3', 3)
                ->setParameter('sequence4', 4)
            ;
        }

        if($term3IsLocked == true)
        { 
            $qb->andWhere('s.sequence != :sequence5')
                ->andWhere('s.sequence != :sequence6')
                ->setParameter('sequence5', 5)
                ->setParameter('sequence6', 6)
            ;
        }

        if($sequence1IsLocked == true)
        { 
            $qb->andWhere('s.sequence != :sequence1')
                ->setParameter('sequence1', 1)
            ;
        }

        if($sequence2IsLocked == true)
        { 
            $qb->andWhere('s.sequence != :sequence2')
                ->setParameter('sequence2', 2)
            ;
        }

        if($sequence3IsLocked == true)
        { 
            $qb->andWhere('s.sequence != :sequence3')
                ->setParameter('sequence3', 3)
            ;
        }

        if($sequence4IsLocked == true)
        { 
            $qb->andWhere('s.sequence != :sequence4')
                ->setParameter('sequence4', 4)
            ;
        }

        if($sequence5IsLocked == true)
        { 
            $qb->andWhere('s.sequence != :sequence5')
                ->setParameter('sequence5', 5)
            ;
        }

        if($sequence6IsLocked == true)
        { 
            $qb->andWhere('s.sequence != :sequence6')
                ->setParameter('sequence6', 6)
            ;
        }


        return $qb->orderBy('s.sequence')
                ->getQuery()
                ->getResult()
        ;
    }

    public function findToDisplaySequenceTrue(SchoolYear $schoolYear): array 
    {
        return $this->createQueryBuilder('s')
        ->andWhere('v.schoolYear = :schoolYear')
        ->andWhere('v.sequence = s.id')
        ->andWhere('v.verrouSequence = 0')
        ->setParameter('schoolYear', $schoolYear)
        ->innerJoin('s.verrouSequence', 'v')
        ->addSelect('v')
        ->getQuery()
        ->getResult()
        ;
    }

    // /**
    //  * @return Sequence[] Returns an array of Sequence objects
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
    public function findOneBySomeField($value): ?Sequence
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

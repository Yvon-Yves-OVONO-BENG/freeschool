<?php

namespace App\Repository;

use App\Entity\Term;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Term|null find($id, $lockMode = null, $lockVersion = null)
 * @method Term|null findOneBy(array $criteria, array $orderBy = null)
 * @method Term[]    findAll()
 * @method Term[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Term::class);
    }

    public function findTermForReport(bool $term1IsLocked, bool $term2IsLocked, bool $term3IsLocked, bool $term0IsLocked): array 
    {
        $qb = $this->createQueryBuilder('t');
            if($term1IsLocked == true)
            {
                $qb->andWhere('t.term != :term1')
                    ->setParameter('term1', 1)
                ;
            }

            if($term2IsLocked == true)
            {
                $qb->andWhere('t.term != :term2')
                    ->setParameter('term2', 2)
                ;
            }

            if($term3IsLocked == true)
            {
                $qb->andWhere('t.term != :term3')
                    ->setParameter('term3', 3)
                ;
            }

            if($term0IsLocked == true)
            {
                $qb->andWhere('t.term != :term0')
                    ->setParameter('term0', 0)
                ;
            }

        return $qb->orderBy('t.term')
                ->getQuery()
                ->getResult()
        ;
    }

    // /**
    //  * @return Term[] Returns an array of Term objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Term
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

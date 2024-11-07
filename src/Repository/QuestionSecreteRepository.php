<?php

namespace App\Repository;

use App\Entity\QuestionSecrete;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestionSecrete>
 *
 * @method QuestionSecrete|null find($id, $lockMode = null, $lockVersion = null)
 * @method QuestionSecrete|null findOneBy(array $criteria, array $orderBy = null)
 * @method QuestionSecrete[]    findAll()
 * @method QuestionSecrete[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QuestionSecreteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QuestionSecrete::class);
    }

    //    /**
    //     * @return QuestionSecrete[] Returns an array of QuestionSecrete objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('q.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?QuestionSecrete
    //    {
    //        return $this->createQueryBuilder('q')
    //            ->andWhere('q.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

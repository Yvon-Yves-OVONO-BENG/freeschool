<?php

namespace App\Repository;

use App\Entity\ReponseQuestion;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReponseQuestion>
 *
 * @method ReponseQuestion|null find($id, $lockMode = null, $lockVersion = null)
 * @method ReponseQuestion|null findOneBy(array $criteria, array $orderBy = null)
 * @method ReponseQuestion[]    findAll()
 * @method ReponseQuestion[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReponseQuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReponseQuestion::class);
    }

    //    /**
    //     * @return ReponseQuestion[] Returns an array of ReponseQuestion objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ReponseQuestion
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

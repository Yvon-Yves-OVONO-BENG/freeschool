<?php

namespace App\Repository;

use App\Entity\VerrouInsolvable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<VerrouInsolvable>
 *
 * @method VerrouInsolvable|null find($id, $lockMode = null, $lockVersion = null)
 * @method VerrouInsolvable|null findOneBy(array $criteria, array $orderBy = null)
 * @method VerrouInsolvable[]    findAll()
 * @method VerrouInsolvable[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class VerrouInsolvableRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, VerrouInsolvable::class);
    }

    public function save(VerrouInsolvable $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(VerrouInsolvable $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return VerrouInsolvable[] Returns an array of VerrouInsolvable objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('v.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?VerrouInsolvable
//    {
//        return $this->createQueryBuilder('v')
//            ->andWhere('v.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

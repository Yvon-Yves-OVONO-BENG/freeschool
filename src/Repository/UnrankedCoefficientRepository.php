<?php

namespace App\Repository;

use App\Entity\SchoolYear;
use App\Entity\UnrankedCoefficient;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method UnrankedCoefficient|null find($id, $lockMode = null, $lockVersion = null)
 * @method UnrankedCoefficient|null findOneBy(array $criteria, array $orderBy = null)
 * @method UnrankedCoefficient[]    findAll()
 * @method UnrankedCoefficient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UnrankedCoefficientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UnrankedCoefficient::class);
    }

    public function findOneForClassroomsLevel(int $level, SchoolYear $schoolYear): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.classroom', 'c')
            ->addSelect('c')
            ->andWhere('c.schoolYear = :schoolYear')
            ->innerJoin('c.level', 'lv')
            ->addSelect('lv')
            ->andWhere('lv.level = :level')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'level' => $level
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    public function findForClassroomCycle2(SchoolYear $schoolYear): array
    {
         return $this->createQueryBuilder('u')
            ->innerJoin('u.classroom', 'c')
            ->addSelect('c')
            ->andWhere('c.schoolYear = :schoolYear')
            ->innerJoin('c.level', 'lv')
            ->addSelect('lv')
            ->andWhere('lv.level > :level')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'level' => 4
            ])
            ->orderBy('lv.level')
            ->addOrderBy('c.classroom')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findForClassroomsLevel(int $level, SchoolYear $schoolYear): array
    {
        return $this->createQueryBuilder('u')
            ->innerJoin('u.classroom', 'c')
            ->addSelect('c')
            ->andWhere('c.schoolYear = :schoolYear')
            ->innerJoin('c.level', 'lv')
            ->addSelect('lv')
            ->andWhere('lv.level = :level')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'level' => $level
            ])
            ->getQuery()
            ->getResult()
        ;
    }

    // /**
    //  * @return UnrankedCoefficient[] Returns an array of UnrankedCoefficient objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UnrankedCoefficient
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

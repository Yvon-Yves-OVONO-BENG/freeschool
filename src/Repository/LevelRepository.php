<?php

namespace App\Repository;

use App\Entity\Level;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Level|null find($id, $lockMode = null, $lockVersion = null)
 * @method Level|null findOneBy(array $criteria, array $orderBy = null)
 * @method Level[]    findAll()
 * @method Level[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LevelRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Level::class);
    }

    public function findLevelForFormFr()
    {
        return $this->createQueryBuilder('l')
            ->where('l.id IN (1,2,3,4,5,6,7)')
            ->orderBy('l.level')
            // ->getQuery()
            // ->getResult()
        ;

        // return $this->createQueryBuilder('l')
        //     ->orderBy('l.level')
        // ;
    }

    public function findLevelForFormEn()
    {
        return $this->createQueryBuilder('l')
            ->where('l.id IN (1,2,3,4,6,7,8)')
            ->orderBy('l.level')
            // ->getQuery()
            // ->getResult()
        ;

        // return $this->createQueryBuilder('l')
        //     ->orderBy('l.level')
        // ;
    }

    // /**
    //  * @return Level[] Returns an array of Level objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Level
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

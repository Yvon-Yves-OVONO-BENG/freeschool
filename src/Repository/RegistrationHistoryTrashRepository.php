<?php

namespace App\Repository;

use App\Entity\Classroom;
use App\Entity\RegistrationHistory;
use App\Entity\StudentTrash;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Proxies\__CG__\App\Entity\Classroom as EntityClassroom;

/**
 * @method RegistrationHistoryTrash|null find($id, $lockMode = null, $lockVersion = null)
 * @method RegistrationHistoryTrash|null findOneBy(array $criteria, array $orderBy = null)
 * @method RegistrationHistoryTrash[]    findAll()
 * @method RegistrationHistoryTrash[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistrationHistoryTrashRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, RegistrationHistoryTrash::class);
    }

     //////////SOMME DES HISTORIQUES DES PAIEMENTS ///////////////////
     public function getSumFeesPerRubrique(Classroom $classroom)
     {
         $queryBuilder = $this->em->createQueryBuilder();
         $queryBuilder
                 ->select('s.fullName AS fullName, SUM(r.apeeFees) as apeeFees, SUM(r.computerFees) as computerFees, SUM(r.cleanSchoolFees) as cleanSchoolFees, SUM(r.medicalBookletFees) as medicalBookletFees, SUM(r.photoFees) as photoFees')
                 ->from(RegistrationHistoryTrash::class, 'r')
                 ->innerJoin(StudentTrash::class, 's')
                 ->andWhere('r.student = s.id')
                 ->andWhere('s.classroom = :classroom')
                 ->setParameter(
                     'classroom', $classroom,
                     )
                 ->groupBy('fullName')
                 ;
 
         $query = $queryBuilder->getQuery();
 
         return $query->execute();
     }

    // /**
    //  * @return RegistrationHistory[] Returns an array of RegistrationHistory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('r.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?RegistrationHistory
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

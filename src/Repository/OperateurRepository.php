<?php

namespace App\Repository;

use App\Entity\Operateur;
use App\Entity\SchoolYear;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Operateur>
 *
 * @method Operateur|null find($id, $lockMode = null, $lockVersion = null)
 * @method Operateur|null findOneBy(array $criteria, array $orderBy = null)
 * @method Operateur[]    findAll()
 * @method Operateur[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OperateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Operateur::class);
    }

    public function save(Operateur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Operateur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

     // Liste des classes à afficher dans un formulaire
     public function findForForm(SchoolYear $schoolYear)
     {
         return $this->createQueryBuilder('o')
             ->where('o.schoolYear = :schoolYear')
             ->setParameter('schoolYear', $schoolYear)
             ->addOrderBy('o.operateur')
             ;
 
     }

//    /**
//     * @return Operateur[] Returns an array of Operateur objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Operateur
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

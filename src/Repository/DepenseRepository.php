<?php

namespace App\Repository;

use App\Entity\Depense;
use App\Entity\Rubrique;
use App\Entity\SchoolYear;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Depense>
 *
 * @method Depense|null find($id, $lockMode = null, $lockVersion = null)
 * @method Depense|null findOneBy(array $criteria, array $orderBy = null)
 * @method Depense[]    findAll()
 * @method Depense[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepenseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Depense::class);
    }

    public function save(Depense $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Depense $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * SOMME DES DEPENSES PAR RUBRIQUES
     *
     * @param SchoolYear $schoolYear
     * @return void
     */
    public function getSumSpendingPerRubrique(SchoolYear $schoolYear)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('SUM(d.montant) AS SOMME, r.rubrique AS RUBRIQUE')
                ->from(Depense::class, 'd')
                ->innerJoin(Rubrique::class, 'r')
                ->andWhere('d.rubrique = r.id')
                ->andWhere('d.schoolYear = :schoolYear')
                ->setParameter('schoolYear', $schoolYear)
                ->groupBy('RUBRIQUE')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    public function getEtatDepense1(SchoolYear $schoolYear): array 
    {
        return $this->createQueryBuilder('d')
            ->innerJoin('d.rubrique', 'rb')
            ->addSelect('rb')
            ->andWhere('d.schoolYear = :schoolYear')
            ->setParameter('schoolYear', $schoolYear)
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return Depense[] Returns an array of Depense objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('d.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Depense
//    {
//        return $this->createQueryBuilder('d')
//            ->andWhere('d.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

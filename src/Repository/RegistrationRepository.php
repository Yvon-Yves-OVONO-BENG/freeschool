<?php

namespace App\Repository;

use App\Entity\Student;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\Registration;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Registration>
 *
 * @method Registration|null find($id, $lockMode = null, $lockVersion = null)
 * @method Registration|null findOneBy(array $criteria, array $orderBy = null)
 * @method Registration[]    findAll()
 * @method Registration[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RegistrationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Registration::class);
    }

    public function save(Registration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Registration $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function getEtatFinancier(SchoolYear $schoolYear)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                    ->select('SUM(r.apeeFees) AS APEE, SUM(r.computerFees) AS INFORMATIQUE, SUM(r.cleanSchoolFees) AS CLEAN_SCHOOL, SUM(r.medicalBookletFees) AS LIVRET_MEDICAL, SUM(r.photoFees) AS PHOTO, SUM(r.stampFees) AS TIMBRE') 
                    ->from(Registration::class, 'r')
                    ->where('r.schoolYear = :schoolYear')
                    ->setParameter('schoolYear', $schoolYear);

        $query = $queryBuilder->getQuery();

        return $query->execute();

    }

    //////////SOMME DES HISTORIQUES DES PAIEMENTS ///////////////////
    public function getSumFeesPerRubrique(Classroom $classroom)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('s.fullName AS fullName, r.apeeFees, r.computerFees, r.cleanSchoolFees, r.medicalBookletFees,  r.photoFees')
                ->from(Registration::class, 'r')
                ->innerJoin(Student::class, 's')
                // ->innerJoin(Classroom::class, 'c')
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

//    /**
//     * @return Registration[] Returns an array of Registration objects
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

//    public function findOneBySomeField($value): ?Registration
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

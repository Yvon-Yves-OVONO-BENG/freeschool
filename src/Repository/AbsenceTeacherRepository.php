<?php

namespace App\Repository;

use App\Entity\Term;
use App\Entity\AbsenceTeacher;
use App\Entity\SchoolYear;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<AbsenceTeacher>
 *
 * @method AbsenceTeacher|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbsenceTeacher|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbsenceTeacher[]    findAll()
 * @method AbsenceTeacher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbsenceTeacherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AbsenceTeacher::class);
    }

    public function save(AbsenceTeacher $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AbsenceTeacher $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAbsencesTeacher(Term $term): array 
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.term = :term')
            ->andWhere('a.supprime = 0')
            ->innerJoin('a.teacher', 't')
            ->addSelect('t')
            ->setParameters([
                'term' => $term,
            ])
            ->orderBy('t.fullName')
            ->getQuery()
            ->getResult()
        ;
    }


    public function getAbsencesTeacher(SchoolYear $schoolYear): array 
    {
        return $this->createQueryBuilder('a')
            ->innerJoin('a.teacher', 't')
            ->addSelect('t')
            ->andWhere('t.schoolYear = :schoolYear')
            ->andWhere('a.supprime = 0')
            ->setParameter('schoolYear', $schoolYear)
            ->orderBy('t.fullName', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

   

//    /**
//     * @return AbsenceTeacher[] Returns an array of AbsenceTeacher objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?AbsenceTeacher
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

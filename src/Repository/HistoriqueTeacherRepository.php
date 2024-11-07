<?php

namespace App\Repository;

use DateTime;
use App\Entity\Teacher;
use App\Entity\HistoriqueTeacher;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<HistoriqueTeacher>
 *
 * @method HistoriqueTeacher|null find($id, $lockMode = null, $lockVersion = null)
 * @method HistoriqueTeacher|null findOneBy(array $criteria, array $orderBy = null)
 * @method HistoriqueTeacher[]    findAll()
 * @method HistoriqueTeacher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HistoriqueTeacherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, HistoriqueTeacher::class);
    }

    public function save(HistoriqueTeacher $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HistoriqueTeacher $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function toutesLesHistoriques()
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t.id AS id, t.fullName')
                ->from(HistoriqueTeacher::class, 'h')
                ->innerJoin(Teacher::class, 't')
                ->andWhere('h.teacher = t.id')
                ->andWhere('h.supprime = 0')
                ->groupBy('id')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    public function historiqueAssiduitePeriode(DateTime $dateDebut, DateTime $dateFin): array
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t.id AS id, t.fullName')
                ->from(HistoriqueTeacher::class, 'h')
                ->innerJoin(Teacher::class, 't')
                ->andWhere('h.teacher = t.id')
                ->andWhere('h.supprime = 0')
                ->andWhere('h.enregistreLeAt BETWEEN :dateDebut AND :dateFin')
                ->setParameter('dateDebut', $dateDebut)
                ->setParameter('dateFin', $dateFin)
                ->groupBy('id')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

//    /**
//     * @return HistoriqueTeacher[] Returns an array of HistoriqueTeacher objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('h.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?HistoriqueTeacher
//    {
//        return $this->createQueryBuilder('h')
//            ->andWhere('h.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

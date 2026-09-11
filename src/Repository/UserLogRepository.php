<?php

namespace App\Repository;

use App\Entity\SchoolYear;
use App\Entity\UserLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserLog>
 *
 * @method UserLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserLog[]    findAll()
 * @method UserLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserLog::class);
    }

    public function save(UserLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserLog $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * fonction qui retourne les utilisateurs connectés du jour
     *
     * @param SchoolYear $schoolYear
     * @return array
     */
    public function findLogsOfToday(SchoolYear $schoolYear): array
    {
        $qb = $this->createQueryBuilder('ul');

        $today = new \DateTimeImmutable('today');

        $tomorrow = $today->modify('+1 day');

        return $qb->where('ul.logedAt >= :today')
                    ->andWhere('ul.logedAt < :tomorrow')
                    ->andWhere('ul.schoolYear = :schoolYear')
                    ->setParameter('today', $today)
                    ->setParameter('tomorrow', $tomorrow)
                    ->setParameter('schoolYear', $schoolYear)
                    ->orderBy('ul.logedAt', 'DESC')
                    ->getQuery()
                    ->getResult()
                    ;
    }


    /**
     * fnction qui retourne les logs en fonction d'une période
     *
     * @param SchoolYear $schoolYear
     * @param \DateTimeInterface $dateDebut
     * @param \DateTimeInterface $dateFin
     * @return array
     */
    public function findLogsBetweenDates(SchoolYear $schoolYear, \DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): array
    {
        return $this->createQueryBuilder('ul')
                    ->where('ul.logedAt >= :dateDebut')
                    ->andWhere('ul.logedAt <= :dateFin')
                    ->andWhere('ul.schoolYear = :schoolYear')
                    ->setParameter('dateDebut', $dateDebut)
                    ->setParameter('dateFin', $dateFin)
                    ->setParameter('schoolYear', $schoolYear)
                    ->orderBy('ul.logedAt', 'DESC')
                    ->getQuery()
                    ->getResult()
                    ;
    }

//    /**
//     * @return UserLog[] Returns an array of UserLog objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?UserLog
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

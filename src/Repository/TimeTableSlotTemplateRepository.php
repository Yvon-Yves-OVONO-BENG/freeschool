<?php

namespace App\Repository;

use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use App\Entity\TimeTableSlotTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TimeTableSlotTemplate>
 *
 * @method TimeTableSlotTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method TimeTableSlotTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method TimeTableSlotTemplate[]    findAll()
 * @method TimeTableSlotTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TimeTableSlotTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TimeTableSlotTemplate::class);
    }

    public function save(TimeTableSlotTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TimeTableSlotTemplate $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return TimeTableSlotTemplate[]
     */
    public function findActiveForContext(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->andWhere('t.active = :active')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'active' => true,
            ])
            ->orderBy('t.rowOrder', 'ASC')
            ->addOrderBy('t.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function deleteForContext(SchoolYear $schoolYear, SubSystem $subSystem): int
    {
        return $this->createQueryBuilder('t')
            ->delete()
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
            ])
            ->getQuery()
            ->execute();
    }
}

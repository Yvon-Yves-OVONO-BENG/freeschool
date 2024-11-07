<?php

namespace App\Repository;

use App\Entity\Decision;
use App\Entity\Classroom;
use App\Entity\ConstantsClass;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Decision|null find($id, $lockMode = null, $lockVersion = null)
 * @method Decision|null findOneBy(array $criteria, array $orderBy = null)
 * @method Decision[]    findAll()
 * @method Decision[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DecisionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Decision::class);
    }

    public function findDecisionToDisplay(Classroom $classroom): array
    {
        $level = $classroom->getLevel()->getLevel();

        if($level == 1 || $level == 2 || $level ==  3 || $level == 5 )
        {
            $qb = $this->createQueryBuilder('d')
                ->andWhere('d.decision != :decision1')
                ->andWhere('d.decision != :decision2')
                ->setParameters([
                    'decision1' => ConstantsClass::DECISION_REAPETED_IF_FAILED,
                    'decision2' => ConstantsClass::DECISION_EXPELLED_IF_FAILED,
                ])
            ;
        }elseif($level == 4 || $level == 6 )
        {
            $qb = $this->createQueryBuilder('d');

        }elseif($level == 7)
        {
            $qb = $this->createQueryBuilder('d')
                ->andWhere('d.decision = :decision1')
                ->orWhere('d.decision = :decision2')
                ->orWhere('d.decision = :decision3')
                ->orWhere('d.decision = :decision4')
                ->orWhere('d.decision = :decision5')
                ->orWhere('d.decision = :decision6')
                ->setParameters([
                    'decision1' => ConstantsClass::DECISION_REAPETED_IF_FAILED,
                    'decision2' => ConstantsClass::DECISION_EXPELLED_IF_FAILED,
                    'decision3' => ConstantsClass::DECISION_EXPELLED,
                    'decision4' => ConstantsClass::DECISION_RESIGNED,
                    'decision5' => ConstantsClass::DECISION_FINISHED,
                    'decision6' => ConstantsClass::DECISION_REAPETED
                ])
            ;
        }

        return $qb->orderBy('d.decision', "ASC")
            ->getQuery()
            ->getResult()
        ;
    }
    

    // /**
    //  * @return Decision[] Returns an array of Decision objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Decision
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

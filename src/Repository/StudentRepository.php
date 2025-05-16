<?php

namespace App\Repository;

use App\Entity\Country;
use App\Entity\Student;
use App\Entity\Handicap;
use App\Entity\Movement;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\EthnicGroup;
use App\Entity\HandicapType;
use App\Entity\ConstantsClass;
use App\Entity\RegistrationHistory;
use App\Entity\SubSystem;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @extends ServiceEntityRepository<Student>
 *
 * @method Student|null find($id, $lockMode = null, $lockVersion = null)
 * @method Student|null findOneBy(array $criteria, array $orderBy = null)
 * @method Student[]    findAll()
 * @method Student[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class StudentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Student::class);
    }

    /**
     * Undocumented function
     *
     * @param Student $entity
     * @param boolean $flush
     * @return void
     */
    public function save(Student $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Undocumented function
     *
     * @param Student $entity
     * @param boolean $flush
     * @return void
     */
    public function remove(Student $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Undocumented function
     *
     * @param Classroom $classroom
     * @param SchoolYear $schoolYear
     * @return array
     */
    public function findAllToDisplay(Classroom $classroom, SchoolYear $schoolYear): array 
    {
        return $this->createQueryBuilder('s')
            ->where('s.classroom = :classroom')
            ->andWhere('s.schoolYear = :schoolYear')
            ->setParameters([
                'classroom' => $classroom,
                'schoolYear' => $schoolYear
            ])
            ->innerJoin('s.sex', 'sx')
            ->innerJoin('s.classroom', 'cl')
            ->innerJoin('s.repeater', 'rp')
            ->addSelect('sx')
            ->addSelect('cl')
            ->addSelect('rp')
            ->orderBy('s.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Undocumented function
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @return array
     */
    public function findToDisplayAllStudentCycle1(SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('s')
            ->addSelect('sx')
            ->addSelect('cl')
            ->addSelect('rp')
            ->addSelect('lv')
            ->addSelect('cy')
            ->innerJoin('s.sex', 'sx')
            ->innerJoin('s.classroom', 'cl')
            ->innerJoin('s.repeater', 'rp')
            ->innerJoin('cl.level', 'lv')
            ->innerJoin('lv.cycle', 'cy')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('s.subSystem = :subSystem')
            ->andWhere('cl.level = lv.id')
            ->andWhere('cy.cycle = 1')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->orderBy('s.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Undocumented function
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @return array
     */
    public function findToDisplayAllStudentCycle2(SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('s')
        ->andWhere('s.schoolYear = :schoolYear')
        ->andWhere('s.subSystem = :subSystem')
        ->andWhere('cl.level = lv.id')
        ->andWhere('cy.cycle = 2')
        ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
        ->innerJoin('s.sex', 'sx')
        ->innerJoin('s.classroom', 'cl')
        ->innerJoin('s.repeater', 'rp')
        ->innerJoin('cl.level', 'lv')
        ->innerJoin('lv.cycle', 'cy')
        ->addSelect('sx')
        ->addSelect('cl')
        ->addSelect('rp')
        ->addSelect('lv')
        ->addSelect('cy')
        ->orderBy('s.fullName')
        ->getQuery()
        ->getResult()
        ;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function findMaxId(): array 
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.id', 'DESC')
            ->setFirstResult(0)
            ->setMaxResults(1)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Undocumented function
     *
     * @param Classroom $classroom
     * @param SchoolYear $schoolYear
     * @return array
     */
    public function findResponsableStudents(Classroom $classroom, SchoolYear $schoolYear): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('s.classroom = :classroom')
            ->innerJoin('s.responsability', 'r')
            ->addSelect('r')
            ->andWhere('r.responsability != :responsability')
            ->andWhere('r.responsability IS NOT NULL')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'classroom' => $classroom, 
                'responsability' => ConstantsClass::RESPONSABILITY_DEFAULT
            ])
            ->getQuery()
            ->getResult()

        ;
    }

    /**
     * Undocumented function
     *
     * @param SchoolYear $schoolYear
     * @param Classroom|null $classroom
     * @param EthnicGroup|null $ethnicGroup
     * @param Movement|null $movement
     * @param Handicap|null $handicap
     * @param HandicapType|null $handicapType
     * @param Country|null $country
     * @return array
     */
    public function findParticularStudent(SchoolYear $schoolYear, Classroom $classroom = null, EthnicGroup $ethnicGroup = null, Movement $movement = null, Handicap $handicap = null, HandicapType $handicapType = null, Country $country = null): array
    {
        $qb = $this->createQueryBuilder('s')
                ->andWhere('s.schoolYear = :schoolYear')
                ->setParameter('schoolYear', $schoolYear);

        if($classroom)
        {
            $qb->andWhere('s.classroom = :classroom')->setParameter('classroom', $classroom);
        }

        if($ethnicGroup)
        {
            $qb->andWhere('s.ethnicGroup = :ethnicGroup')->setParameter('ethnicGroup', $ethnicGroup);
        }

        if($movement)
        {
            $qb->andWhere('s.movement = :movement')->setParameter('movement', $movement);
        }

        if($handicap)
        {
            $qb->andWhere('s.handicap = :handicap')->setParameter('handicap', $handicap);
        }

        if($handicapType)
        {
            $qb->andWhere('s.handicapType = :handicapType')->setParameter('handicapType', $handicapType);
        }

        if($country)
        {
            $qb->andWhere('s.country = :country')->setParameter('country', $country);
        }

        return $qb->getQuery()->getResult();
    }

    //////////SOMME DES HISTORIQUES DES PAIEMENTS ///////////////////
    /**
     * Undocumented function
     *
     * @param Classroom $classroom
     * @return void
     */
    public function getSumFeesPerRubrique(Classroom $classroom)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('s.fullName AS fullName, c.classroom AS classroom, SUM(r.apeeFees) as apeeFees, SUM(r.computerFees) as computerFees, SUM(r.cleanSchoolFees) as cleanSchoolFees, SUM(r.medicalBookletFees) as medicalBookletFees, SUM(r.photoFees) as photoFees')
                ->from(Student::class, 's')
                ->innerJoin(Classroom::class, 'c')
                ->innerJoin(RegistrationHistory::class, 'r')
                ->andWhere('s.classroom = c.id')
                ->andWhere('r.student = s.id')
                ->andWhere('c.classroom = :classroom')
                ->setParameter(
                    'classroom', $classroom,
                    )
                // ->groupBy('fullName')
                ;

        $query = $queryBuilder->getQuery();

        return $query->execute();
    }


    /**
     * Retourne les élèves admis
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentAdmis(Classroom $classroom): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.decision', 'd')
            ->addSelect('d')
            ->where('s.decision = :decision')
            ->andWhere('s.classroom = :classroom')
            ->setParameters([
                'decision' => 1,
                'classroom' => $classroom,
            ])
            ->getQuery()
            ->getResult()
        ;
    }
    
    /**
     * Retourne les élèves admis
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentRepeater(Classroom $classroom): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.decision', 'd')
            ->addSelect('d')
            ->where('s.decision = :decision')
            ->andWhere('s.classroom = :classroom')
            ->setParameters([
                'decision' => 2,
                'classroom' => $classroom,
            ])
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return Student[] Returns an array of Student objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Student
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

<?php

namespace App\Repository;

use App\Entity\ConstantsClass;
use App\Entity\Department;
use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Department|null find($id, $lockMode = null, $lockVersion = null)
 * @method Department|null findOneBy(array $criteria, array $orderBy = null)
 * @method Department[]    findAll()
 * @method Department[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DepartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Department::class);
    }

    public function findToDisplay(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.schoolYear = :schoolYear')
            ->andWhere('d.subSystem = :subSystem')
            ->leftJoin('d.educationalFacilitator', 't')
            ->addSelect('t')
            ->leftJoin('t.grade', 'g')
            ->addSelect('g')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->orderBy('d.department', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }


    public function findDepartments(SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.schoolYear = :schoolYear')
            ->andWhere('d.department != :department')
            ->andWhere('d.subSystem = :subSystem')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'department' => ConstantsClass::OTHERS_DEPARTMENT,
                'subSystem' => $subSystem,
            ])
            ->leftJoin('d.educationalFacilitator', 't')
            ->addSelect('t')
            ->leftJoin('t.grade', 'g')
            ->addSelect('g')
            ->orderBy('d.department', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDepartmentsForFacilitator(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.schoolYear = :schoolYear')
            ->andWhere('d.subSystem = :subSystem')
            ->andWhere('d.department != :department')
            ->leftJoin('d.educationalFacilitator', 't')
            ->addSelect('t')
            ->leftJoin('t.grade', 'g')
            ->addSelect('g')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'department' => ConstantsClass::OTHERS_DEPARTMENT
            ])
            ->orderBy('d.department', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findDepartmentWithoutEducationalFacilitator(SchoolYear $schoolYear): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.schoolYear = :schoolYear')
            ->andWhere('d.educationalFacilitator IS NULL')
            ->setParameter('schoolYear', $schoolYear)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.schoolYear = :schoolYear')
            ->andWhere('d.subSystem = :subSystem')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->orderBy('d.department', 'ASC');
    
    }


    // /**
    //  * @return Department[] Returns an array of Department objects
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
    public function findOneBySomeField($value): ?Department
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

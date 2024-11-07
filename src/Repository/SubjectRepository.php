<?php

namespace App\Repository;

use App\Entity\Lesson;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\SchoolYear;
use App\Entity\SubSystem;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * @method Subject|null find($id, $lockMode = null, $lockVersion = null)
 * @method Subject|null findOneBy(array $criteria, array $orderBy = null)
 * @method Subject[]    findAll()
 * @method Subject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SubjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Subject::class);
    }

    // Liste des matières à afficher dans un formulaire
    public function findForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('s.subSystem = :subSystem')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->orderBy('s.subject')
        ;
    }

    public function findToDisplay(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('s.subSystem = :subSystem')
            ->leftJoin('s.department', 'd')
            ->addSelect('d')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->orderBy('s.subject', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * je sélectionne les matières qu'un enseignant dispense
     */
    public function findSubjectPerTeacher(Teacher $teacher)
    {
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('s')
                ->from(Subject::class, 's')
                ->innerJoin(Lesson::class, 'l')
                ->andWhere('l.subject = s.id')
                ->andWhere('l.teacher = :teacher')
                ->setParameter('teacher', $teacher)
                ;
        return $queryBuilder ;
    }

    // /**
    //  * @return Subject[] Returns an array of Subject objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Subject
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

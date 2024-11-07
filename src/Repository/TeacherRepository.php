<?php

namespace App\Repository;

use App\Entity\Duty;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\Progress;
use App\Entity\Classroom;
use App\Entity\SubSystem;
use App\Entity\Department;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Teacher|null find($id, $lockMode = null, $lockVersion = null)
 * @method Teacher|null findOneBy(array $criteria, array $orderBy = null)
 * @method Teacher[]    findAll()
 * @method Teacher[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeacherRepository extends ServiceEntityRepository
{
    protected $schoolYearRepository;
    protected $nextYearRepository;

    public function __construct(ManagerRegistry $registry, SchoolYearRepository $schoolYearRepository, NextYearRepository $nextYearRepository, protected EntityManagerInterface $em)
    {
        parent::__construct($registry, Teacher::class);

        $this->schoolYearRepository = $schoolYearRepository;
        $this->nextYearRepository = $nextYearRepository;
    } 

    // Liste des enseignants à afficher dans le select d'un formulaire
    public function findTeacherForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('t')
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->andWhere('t.supprime = 0')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->orderBy('t.fullName')
        ;
    }

     // Liste des enseignants à afficher dans le select d'un formulaire
     public function findHeadmasterForForm(SchoolYear $schoolYear)
     {
         return $this->createQueryBuilder('t')
             ->where('t.schoolYear = :schoolYear')
             ->andWhere('t.supprime = 0')
             ->setParameter('schoolYear', $schoolYear)
             ->orderBy('t.fullName')
         ;
     }

    // Liste des surveillants à afficher dans le select d'un formulaire
    public function findSupervisorForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('t')
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->andWhere('t.supprime = 0')
            ->innerJoin('t.duty', 'd')
            ->addSelect('d')
            ->andWhere('d.duty = :duty')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'duty' => ConstantsClass::SUPERVISOR_DUTY
            ])
            ->orderBy('t.fullName')
        ;
    }

    // Liste des censeurs à afficher dans le select d'un formulaire
    public function findCensorForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('t')
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->andWhere('t.supprime = 0')
            ->innerJoin('t.duty', 'd')
            ->addSelect('d')
            ->andWhere('d.duty = :duty')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'duty' => ConstantsClass::CENSOR_DUTY
            ])
            ->orderBy('t.fullName')
        ;
    }

    // Liste des conseillers à afficher dans le select d'un formulaire
    public function findCounsellorForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('t')
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->innerJoin('t.duty', 'd')
            ->addSelect('d')
            ->andWhere('d.duty = :duty')
            ->andWhere('t.supprime = 0')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'duty' => ConstantsClass::COUNSELLOR_DUTY
            ])
            ->orderBy('t.fullName')
        ;
    }

    // Liste du personnel de l'action sociale à afficher dans le select d'un formulaire
    public function findSocialActionForForm(SchoolYear $schoolYear, SubSystem $subSystem)
    {
        return $this->createQueryBuilder('t')
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->innerJoin('t.duty', 'd')
            ->addSelect('d')
            ->andWhere('d.duty = :duty')
            ->andWhere('t.supprime = 0')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'duty' => ConstantsClass::SOCIAL_DUTY
            ])
            ->orderBy('t.fullName')
        ;
    }

    public function findAllToDisplay(SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        return $this->createQueryBuilder('t')
            ->where('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->andWhere('t.supprime = 0')
            ->setParameters(['schoolYear' => $schoolYear, 'subSystem' => $subSystem])
            ->innerJoin('t.duty', 'd')
            ->innerJoin('t.sex', 'sx')
            ->innerJoin('t.grade', 'g')
            ->addSelect('d')
            ->addSelect('sx')
            ->addSelect('g')
            ->orderBy('t.fullName')
            ->getQuery()
            ->getResult()    
        ;

    }

    /**
     * On recupère les enseignants pour le staff
     */
    public function findTeachersForStaff(SchoolYear $schoolYear, SubSystem $subSystem): array
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.schoolYear = :schoolYear')
            ->andWhere('t.subSystem = :subSystem')
            ->andWhere('t.supprime = 0')
            ->leftJoin('t.duty', 'd')
            ->addSelect('d')
            ->andWhere('d.duty != :headmasterDuty')
            ->andWhere('d.duty != :censorDuty')
            ->andWhere('d.duty != :supervisorDuty')
            ->andWhere('d.duty != :appsDuty')
            ->andWhere('d.duty != :chiefOrientationDuty')
            ->andWhere('d.duty != :chiefSportDuty')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'headmasterDuty' => ConstantsClass::HEADMASTER_DUTY,
                'censorDuty' => ConstantsClass::CENSOR_DUTY,
                'supervisorDuty' => ConstantsClass::SUPERVISOR_DUTY,
                'appsDuty' => ConstantsClass::APPS_DUTY,
                'chiefOrientationDuty' => ConstantsClass::CHIEF_ORIENTATION_DUTY,
                'chiefSportDuty' => ConstantsClass::SPORT_SERVICE_DUTY
            ])
            ->orderBy('t.fullName')
            ->getQuery()
            ->getResult()
        ;

    }


    /**
     * je sélectionne les enseignants qui dispensent les cours
     */
    public function findTeacherPerClassroom(Subject $subject): array 
    {
        // return $this->
        $queryBuilder = $this->em->createQueryBuilder();
        $queryBuilder
                ->select('t')
                ->from(Teacher::class, 't')
                ->innerJoin(Progress::class, 'p')
                ->andWhere('p.teacher = t.id')
                ->andWhere('p.subject = :subject')
                ->andWhere('t.supprime = 0')
                ->setParameter('subject', $subject)
                ;
        
        $query = $queryBuilder->getQuery();

        return $query->execute();
    }

    // /**
    //  * @return Teacher[] Returns an array of Teacher objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Teacher
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

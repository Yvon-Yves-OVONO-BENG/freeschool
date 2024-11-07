<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Entity\SubSystem;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', \get_class($user)));
        }

        $user->setPassword($newHashedPassword);

        $this->save($user, true);
    }

   

    public function findUserByUserType(string $duty, SchoolYear $schoolYear, SubSystem $subSystem): array 
    {
        if ($duty) 
        {
            $qb = $this->createQueryBuilder('u')
                        ->innerJoin('u.teacher', 't')
                        ->addSelect('t')
                        ->andWhere('t.schoolYear = :schoolYear')
                        ->andWhere('t.subSystem = :subSystem')
                        ->setParameter('schoolYear', $schoolYear)
                        ->setParameter('subSystem', $subSystem)
                        ->orderBy('t.fullName')
            ;

            if ($duty !== ConstantsClass::TEACHER_DUTY) 
            {
                if($duty == ConstantsClass::HEADMASTER_DUTY)
                {
                    $qb->innerJoin('t.duty', 'd')
                        ->addSelect('d')
                        ->andWhere('d.duty = :headmasterDuty')
                        ->orWhere('d.duty = :directorDuty')
                        ->setParameter('headmasterDuty', ConstantsClass::HEADMASTER_DUTY)
                        ->setParameter('directorDuty', ConstantsClass::DIRECTOR_DUTY)
                    ;
                }else
                {
                    $qb->innerJoin('t.duty', 'd')
                        ->addSelect('d')
                        ->andWhere('d.duty = :duty')
                        ->setParameter('duty', $duty) 
                    ;

                }
            }
        }else 
        {
            $qb = $this->createQueryBuilder('u')
                ->where('u.teacher IS NULL')
                ->orderBy('u.fullName')
            ;
        }
        
        return $qb->getQuery()
            ->getResult()
        ;


    }
//    /**
//     * @return User[] Returns an array of User objects
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

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

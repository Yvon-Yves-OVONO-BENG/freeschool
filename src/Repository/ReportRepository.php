<?php

namespace App\Repository;

use App\Entity\Term;
use App\Entity\Report;
use App\Entity\Decision;
use App\Entity\Classroom;
use App\Entity\SchoolYear;
use App\Entity\ConstantsClass;
use App\Entity\Cycle;
use App\Entity\Level;
use App\Entity\Student;
use App\Entity\SubSystem;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Report>
 *
 * @method Report|null find($id, $lockMode = null, $lockVersion = null)
 * @method Report|null findOneBy(array $criteria, array $orderBy = null)
 * @method Report[]    findAll()
 * @method Report[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReportRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Report::class);
    }

    public function save(Report $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Report $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


    /**
     * Fonction qui retourne les 5 premiers élèves de l'année
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Term $term
     * @return array
     */
    public function findSchoolTopFiveStudents(SchoolYear $schoolYear, SubSystem $subSystem, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('st.schoolYear = :schoolYear')
            ->andWhere('st.subSystem = :subSystem')
            ->andWhere('st.supprime = 0')
            ->andWhere('r.term = :term')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'term' => $term,
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * fonction qui retourne les 5 meileurs élèves par cycle
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Cycle $cycle
     * @param Term $term
     * @return array
     */
    public function findTopFiveStudentsByCycle(SchoolYear $schoolYear, SubSystem $subSystem, Cycle $cycle, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('st.id = r.student')
            ->innerJoin('st.classroom', 'cl')
            ->innerJoin('cl.level', 'lv')
            ->innerJoin('lv.cycle', 'cy')
            ->andWhere('lv.cycle = :cycle')
            ->andWhere('st.schoolYear = :schoolYear')
            ->andWhere('st.subSystem = :subSystem')
            ->andWhere('st.supprime = 0')
            ->andWhere('r.term = :term')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'cycle' => $cycle,
                'term' => $term,
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * fonction qui retourne les 5 meilleurs élèves par niveau
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Level $level
     * @param Term $term
     * @return array
     */
    public function findTopFiveStudentsByLevel(SchoolYear $schoolYear, SubSystem $subSystem, Level $level, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('st.id = r.student')
            ->innerJoin('st.classroom', 'cl')
            ->andWhere('cl.level = :level')
            ->andWhere('st.schoolYear = :schoolYear')
            ->andWhere('st.subSystem = :subSystem')
            ->andWhere('st.supprime = 0')
            ->andWhere('r.term = :term')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'level' => $level,
                'term' => $term,
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * requête qui retourne les meileurs élèves des niveaux
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Level $level
     * @param Term $term
     * @return array
     */
    public function findBestStudentsByLevel(SchoolYear $schoolYear, SubSystem $subSystem, Level $level, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('st.id = r.student')
            ->innerJoin('st.classroom', 'cl')
            ->andWhere('cl.level = :level')
            ->andWhere('st.schoolYear = :schoolYear')
            ->andWhere('st.subSystem = :subSystem')
            ->andWhere('st.supprime = 0')
            ->andWhere('r.term = :term')
            ->andWhere('r.moyenne >= 12.5')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'level' => $level,
                'term' => $term,
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * fonction qui retourne les 5 premiers dans une classe
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findTopFiveStudentsByClassroom(SchoolYear $schoolYear, SubSystem $subSystem, Classroom $classroom, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('st.id = r.student')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('st.schoolYear = :schoolYear')
            ->andWhere('st.subSystem = :subSystem')
            ->andWhere('st.supprime = 0')
            ->andWhere('r.term = :term')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'classroom' => $classroom,
                'term' => $term,
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * requete qui renvoie les meilleurs élèves filles dans les classes scientifiques
     *
     * @param SchoolYear $schoolYear
     * @param SubSystem $subSystem
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findTopGirlsStudentsByScienceClassroom(SchoolYear $schoolYear, SubSystem $subSystem, Classroom $classroom, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->innerJoin('st.sex', 'sx')
            ->addSelect('st')
            ->addSelect('sx')
            ->andWhere('st.id = r.student')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('sx.sex = :sex')
            ->andWhere('st.schoolYear = :schoolYear')
            ->andWhere('st.subSystem = :subSystem')
            ->andWhere('r.term = :term')
            ->andWhere('r.moyenne >= 12.5')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'schoolYear' => $schoolYear,
                'subSystem' => $subSystem,
                'classroom' => $classroom,
                'sex' => "F",
                'term' => $term,
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recupère les students à afficher pour tableau d'honneur
     *
     * @param Classroom $classroom
     * @return array
     */
    public function findStudentToDisplayRollOfHonor(Classroom $classroom, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.term = :term')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'classroom' => $classroom,
                'term' => $term
            ])
            ->orderBy('st.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Recupère les students à afficher pour tableau d'honneur
     *
     * @param Classroom $classroom
     * @return array
     */
    public function findStudentToPrintRollOfHonor(Classroom $classroom, Term $term): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.sex', 'sx')
            ->addSelect('sx')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.term = :term')
            ->andWhere('r.moyenne >= :moyenne')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'classroom' => $classroom,
                'term' => $term,
                'moyenne' => ConstantsClass::ROLL_OF_HONOUR
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Recupère un report si les notes sont deja enregistrées
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return void
     */
    public function findAlreadyReport(Classroom $classroom, Term $term = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('st.supprime = 0')
            ->setParameter('classroom', $classroom);
        
            if($term)
            {
                $qb->andWhere('r.term = :term')
                    ->setParameter('term', $term);
            }

        return $qb->getQuery()
                ->getResult();

    }


    /**
     * Recupère les reports pour les délibérations
     *
     * @param Classroom $classroom
     * @return array
     */
    public function findReportForDeliberation(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('r.term', 'tm')
            ->addSelect('tm')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('st.supprime = 0')
            ->setParameter('classroom', $classroom)
            ->orderBy('r.student')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les students admis ou redoublant ou exclu selon la décision
     *
     * @param Classroom $classroom
     * @param Decision $decision
     * @return void
     */
    public function findAllByDecision(Classroom $classroom, Decision $decision): array 
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('st')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('st.decision = :decision')
            ->andWhere('st.supprime = 0')
            // ->leftJoin('st.nextClassroom', 'ncl')
            // ->addSelect('ncl')
            ->setParameters([
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'decision' => $decision
            ])
            ->orderBy('st.fullName')
            ->getQuery()
            ->getResult()
        ;
    }

    
    /**
     * Retourne les élèves classés par order de mérite
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findClassifiedStudents(Classroom $classroom, Term $term): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->where('r.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'term' => $term,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les élèves classés par order de mérite pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findClassifiedStudentsEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->andWhere('r.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'term' => 4,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les élèves admis par order de mérite pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentsAdmisEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_PASSED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Retourne les élèves redoublablants pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentsRepeaterEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_REAPETED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Retourne les élèves exclus pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentsExpelledEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_EXPELLED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Retourne les élèves démissionnaires pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentsResignedEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            // ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_RESIGNED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                // 'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Retourne les élèves redoublablants si echec pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentsRepeaterIfFailedEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_REAPETED_IF_FAILED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Retourne les élèves exclus si echec pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentsExpelledIfFailedEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_EXPELLED_IF_FAILED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Retourne les élèves admis au rattrapage pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findStudentsCatchuppedEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_CATCHUPPED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne les élèves terminéspour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findFinishedEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('st.decision', 'd')
            ->addSelect('d')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('d.decision = :decision')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'decision' => ConstantsClass::DECISION_FINISHED,
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }


    /**
     * Retourne la moyenne du 1er des élèves classés pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findAverageFirstStudentsEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('r.term', 'tm')
            ->where('tm.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * Retourne la moyennes du dernier des élèves classés pour la fin d'année
     *
     * @param Classroom $classroom
     * @param Term $term
     * @return array
     */
    public function findAverageLastStudentsEndYear(Classroom $classroom): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.student', 'st')
            ->addSelect('st')
            ->innerJoin('r.term', 't')
            ->addSelect('t')
            ->where('t.term = :term')
            ->andWhere('st.classroom = :classroom')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'term' => ConstantsClass::ANNUEL_TERM,
                'classroom' => $classroom,
            ])
            ->orderBy('r.moyenne', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findClassifiedStudent(Classroom $classroom, Term $term, SchoolYear $schoolYear): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin(Student::class, 's')
            ->innerJoin(SchoolYear::class, 'sc')
            ->Where('s.schoolYear = sc.id')
            ->andwhere('r.term = :term')
            ->andWhere('s.classroom = :classroom')
            ->andWhere('s.schoolYear = :schoolYear')
            ->andWhere('r.moyenne != :moyenne')
            ->andWhere('st.supprime = 0')
            ->setParameters([
                'term' => $term,
                'classroom' => $classroom,
                'schoolYear' => $schoolYear,
                'moyenne' => ConstantsClass::UNRANKED_AVERAGE
            ])
            ->orderBy('r.moyenne', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

//    /**
//     * @return Report[] Returns an array of Report objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Report
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}

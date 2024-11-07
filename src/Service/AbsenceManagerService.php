<?php

namespace App\Service;

use App\Entity\Term;
use App\Entity\Absence;
use App\Entity\AbsenceTeacher;
use App\Repository\TermRepository;
use App\Repository\AbsenceRepository;
use App\Repository\AbsenceTeacherRepository;
use App\Repository\StudentRepository;
use App\Repository\ClassroomRepository;
use App\Repository\TeacherRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
// use Symfony\Component\Security\Core\Security;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;

class AbsenceManagerService
{
    public function __construct(
        protected Security $security, 
        protected EntityManagerInterface $em, 
        protected TermRepository $termRepository, 
        protected TeacherRepository $teacherRepository, 
        protected StudentRepository $studentRepository, 
        protected AbsenceRepository $absenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected AbsenceTeacherRepository $absenceTeacherRepository,
        )
    {}

    /**
     * Save absences in the database
     *
     * @param Sequence $selectedSequence
     * @param Lesson $selectedLesson
     * @return void
     */
    public function saveAbsences(Term $selectedTerm, Request $request)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $elementIsPerssited = false;

        $numberOfStudents = $request->request->get('numberOfStudents');
                
        for ($i=1; $i <= $numberOfStudents ; $i++) 
        {
            $absence = $request->request->get('absence'.$i);

            $student = $this->studentRepository->find($request->request->get('student'.$i));

            // On verifie si l'absence n'existe pas encore
            $studentAbsence = $this->absenceRepository->findOneBy([
                'term' => $selectedTerm,
                'student' => $student
            ]);
            
            if($studentAbsence == null) // Si l'absence n'existe pas on l'insère
            {
                $absenceToSave = new Absence();

                #je fabrique mon slug
                $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
                $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
                $characts   .= '1234567890'; 
                $slug      = ''; 
        
                for($i=0;$i < 15;$i++) 
                { 
                    $slug .= substr($characts,rand()%(strlen($characts)),1); 
                }

                //////j'extrait la derniere matiere de la table
                $dernierAbsence = $this->absenceRepository->findBy([],['id' => 'DESC'],1,0);

                /////je récupère l'id du sernier utilisateur
                
                if ($dernierAbsence) 
                {
                    $id = $dernierAbsence[0]->getId();
                } 
                else 
                {
                    $id = 1;
                }

                $absenceToSave->setTerm($selectedTerm)
                    ->setStudent($student)
                    ->setAbsence($absence)
                    ->setCreatedBy($this->security->getUser())
                    ->setUpdatedBy($this->security->getUser())
                    ->setSlug($slug.$id)
                    ;
                    
                $this->em->persist($absenceToSave);
                $elementIsPerssited = true;
               
            } 
            
        }
            
        if($elementIsPerssited)
        {
            $this->em->flush();
        }
    }


    /**
     * Save absences in the database
     *
     * @param Sequence $selectedSequence
     * @param Lesson $selectedLesson
     * @return void
     */
    public function saveAbsencesTeacher(Term $selectedTerm, Request $request)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $elementIsPerssited = false;

        $numberOfTeachers = $request->request->get('numberOfTeachers');
                
        for ($i=1; $i <= $numberOfTeachers ; $i++) 
        {
            $absence = $request->request->get('absence'.$i);

            $teacher = $this->teacherRepository->find($request->request->get('teacher'.$i));

            // On verifie si l'absence n'existe pas encore
            $teacherAbsence = $this->absenceTeacherRepository->findOneBy([
                'term' => $selectedTerm,
                'teacher' => $teacher
            ]);
            
            if($teacherAbsence == null) // Si l'absence n'existe pas on l'insère
            {
                $now = new DateTime('now');
                $absenceToSave = new AbsenceTeacher();

                #je fabrique mon slug
                $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
                $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
                $characts   .= '1234567890'; 
                $slug      = ''; 
        
                for($i=0;$i < 15;$i++) 
                { 
                    $slug .= substr($characts,rand()%(strlen($characts)),1); 
                }

                //////j'extrait la derniere matiere de la table
                $dernierAbsence = $this->absenceTeacherRepository->findBy([],['id' => 'DESC'],1,0);

                /////je récupère l'id du sernier utilisateur
                
                if ($dernierAbsence) 
                {
                    $id = $dernierAbsence[0]->getId();
                } 
                else 
                {
                    $id = 1;
                }

                $absenceToSave->setTerm($selectedTerm)
                    ->setTeacher($teacher)
                    ->setAbsenceTeacher($absence)
                    ->setCreatedBy($this->security->getUser())
                    ->setCreatedAt($now)
                    ->setSlug($slug.$id)
                    ;
                    
                $this->em->persist($absenceToSave);
                $elementIsPerssited = true;
               
            } 
            
        }
            
        if($elementIsPerssited)
        {
            $this->em->flush();
        }
    }


    /**
     * Update a absence
     *
     * @param integer $absenceId
     * @param integer $absence
     * @return void
     */
    public function updateAbsence(int $absenceId, int $absence, Request $request)
    {
         /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $elementIsPerssited = false;

        $updatedAbsence = $this->absenceRepository->find($absenceId);

        // Si l'absence existe dejà, alors on la met à jour
        if($updatedAbsence !== null) 
        {
            #je fabrique mon slug
            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait la derniere matiere de la table
            $dernierAbsence = $this->absenceRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierAbsence) 
            {
                $id = $dernierAbsence[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $updatedAbsence->setAbsence($absence)
                ->setUpdatedBy($this->security->getUser())
                ->setSlug($slug.$id)
                ;

            $this->em->persist($updatedAbsence);

            $elementIsPerssited = true;
        }
        
        if($elementIsPerssited)
        {
            $this->em->flush();

        }
    }


     /**
     * Update a absence
     *
     * @param integer $absenceId
     * @param integer $absence
     * @return void
     */
    public function updateAbsenceTeacher(int $absenceId, int $absence, Request $request)
    {
         /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $elementIsPerssited = false;

        $updatedAbsence = $this->absenceTeacherRepository->find($absenceId);

        // Si l'absence existe dejà, alors on la met à jour
        if($updatedAbsence !== null) 
        {
            $now = new DateTime('now');

            $characts    = 'abcdefghijklmnopqrstuvwxyz#{};()';
            $characts   .= 'ABCDEFGHIJKLMNOPQRSTUVWXYZ#{};()';	
            $characts   .= '1234567890'; 
            $slug      = ''; 
    
            for($i=0;$i < 15;$i++) 
            { 
                $slug .= substr($characts,rand()%(strlen($characts)),1); 
            }

            //////j'extrait la derniere matiere de la table
            $dernierAbsence = $this->absenceTeacherRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierAbsence) 
            {
                $id = $dernierAbsence[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $updatedAbsence->setAbsenceTeacher($absence)
                ->setUpdatedBy($this->security->getUser())
                ->setUpdatedAt($now)
                ->setSlug($slug.$id)
                ;

            $this->em->persist($updatedAbsence);

            $elementIsPerssited = true;
        }
        
        if($elementIsPerssited)
        {
            $this->em->flush();

        }
    }


    /**
     * remove all absences for a giving classroom and term
     *
     * @param integer $termId
     * @param integer $classroomId
     * @return void
     */
    public function removeAbsences(int $termId, int $classroomId, Request $request)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $selectedTerm = $this->termRepository->find($termId);
        $selectedClassroom = $this->classroomRepository->find($classroomId);

        $elementIsRemoved = false;

        // on recupere les absences concernees
        $absencesToRemove = $this->absenceRepository->findAbsences($selectedTerm, $selectedClassroom);

        // On supprime les absences en question
        foreach ($absencesToRemove as $absenceToRemove) 
        {
            $this->em->remove($absenceToRemove);
            $elementIsRemoved = true;
        }

        if($elementIsRemoved)
        {
            $this->em->flush();
        }
    }

    /**
     * remove all absences for a giving classroom and term
     *
     * @param integer $termId
     * @param integer $classroomId
     * @return void
     */
    public function removeAbsencesTeacher(int $termId, Request $request)
    {
        /**
         * @var FlashBag
         */
        $flashBag = $request->getSession()->get('flashes');

        $selectedTerm = $this->termRepository->find($termId);

        $elementIsRemoved = false;

        // on recupere les absences concernees
        $absencesToRemove = $this->absenceTeacherRepository->findAbsencesTeacher($selectedTerm);

        // On supprime les absences en question
        foreach ($absencesToRemove as $absenceToRemove) 
        {
            $this->em->remove($absenceToRemove);
            $elementIsRemoved = true;
        }

        if($elementIsRemoved)
        {
            $this->em->flush();
        }
    }
}
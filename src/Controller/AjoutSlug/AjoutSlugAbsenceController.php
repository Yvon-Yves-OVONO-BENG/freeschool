<?php

namespace App\Controller\AjoutSlug;

use App\Repository\TermRepository;
use App\Repository\GradeRepository;
use App\Repository\LessonRepository;
use App\Repository\AbsenceRepository;
use App\Repository\DiplomaRepository;
use App\Repository\StudentRepository;
use App\Repository\SubjectRepository;
use App\Repository\TeacherRepository;
use App\Repository\ClassroomRepository;
use App\Repository\DepartmentRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AbsenceTeacherRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/ajoutSlug')]
class AjoutSlugAbsenceController extends AbstractController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected AbsenceRepository $absenceRepository,
        protected AbsenceTeacherRepository $absenceTeacherRepository,
        protected ClassroomRepository $classroomRepository,
        protected DepartmentRepository $departmentRepository,
        protected DiplomaRepository $diplomaRepository,
        protected GradeRepository $gradeRepository,
        protected LessonRepository $lessonRepository,
        protected StudentRepository $studentRepository,
        protected SubjectRepository $subjectRepository,
        protected TeacherRepository $teacherRepository,
        protected TermRepository $termRepository
    )
    {}

    #[Route('/ajout-slug', name: 'ajout_slug')]
    public function index(): Response
    {
        $absences = $this->absenceRepository->findAll();

        foreach ($absences as $absence) 
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

            $absence->setSlug($slug.$id);
            $this->em->persist($absence);
            
        }

        ///////////////
        $absenceTeachers = $this->absenceTeacherRepository->findAll();

        foreach ($absenceTeachers as $absenceTeacher) 
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
            $dernierAbsenceTeacher = $this->absenceTeacherRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierAbsenceTeacher) 
            {
                $id = $dernierAbsenceTeacher[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $absenceTeacher->setSlug($slug.$id);
            $this->em->persist($absenceTeacher);
            
        }

        ////////////////
        $classrooms = $this->classroomRepository->findAll();

        foreach ($classrooms as $classroom) 
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
            $dernierClassroom = $this->classroomRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierClassroom) 
            {
                $id = $dernierClassroom[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $classroom->setSlug($slug.$id);
            $this->em->persist($classroom);
            
        }

        ////////////////
        $departments = $this->departmentRepository->findAll();

        foreach ($departments as $department) 
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
            $dernierDepartment = $this->departmentRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDepartment) 
            {
                $id = $dernierDepartment[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $department->setSlug($slug.$id);
            $this->em->persist($department);
            
        }

        ////////
        $diplomas = $this->diplomaRepository->findAll();

        foreach ($diplomas as $diploma) 
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
            $dernierDiploma = $this->diplomaRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierDiploma) 
            {
                $id = $dernierDiploma[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $diploma->setSlug($slug.$id);
            $this->em->persist($diploma);
            
        }

        ////////////
        $grades = $this->gradeRepository->findAll();

        foreach ($grades as $grade) 
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
            $dernierGrade = $this->gradeRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierGrade) 
            {
                $id = $dernierGrade[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $grade->setSlug($slug.$id);
            $this->em->persist($grade);
            
        }

        /////////
        $lessons = $this->lessonRepository->findAll();

        foreach ($lessons as $lesson) 
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
            $dernierLesson = $this->lessonRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierLesson) 
            {
                $id = $dernierLesson[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $lesson->setSlug($slug.$id);
            $this->em->persist($lesson);
            
        }

        ////////////
        $students = $this->studentRepository->findAll();

        foreach ($students as $student) 
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
            $dernierStudent = $this->studentRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierStudent) 
            {
                $id = $dernierStudent[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $student->setSlug($slug.$id);
            $this->em->persist($student);
            
        }

        ///////////
        $subjects = $this->subjectRepository->findAll();
        foreach ($subjects as $subject) 
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
            $dernierSubject = $this->subjectRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierSubject) 
            {
                $id = $dernierSubject[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $subject->setSlug($slug.$id);
            $this->em->persist($subject);
            
        }

        ////////////
        $teachers = $this->teacherRepository->findAll();

        foreach ($teachers as $teacher) 
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
            $dernierTeacher = $this->teacherRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierTeacher) 
            {
                $id = $dernierTeacher[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $teacher->setSlug($slug.$id);
            $this->em->persist($teacher);
            
        }

        /////////////
        $terms = $this->termRepository->findAll();

        foreach ($terms as $term) 
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
            $dernierTerm = $this->termRepository->findBy([],['id' => 'DESC'],1,0);

            /////je récupère l'id du sernier utilisateur
            
            if ($dernierTerm) 
            {
                $id = $dernierTerm[0]->getId();
            } 
            else 
            {
                $id = 1;
            }

            $term->setSlug($slug.$id);
            $this->em->persist($term);
            
        }

        ////////////////////
        $this->em->flush();

        return $this->redirectToRoute('home_dashboard');
    }
}

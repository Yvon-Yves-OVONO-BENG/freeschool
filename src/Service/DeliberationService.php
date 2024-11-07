<?php

namespace App\Service;

use App\Entity\Fees;
use App\Entity\User;
use App\Entity\School;
use App\Entity\Verrou;
use App\Entity\Subject;
use App\Entity\Teacher;
use App\Entity\Decision;
use App\Entity\Classroom;
use App\Entity\SubSystem;
use App\Entity\Department;
use App\Entity\SchoolYear;
use App\Entity\VerrouReport;
use App\Entity\ConstantsClass;
use App\Entity\ReportElements\Pagination;
use App\Repository\DutyRepository;
use App\Repository\FeesRepository;
use App\Repository\TermRepository;
use App\Repository\UserRepository;
use App\Entity\UnrankedCoefficient;
use App\Repository\LevelRepository;
use App\Repository\ReportRepository;
use App\Repository\SchoolRepository;
use App\Repository\VerrouRepository;
use App\Repository\StudentRepository;
use App\Repository\SubjectRepository;
use App\Repository\TeacherRepository;
use App\Repository\DecisionRepository;
use App\Repository\NextYearRepository;
use App\Repository\SequenceRepository;
use App\Repository\ClassroomRepository;
use App\Repository\DepartmentRepository;
use App\Repository\SchoolYearRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\VerrouReportRepository;
use Symfony\Bundle\SecurityBundle\Security;
use App\Repository\VerrouSequenceRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use App\Entity\DeliberationElements\DeliberationRow;
use App\Entity\VerrouSequence;
use Symfony\Contracts\Translation\TranslatorInterface;

class DeliberationService
{

    public function __construct(
        protected Security $security, 
        protected RequestStack $request, 
        protected EntityManagerInterface $em, 
        protected FeesRepository $feesRepository, 
        protected GeneralService $generalService, 
        protected UserRepository $userRepository, 
        protected DutyRepository $dutyRepository, 
        protected TermRepository $termRepository, 
        protected TranslatorInterface $translator, 
        protected LevelRepository $levelRepository, 
        protected ReportRepository $reportRepository, 
        protected SchoolRepository $schoolRepository, 
        protected VerrouRepository $verrouRepository, 
        protected TeacherRepository $teacherRepository, 
        protected StudentRepository $studentRepository, 
        protected SubjectRepository $subjectRepository, 
        protected SchoolYearService $schoolYearService, 
        protected DecisionRepository $decisionRepository, 
        protected NextYearRepository $nextYearRepository, 
        protected SequenceRepository $sequenceRepository, 
        protected ClassroomRepository $classroomRepository, 
        protected SchoolYearRepository $schoolYearRepository, 
        protected DepartmentRepository $departmentRepository, 
        protected VerrouReportRepository $verrouReportRepository, 
        protected VerrouSequenceRepository $verrouSequenceRepository,
        )
    {}

   /**
    * Recupère les enseignants à tranferer au next school year
    *
    * @return array
    */
    public function getTeachersToTransfer(Request $request): array 
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');

        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // Enseignants de l'année en cours
        $currentYearTeachers = $this->teacherRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        // Enseignants déjà transferés au next year
        $nextYearTeachers = $this->teacherRepository->findBy([
            'schoolYear' => $nextSchoolYear
        ]);

        $matriculeLooker = function(Teacher $teacher1, Teacher $teacher2)
        {
            if($teacher1->getAdministrativeNumber() < $teacher2->getAdministrativeNumber())
            {
                return -1;

            }elseif($teacher1->getAdministrativeNumber() > $teacher2->getAdministrativeNumber())
            {
                return 1;
            }else 
            {
                return 0;
            }
        };

        return array_udiff($currentYearTeachers, $nextYearTeachers, $matriculeLooker);
    }

    /**
     * Transfère les enseignants au next school year
     *
     * @return void
     */
    public function transferTeachers(Request $request): void
    {
        $teachersToTransfer = $this->getTeachersToTransfer($request);
       
         // on recupère le next year
         $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        foreach ($teachersToTransfer as $teacher) 
        {
            // On le transfère au next year
            $newTeacher = new Teacher();

            // On le transfère au next year
            $newDepartment = new Department();

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
            $newTeacher->setGrade($teacher->getGrade())
                ->setSex($teacher->getSex())
                ->setSchoolYear($nextSchoolYear)
                ->setDuty($teacher->getDuty())
                ->setFullName($teacher->getFullName())
                ->setAdministrativeNumber($teacher->getAdministrativeNumber())
                ->setCreatedBy($this->security->getUser())
                ->setUpdatedBy($this->security->getUser())
                ->setIntegrationDate($teacher->getIntegrationDate())
                ->setPhoneNumber($teacher->getPhoneNumber())
                ->setBirthday($teacher->getBirthday())
                ->setBirthplace($teacher->getBirthplace())
                ->setAffectationDate($teacher->getAffectationDate())
                ->setMatrimonialStatus($teacher->getMatrimonialStatus())
                ->setDiploma($teacher->getDiploma())
                ->setRegion($teacher->getRegion())
                ->setStatus($teacher->getStatus())
                ->setSubSystem($teacher->getSubSystem())
                ->setDepartment($this->departmentRepository->findOneBy([
                    'schoolYear' => $nextSchoolYear,
                    'department' => $teacher->getDepartment()->getDepartment()
                ]))
                ->setSlug($slug.$id)
                ->setSupprime(0)

            ;

            $this->em->persist($newTeacher);
            $this->em->flush();

            // On créé le nouveau user associé au nouveau enseignant du next year
            if( $userTeacher = $this->userRepository->findOneByTeacher($teacher))
            {
                $user = new User();
                $user->setUsername($newTeacher->getAdministrativeNumber().$newTeacher->getId())
                    ->setFullName($newTeacher->getFullName())
                    ->setTeacher($newTeacher)
                    ->setPassword($userTeacher->getPassword())
                    ->setRoles(['ROLE_'.strtoupper($newTeacher->getDuty()->getDuty())]);
    
                $this->em->persist($user);
            }
            $this->em->flush(); 
        }
    }

     /**
      * Recupère les classes à tranferer au next school year
      *
      * @return array
      */
    public function getClassroomsToTransfer(Request $request): array 
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');

        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // Classes de l'année en cours
        $currentClassrooms = $this->classroomRepository->findBy([
            'schoolYear' => $schoolYear
        ]);

        // Classes déjà transferées au next year
        $nextYearClassrooms = $this->classroomRepository->findBy([
            'schoolYear' => $nextSchoolYear
        ]);

        $classroomLooker = function(Classroom $classroom1, Classroom $classroom2)
        {
            if($classroom1->getClassroom() < $classroom2->getClassroom())
            {
                return -1;

            }elseif($classroom1->getClassroom() > $classroom2->getClassroom())
            {
                return 1;
            }else 
            {
                return 0;
            }
        };

        return array_udiff($currentClassrooms, $nextYearClassrooms, $classroomLooker);
    }

    /**
     * Transfère les classes au next school year
     *
     * @return void
     */
    public function transferClassrooms(Request $request): void
    {
        $classroomsToTransfer = $this->getClassroomsToTransfer($request);
        
         // on recupère le next year
         $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        foreach ($classroomsToTransfer as $classroom)
        {
            // On le transfère au next year
            $newClassroom = new Classroom();

            // On le transfère au next year
            $newDepartment = new Department();

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
            
            $newClassroom->setSchoolYear($nextSchoolYear)
                ->setLevel($classroom->getLevel())
                ->setClassroom($classroom->getClassroom())
                ->setIsDeliberated(false)
                ->setCreatedBy($this->security->getUser())
                ->setUpdatedBy($this->security->getUser())
                ->setSubSystem($classroom->getSubSystem())
                ->setPrincipalTeacher($this->teacherRepository->findOneBy([
                    'schoolYear' => $nextSchoolYear,
                    'administrativeNumber' => $classroom->getPrincipalTeacher()->getAdministrativeNumber()
                ]))
                ->setSlug($slug.$id);

                if($this->teacherRepository->findOneBy([
                    'schoolYear' => $nextSchoolYear,
                    'administrativeNumber' => $classroom->getCensor()
                ]))
                {
                    $newClassroom->setCensor($this->teacherRepository->findOneBy([
                        'schoolYear' => $nextSchoolYear,
                        'administrativeNumber' => $classroom->getCensor()->getAdministrativeNumber() 
                    ]));
                }
                
                if($this->teacherRepository->findOneBy([
                    'schoolYear' => $nextSchoolYear,
                    'administrativeNumber' => $classroom->getSupervisor()
                ]))
                {
                    $newClassroom->setSupervisor($this->teacherRepository->findOneBy([
                        'schoolYear' => $nextSchoolYear,
                        'administrativeNumber' => $classroom->getSupervisor()->getAdministrativeNumber()
                    ]));
                }
                
                if($this->teacherRepository->findOneBy([
                    'schoolYear' => $nextSchoolYear,
                    'administrativeNumber' => $classroom->getCounsellor()
                ]))
                {
                    $newClassroom->setCounsellor($this->teacherRepository->findOneBy([
                        'schoolYear' => $nextSchoolYear,
                        'administrativeNumber' => $classroom->getCounsellor()->getAdministrativeNumber()
                    ]));
                }
                
                if($this->teacherRepository->findOneBy([
                    'schoolYear' => $nextSchoolYear,
                    'administrativeNumber' => $classroom->getActionSociale()
                ]))
                {
                    $newClassroom->setActionSociale($this->teacherRepository->findOneBy([
                        'schoolYear' => $nextSchoolYear,
                        'administrativeNumber' => $classroom->getActionSociale()->getAdministrativeNumber()
                    ]));
                }
                
            ;

            $this->em->persist($newClassroom);

            $unrankedCoefficient = new UnrankedCoefficient;

            $unrankedCoefficient->setClassroom($newClassroom)
                    ->setUnrankedCoefficient(ConstantsClass::UNRANKED_COEFFICIENT)
                    ->setForFirstGroup(false)
            ;

            $this->em->persist($unrankedCoefficient);
        }
        $this->em->flush();
    }

    public function getDepartmentsToTransfer(Request $request)
    {
        
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');
        $subSystem = $mySession->get('subSystem');

        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // Départements de l'année en cours
        $currentDepartments = $this->departmentRepository->findToDisplay($schoolYear, $subSystem);

        // Départements déjà transferés au next year
        $nextYearDepartments = $this->departmentRepository->findToDisplay($nextSchoolYear, $subSystem);

        $departmentLooker = function(Department $department1, Department $department2){
            if($department1->getDepartment() < $department2->getDepartment())
            {
                return -1;

            }elseif($department1->getDepartment() > $department2->getDepartment())
            {
                return 1;
            }else 
            {
                return 0;
            }
        };

        $departmentsToTransfer = array_udiff($currentDepartments, $nextYearDepartments, $departmentLooker);
        
        return $departmentsToTransfer;

    }

    public function transferDepartments(Request $request)
    {
        $departmentsToTransfer = $this->getDepartmentsToTransfer($request);

        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

       foreach ($departmentsToTransfer as $department)
       {
            // On le transfère au next year
            $newDepartment = new Department();

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

            $newDepartment->setSchoolYear($nextSchoolYear)
                ->setDepartment($department->getDepartment())
                ->setSubSystem($department->getSubSystem())
                ->setSlug($slug.$id)
                ;

           $this->em->persist($newDepartment);
       }
       $this->em->flush();
    }

    public function transferSchool(Request $request)
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');
        
        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();
        
        // Etablissement de l'année en cours
        $currentSchool = $this->schoolRepository->findOneBySchoolYear($schoolYear);
        
        // Etablissement déjà transferés au next year
        $nextSchool = $this->schoolRepository->findOneBySchoolYear($nextSchoolYear);

        if(is_null($nextSchool))
        {
            $newSchool = new School;

            $newSchool->setFrenchName($currentSchool->getFrenchName())
                ->setEnglishName($currentSchool->getEnglishName())
                ->setFrenchMotto($currentSchool->getFrenchMotto())
                ->setEnglishMotto($currentSchool->getEnglishMotto())
                ->setPobox($currentSchool->getPobox())
                ->setTelephone($currentSchool->getTelephone())
                ->setPlace($currentSchool->getPlace())
                ->setLogo($currentSchool->getLogo())
                ->setFiligree($currentSchool->getFiligree())
                ->setEmail($currentSchool->getEmail())
                ->setFrenchCountry($currentSchool->getFrenchCountry())
                ->setEnglishCountry($currentSchool->getEnglishCountry())
                ->setFrenchMinister($currentSchool->getFrenchMinister())
                ->setEnglishMinister($currentSchool->getEnglishMinister())
                ->setFrenchRegion($currentSchool->getFrenchRegion())
                ->setEnglishRegion($currentSchool->getEnglishRegion())
                ->setFrenchDivision($currentSchool->getFrenchDivision())
                ->setEnglishDivision($currentSchool->getEnglishDivision())
                ->setFrenchSubDivision($currentSchool->getFrenchSubDivision())
                ->setEnglishSubDivision($currentSchool->getEnglishSubDivision())
                ->setFrenchCountryMotto($currentSchool->getFrenchCountryMotto())
                ->setEnglishCountryMotto($currentSchool->getEnglishCountryMotto())
                ->setSchoolYear($nextSchoolYear)
                ->setServiceNote($currentSchool->getServiceNote())
                ->setHeadmaster($currentSchool->getHeadmaster())
                ->setEducation($currentSchool->getEducation())
                ->setPublic($currentSchool->isPublic())
                ->setLycee($currentSchool->isLycee())
                ->setUpdatedAt($currentSchool->getUpdatedAt())
            ;

            $this->em->persist($newSchool);
            $this->em->flush();

        }
    }

    public function getSubjectsToTransfer(Request $request)
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');
        $subSystem = $mySession->get('subSystem');

        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // Matières de l'année en cours
        $currentSubjects = $this->subjectRepository->findToDisplay($schoolYear, $subSystem);

        // Départements déjà transferés au next year
        $nextYearSubjects = $this->subjectRepository->findToDisplay($nextSchoolYear, $subSystem);

        $subjectLooker = function(Subject $subject1, Subject $subject2){
            if($subject1->getSubject() < $subject2->getSubject())
            {
                return -1;

            }elseif($subject1->getSubject() > $subject2->getSubject())
            {
                return 1;
            }else 
            {
                return 0;
            }
        };

        $departmentsToTransfer = array_udiff($currentSubjects, $nextYearSubjects, $subjectLooker);
        
        return $departmentsToTransfer;
    }

    public function transferSubjects(Request $request)
    {
        $subjectsToTransfer = $this->getSubjectsToTransfer($request);

        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

       foreach ($subjectsToTransfer as $subject)
       {
            // On le transfère au next year
            $newSubject = new Subject;

            // On le transfère au next year
            $newDepartment = new Department();

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

            $newSubject->setSchoolYear($nextSchoolYear)
                ->setSubject($subject->getSubject())
                ->setCategory($subject->getCategory())
                ->setSubSystem($subject->getSubSystem())
                ->setDepartment($this->departmentRepository->findOneBy([
                    'schoolYear' => $nextSchoolYear,
                    'department' => $subject->getDepartment()->getDepartment()
                ]))
                ->setSlug($slug.$id)
            ;

           $this->em->persist($newSubject);
       }
       $this->em->flush();
    }

    public function setEducationalFacilitator(Request $request)
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');
        
        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // on recupère les departement du next year qui n'ont pas d'AP
        $nextYearDepartments = $this->departmentRepository->findDepartmentWithoutEducationalFacilitator($nextSchoolYear);

        foreach ($nextYearDepartments as $department) 
        {
            $oldDepartment = $this->departmentRepository->findOneBy([
                'schoolYear' => $schoolYear,
                'department' => $department->getDepartment()
            ]);

            if($oldDepartment != null)
            {
                $oldEducationalFacilitator = $oldDepartment->getEducationalFacilitator();
                
                if($oldEducationalFacilitator != null)
                {
                    $department->setEducationalFacilitator($oldEducationalFacilitator);
                    $this->em->persist($department);
                }
            }

        }

        $this->em->flush();
    }

    public function setSchoolHeadmaster(Request $request)
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');
        
        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // Etablissement de l'année en cours
        $currentSchool= $this->schoolRepository->findOneBySchoolYear($schoolYear);

        // Etablissement déjà transferés au next year
        $nextSchool = $this->schoolRepository->findOneBySchoolYear($nextSchoolYear);

        if(is_null($nextSchool->getHeadmaster()))
        {
            $nextSchool->setHeadmaster($this->teacherRepository->findOneBy([
                'schoolYear' => $nextSchoolYear,
                'duty' => $this->dutyRepository->findOneByDuty(ConstantsClass::HEADMASTER_DUTY)
            ]));
            $this->em->flush();
        }
    }

    public function createVerrou()
    {
        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        $nextVerrou = $this->verrouRepository->findOneBySchoolYear($nextSchoolYear);

        if(is_null($nextVerrou))
        {
            $newVerrou = new Verrou;
            $newVerrou->setSchoolYear($nextSchoolYear)->setVerrou(false);

            $this->em->persist($newVerrou);
            $this->em->flush();
        }

    }

    public function createVerrouReport()
    {
        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // on recupère les trimestres
        $term1 = $this->termRepository->findOneByTerm(1);
        $term2 = $this->termRepository->findOneByTerm(2);
        $term3 = $this->termRepository->findOneByTerm(3);
        $term0 = $this->termRepository->findOneByTerm(0);

        // on recupère les verrouReport du nextYear
        $nextVerrouReportTerm1 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'term' => $term1
        ]);
        $nextVerrouReportTerm2 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'term' => $term2
        ]);
        $nextVerrouReportTerm3 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'term' => $term3
        ]);
        $nextVerrouReportTerm0 = $this->verrouReportRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'term' => $term0
        ]);

        if(is_null($nextVerrouReportTerm1))
        {
            $newVerrouReport = new VerrouReport;
            $newVerrouReport->setSchoolYear($nextSchoolYear)
                ->setTerm($term1)
                ->setVerrouReport(false)
                ;

            $this->em->persist($newVerrouReport);
        }

        if(is_null($nextVerrouReportTerm2))
        {
            $newVerrouReport = new VerrouReport;
            $newVerrouReport->setSchoolYear($nextSchoolYear)
                ->setTerm($term2)
                ->setVerrouReport(false)
                ;

            $this->em->persist($newVerrouReport);
        }

        if(is_null($nextVerrouReportTerm3))
        {
            $newVerrouReport = new VerrouReport;
            $newVerrouReport->setSchoolYear($nextSchoolYear)
                ->setTerm($term3)
                ->setVerrouReport(false)
                ;

            $this->em->persist($newVerrouReport);
        }

        if(is_null($nextVerrouReportTerm0))
        {
            $newVerrouReport = new VerrouReport;
            $newVerrouReport->setSchoolYear($nextSchoolYear)
                ->setTerm($term0)
                ->setVerrouReport(false)
                ;

            $this->em->persist($newVerrouReport);
        }

        $this->em->flush();


    }

    public function createVerrouSequence()
    {
        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();
       
        // on recupère les trimestres
        $sequence1 = $this->sequenceRepository->findOneBySequence(1);
        $sequence2 = $this->sequenceRepository->findOneBySequence(2);
        $sequence3 = $this->sequenceRepository->findOneBySequence(3);
        $sequence4 = $this->sequenceRepository->findOneBySequence(4);
        $sequence5 = $this->sequenceRepository->findOneBySequence(5);
        $sequence6 = $this->sequenceRepository->findOneBySequence(6);
        
        // on recupère les verrouReport du nextYear
        $nextVerrouSequence1 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'sequence' => $sequence1
        ]);
        $nextVerrouSequence2 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'sequence' => $sequence2
        ]);
        $nextVerrouSequence3 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'sequence' => $sequence3
        ]);
        $nextVerrouSequence4 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'sequence' => $sequence4
        ]);
        $nextVerrouSequence5 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'sequence' => $sequence5
        ]);
        $nextVerrouSequence6 = $this->verrouSequenceRepository->findOneBy([
            'schoolYear' => $nextSchoolYear,
            'sequence' => $sequence6
        ]);

        if(is_null($nextVerrouSequence1))
        {
            $newVerrouSequence = new VerrouSequence;

            $newVerrouSequence->setSchoolYear($nextSchoolYear)
                ->setSequence($sequence1)
                ->setVerrouSequence(false)
                ;

            $this->em->persist($newVerrouSequence);
        }

        if(is_null($nextVerrouSequence2))
        {
            $newVerrouSequence = new VerrouSequence;
            $newVerrouSequence->setSchoolYear($nextSchoolYear)
                ->setSequence($sequence2)
                ->setVerrouSequence(false)
                ;

            $this->em->persist($newVerrouSequence);
        }

        if(is_null($nextVerrouSequence3))
        {
            $newVerrouSequence = new VerrouSequence;
            $newVerrouSequence->setSchoolYear($nextSchoolYear)
                ->setSequence($sequence3)
                ->setVerrouSequence(false)
                ;

            $this->em->persist($newVerrouSequence);
        }

        if(is_null($nextVerrouSequence4))
        {
            $newVerrouSequence = new VerrouSequence;
            $newVerrouSequence->setSchoolYear($nextSchoolYear)
                ->setSequence($sequence4)
                ->setVerrouSequence(false)
                ;

            $this->em->persist($newVerrouSequence);
        }

        if(is_null($nextVerrouSequence5))
        {
            $newVerrouSequence = new VerrouSequence;
            $newVerrouSequence->setSchoolYear($nextSchoolYear)
                ->setSequence($sequence5)
                ->setVerrouSequence(false)
                ;

            $this->em->persist($newVerrouSequence);
        }

        if(is_null($nextVerrouSequence6))
        {
            $newVerrouSequence = new VerrouSequence;
            $newVerrouSequence->setSchoolYear($nextSchoolYear)
                ->setSequence($sequence6)
                ->setVerrouSequence(false)
                ;

            $this->em->persist($newVerrouSequence);
        }

        $this->em->flush();


    }

    public function TransferFees(Request $request)
    {
        $mySession = $request->getSession();
        $schoolYear = $mySession->get('schoolYear');

        // on recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        $fees = $this->feesRepository->findOneBySchoolYear($schoolYear);
        
        $nextFees = $this->feesRepository->findOneBySchoolYear($nextSchoolYear);
        
        if(is_null($nextFees))
        {
            $newFees = new Fees;
            $newFees->setSchoolYear($nextSchoolYear)
                ->setSchoolFees1($fees->getSchoolFees1())
                ->setSchoolFees2($fees->getSchoolFees2())
                ->setApeeFees1($fees->getApeeFees1())
                ->setApeeFees2($fees->getApeeFees2())
                ->setComputerFees1($fees->getComputerFees1())
                ->setComputerFees2($fees->getComputerFees2())
                ->setCleanSchoolFees($fees->getCleanSchoolFees())
                ->setPhotoFees($fees->getPhotoFees())
                ->setSchoolYear($fees->getSchoolYear())
                ->setSubSystem($fees->getSubSystem())
                ;

            $this->em->persist($newFees);
            $this->em->flush();
        }
        
    }

    /**
     * Contruit les lignes à afficher
     *
     * @param Classroom $classroom
     * @return array
     */
    public function getDeliberations(Classroom $classroom, SubSystem $subSystem): array
    {
        $reports = $this->reportRepository->findReportForDeliberation($classroom);
        $numberOfTerms = 0;
        $deliberationRows = [];

        $numberOfStudents = count($classroom->getStudents());
        $numberOfReports = count($reports);
        
        if($numberOfStudents)
        {
            $numberOfTerms = $numberOfReports / $numberOfStudents;
        }

        if ($numberOfTerms) 
        {
            $deliberationRow = new DeliberationRow();
            $counter = 0; // compteur du nombre de trimestres (4 par elève)

            for ($i=0; $i < $numberOfReports; $i++) 
            { 
                $report = $reports[$i];

                switch ($report->getTerm()->getTerm()) 
                {
                    case 0:
                        
                        $deliberationRow->setMoyenneTerm0(number_format($report->getMoyenne(), 2));
                        break;

                        case 1:
                        $deliberationRow->setMoyenneTerm1(number_format($report->getMoyenne(), 2));
                        break;

                        case 2:
                        $deliberationRow->setMoyenneTerm2(number_format($report->getMoyenne(), 2));
                        break;

                        case 3:
                        $deliberationRow->setMoyenneTerm3(number_format($report->getMoyenne(), 2));
                        break;
                }
                $counter += 1;

                if($counter == $numberOfTerms)
                {
                    $student = $report->getStudent();
                    $deliberationRow->setStudent($student);
                    // Si la classe est déjà délibérée, on set le deliberationDecision
                    if($classroom->isIsDeliberated())
                    {
                        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
                        {
                            switch ($student->getDecision()->getDecision()) 
                            {
                                case ConstantsClass::DECISION_PASSED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Admis en').' '.$student->getNextClassroomName());
                                break;

                                case ConstantsClass::DECISION_REAPETED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Redouble la').' '.$student->getNextClassroomName());
                                break;

                                case ConstantsClass::DECISION_EXPELLED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Exclu pour').'  '.$student->getMotif());
                                break;

                                case ConstantsClass::DECISION_CATCHUPPED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Admis au rattrapage'));
                                break;

                                case ConstantsClass::DECISION_RESIGNED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Démissionnaire'));
                                break;

                                case ConstantsClass::DECISION_FINISHED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Terminé(e)'));
                                break;

                                case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Redouble si échec la').' '.$student->getNextClassroomName());
                                break;

                                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Exclu si échec pour').'  '.$student->getMotif());
                                break;
                            }
                        }else
                        {
                            switch ($student->getDecision()->getDecision()) 
                            {
                                case ConstantsClass::DECISION_PASSED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Admitted in').' '.$student->getNextClassroomName());
                                break;

                                case ConstantsClass::DECISION_REAPETED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Repeat it').' '.$student->getNextClassroomName());
                                break;

                                case ConstantsClass::DECISION_EXPELLED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Excluded for').'  '.$student->getMotif());
                                break;

                                case ConstantsClass::DECISION_CATCHUPPED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Admitted to catch-up'));
                                break;

                                case ConstantsClass::DECISION_RESIGNED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Resigned'));
                                break;

                                case ConstantsClass::DECISION_FINISHED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Complete'));
                                break;

                                case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Repeat if it fails').' '.$student->getNextClassroomName());
                                break;

                                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                                    $deliberationRow->setDeliberationDecision($this->translator->trans('Excluded if failure for').'  '.$student->getMotif());
                                break;
                            }
                        }
                        
                    }

                    $deliberationRows[] = $deliberationRow;
                    $deliberationRow = new DeliberationRow();
                    $counter = 0;

                }

            }
        }

        usort($deliberationRows,  function($a, $b)
        {
            $averageA = $a->getMoyenneTerm0();
            $averageB = $b->getMoyenneTerm0();
    
            if($averageA == $averageB)
            {
              return 0;
            }
    
            return ($averageA > $averageB) ? -1 : 1;
        });
        
        return $deliberationRows;
    }


    /**
     * Retourne les classes de nieau suivant à une classe donnée
     *
     * @param Classroom $classroom
     * @return array
     */
    public function getNextClassrooms(Classroom $classroom, SubSystem $subSystem): array
    {
        // On recupère le next year
        $nextSchoolYear = $this->schoolYearService->getNextSchoolYear();

        // On recupère le niveau suivant de la classe
        $nextLevel = $this->levelRepository->findOneByLevel($classroom->getLevel()->getLevel()+1);

        $nextClassrooms = $this->classroomRepository->findBy([
            'schoolYear' => $nextSchoolYear,
            'level' => $nextLevel, 
            'subSystem' => $subSystem, 
        ], [
            'classroom' => 'ASC'
        ]);

        return $nextClassrooms;

    }

    /**
     * Fabrique et retourne la délibération d'un student
     *
     * @param integer $idS
     * @return DeliberationRow
     */
    public function getStudentDeliberation(int $idS): DeliberationRow
    {
        $reports = $this->reportRepository->findBy([
            'student' => $this->studentRepository->find($idS)
        ]);

        $student = $reports[0]->getStudent();
        
        $deliberation = new DeliberationRow();

        $deliberation->setStudent($student)
            ->setDecision($student->getDecision())
            ->setMotif($student->getMotif());

        if($student->getNextClassroomName() != null) 
        {
            $deliberation->setNextClassroomName($student->getNextClassroomName());
            
        }

        foreach ($reports as $report) 
        {
            switch ($report->getTerm()->getTerm()) 
            {
                case 1:
                   $deliberation->setMoyenneTerm1(number_format($report->getMoyenne(), 2));
                break;

                case 2:
                    $deliberation->setMoyenneTerm2(number_format($report->getMoyenne(), 2));
                break;

                case 3:
                    $deliberation->setMoyenneTerm3(number_format($report->getMoyenne(), 2));
                break;

                case ConstantsClass::ANNUEL_TERM:
                    $deliberation->setMoyenneTerm0(number_format($report->getMoyenne(), 2));
                break;
            }
        }

        return $deliberation;
    }


    /**
     * Retourne la liste des élèves par rubrique(Admis, redouble, exclu)
     *
     * @param array $classrooms
     * @param Decision|null $decision
     * @return array
     */
    public function getStudentDeliberationList(array $classrooms, ?Decision $decision): array
    {
        $allStudentList = [];
        $studentList['admitted'] = [];
        $studentList['repeated'] = [];
        $studentList['expelled'] = [];
        $studentList['catchupped'] = [];

        $studentList['finished'] = [];
        $studentList['resigned'] = [];
        $studentList['expelledIfFailed'] = [];
        $studentList['repeatIfFailed'] = [];

        $studentList['classroom'] = null;

        foreach($classrooms as $classroom)
        {
            if($decision) 
            {
                switch ($decision->getDecision()) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $studentList['admitted'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;
                    
                    case ConstantsClass::DECISION_REAPETED:
                        $studentList['repeated'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;
    
                    case ConstantsClass::DECISION_EXPELLED:
                        $studentList['expelled'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;
    
                    case ConstantsClass::DECISION_CATCHUPPED:
                        $studentList['catchupped'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $studentList['finished'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $studentList['resigned'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $studentList['expelledIfFailed'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $studentList['repeatIfFailed'] = $this->reportRepository->findAllByDecision($classroom, $decision);
                    break;
                }
               
            }else
            {
                $decisions = $this->decisionRepository->findAll();
                foreach($decisions as $oneDecision)
                {
                    switch ($oneDecision->getDecision()) 
                    {
                        case ConstantsClass::DECISION_PASSED:
                            $studentList['admitted'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;
                        
                        case ConstantsClass::DECISION_REAPETED:
                            $studentList['repeated'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;
        
                        case ConstantsClass::DECISION_EXPELLED:
                            $studentList['expelled'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;
        
                        case ConstantsClass::DECISION_CATCHUPPED:
                            $studentList['catchupped'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;

                        case ConstantsClass::DECISION_RESIGNED:
                            $studentList['resigned'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;
    
                        case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                            $studentList['expelledIfFailed'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;
    
                        case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                            $studentList['repeatedIfFailed'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;

                        case ConstantsClass::DECISION_FINISHED:
                            $studentList['finished'] = $this->reportRepository->findAllByDecision($classroom, $oneDecision);
                        break;
                    }
                }
            }
            $studentList['classroom'] = $classroom;
            $allStudentList[] = $studentList;
        }

        return $allStudentList;
    }


   /**
    * Imprime les listes des admis, des redoublants et des exclus par classe
    *
    * @param array $studentList
    * @param School $school
    * @param SchoolYear $schoolYear
    * @return Pagination
    */
    public function printStudentDeliberationList(array $allStudentList, School $school, SchoolYear $schoolYear, String $decision): Pagination
    {
        $fontSize = 10;
        $cellHeaderHeight = 3;

        $cellHeaderHeight2 = 7;
        $cellBodyHeight2 = 6;

        $pdf = new Pagination();
        
        foreach($allStudentList as $studentList)
        {   
            $classroom = $studentList['classroom'];
            $admittedStudents = $studentList['admitted'];
            $repeatedStudents = $studentList['repeated'];
            $expelledStudents = $studentList['expelled'];
            $catchuppedStudents = $studentList['catchupped'];

            $resignedStudents = $studentList['resigned'];
            $expelledIfFailedStudents = $studentList['expelledIfFailed'];
            $repeatIfFailedStudents = $studentList['repeatIfFailed'];
            $finishedStudents = $studentList['finished'];

            $numberOfAdmittedStudents = count($admittedStudents);
            $numberOfRepeatedStudents = count($repeatedStudents);
            $numberOfExpelledStudents = count($expelledStudents);
            $numberOfCatchuppedStudents = count($catchuppedStudents);

            $numberOfResignedStudents = count($resignedStudents);
            $numberOfExpelledIfFailedStudents = count($expelledIfFailedStudents);
            $numberOfRepeatIfFailedStudents = count($repeatIfFailedStudents);
            $numberOfFinishedStudents = count($finishedStudents);

            // dump($admittedStudents);
            // dd($decision);
            switch ($decision) 
            {
                case ConstantsClass::DECISION_PASSED:
                    // Liste des admis
                    if($numberOfAdmittedStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school,  $schoolYear, $classroom, $admittedStudents, $numberOfAdmittedStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_PASSED, false);
                    }else 
                    {
                        $pdf = $this->empty($pdf, $school,  $schoolYear, $classroom, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_PASSED);
                    }
                    break;

                case ConstantsClass::DECISION_REAPETED:
                    // Liste des redoublants
                    if($numberOfRepeatedStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school,  $schoolYear, $classroom, $repeatedStudents, $numberOfRepeatedStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_REAPETED, false);
                    }else 
                    {
                        $pdf = $this->empty($pdf, $school,  $schoolYear, $classroom,  $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_REAPETED);
                    }
                    break;

                case ConstantsClass::DECISION_EXPELLED:
                    // Liste des exclus
                    if($numberOfExpelledStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school,  $schoolYear, $classroom, $expelledStudents, $numberOfExpelledStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_EXPELLED, false);
                    }else 
                    {
                        $pdf = $this->empty($pdf, $school,  $schoolYear, $classroom, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_EXPELLED);
                    }
                    break;

                case ConstantsClass::DECISION_CATCHUPPED:
                    // Liste des rattrapages
                    if($catchuppedStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school,  $schoolYear, $classroom, $catchuppedStudents, $numberOfCatchuppedStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_CATCHUPPED,false);
                    }else 
                    {
                        $pdf = $this->empty($pdf, $school,  $schoolYear, $classroom, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_CATCHUPPED);
                    }
                    break;

                case ConstantsClass::DECISION_RESIGNED:
                    //Liste des démissionaires
                    if($numberOfResignedStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school, $schoolYear, $classroom, $resignedStudents, $numberOfResignedStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_RESIGNED, false);
                    }else 
                    {
                        $pdf = $this->empty($pdf, $school, $schoolYear, $classroom, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_RESIGNED,);
                    }
                    break;

                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                    //Liste des exclus en cas d'échec
                    if($numberOfExpelledIfFailedStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school, $schoolYear, $classroom, $expelledIfFailedStudents, $numberOfExpelledIfFailedStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_EXPELLED_IF_FAILED,false);
                    }
                    else 
                    {
                        $pdf = $this->empty($pdf, $school, $schoolYear, $classroom, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_EXPELLED_IF_FAILED);
                    }
                    break;

                case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                    ///Liste des redoublants en cas d'échec
                    if($numberOfRepeatIfFailedStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school, $schoolYear, $classroom, $repeatIfFailedStudents, $numberOfRepeatIfFailedStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_REAPETED_IF_FAILED,false);
                    }else 
                    {
                        $pdf = $this->empty($pdf, $school, $schoolYear, $classroom, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_REAPETED_IF_FAILED);
                    }
                    break;

                case ConstantsClass::DECISION_FINISHED:
                    //Liste des ayants terminés
                    if($numberOfFinishedStudents)
                    {
                        $pdf = $this->displayDeliberationListContent($pdf, $school, $schoolYear, $classroom, $finishedStudents, $numberOfFinishedStudents, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, $cellBodyHeight2, ConstantsClass::DECISION_FINISHED,false);
                    }else 
                    {
                        $pdf = $this->empty($pdf, $school, $schoolYear, $classroom, $fontSize, $cellHeaderHeight, $cellHeaderHeight2, ConstantsClass::DECISION_FINISHED);
                    }
                    break;
                
               
            }
            

        }

        return $pdf;
    }

    /**
     * Entête de la fiche de la liste des élèves
     *
     * @param Pagination $pdf
     * @param School $school
     * @param Classroom $classroom
     * @param string $title
     * @return Pagination
     */
    public function getHeaderStudentDeliberationList(Pagination $pdf, School $school, Classroom $classroom, SchoolYear $schoolYear, string $headerTitle, string $type): Pagination
    {
        $serviceYear = substr($schoolYear->getSchoolYear(), 2, 2);
        $serviceYear = $serviceYear.'-'.(int)$serviceYear + 1;
        $pdf->Ln();

        $pdf->SetFont('Times', '', 10);
        $pdf->Cell(0, 5, utf8_decode('N°__________/'.$serviceYear.'/CC/'.$school->getServiceNote()), 0, 1, 'C');
        $pdf->Ln();

        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode($headerTitle), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, 'Classe : '.utf8_decode($classroom->getClassroom()), 0, 1, 'R');
            $pdf->SetFont('Times', 'BU', 12);

            switch ($type) 
            {
                case ConstantsClass::DECISION_PASSED:
                    $pdf->Cell(0, 5, utf8_decode('LISTE DES ADMIS PAR ORDRE ALPHABETIQUE'), 0, 1, 'C');
                break;
                
                case ConstantsClass::DECISION_REAPETED:
                    $pdf->Cell(0, 5, utf8_decode('LISTE DES REDOUBLANTS PAR ORDRE ALPHABETIQUE'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_EXPELLED:
                    $pdf->Cell(0, 5, utf8_decode('LISTE DES EXCLUS PAR ORDRE ALPHABETIQUE'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_CATCHUPPED:
                    $pdf->Cell(0, 5, utf8_decode('LISTE DES ELEVES ADMIS AU RATTRAPAGE PAR ORDRE ALPHABETIQUE'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_FINISHED:
                    $pdf->Cell(0, 5, utf8_decode('LISTE DES ELEVES AYANT TERMINES PAR ORDRE ALPHABETIQUE'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_RESIGNED:
                    $pdf->Cell(0, 5, utf8_decode('LISTE DES ELEVES AYANT DEMISSIONNES ORDRE ALPHABETIQUE'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                    $pdf->Cell(0, 5, utf8_decode("LISTE DES ELEVES EXCLUS EN CAS D'ECHEC ORDRE ALPHABETIQUE"), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                    $pdf->Cell(0, 5, utf8_decode("LISTE DES ELEVES REBLOUBLANTS EN CAS D'ECHEC ORDRE ALPHABETIQUE"), 0, 1, 'C');
                break;
            }
        }else
        {
            $pdf->SetFont('Times', 'B', 14);
            $pdf->Cell(0, 5, utf8_decode($headerTitle), 0, 1, 'C');
            $pdf->SetFont('Times', 'B', 12);
            $pdf->Cell(0, 5, 'Class : '.utf8_decode($classroom->getClassroom()), 0, 1, 'R');
            $pdf->SetFont('Times', 'BU', 12);

            switch ($type) 
            {
                case ConstantsClass::DECISION_PASSED:
                    $pdf->Cell(0, 5, utf8_decode('LIST OF ADMITTED IN ALPHABETICAL ORDER'), 0, 1, 'C');
                break;
                
                case ConstantsClass::DECISION_REAPETED:
                    $pdf->Cell(0, 5, utf8_decode('LIST OF REPEATERS IN ALPHABETICAL ORDER'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_EXPELLED:
                    $pdf->Cell(0, 5, utf8_decode('LIST OF EXCLUDED IN ALPHABETICAL ORDER'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_CATCHUPPED:
                    $pdf->Cell(0, 5, utf8_decode('LIST OF STUDENTS ADMITTED TO REMEDIATION IN ALPHABETICAL ORDER'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_FINISHED:
                    $pdf->Cell(0, 5, utf8_decode('LIST OF STUDENTS WHO FINISHED IN ALPHABETICAL ORDER'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_RESIGNED:
                    $pdf->Cell(0, 5, utf8_decode('LIST OF STUDENTS WHO HAVE RESIGNED IN ALPHABETICAL ORDER'), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                    $pdf->Cell(0, 5, utf8_decode("LIST OF STUDENTS EXCLUDED IN CASE OF FAILURE ALPHABETICAL ORDER"), 0, 1, 'C');
                break;

                case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                    $pdf->Cell(0, 5, utf8_decode("LIST OF STUDENTS REBLUNKING IN CASE OF FAILURE ALPHABETICAL ORDER"), 0, 1, 'C');
                break;
            }
        }
        

        $pdf->SetFont('Times', 'B', 12);
        $pdf->Ln();

        return $pdf;
    }

     /**
     * Entête du tableau de la liste des élèves
     *
     * @param Pagination $pdf
     * @param integer $cellHeaderHeight2
     * @return Pagination
     */
    public function getTableHeaderStudentDeliberationList(Pagination $pdf, int $cellHeaderHeight2): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(7, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(70, $cellHeaderHeight2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Cell(50, $cellHeaderHeight2, 'Date et Lieu de Naissance', 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Cell(14, $cellHeaderHeight2/2, 'Moy', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(14, $cellHeaderHeight2/2, 'annuelle', 'LBR', 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $x += 14;
            $pdf->SetXY($x, $y);

            $pdf->Cell(14, $cellHeaderHeight2/2, 'Rang', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(14, $cellHeaderHeight2/2, 'annuel', 'LBR', 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $x += 14;
            $pdf->SetXY($x, $y);

            $pdf->Cell(40, $cellHeaderHeight2, utf8_decode('Décision du conseil'), 1, 1, 'C', true);
        }else
        {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->Cell(7, $cellHeaderHeight2, 'No', 1, 0, 'C', true);
            $pdf->Cell(70, $cellHeaderHeight2, utf8_decode('Noms et Prénoms'), 1, 0, 'C', true);
            $pdf->Cell(50, $cellHeaderHeight2, 'Date and place of birth', 1, 0, 'C', true);
            $x = $pdf->GetX();
            $y = $pdf->GetY();

            $pdf->Cell(14, $cellHeaderHeight2/2, 'Avg', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(14, $cellHeaderHeight2/2, 'annual', 'LBR', 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $x += 14;
            $pdf->SetXY($x, $y);

            $pdf->Cell(14, $cellHeaderHeight2/2, 'Rank', 'LTR', 2, 'C', true);
            $pdf->SetFont('Times', 'B', 8);
            $pdf->Cell(14, $cellHeaderHeight2/2, 'annual', 'LBR', 0, 'C', true);
            $pdf->SetFont('Times', 'B', 10);
            $x += 14;
            $pdf->SetXY($x, $y);

            $pdf->Cell(40, $cellHeaderHeight2, utf8_decode('Board decision'), 1, 1, 'C', true);
        }
        return $pdf;
    }

    public function displayDeliberationListContent(Pagination $pdf, School $school, SchoolYear $schoolYear, Classroom $classroom, array $studentReports, int $numberOfStudents, int $fontSize, int $cellHeaderHeight, int $cellHeaderHeight2, int $cellBodyHeight2, string $type, bool $headerOnly = false): Pagination
    {
        $mySession =  $this->request->getSession();

        if($mySession)
        {
            $subSystem = $mySession->get('subSystem');
        }
        if($subSystem->getSubSystem() == ConstantsClass::FRANCOPHONE)
        {
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // entête de la fiche
            $pdf = $this-> getHeaderStudentDeliberationList($pdf, $school, $classroom, $schoolYear, "CONSEILS DE CLASSES DE FIN D'ANNEE SCOLAIRE ".$schoolYear->getSchoolYear(), $type);

            // entête du tableau
            $pdf = $this->getTableHeaderStudentDeliberationList($pdf, $cellHeaderHeight2);

            if($headerOnly == false)
            {
                // contenu du tableau
                $numero = 0;
                foreach($studentReports as $report)
                {
                    $student = $report->getStudent();
                    
                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }

                    $pdf->SetFont('Times', '', 9);
                    $numero++;
                    $pdf->Cell(7, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(70, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(50, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    $pdf->Cell(14, $cellBodyHeight2, $this->generalService->formatMark($report->getMoyenne()), 1, 0, 'C', true);
                    $pdf->Cell(14, $cellBodyHeight2, utf8_decode($this->generalService->formatRank($report->getRang(), $student->getSex()->getSex())), 1, 0, 'C', true);

                    switch ($type) 
                    {
                        case ConstantsClass::DECISION_PASSED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Admis en '. utf8_decode( $student->getNextClassroomName()), 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Admise en '.utf8_decode($student->getNextClassroomName()), 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_RESIGNED:
                            $pdf->Cell(40, $cellBodyHeight2, utf8_decode('Démissionnaire'), 1, 0, 'C', true);
                            
                        break;

                        
                        case ConstantsClass::DECISION_REAPETED:
                            $pdf->Cell(40, $cellBodyHeight2, 'Redouble la '.utf8_decode($student->getNextClassroomName()), 1, 0, 'C', true);
                        break;

                        case ConstantsClass::DECISION_EXPELLED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Exclu pour '.$student->getMotif(), 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Exclue pour '.$student->getMotif(), 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_CATCHUPPED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Admis au rattrapage', 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Admise au rattrapage', 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_FINISHED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, utf8_decode('Terminé'), 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, utf8_decode('Terminé(e)'), 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                            $pdf->Cell(40, $cellBodyHeight2, 'Redouble si échec', 1, 0, 'C', true);
                        break;

                        case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Exclu si échec', 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Exclu(e) si échec', 1, 0, 'C', true);
                            }
                        break;
                    }

                    $pdf->Ln();
        
                    // if( ($numero % 30) == 0 && $numberOfStudents > 30) /*On passe à une nouvelle page après 30 lignes*/
                    // {
                        // On insère une page
                        // $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
                        
                        // Administrative Header
                        // $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                        // entête de la fiche
                        // $pdf = $this-> getHeaderStudentDeliberationList($pdf, $school, $classroom, $schoolYear, 'LISTE DES ELEVES ADMIS EN CLASSE SUPERIEURE', $type);
        
                        // entête du tableau
                        // $pdf = $this->getTableHeaderStudentDeliberationList($pdf, $cellHeaderHeight2);
        
                    // }
                }

            }else 
            {
                $pdf->Ln();
                switch ($type) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'AUCUN ADMIS ENREGISTRE', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'AUCUN REDOUBLANT ENREGISTRE', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'AUCUN EXCLU ENREGISTRE', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'AUCUN ELEVE ADMIS AU RATTRAPAGE', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "AUCUN ELEVE N'A TERMINE", 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "AUCUN ELEVE N'A DEMISSIONNE", 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "AUCUN ELEVE N'EST EXCLU", 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "AUCUN ELEVE NE REDOUBLE", 0, 1, 'C');
                    break;
                }
            }
        }else
        {
            /////////////sous système anglohpne
            // On insère une page
            $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

            // Administrative Header
            $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

            // entête de la fiche
            $pdf = $this-> getHeaderStudentDeliberationList($pdf, $school, $classroom, $schoolYear, "END OF SCHOOL YEAR CLASS ADVICE ".$schoolYear->getSchoolYear(), $type);

            // entête du tableau
            $pdf = $this->getTableHeaderStudentDeliberationList($pdf, $cellHeaderHeight2);

            if($headerOnly == false)
            {
                // contenu du tableau
                $numero = 0;
                foreach($studentReports as $report)
                {
                    $student = $report->getStudent();
                    
                    if ($numero % 2 != 0) 
                    {
                        $pdf->SetFillColor(219,238,243);
                    }else {
                        $pdf->SetFillColor(255,255,255);
                    }

                    $pdf->SetFont('Times', '', 9);
                    $numero++;
                    $pdf->Cell(7, $cellBodyHeight2, $numero, 1, 0, 'C', true);
                    $pdf->Cell(70, $cellBodyHeight2, utf8_decode($student->getFullName()), 1, 0, 'L', true);
                    $pdf->Cell(50, $cellBodyHeight2, $student->getBirthday()->format('d/m/Y').utf8_decode(' à ').utf8_decode($student->getBirthplace()), 1, 0, 'L', true);
                    $pdf->Cell(14, $cellBodyHeight2, $this->generalService->formatMark($report->getMoyenne()), 1, 0, 'C', true);
                    $pdf->Cell(14, $cellBodyHeight2, utf8_decode($this->generalService->formatRank($report->getRang(), $student->getSex()->getSex())), 1, 0, 'C', true);

                    switch ($type) 
                    {
                        case ConstantsClass::DECISION_PASSED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Admitted in '. utf8_decode( $student->getNextClassroomName()), 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Admitted in '.utf8_decode($student->getNextClassroomName()), 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_RESIGNED:
                            $pdf->Cell(40, $cellBodyHeight2, utf8_decode('Resigned'), 1, 0, 'C', true);
                            
                        break;

                        
                        case ConstantsClass::DECISION_REAPETED:
                            $pdf->Cell(40, $cellBodyHeight2, 'Redouble it '.utf8_decode($student->getNextClassroomName()), 1, 0, 'C', true);
                        break;

                        case ConstantsClass::DECISION_EXPELLED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Excluded for '.$student->getMotif(), 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Excluded for'.$student->getMotif(), 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_CATCHUPPED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Admitted to catch-up', 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Admitted to catch-up', 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_FINISHED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, utf8_decode('Finished'), 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, utf8_decode('Finished'), 1, 0, 'C', true);
                            }
                        break;

                        case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                            $pdf->Cell(40, $cellBodyHeight2, 'Repeat if failure', 1, 0, 'C', true);
                        break;

                        case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                            if($student->getSex()->getSex() == 'M')
                            {
                                $pdf->Cell(40, $cellBodyHeight2, 'Excluded if failure', 1, 0, 'C', true);
                            }else 
                            {   
                                $pdf->Cell(40, $cellBodyHeight2, 'Excluded if failure', 1, 0, 'C', true);
                            }
                        break;
                    }

                    $pdf->Ln();
        
                    if( ($numero % 30) == 0 && $numberOfStudents > 30) /*On passe à une nouvelle page après 30 lignes*/
                    {
                        // On insère une page
                        $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);
                        
                        // Administrative Header
                        $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);
            
                        // entête de la fiche
                        $pdf = $this-> getHeaderStudentDeliberationList($pdf, $school, $classroom, $schoolYear, 'LIST OF STUDENTS ADMITTED TO THE HIGHER CLASS', $type);
        
                        // entête du tableau
                        $pdf = $this->getTableHeaderStudentDeliberationList($pdf, $cellHeaderHeight2);
        
                    }
                }

            }else 
            {
                $pdf->Ln();
                switch ($type) 
                {
                    case ConstantsClass::DECISION_PASSED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'NO REGISTERED ADMITTED', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_REAPETED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'NO RECORDED REPEATERS', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_EXPELLED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'NO EXCLUSIVES REGISTERED', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_CATCHUPPED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, 'NO STUDENTS ADMITTED TO REMEDY', 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_FINISHED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "NO STUDENT FINISHED", 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_RESIGNED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "NO STUDENT HAS RESIGNED", 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "NO STUDENT IS EXCLUDED", 0, 1, 'C');
                    break;

                    case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                        $pdf->Cell(0, $cellHeaderHeight2*3, "NO STUDENT REPEATS", 0, 1, 'C');
                    break;
                }
            }
        }

        return $pdf;
    }

    public function empty(Pagination $pdf, School $school, SchoolYear $schoolYear, Classroom $classroom, int $fontSize, int $cellHeaderHeight, int $cellHeaderHeight2, string $type): Pagination
    {
        // On insère une page
        $pdf = $this->generalService->newPagePagination($pdf, 'P', 10, $fontSize-3);

        // Administrative Header
        $pdf = $this->generalService->getAdministrativeHeaderPagination($school, $pdf, $cellHeaderHeight, $fontSize, $schoolYear);

        // entête de la fiche
        $pdf = $this-> getHeaderStudentDeliberationList($pdf, $school, $classroom, $schoolYear, "END OF SCHOOL YEAR CLASS ADVICE ".$schoolYear->getSchoolYear(), $type);

        // entête du tableau
        $pdf = $this->getTableHeaderStudentDeliberationList($pdf, $cellHeaderHeight2);

        $pdf->SetFont('Times', 'B', 14);
        $pdf->Ln();
        switch ($type) 
        {
            case ConstantsClass::DECISION_PASSED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_PASSED, 0, 1, 'C');
            break;

            case ConstantsClass::DECISION_REAPETED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_REAPETED, 0, 1, 'C');
            break;

            case ConstantsClass::DECISION_EXPELLED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_EXPELLED, 0, 1, 'C');
            break;

            case ConstantsClass::DECISION_CATCHUPPED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_CATCHUPPED, 0, 1, 'C');
            break;

            case ConstantsClass::DECISION_FINISHED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_FINISHED, 0, 1, 'C');
            break;

            case ConstantsClass::DECISION_RESIGNED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_RESIGNED, 0, 1, 'C');
            break;

            case ConstantsClass::DECISION_EXPELLED_IF_FAILED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_EXPELLED_IF_FAILED, 0, 1, 'C');
            break;

            case ConstantsClass::DECISION_REAPETED_IF_FAILED:
                $pdf->Cell(0, $cellHeaderHeight2*3, ConstantsClass::NOT_REAPETED_IF_FAILED, 0, 1, 'C');
            break;
        }
        

        return $pdf;
    }

}
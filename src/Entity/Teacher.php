<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\TeacherRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: TeacherRepository::class)]
class Teacher
{
    // use CreatedAndUpdatedTime;
    
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullName = null;

    #[ORM\Column(length: 255)]
    private ?string $administrativeNumber = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Grade $grade = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Sex $sex = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Duty $duty = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?User $updatedBy = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $integrationDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phoneNumber = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $birthplace = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $affectationDate = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?MatrimonialStatus $matrimonialStatus = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Diploma $diploma = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Region $region = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Status $status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $previousPost = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $affectationNote = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $takeFunctiondate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $firstDateFunction = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $firstDateActualFunction = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Subject $speciality = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Subject $teachingSubject = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Division $division = null;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Subdivision $subDivision = null;

    #[ORM\OneToMany(mappedBy: 'educationalFacilitator', targetEntity: Department::class)]
    private Collection $departments;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?Department $department = null;

    #[ORM\OneToMany(mappedBy: 'principalTeacher', targetEntity: Classroom::class)]
    private Collection $classrooms;

    #[ORM\OneToMany(mappedBy: 'supervisor', targetEntity: Classroom::class)]
    private Collection $supervisorClassrooms;

    #[ORM\OneToMany(mappedBy: 'counsellor', targetEntity: Classroom::class)]
    private Collection $counsellorClassrooms;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: Lesson::class)]
    private Collection $lessons;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: Progress::class)]
    private Collection $progress;

    #[ORM\OneToMany(mappedBy: 'headmaster', targetEntity: School::class)]
    private Collection $schools;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: User::class)]
    private Collection $users;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'actionSociale', targetEntity: Classroom::class)]
    private Collection $classroomsActionSocial;

    #[ORM\ManyToOne(inversedBy: 'teachers')]
    private ?SubSystem $subSystem = null;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: AbsenceTeacher::class)]
    private Collection $absenceTeachers;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'teacher', targetEntity: HistoriqueTeacher::class)]
    private Collection $historiqueTeachers;

    #[ORM\Column]
    private ?bool $supprime = null;

    public function __construct()
    {
        $this->departments = new ArrayCollection();
        $this->classrooms = new ArrayCollection();
        $this->supervisorClassrooms = new ArrayCollection();
        $this->counsellorClassrooms = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->progress = new ArrayCollection();
        $this->schools = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->classroomsActionSocial = new ArrayCollection();
        $this->absenceTeachers = new ArrayCollection();
        $this->historiqueTeachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): self
    {
        $this->fullName = $fullName;

        return $this;
    }

    public function getAdministrativeNumber(): ?string
    {
        return $this->administrativeNumber;
    }

    public function setAdministrativeNumber(string $administrativeNumber): self
    {
        $this->administrativeNumber = $administrativeNumber;

        return $this;
    }

    public function getGrade(): ?Grade
    {
        return $this->grade;
    }

    public function setGrade(?Grade $grade): self
    {
        $this->grade = $grade;

        return $this;
    }

    public function getSex(): ?Sex
    {
        return $this->sex;
    }

    public function setSex(?Sex $sex): self
    {
        $this->sex = $sex;

        return $this;
    }

    public function getSchoolYear(): ?SchoolYear
    {
        return $this->schoolYear;
    }

    public function setSchoolYear(?SchoolYear $schoolYear): self
    {
        $this->schoolYear = $schoolYear;

        return $this;
    }

    public function getDuty(): ?Duty
    {
        return $this->duty;
    }

    public function setDuty(?Duty $duty): self
    {
        $this->duty = $duty;

        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(?User $createdBy): self
    {
        $this->createdBy = $createdBy;

        return $this;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }

    public function setUpdatedBy(?User $updatedBy): self
    {
        $this->updatedBy = $updatedBy;

        return $this;
    }

    public function getIntegrationDate(): ?\DateTimeInterface
    {
        return $this->integrationDate;
    }

    public function setIntegrationDate(?\DateTimeInterface $integrationDate): self
    {
        $this->integrationDate = $integrationDate;

        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): self
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getBirthplace(): ?string
    {
        return $this->birthplace;
    }

    public function setBirthplace(?string $birthplace): self
    {
        $this->birthplace = $birthplace;

        return $this;
    }

    public function getAffectationDate(): ?\DateTimeInterface
    {
        return $this->affectationDate;
    }

    public function setAffectationDate(?\DateTimeInterface $affectationDate): self
    {
        $this->affectationDate = $affectationDate;

        return $this;
    }

    public function getMatrimonialStatus(): ?MatrimonialStatus
    {
        return $this->matrimonialStatus;
    }

    public function setMatrimonialStatus(?MatrimonialStatus $matrimonialStatus): self
    {
        $this->matrimonialStatus = $matrimonialStatus;

        return $this;
    }

    public function getDiploma(): ?Diploma
    {
        return $this->diploma;
    }

    public function setDiploma(?Diploma $diploma): self
    {
        $this->diploma = $diploma;

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

        return $this;
    }

    public function getStatus(): ?Status
    {
        return $this->status;
    }

    public function setStatus(?Status $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getPreviousPost(): ?string
    {
        return $this->previousPost;
    }

    public function setPreviousPost(?string $previousPost): self
    {
        $this->previousPost = $previousPost;

        return $this;
    }

    public function getAffectationNote(): ?string
    {
        return $this->affectationNote;
    }

    public function setAffectationNote(?string $affectationNote): self
    {
        $this->affectationNote = $affectationNote;

        return $this;
    }

    public function getTakeFunctiondate(): ?\DateTimeInterface
    {
        return $this->takeFunctiondate;
    }

    public function setTakeFunctiondate(?\DateTimeInterface $takeFunctiondate): self
    {
        $this->takeFunctiondate = $takeFunctiondate;

        return $this;
    }

    public function getFirstDateFunction(): ?\DateTimeInterface
    {
        return $this->firstDateFunction;
    }

    public function setFirstDateFunction(?\DateTimeInterface $firstDateFunction): self
    {
        $this->firstDateFunction = $firstDateFunction;

        return $this;
    }

    public function getFirstDateActualFunction(): ?\DateTimeInterface
    {
        return $this->firstDateActualFunction;
    }

    public function setFirstDateActualFunction(?\DateTimeInterface $firstDateActualFunction): self
    {
        $this->firstDateActualFunction = $firstDateActualFunction;

        return $this;
    }

    public function getSpeciality(): ?Subject
    {
        return $this->speciality;
    }

    public function setSpeciality(?Subject $speciality): self
    {
        $this->speciality = $speciality;

        return $this;
    }

    public function getTeachingSubject(): ?Subject
    {
        return $this->teachingSubject;
    }

    public function setTeachingSubject(?Subject $teachingSubject): self
    {
        $this->teachingSubject = $teachingSubject;

        return $this;
    }

    public function getDivision(): ?Division
    {
        return $this->division;
    }

    public function setDivision(?Division $division): self
    {
        $this->division = $division;

        return $this;
    }

    public function getSubDivision(): ?Subdivision
    {
        return $this->subDivision;
    }

    public function setSubDivision(?Subdivision $subDivision): self
    {
        $this->subDivision = $subDivision;

        return $this;
    }

    /**
     * @return Collection<int, Department>
     */
    public function getDepartments(): Collection
    {
        return $this->departments;
    }

    public function addDepartment(Department $department): self
    {
        if (!$this->departments->contains($department)) {
            $this->departments->add($department);
            $department->setEducationalFacilitator($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): self
    {
        if ($this->departments->removeElement($department)) {
            // set the owning side to null (unless already changed)
            if ($department->getEducationalFacilitator() === $this) {
                $department->setEducationalFacilitator(null);
            }
        }

        return $this;
    }

    public function getDepartment(): ?Department
    {
        return $this->department;
    }

    public function setDepartment(?Department $department): self
    {
        $this->department = $department;

        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getClassrooms(): Collection
    {
        return $this->classrooms;
    }

    public function addClassroom(Classroom $classroom): self
    {
        if (!$this->classrooms->contains($classroom)) {
            $this->classrooms->add($classroom);
            $classroom->setPrincipalTeacher($this);
        }

        return $this;
    }

    public function removeClassroom(Classroom $classroom): self
    {
        if ($this->classrooms->removeElement($classroom)) {
            // set the owning side to null (unless already changed)
            if ($classroom->getPrincipalTeacher() === $this) {
                $classroom->setPrincipalTeacher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getSupervisorClassrooms(): Collection
    {
        return $this->supervisorClassrooms;
    }

    public function addSupervisorClassroom(Classroom $supervisorClassroom): self
    {
        if (!$this->supervisorClassrooms->contains($supervisorClassroom)) {
            $this->supervisorClassrooms->add($supervisorClassroom);
            $supervisorClassroom->setSupervisor($this);
        }

        return $this;
    }

    public function removeSupervisorClassroom(Classroom $supervisorClassroom): self
    {
        if ($this->supervisorClassrooms->removeElement($supervisorClassroom)) {
            // set the owning side to null (unless already changed)
            if ($supervisorClassroom->getSupervisor() === $this) {
                $supervisorClassroom->setSupervisor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getCounsellorClassrooms(): Collection
    {
        return $this->counsellorClassrooms;
    }

    public function addCounsellorClassroom(Classroom $counsellorClassroom): self
    {
        if (!$this->counsellorClassrooms->contains($counsellorClassroom)) {
            $this->counsellorClassrooms->add($counsellorClassroom);
            $counsellorClassroom->setCounsellor($this);
        }

        return $this;
    }

    public function removeCounsellorClassroom(Classroom $counsellorClassroom): self
    {
        if ($this->counsellorClassrooms->removeElement($counsellorClassroom)) {
            // set the owning side to null (unless already changed)
            if ($counsellorClassroom->getCounsellor() === $this) {
                $counsellorClassroom->setCounsellor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): self
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setTeacher($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getTeacher() === $this) {
                $lesson->setTeacher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Progress>
     */
    public function getProgress(): Collection
    {
        return $this->progress;
    }

    public function addProgress(Progress $progress): self
    {
        if (!$this->progress->contains($progress)) {
            $this->progress->add($progress);
            $progress->setTeacher($this);
        }

        return $this;
    }

    public function removeProgress(Progress $progress): self
    {
        if ($this->progress->removeElement($progress)) {
            // set the owning side to null (unless already changed)
            if ($progress->getTeacher() === $this) {
                $progress->setTeacher(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, School>
     */
    public function getSchools(): Collection
    {
        return $this->schools;
    }

    public function addSchool(School $school): self
    {
        if (!$this->schools->contains($school)) {
            $this->schools->add($school);
            $school->setHeadmaster($this);
        }

        return $this;
    }

    public function removeSchool(School $school): self
    {
        if ($this->schools->removeElement($school)) {
            // set the owning side to null (unless already changed)
            if ($school->getHeadmaster() === $this) {
                $school->setHeadmaster(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Classroom>
     */
    public function getClassroomsActionSocial(): Collection
    {
        return $this->classroomsActionSocial;
    }

    public function addClassroomsActionSocial(Classroom $classroomsActionSocial): self
    {
        if (!$this->classroomsActionSocial->contains($classroomsActionSocial)) {
            $this->classroomsActionSocial->add($classroomsActionSocial);
            $classroomsActionSocial->setActionSociale($this);
        }

        return $this;
    }

    public function removeClassroomsActionSocial(Classroom $classroomsActionSocial): self
    {
        if ($this->classroomsActionSocial->removeElement($classroomsActionSocial)) {
            // set the owning side to null (unless already changed)
            if ($classroomsActionSocial->getActionSociale() === $this) {
                $classroomsActionSocial->setActionSociale(null);
            }
        }

        return $this;
    }

    public function getSubSystem(): ?SubSystem
    {
        return $this->subSystem;
    }

    public function setSubSystem(?SubSystem $subSystem): self
    {
        $this->subSystem = $subSystem;

        return $this;
    }

    /**
     * @return Collection<int, AbsenceTeacher>
     */
    public function getAbsenceTeachers(): Collection
    {
        return $this->absenceTeachers;
    }

    public function addAbsenceTeacher(AbsenceTeacher $absenceTeacher): self
    {
        if (!$this->absenceTeachers->contains($absenceTeacher)) {
            $this->absenceTeachers->add($absenceTeacher);
            $absenceTeacher->setTeacher($this);
        }

        return $this;
    }

    public function removeAbsenceTeacher(AbsenceTeacher $absenceTeacher): self
    {
        if ($this->absenceTeachers->removeElement($absenceTeacher)) {
            // set the owning side to null (unless already changed)
            if ($absenceTeacher->getTeacher() === $this) {
                $absenceTeacher->setTeacher(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * @return Collection<int, HistoriqueTeacher>
     */
    public function getHistoriqueTeachers(): Collection
    {
        return $this->historiqueTeachers;
    }

    public function addHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if (!$this->historiqueTeachers->contains($historiqueTeacher)) {
            $this->historiqueTeachers->add($historiqueTeacher);
            $historiqueTeacher->setTeacher($this);
        }

        return $this;
    }

    public function removeHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if ($this->historiqueTeachers->removeElement($historiqueTeacher)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTeacher->getTeacher() === $this) {
                $historiqueTeacher->setTeacher(null);
            }
        }

        return $this;
    }

    public function isSupprime(): ?bool
    {
        return $this->supprime;
    }

    public function setSupprime(bool $supprime): self
    {
        $this->supprime = $supprime;

        return $this;
    }

}

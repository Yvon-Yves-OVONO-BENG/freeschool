<?php

namespace App\Entity;

use App\Repository\ClassroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassroomRepository::class)]
class Classroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $classroom = null;

    #[ORM\Column]
    private ?bool $isDeliberated = null;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?Level $level = null;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?Teacher $principalTeacher = null;

    #[ORM\ManyToOne]
    private ?Teacher $censor = null;

    #[ORM\ManyToOne(inversedBy: 'supervisorClassrooms')]
    private ?Teacher $supervisor = null;

    #[ORM\ManyToOne(inversedBy: 'counsellorClassrooms')]
    private ?Teacher $counsellor = null;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?User $updatedBy = null;

    #[ORM\OneToMany(mappedBy: 'classroom', targetEntity: Student::class)]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'classroom', targetEntity: Lesson::class)]
    private Collection $lessons;

    #[ORM\OneToMany(mappedBy: 'classroom', targetEntity: Progress::class)]
    private Collection $progress;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'classroomsActionSocial')]
    private ?Teacher $actionSociale = null;

    #[ORM\OneToMany(mappedBy: 'classeEntree', targetEntity: Student::class)]
    private Collection $studentsEntree;

    #[ORM\OneToMany(mappedBy: 'classeFrereSoeur', targetEntity: Student::class)]
    private Collection $studentFrereSoeurs;

    #[ORM\ManyToOne(inversedBy: 'classrooms')]
    private ?SubSystem $subSystem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'classroom', targetEntity: HistoriqueTeacher::class)]
    private Collection $historiqueTeachers;

    public function __construct()
    {
        $this->students = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->progress = new ArrayCollection();
        $this->studentsEntree = new ArrayCollection();
        $this->studentFrereSoeurs = new ArrayCollection();
        $this->historiqueTeachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClassroom(): ?string
    {
        return $this->classroom;
    }

    public function setClassroom(string $classroom): self
    {
        $this->classroom = $classroom;

        return $this;
    }

    public function isIsDeliberated(): ?bool
    {
        return $this->isDeliberated;
    }

    public function setIsDeliberated(bool $isDeliberated): self
    {
        $this->isDeliberated = $isDeliberated;

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

    public function getLevel(): ?Level
    {
        return $this->level;
    }

    public function setLevel(?Level $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getPrincipalTeacher(): ?Teacher
    {
        return $this->principalTeacher;
    }

    public function setPrincipalTeacher(?Teacher $principalTeacher): self
    {
        $this->principalTeacher = $principalTeacher;

        return $this;
    }

    public function getCensor(): ?Teacher
    {
        return $this->censor;
    }

    public function setCensor(?Teacher $censor): self
    {
        $this->censor = $censor;

        return $this;
    }

    public function getSupervisor(): ?Teacher
    {
        return $this->supervisor;
    }

    public function setSupervisor(?Teacher $supervisor): self
    {
        $this->supervisor = $supervisor;

        return $this;
    }

    public function getCounsellor(): ?Teacher
    {
        return $this->counsellor;
    }

    public function setCounsellor(?Teacher $counsellor): self
    {
        $this->counsellor = $counsellor;

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

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): self
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setClassroom($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getClassroom() === $this) {
                $student->setClassroom(null);
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
            $lesson->setClassroom($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getClassroom() === $this) {
                $lesson->setClassroom(null);
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
            $progress->setClassroom($this);
        }

        return $this;
    }

    public function removeProgress(Progress $progress): self
    {
        if ($this->progress->removeElement($progress)) {
            // set the owning side to null (unless already changed)
            if ($progress->getClassroom() === $this) {
                $progress->setClassroom(null);
            }
        }

        return $this;
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

    public function getActionSociale(): ?Teacher
    {
        return $this->actionSociale;
    }

    public function setActionSociale(?Teacher $actionSociale): self
    {
        $this->actionSociale = $actionSociale;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudentsEntree(): Collection
    {
        return $this->studentsEntree;
    }

    public function addStudentsEntree(Student $studentsEntree): self
    {
        if (!$this->studentsEntree->contains($studentsEntree)) {
            $this->studentsEntree->add($studentsEntree);
            $studentsEntree->setClasseEntree($this);
        }

        return $this;
    }

    public function removeStudentsEntree(Student $studentsEntree): self
    {
        if ($this->studentsEntree->removeElement($studentsEntree)) {
            // set the owning side to null (unless already changed)
            if ($studentsEntree->getClasseEntree() === $this) {
                $studentsEntree->setClasseEntree(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudentFrereSoeurs(): Collection
    {
        return $this->studentFrereSoeurs;
    }

    public function addStudentFrereSoeur(Student $studentFrereSoeur): self
    {
        if (!$this->studentFrereSoeurs->contains($studentFrereSoeur)) {
            $this->studentFrereSoeurs->add($studentFrereSoeur);
            $studentFrereSoeur->setClasseFrereSoeur($this);
        }

        return $this;
    }

    public function removeStudentFrereSoeur(Student $studentFrereSoeur): self
    {
        if ($this->studentFrereSoeurs->removeElement($studentFrereSoeur)) {
            // set the owning side to null (unless already changed)
            if ($studentFrereSoeur->getClasseFrereSoeur() === $this) {
                $studentFrereSoeur->setClasseFrereSoeur(null);
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
            $historiqueTeacher->setClassroom($this);
        }

        return $this;
    }

    public function removeHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if ($this->historiqueTeachers->removeElement($historiqueTeacher)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTeacher->getClassroom() === $this) {
                $historiqueTeacher->setClassroom(null);
            }
        }

        return $this;
    }

}

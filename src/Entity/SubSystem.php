<?php

namespace App\Entity;

use App\Repository\SubSystemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubSystemRepository::class)]
class SubSystem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subSystem = null;

    #[ORM\OneToMany(mappedBy: 'subSytem', targetEntity: Teacher::class)]
    private Collection $teachers;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: Department::class)]
    private Collection $departments;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: Student::class)]
    private Collection $students;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: Subject::class)]
    private Collection $subjects;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: Lesson::class)]
    private Collection $lessons;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: Fees::class)]
    private Collection $fees;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: Classroom::class)]
    private Collection $classrooms;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: TimeTable::class)]
    private Collection $timeTables;

    #[ORM\OneToMany(mappedBy: 'subSystem', targetEntity: HistoriqueTeacher::class)]
    private Collection $historiqueTeachers;

    public function __construct()
    {
        $this->teachers = new ArrayCollection();
        $this->departments = new ArrayCollection();
        $this->students = new ArrayCollection();
        $this->subjects = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->fees = new ArrayCollection();
        $this->classrooms = new ArrayCollection();
        $this->timeTables = new ArrayCollection();
        $this->historiqueTeachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubSystem(): ?string
    {
        return $this->subSystem;
    }

    public function setSubSystem(?string $subSystem): self
    {
        $this->subSystem = $subSystem;

        return $this;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    public function addTeacher(Teacher $teacher): self
    {
        if (!$this->teachers->contains($teacher)) {
            $this->teachers->add($teacher);
            $teacher->setSubSystem($this);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): self
    {
        if ($this->teachers->removeElement($teacher)) {
            // set the owning side to null (unless already changed)
            if ($teacher->getSubSystem() === $this) {
                $teacher->setSubSystem(null);
            }
        }

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
            $department->setSubSystem($this);
        }

        return $this;
    }

    public function removeDepartment(Department $department): self
    {
        if ($this->departments->removeElement($department)) {
            // set the owning side to null (unless already changed)
            if ($department->getSubSystem() === $this) {
                $department->setSubSystem(null);
            }
        }

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
            $student->setSubSystem($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getSubSystem() === $this) {
                $student->setSubSystem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Subject>
     */
    public function getSubjects(): Collection
    {
        return $this->subjects;
    }

    public function addSubject(Subject $subject): self
    {
        if (!$this->subjects->contains($subject)) {
            $this->subjects->add($subject);
            $subject->setSubSystem($this);
        }

        return $this;
    }

    public function removeSubject(Subject $subject): self
    {
        if ($this->subjects->removeElement($subject)) {
            // set the owning side to null (unless already changed)
            if ($subject->getSubSystem() === $this) {
                $subject->setSubSystem(null);
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
            $lesson->setSubSystem($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getSubSystem() === $this) {
                $lesson->setSubSystem(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Fees>
     */
    public function getFees(): Collection
    {
        return $this->fees;
    }

    public function addFee(Fees $fee): self
    {
        if (!$this->fees->contains($fee)) {
            $this->fees->add($fee);
            $fee->setSubSystem($this);
        }

        return $this;
    }

    public function removeFee(Fees $fee): self
    {
        if ($this->fees->removeElement($fee)) {
            // set the owning side to null (unless already changed)
            if ($fee->getSubSystem() === $this) {
                $fee->setSubSystem(null);
            }
        }

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
            $classroom->setSubSystem($this);
        }

        return $this;
    }

    public function removeClassroom(Classroom $classroom): self
    {
        if ($this->classrooms->removeElement($classroom)) {
            // set the owning side to null (unless already changed)
            if ($classroom->getSubSystem() === $this) {
                $classroom->setSubSystem(null);
            }
        }

        return $this;
    }

    public function __toString()
    {
        return $this->subSystem;
    }

    /**
     * @return Collection<int, TimeTable>
     */
    public function getTimeTables(): Collection
    {
        return $this->timeTables;
    }

    public function addTimeTable(TimeTable $timeTable): self
    {
        if (!$this->timeTables->contains($timeTable)) {
            $this->timeTables->add($timeTable);
            $timeTable->setSubSystem($this);
        }

        return $this;
    }

    public function removeTimeTable(TimeTable $timeTable): self
    {
        if ($this->timeTables->removeElement($timeTable)) {
            // set the owning side to null (unless already changed)
            if ($timeTable->getSubSystem() === $this) {
                $timeTable->setSubSystem(null);
            }
        }

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
            $historiqueTeacher->setSubSystem($this);
        }

        return $this;
    }

    public function removeHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if ($this->historiqueTeachers->removeElement($historiqueTeacher)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTeacher->getSubSystem() === $this) {
                $historiqueTeacher->setSubSystem(null);
            }
        }

        return $this;
    }

}

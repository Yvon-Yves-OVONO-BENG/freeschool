<?php

namespace App\Entity;

use App\Repository\SubjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectRepository::class)]
class Subject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subject = null;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    private ?Category $category = null;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\OneToMany(mappedBy: 'speciality', targetEntity: Teacher::class)]
    private Collection $teachers;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    private ?Department $department = null;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Lesson::class)]
    private Collection $lessons;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: Progress::class)]
    private Collection $progress;

    #[ORM\ManyToOne(inversedBy: 'subjects')]
    private ?SubSystem $subSystem = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\OneToMany(mappedBy: 'subject', targetEntity: HistoriqueTeacher::class)]
    private Collection $historiqueTeachers;

    public function __construct()
    {
        $this->teachers = new ArrayCollection();
        $this->lessons = new ArrayCollection();
        $this->progress = new ArrayCollection();
        $this->historiqueTeachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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
            $teacher->setSpeciality($this);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): self
    {
        if ($this->teachers->removeElement($teacher)) {
            // set the owning side to null (unless already changed)
            if ($teacher->getSpeciality() === $this) {
                $teacher->setSpeciality(null);
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
            $lesson->setSubject($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getSubject() === $this) {
                $lesson->setSubject(null);
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
            $progress->setSubject($this);
        }

        return $this;
    }

    public function removeProgress(Progress $progress): self
    {
        if ($this->progress->removeElement($progress)) {
            // set the owning side to null (unless already changed)
            if ($progress->getSubject() === $this) {
                $progress->setSubject(null);
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
            $historiqueTeacher->setSubject($this);
        }

        return $this;
    }

    public function removeHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if ($this->historiqueTeachers->removeElement($historiqueTeacher)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTeacher->getSubject() === $this) {
                $historiqueTeacher->setSubject(null);
            }
        }

        return $this;
    }

}

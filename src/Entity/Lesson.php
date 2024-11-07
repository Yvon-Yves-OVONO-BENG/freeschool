<?php

namespace App\Entity;

use App\Repository\LessonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LessonRepository::class)]
class Lesson
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $coefficient = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    private ?Subject $subject = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    private ?Classroom $classroom = null;

    #[ORM\Column(nullable: true)]
    private ?int $weekHours = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriquePrevueSeq1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriquePrevueSeq2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriquePrevueSeq3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriquePrevueSeq4 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriquePrevueSeq5 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriquePrevueSeq6 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiquePrevueSeq1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiquePrevueSeq2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiquePrevueSeq3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiquePrevueSeq4 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiquePrevueSeq5 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiquePrevueSeq6 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteSeq1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteSeq2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteSeq3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteSeq4 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteSeq5 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteSeq6 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteSeq1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteSeq2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteSeq3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteSeq4 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteSeq5 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteSeq6 = null;

    #[ORM\OneToMany(mappedBy: 'lesson', targetEntity: Skill::class)]
    private Collection $skills;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    private ?SubjectGroup $subjectGroup = null;

    #[ORM\ManyToOne(inversedBy: 'lessons')]
    private ?SubSystem $subSystem = null;

    #[ORM\OneToMany(mappedBy: 'lesson', targetEntity: Evaluation::class)]
    private Collection $evaluations;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteAvecRessourceSeq1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteAvecRessourceSeq2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteAvecRessourceSeq3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteAvecRessourceSeq4 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteAvecRessourceSeq5 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonTheoriqueFaiteAvecRessourceSeq6 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteAvecRessourceSeq1 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteAvecRessourceSeq2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteAvecRessourceSeq3 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteAvecRessourceSeq4 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteAvecRessourceSeq5 = null;

    #[ORM\Column(nullable: true)]
    private ?int $nbreLessonPratiqueFaiteAvecRessourceSeq6 = null;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCoefficient(): ?float
    {
        return $this->coefficient;
    }

    public function setCoefficient(float $coefficient): self
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): self
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getSubject(): ?Subject
    {
        return $this->subject;
    }

    public function setSubject(?Subject $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;

        return $this;
    }

    public function getWeekHours(): ?int
    {
        return $this->weekHours;
    }

    public function setWeekHours(?int $weekHours): self
    {
        $this->weekHours = $weekHours;

        return $this;
    }

    public function getNbreLessonTheoriquePrevueSeq1(): ?int
    {
        return $this->nbreLessonTheoriquePrevueSeq1;
    }

    public function setNbreLessonTheoriquePrevueSeq1(?int $nbreLessonTheoriquePrevueSeq1): self
    {
        $this->nbreLessonTheoriquePrevueSeq1 = $nbreLessonTheoriquePrevueSeq1;

        return $this;
    }

    public function getNbreLessonTheoriquePrevueSeq2(): ?int
    {
        return $this->nbreLessonTheoriquePrevueSeq2;
    }

    public function setNbreLessonTheoriquePrevueSeq2(?int $nbreLessonTheoriquePrevueSeq2): self
    {
        $this->nbreLessonTheoriquePrevueSeq2 = $nbreLessonTheoriquePrevueSeq2;

        return $this;
    }

    public function getNbreLessonTheoriquePrevueSeq3(): ?int
    {
        return $this->nbreLessonTheoriquePrevueSeq3;
    }

    public function setNbreLessonTheoriquePrevueSeq3(?int $nbreLessonTheoriquePrevueSeq3): self
    {
        $this->nbreLessonTheoriquePrevueSeq3 = $nbreLessonTheoriquePrevueSeq3;

        return $this;
    }

    public function getNbreLessonTheoriquePrevueSeq4(): ?int
    {
        return $this->nbreLessonTheoriquePrevueSeq4;
    }

    public function setNbreLessonTheoriquePrevueSeq4(?int $nbreLessonTheoriquePrevueSeq4): self
    {
        $this->nbreLessonTheoriquePrevueSeq4 = $nbreLessonTheoriquePrevueSeq4;

        return $this;
    }

    public function getNbreLessonTheoriquePrevueSeq5(): ?int
    {
        return $this->nbreLessonTheoriquePrevueSeq5;
    }

    public function setNbreLessonTheoriquePrevueSeq5(?int $nbreLessonTheoriquePrevueSeq5): self
    {
        $this->nbreLessonTheoriquePrevueSeq5 = $nbreLessonTheoriquePrevueSeq5;

        return $this;
    }

    public function getNbreLessonTheoriquePrevueSeq6(): ?int
    {
        return $this->nbreLessonTheoriquePrevueSeq6;
    }

    public function setNbreLessonTheoriquePrevueSeq6(?int $nbreLessonTheoriquePrevueSeq6): self
    {
        $this->nbreLessonTheoriquePrevueSeq6 = $nbreLessonTheoriquePrevueSeq6;

        return $this;
    }

    public function getNbreLessonPratiquePrevueSeq1(): ?int
    {
        return $this->nbreLessonPratiquePrevueSeq1;
    }

    public function setNbreLessonPratiquePrevueSeq1(?int $nbreLessonPratiquePrevueSeq1): self
    {
        $this->nbreLessonPratiquePrevueSeq1 = $nbreLessonPratiquePrevueSeq1;

        return $this;
    }

    public function getNbreLessonPratiquePrevueSeq2(): ?int
    {
        return $this->nbreLessonPratiquePrevueSeq2;
    }

    public function setNbreLessonPratiquePrevueSeq2(?int $nbreLessonPratiquePrevueSeq2): self
    {
        $this->nbreLessonPratiquePrevueSeq2 = $nbreLessonPratiquePrevueSeq2;

        return $this;
    }

    public function getNbreLessonPratiquePrevueSeq3(): ?int
    {
        return $this->nbreLessonPratiquePrevueSeq3;
    }

    public function setNbreLessonPratiquePrevueSeq3(?int $nbreLessonPratiquePrevueSeq3): self
    {
        $this->nbreLessonPratiquePrevueSeq3 = $nbreLessonPratiquePrevueSeq3;

        return $this;
    }

    public function getNbreLessonPratiquePrevueSeq4(): ?int
    {
        return $this->nbreLessonPratiquePrevueSeq4;
    }

    public function setNbreLessonPratiquePrevueSeq4(?int $nbreLessonPratiquePrevueSeq4): self
    {
        $this->nbreLessonPratiquePrevueSeq4 = $nbreLessonPratiquePrevueSeq4;

        return $this;
    }

    public function getNbreLessonPratiquePrevueSeq5(): ?int
    {
        return $this->nbreLessonPratiquePrevueSeq5;
    }

    public function setNbreLessonPratiquePrevueSeq5(?int $nbreLessonPratiquePrevueSeq5): self
    {
        $this->nbreLessonPratiquePrevueSeq5 = $nbreLessonPratiquePrevueSeq5;

        return $this;
    }

    public function getNbreLessonPratiquePrevueSeq6(): ?int
    {
        return $this->nbreLessonPratiquePrevueSeq6;
    }

    public function setNbreLessonPratiquePrevueSeq6(?int $nbreLessonPratiquePrevueSeq6): self
    {
        $this->nbreLessonPratiquePrevueSeq6 = $nbreLessonPratiquePrevueSeq6;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteSeq1(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteSeq1;
    }

    public function setNbreLessonTheoriqueFaiteSeq1(?int $nbreLessonTheoriqueFaiteSeq1): self
    {
        $this->nbreLessonTheoriqueFaiteSeq1 = $nbreLessonTheoriqueFaiteSeq1;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteSeq2(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteSeq2;
    }

    public function setNbreLessonTheoriqueFaiteSeq2(?int $nbreLessonTheoriqueFaiteSeq2): self
    {
        $this->nbreLessonTheoriqueFaiteSeq2 = $nbreLessonTheoriqueFaiteSeq2;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteSeq3(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteSeq3;
    }

    public function setNbreLessonTheoriqueFaiteSeq3(?int $nbreLessonTheoriqueFaiteSeq3): self
    {
        $this->nbreLessonTheoriqueFaiteSeq3 = $nbreLessonTheoriqueFaiteSeq3;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteSeq4(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteSeq4;
    }

    public function setNbreLessonTheoriqueFaiteSeq4(?int $nbreLessonTheoriqueFaiteSeq4): self
    {
        $this->nbreLessonTheoriqueFaiteSeq4 = $nbreLessonTheoriqueFaiteSeq4;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteSeq5(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteSeq5;
    }

    public function setNbreLessonTheoriqueFaiteSeq5(?int $nbreLessonTheoriqueFaiteSeq5): self
    {
        $this->nbreLessonTheoriqueFaiteSeq5 = $nbreLessonTheoriqueFaiteSeq5;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteSeq6(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteSeq6;
    }

    public function setNbreLessonTheoriqueFaiteSeq6(?int $nbreLessonTheoriqueFaiteSeq6): self
    {
        $this->nbreLessonTheoriqueFaiteSeq6 = $nbreLessonTheoriqueFaiteSeq6;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteSeq1(): ?int
    {
        return $this->nbreLessonPratiqueFaiteSeq1;
    }

    public function setNbreLessonPratiqueFaiteSeq1(?int $nbreLessonPratiqueFaiteSeq1): self
    {
        $this->nbreLessonPratiqueFaiteSeq1 = $nbreLessonPratiqueFaiteSeq1;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteSeq2(): ?int
    {
        return $this->nbreLessonPratiqueFaiteSeq2;
    }

    public function setNbreLessonPratiqueFaiteSeq2(?int $nbreLessonPratiqueFaiteSeq2): self
    {
        $this->nbreLessonPratiqueFaiteSeq2 = $nbreLessonPratiqueFaiteSeq2;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteSeq3(): ?int
    {
        return $this->nbreLessonPratiqueFaiteSeq3;
    }

    public function setNbreLessonPratiqueFaiteSeq3(?int $nbreLessonPratiqueFaiteSeq3): self
    {
        $this->nbreLessonPratiqueFaiteSeq3 = $nbreLessonPratiqueFaiteSeq3;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteSeq4(): ?int
    {
        return $this->nbreLessonPratiqueFaiteSeq4;
    }

    public function setNbreLessonPratiqueFaiteSeq4(?int $nbreLessonPratiqueFaiteSeq4): self
    {
        $this->nbreLessonPratiqueFaiteSeq4 = $nbreLessonPratiqueFaiteSeq4;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteSeq5(): ?int
    {
        return $this->nbreLessonPratiqueFaiteSeq5;
    }

    public function setNbreLessonPratiqueFaiteSeq5(?int $nbreLessonPratiqueFaiteSeq5): self
    {
        $this->nbreLessonPratiqueFaiteSeq5 = $nbreLessonPratiqueFaiteSeq5;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteSeq6(): ?int
    {
        return $this->nbreLessonPratiqueFaiteSeq6;
    }

    public function setNbreLessonPratiqueFaiteSeq6(?int $nbreLessonPratiqueFaiteSeq6): self
    {
        $this->nbreLessonPratiqueFaiteSeq6 = $nbreLessonPratiqueFaiteSeq6;

        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->setLesson($this);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        if ($this->skills->removeElement($skill)) {
            // set the owning side to null (unless already changed)
            if ($skill->getLesson() === $this) {
                $skill->setLesson(null);
            }
        }

        return $this;
    }

    public function getSubjectGroup(): ?SubjectGroup
    {
        return $this->subjectGroup;
    }

    public function setSubjectGroup(?SubjectGroup $subjectGroup): self
    {
        $this->subjectGroup = $subjectGroup;

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
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): self
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setLesson($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): self
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getLesson() === $this) {
                $evaluation->setLesson(null);
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

    public function getNbreLessonTheoriqueFaiteAvecRessourceSeq1(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteAvecRessourceSeq1;
    }

    public function setNbreLessonTheoriqueFaiteAvecRessourceSeq1(?int $nbreLessonTheoriqueFaiteAvecRessourceSeq1): self
    {
        $this->nbreLessonTheoriqueFaiteAvecRessourceSeq1 = $nbreLessonTheoriqueFaiteAvecRessourceSeq1;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteAvecRessourceSeq2(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteAvecRessourceSeq2;
    }

    public function setNbreLessonTheoriqueFaiteAvecRessourceSeq2(?int $nbreLessonTheoriqueFaiteAvecRessourceSeq2): self
    {
        $this->nbreLessonTheoriqueFaiteAvecRessourceSeq2 = $nbreLessonTheoriqueFaiteAvecRessourceSeq2;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteAvecRessourceSeq3(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteAvecRessourceSeq3;
    }

    public function setNbreLessonTheoriqueFaiteAvecRessourceSeq3(?int $nbreLessonTheoriqueFaiteAvecRessourceSeq3): self
    {
        $this->nbreLessonTheoriqueFaiteAvecRessourceSeq3 = $nbreLessonTheoriqueFaiteAvecRessourceSeq3;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteAvecRessourceSeq4(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteAvecRessourceSeq4;
    }

    public function setNbreLessonTheoriqueFaiteAvecRessourceSeq4(?int $nbreLessonTheoriqueFaiteAvecRessourceSeq4): self
    {
        $this->nbreLessonTheoriqueFaiteAvecRessourceSeq4 = $nbreLessonTheoriqueFaiteAvecRessourceSeq4;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteAvecRessourceSeq5(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteAvecRessourceSeq5;
    }

    public function setNbreLessonTheoriqueFaiteAvecRessourceSeq5(?int $nbreLessonTheoriqueFaiteAvecRessourceSeq5): self
    {
        $this->nbreLessonTheoriqueFaiteAvecRessourceSeq5 = $nbreLessonTheoriqueFaiteAvecRessourceSeq5;

        return $this;
    }

    public function getNbreLessonTheoriqueFaiteAvecRessourceSeq6(): ?int
    {
        return $this->nbreLessonTheoriqueFaiteAvecRessourceSeq6;
    }

    public function setNbreLessonTheoriqueFaiteAvecRessourceSeq6(?int $nbreLessonTheoriqueFaiteAvecRessourceSeq6): self
    {
        $this->nbreLessonTheoriqueFaiteAvecRessourceSeq6 = $nbreLessonTheoriqueFaiteAvecRessourceSeq6;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteAvecRessourceSeq1(): ?int
    {
        return $this->nbreLessonPratiqueFaiteAvecRessourceSeq1;
    }

    public function setNbreLessonPratiqueFaiteAvecRessourceSeq1(?int $nbreLessonPratiqueFaiteAvecRessourceSeq1): self
    {
        $this->nbreLessonPratiqueFaiteAvecRessourceSeq1 = $nbreLessonPratiqueFaiteAvecRessourceSeq1;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteAvecRessourceSeq2(): ?int
    {
        return $this->nbreLessonPratiqueFaiteAvecRessourceSeq2;
    }

    public function setNbreLessonPratiqueFaiteAvecRessourceSeq2(?int $nbreLessonPratiqueFaiteAvecRessourceSeq2): self
    {
        $this->nbreLessonPratiqueFaiteAvecRessourceSeq2 = $nbreLessonPratiqueFaiteAvecRessourceSeq2;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteAvecRessourceSeq3(): ?int
    {
        return $this->nbreLessonPratiqueFaiteAvecRessourceSeq3;
    }

    public function setNbreLessonPratiqueFaiteAvecRessourceSeq3(?int $nbreLessonPratiqueFaiteAvecRessourceSeq3): self
    {
        $this->nbreLessonPratiqueFaiteAvecRessourceSeq3 = $nbreLessonPratiqueFaiteAvecRessourceSeq3;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteAvecRessourceSeq4(): ?int
    {
        return $this->nbreLessonPratiqueFaiteAvecRessourceSeq4;
    }

    public function setNbreLessonPratiqueFaiteAvecRessourceSeq4(?int $nbreLessonPratiqueFaiteAvecRessourceSeq4): self
    {
        $this->nbreLessonPratiqueFaiteAvecRessourceSeq4 = $nbreLessonPratiqueFaiteAvecRessourceSeq4;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteAvecRessourceSeq5(): ?int
    {
        return $this->nbreLessonPratiqueFaiteAvecRessourceSeq5;
    }

    public function setNbreLessonPratiqueFaiteAvecRessourceSeq5(?int $nbreLessonPratiqueFaiteAvecRessourceSeq5): self
    {
        $this->nbreLessonPratiqueFaiteAvecRessourceSeq5 = $nbreLessonPratiqueFaiteAvecRessourceSeq5;

        return $this;
    }

    public function getNbreLessonPratiqueFaiteAvecRessourceSeq6(): ?int
    {
        return $this->nbreLessonPratiqueFaiteAvecRessourceSeq6;
    }

    public function setNbreLessonPratiqueFaiteAvecRessourceSeq6(?int $nbreLessonPratiqueFaiteAvecRessourceSeq6): self
    {
        $this->nbreLessonPratiqueFaiteAvecRessourceSeq6 = $nbreLessonPratiqueFaiteAvecRessourceSeq6;

        return $this;
    }
   
}

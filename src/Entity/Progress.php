<?php

namespace App\Entity;

use App\Repository\ProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgressRepository::class)]
class Progress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

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

    #[ORM\ManyToOne(inversedBy: 'progress')]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne(inversedBy: 'progress')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne(inversedBy: 'progress')]
    private ?Classroom $classroom = null;

    #[ORM\ManyToOne(inversedBy: 'progress')]
    private ?Subject $subject = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTeacher(): ?Teacher
    {
        return $this->teacher;
    }

    public function setTeacher(?Teacher $teacher): self
    {
        $this->teacher = $teacher;

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

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;

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
}

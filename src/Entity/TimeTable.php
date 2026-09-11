<?php

namespace App\Entity;

use App\Repository\TimeTableRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeTableRepository::class)]
class TimeTable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    #[ORM\ManyToOne]
    private ?Classroom $classroom = null;

    #[ORM\ManyToOne]
    private ?Teacher $teacher = null;

    #[ORM\ManyToOne]
    private ?Subject $subject = null;

    #[ORM\ManyToOne]
    private ?Day $day = null;

    #[ORM\ManyToOne]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne]
    private ?SubSystem $subSystem = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $startTime = null;

    #[ORM\Column(length: 8, nullable: true)]
    private ?string $endTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    /*
     * Très important :
     * Ne jamais ajouter de setId() dans cette entity.
     * L'id doit être généré automatiquement par MySQL AUTO_INCREMENT.
     */

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

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

    public function getDay(): ?Day
    {
        return $this->day;
    }

    public function setDay(?Day $day): self
    {
        $this->day = $day;

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

    public function getSubSystem(): ?SubSystem
    {
        return $this->subSystem;
    }

    public function setSubSystem(?SubSystem $subSystem): self
    {
        $this->subSystem = $subSystem;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(?string $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(?string $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }
}

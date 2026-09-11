<?php

namespace App\Entity;

use App\Repository\TimeTableSlotTemplateRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TimeTableSlotTemplateRepository::class)]
class TimeTableSlotTemplate
{
    public const TYPE_COURSE = 'course';
    public const TYPE_BREAK = 'break';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?SubSystem $subSystem = null;

    #[ORM\Column]
    private ?int $rowOrder = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_COURSE;

    #[ORM\Column(length: 8)]
    private ?string $startTime = null;

    #[ORM\Column(length: 8)]
    private ?string $endTime = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $label = null;

    #[ORM\Column]
    private bool $active = true;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRowOrder(): ?int
    {
        return $this->rowOrder;
    }

    public function setRowOrder(int $rowOrder): self
    {
        $this->rowOrder = $rowOrder;

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type === self::TYPE_BREAK ? self::TYPE_BREAK : self::TYPE_COURSE;

        return $this;
    }

    public function getStartTime(): ?string
    {
        return $this->startTime;
    }

    public function setStartTime(string $startTime): self
    {
        $this->startTime = $startTime;

        return $this;
    }

    public function getEndTime(): ?string
    {
        return $this->endTime;
    }

    public function setEndTime(string $endTime): self
    {
        $this->endTime = $endTime;

        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;

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
}

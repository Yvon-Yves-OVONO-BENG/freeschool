<?php

namespace App\Entity;

use App\Repository\UnrankedCoefficientRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UnrankedCoefficientRepository::class)]
class UnrankedCoefficient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $unrankedCoefficient = null;

    #[ORM\Column]
    private ?bool $forFirstGroup = null;

    #[ORM\Column(nullable: true)]
    private ?bool $forMark = null;

    #[ORM\OneToOne(cascade: ['persist', 'remove'])]
    private ?Classroom $classroom = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUnrankedCoefficient(): ?float
    {
        return $this->unrankedCoefficient;
    }

    public function setUnrankedCoefficient(float $unrankedCoefficient): self
    {
        $this->unrankedCoefficient = $unrankedCoefficient;

        return $this;
    }

    public function isForFirstGroup(): ?bool
    {
        return $this->forFirstGroup;
    }

    public function setForFirstGroup(bool $forFirstGroup): self
    {
        $this->forFirstGroup = $forFirstGroup;

        return $this;
    }

    public function isForMark(): ?bool
    {
        return $this->forMark;
    }

    public function setForMark(?bool $forMark): self
    {
        $this->forMark = $forMark;

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
}

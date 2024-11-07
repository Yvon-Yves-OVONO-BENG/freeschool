<?php

namespace App\Entity;

use App\Repository\VerrouSequenceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerrouSequenceRepository::class)]
class VerrouSequence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $verrouSequence = null;

    #[ORM\ManyToOne(inversedBy: 'verrouSequences')]
    private ?Sequence $sequence = null;

    #[ORM\ManyToOne(inversedBy: 'verrouSequences')]
    private ?SchoolYear $schoolYear = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isVerrouSequence(): ?bool
    {
        return $this->verrouSequence;
    }

    public function setVerrouSequence(bool $verrouSequence): self
    {
        $this->verrouSequence = $verrouSequence;

        return $this;
    }

    public function getSequence(): ?Sequence
    {
        return $this->sequence;
    }

    public function setSequence(?Sequence $sequence): self
    {
        $this->sequence = $sequence;

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

}

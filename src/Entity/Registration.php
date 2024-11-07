<?php

namespace App\Entity;

use App\Repository\RegistrationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
class Registration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $schoolFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $apeeFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $computerFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $medicalBookletFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $cleanSchoolFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $photoFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $stampFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $examFees = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    private ?User $updatedBy = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne(inversedBy: 'registrations')]
    private ?Student $student = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchoolFees(): ?int
    {
        return $this->schoolFees;
    }

    public function setSchoolFees(?int $schoolFees): self
    {
        $this->schoolFees = $schoolFees;

        return $this;
    }

    public function getApeeFees(): ?int
    {
        return $this->apeeFees;
    }

    public function setApeeFees(?int $apeeFees): self
    {
        $this->apeeFees = $apeeFees;

        return $this;
    }

    public function getComputerFees(): ?int
    {
        return $this->computerFees;
    }

    public function setComputerFees(?int $computerFees): self
    {
        $this->computerFees = $computerFees;

        return $this;
    }

    public function getMedicalBookletFees(): ?int
    {
        return $this->medicalBookletFees;
    }

    public function setMedicalBookletFees(?int $medicalBookletFees): self
    {
        $this->medicalBookletFees = $medicalBookletFees;

        return $this;
    }

    public function getCleanSchoolFees(): ?int
    {
        return $this->cleanSchoolFees;
    }

    public function setCleanSchoolFees(?int $cleanSchoolFees): self
    {
        $this->cleanSchoolFees = $cleanSchoolFees;

        return $this;
    }

    public function getPhotoFees(): ?int
    {
        return $this->photoFees;
    }

    public function setPhotoFees(?int $photoFees): self
    {
        $this->photoFees = $photoFees;

        return $this;
    }

    public function getStampFees(): ?int
    {
        return $this->stampFees;
    }

    public function setStampFees(?int $stampFees): self
    {
        $this->stampFees = $stampFees;

        return $this;
    }

    public function getExamFees(): ?int
    {
        return $this->examFees;
    }

    public function setExamFees(?int $examFees): self
    {
        $this->examFees = $examFees;

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

    public function getSchoolYear(): ?SchoolYear
    {
        return $this->schoolYear;
    }

    public function setSchoolYear(?SchoolYear $schoolYear): self
    {
        $this->schoolYear = $schoolYear;

        return $this;
    }

    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): self
    {
        $this->student = $student;

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

}

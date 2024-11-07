<?php

namespace App\Entity;

use App\Repository\EtatFinanceRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EtatFinanceRepository::class)]
class EtatFinance
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $apeeFees = null;

    #[ORM\Column]
    private ?int $computerFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $medicalBookletFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $cleanSchoolFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $photoFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $stampFees = null;

    #[ORM\ManyToOne(inversedBy: 'etatFinances')]
    private ?SchoolYear $schoolYear = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApeeFees(): ?int
    {
        return $this->apeeFees;
    }

    public function setApeeFees(int $apeeFees): self
    {
        $this->apeeFees = $apeeFees;

        return $this;
    }

    public function getComputerFees(): ?int
    {
        return $this->computerFees;
    }

    public function setComputerFees(int $computerFees): self
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

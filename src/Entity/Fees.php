<?php

namespace App\Entity;

use App\Repository\FeesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FeesRepository::class)]
class Fees
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $schoolFees1 = null;

    #[ORM\Column]
    private ?int $apeeFees1 = null;

    #[ORM\Column]
    private ?int $computerFees1 = null;

    #[ORM\Column]
    private ?int $schoolFees2 = null;

    #[ORM\Column]
    private ?int $apeeFees2 = null;

    #[ORM\Column]
    private ?int $computerFees2 = null;

    #[ORM\Column(nullable: true)]
    private ?int $medicalBookletFees = null;

    #[ORM\Column]
    private ?int $cleanSchoolFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $photoFees = null;

    #[ORM\Column(nullable: true)]
    private ?int $stampFees3eme = null;

    #[ORM\Column(nullable: true)]
    private ?int $stampFees1ere = null;

    #[ORM\Column(nullable: true)]
    private ?int $stampFeesTle = null;

    #[ORM\Column(nullable: true)]
    private ?int $examFees3eme = null;

    #[ORM\Column(nullable: true)]
    private ?int $examFees1ere = null;

    #[ORM\Column(nullable: true)]
    private ?int $examFeesTle = null;

    #[ORM\ManyToOne(inversedBy: 'fees')]
    private ?SchoolYear $schoolYear = null;

    #[ORM\ManyToOne(inversedBy: 'fees')]
    private ?SubSystem $subSystem = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSchoolFees1(): ?int
    {
        return $this->schoolFees1;
    }

    public function setSchoolFees1(int $schoolFees1): self
    {
        $this->schoolFees1 = $schoolFees1;

        return $this;
    }

    public function getApeeFees1(): ?int
    {
        return $this->apeeFees1;
    }

    public function setApeeFees1(int $apeeFees1): self
    {
        $this->apeeFees1 = $apeeFees1;

        return $this;
    }

    public function getComputerFees1(): ?int
    {
        return $this->computerFees1;
    }

    public function setComputerFees1(int $computerFees1): self
    {
        $this->computerFees1 = $computerFees1;

        return $this;
    }

    public function getSchoolFees2(): ?int
    {
        return $this->schoolFees2;
    }

    public function setSchoolFees2(int $schoolFees2): self
    {
        $this->schoolFees2 = $schoolFees2;

        return $this;
    }

    public function getApeeFees2(): ?int
    {
        return $this->apeeFees2;
    }

    public function setApeeFees2(int $apeeFees2): self
    {
        $this->apeeFees2 = $apeeFees2;

        return $this;
    }

    public function getComputerFees2(): ?int
    {
        return $this->computerFees2;
    }

    public function setComputerFees2(int $computerFees2): self
    {
        $this->computerFees2 = $computerFees2;

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

    public function setCleanSchoolFees(int $cleanSchoolFees): self
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

    public function getStampFees3eme(): ?int
    {
        return $this->stampFees3eme;
    }

    public function setStampFees3eme(?int $stampFees3eme): self
    {
        $this->stampFees3eme = $stampFees3eme;

        return $this;
    }

    public function getStampFees1ere(): ?int
    {
        return $this->stampFees1ere;
    }

    public function setStampFees1ere(?int $stampFees1ere): self
    {
        $this->stampFees1ere = $stampFees1ere;

        return $this;
    }

    public function getStampFeesTle(): ?int
    {
        return $this->stampFeesTle;
    }

    public function setStampFeesTle(?int $stampFeesTle): self
    {
        $this->stampFeesTle = $stampFeesTle;

        return $this;
    }

    public function getExamFees3eme(): ?int
    {
        return $this->examFees3eme;
    }

    public function setExamFees3eme(?int $examFees3eme): self
    {
        $this->examFees3eme = $examFees3eme;

        return $this;
    }

    public function getExamFees1ere(): ?int
    {
        return $this->examFees1ere;
    }

    public function setExamFees1ere(?int $examFees1ere): self
    {
        $this->examFees1ere = $examFees1ere;

        return $this;
    }

    public function getExamFeesTle(): ?int
    {
        return $this->examFeesTle;
    }

    public function setExamFeesTle(?int $examFeesTle): self
    {
        $this->examFeesTle = $examFeesTle;

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

}

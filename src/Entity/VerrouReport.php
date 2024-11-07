<?php

namespace App\Entity;

use App\Repository\VerrouReportRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerrouReportRepository::class)]
class VerrouReport
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $verrouReport = null;

    #[ORM\ManyToOne(inversedBy: 'verrouReports')]
    private ?Term $term = null;

    #[ORM\ManyToOne(inversedBy: 'verrouReports')]
    private ?SchoolYear $schoolYear = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isVerrouReport(): ?bool
    {
        return $this->verrouReport;
    }

    public function setVerrouReport(bool $verrouReport): self
    {
        $this->verrouReport = $verrouReport;

        return $this;
    }

    public function getTerm(): ?term
    {
        return $this->term;
    }

    public function setTerm(?term $term): self
    {
        $this->term = $term;

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

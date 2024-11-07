<?php

namespace App\Entity;

use App\Repository\VerrouRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerrouRepository::class)]
class Verrou
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?bool $verrou = null;

    #[ORM\ManyToOne(inversedBy: 'verrous')]
    private ?SchoolYear $schoolYear = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isVerrou(): ?bool
    {
        return $this->verrou;
    }

    public function setVerrou(bool $verrou): self
    {
        $this->verrou = $verrou;

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

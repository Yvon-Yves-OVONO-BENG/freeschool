<?php

namespace App\Entity;

use App\Repository\NextYearRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NextYearRepository::class)]
class NextYear
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nextYear = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNextYear(): ?string
    {
        return $this->nextYear;
    }

    public function setNextYear(string $nextYear): self
    {
        $this->nextYear = $nextYear;

        return $this;
    }
}

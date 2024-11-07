<?php

namespace App\Entity;

use App\Repository\VerrouInsolvableRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: VerrouInsolvableRepository::class)]
class VerrouInsolvable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?bool $verrouInsolvable = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isVerrouInsolvable(): ?bool
    {
        return $this->verrouInsolvable;
    }

    public function setVerrouInsolvable(?bool $verrouInsolvable): self
    {
        $this->verrouInsolvable = $verrouInsolvable;

        return $this;
    }

}

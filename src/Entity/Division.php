<?php

namespace App\Entity;

use App\Repository\DivisionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DivisionRepository::class)]
class Division
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $division = null;

    #[ORM\OneToMany(mappedBy: 'division', targetEntity: Subdivision::class)]
    private Collection $subdivisions;

    #[ORM\OneToMany(mappedBy: 'division', targetEntity: Teacher::class)]
    private Collection $teachers;

    #[ORM\ManyToOne(inversedBy: 'divisions')]
    private ?Region $region = null;

    public function __construct()
    {
        $this->subdivisions = new ArrayCollection();
        $this->teachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDivision(): ?string
    {
        return $this->division;
    }

    public function setDivision(string $division): self
    {
        $this->division = $division;

        return $this;
    }

    /**
     * @return Collection<int, Subdivision>
     */
    public function getSubdivisions(): Collection
    {
        return $this->subdivisions;
    }

    public function addSubdivision(Subdivision $subdivision): self
    {
        if (!$this->subdivisions->contains($subdivision)) {
            $this->subdivisions->add($subdivision);
            $subdivision->setDivision($this);
        }

        return $this;
    }

    public function removeSubdivision(Subdivision $subdivision): self
    {
        if ($this->subdivisions->removeElement($subdivision)) {
            // set the owning side to null (unless already changed)
            if ($subdivision->getDivision() === $this) {
                $subdivision->setDivision(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Teacher>
     */
    public function getTeachers(): Collection
    {
        return $this->teachers;
    }

    public function addTeacher(Teacher $teacher): self
    {
        if (!$this->teachers->contains($teacher)) {
            $this->teachers->add($teacher);
            $teacher->setDivision($this);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): self
    {
        if ($this->teachers->removeElement($teacher)) {
            // set the owning side to null (unless already changed)
            if ($teacher->getDivision() === $this) {
                $teacher->setDivision(null);
            }
        }

        return $this;
    }

    public function getRegion(): ?Region
    {
        return $this->region;
    }

    public function setRegion(?Region $region): self
    {
        $this->region = $region;

        return $this;
    }
}

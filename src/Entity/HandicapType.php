<?php

namespace App\Entity;

use App\Repository\HandicapTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HandicapTypeRepository::class)]
class HandicapType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $handicapType = null;

    #[ORM\OneToMany(mappedBy: 'handicapType', targetEntity: Student::class)]
    private Collection $students;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHandicapType(): ?string
    {
        return $this->handicapType;
    }

    public function setHandicapType(string $handicapType): self
    {
        $this->handicapType = $handicapType;

        return $this;
    }

    /**
     * @return Collection<int, Student>
     */
    public function getStudents(): Collection
    {
        return $this->students;
    }

    public function addStudent(Student $student): self
    {
        if (!$this->students->contains($student)) {
            $this->students->add($student);
            $student->setHandicapType($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getHandicapType() === $this) {
                $student->setHandicapType(null);
            }
        }

        return $this;
    }
}

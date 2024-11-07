<?php

namespace App\Entity;

use App\Repository\ModeAdmissionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ModeAdmissionRepository::class)]
class ModeAdmission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modeAdmission = null;

    #[ORM\OneToMany(mappedBy: 'ModeAdmission', targetEntity: Student::class)]
    private Collection $students;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getModeAdmission(): ?string
    {
        return $this->modeAdmission;
    }

    public function setModeAdmission(?string $modeAdmission): self
    {
        $this->modeAdmission = $modeAdmission;

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
            $student->setModeAdmission($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getModeAdmission() === $this) {
                $student->setModeAdmission(null);
            }
        }

        return $this;
    }
}

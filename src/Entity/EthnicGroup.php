<?php

namespace App\Entity;

use App\Repository\EthnicGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EthnicGroupRepository::class)]
class EthnicGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $ethnicGroup = null;

    #[ORM\OneToMany(mappedBy: 'ethnicGroup', targetEntity: Student::class)]
    private Collection $students;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEthnicGroup(): ?string
    {
        return $this->ethnicGroup;
    }

    public function setEthnicGroup(string $ethnicGroup): self
    {
        $this->ethnicGroup = $ethnicGroup;

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
            $student->setEthnicGroup($this);
        }

        return $this;
    }

    public function removeStudent(Student $student): self
    {
        if ($this->students->removeElement($student)) {
            // set the owning side to null (unless already changed)
            if ($student->getEthnicGroup() === $this) {
                $student->setEthnicGroup(null);
            }
        }

        return $this;
    }
}

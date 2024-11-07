<?php

namespace App\Entity;

use App\Repository\MatrimonialStatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MatrimonialStatusRepository::class)]
class MatrimonialStatus
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $matrimonialStatus = null;

    #[ORM\OneToMany(mappedBy: 'matrimonialStatus', targetEntity: Teacher::class)]
    private Collection $teachers;

    public function __construct()
    {
        $this->teachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMatrimonialStatus(): ?string
    {
        return $this->matrimonialStatus;
    }

    public function setMatrimonialStatus(?string $matrimonialStatus): self
    {
        $this->matrimonialStatus = $matrimonialStatus;

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
            $teacher->setMatrimonialStatus($this);
        }

        return $this;
    }

    public function removeTeacher(Teacher $teacher): self
    {
        if ($this->teachers->removeElement($teacher)) {
            // set the owning side to null (unless already changed)
            if ($teacher->getMatrimonialStatus() === $this) {
                $teacher->setMatrimonialStatus(null);
            }
        }

        return $this;
    }
}

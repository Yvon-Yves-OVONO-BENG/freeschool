<?php

namespace App\Entity;

use App\Repository\DayRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DayRepository::class)]
class Day
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $day = null;

    #[ORM\OneToMany(mappedBy: 'day', targetEntity: HistoriqueTeacher::class)]
    private Collection $historiqueTeachers;

    public function __construct()
    {
        $this->historiqueTeachers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDay(): ?string
    {
        return $this->day;
    }

    public function setDay(string $day): self
    {
        $this->day = $day;

        return $this;
    }

    /**
     * @return Collection<int, HistoriqueTeacher>
     */
    public function getHistoriqueTeachers(): Collection
    {
        return $this->historiqueTeachers;
    }

    public function addHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if (!$this->historiqueTeachers->contains($historiqueTeacher)) {
            $this->historiqueTeachers->add($historiqueTeacher);
            $historiqueTeacher->setDay($this);
        }

        return $this;
    }

    public function removeHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if ($this->historiqueTeachers->removeElement($historiqueTeacher)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTeacher->getDay() === $this) {
                $historiqueTeacher->setDay(null);
            }
        }

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\SubjectGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SubjectGroupRepository::class)]
class SubjectGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $subjectGroup = null;

    #[ORM\OneToMany(mappedBy: 'subjectgroup', targetEntity: Lesson::class)]
    private Collection $lessons;

    public function __construct()
    {
        $this->lessons = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSubjectGroup(): ?string
    {
        return $this->subjectGroup;
    }

    public function setSubjectGroup(string $subjectGroup): self
    {
        $this->subjectGroup = $subjectGroup;

        return $this;
    }

    /**
     * @return Collection<int, Lesson>
     */
    public function getLessons(): Collection
    {
        return $this->lessons;
    }

    public function addLesson(Lesson $lesson): self
    {
        if (!$this->lessons->contains($lesson)) {
            $this->lessons->add($lesson);
            $lesson->setSubjectgroup($this);
        }

        return $this;
    }

    public function removeLesson(Lesson $lesson): self
    {
        if ($this->lessons->removeElement($lesson)) {
            // set the owning side to null (unless already changed)
            if ($lesson->getSubjectgroup() === $this) {
                $lesson->setSubjectgroup(null);
            }
        }

        return $this;
    }
}

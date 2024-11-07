<?php

namespace App\Entity;

use App\Repository\SequenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SequenceRepository::class)]
class Sequence
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $sequence = null;

    #[ORM\ManyToOne(inversedBy: 'sequences')]
    private ?Term $term = null;

    #[ORM\OneToMany(mappedBy: 'sequence', targetEntity: VerrouSequence::class)]
    private Collection $verrouSequences;

    #[ORM\OneToMany(mappedBy: 'sequence', targetEntity: Evaluation::class)]
    private Collection $evaluations;

    #[ORM\OneToMany(mappedBy: 'sequence', targetEntity: HistoriqueTeacher::class)]
    private Collection $historiqueTeachers;

    #[ORM\OneToMany(mappedBy: 'sequence', targetEntity: Skill::class)]
    private Collection $skills;


    public function __construct()
    {
        $this->verrouSequences = new ArrayCollection();
        $this->evaluations = new ArrayCollection();
        $this->historiqueTeachers = new ArrayCollection();
        $this->skills = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSequence(): ?int
    {
        return $this->sequence;
    }

    public function setSequence(int $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }

    public function getTerm(): ?term
    {
        return $this->term;
    }

    public function setTerm(?term $term): self
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @return Collection<int, VerrouSequence>
     */
    public function getVerrouSequences(): Collection
    {
        return $this->verrouSequences;
    }

    public function addVerrouSequence(VerrouSequence $verrouSequence): self
    {
        if (!$this->verrouSequences->contains($verrouSequence)) {
            $this->verrouSequences->add($verrouSequence);
            $verrouSequence->setSequence($this);
        }

        return $this;
    }

    public function removeVerrouSequence(VerrouSequence $verrouSequence): self
    {
        if ($this->verrouSequences->removeElement($verrouSequence)) {
            // set the owning side to null (unless already changed)
            if ($verrouSequence->getSequence() === $this) {
                $verrouSequence->setSequence(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Evaluation>
     */
    public function getEvaluations(): Collection
    {
        return $this->evaluations;
    }

    public function addEvaluation(Evaluation $evaluation): self
    {
        if (!$this->evaluations->contains($evaluation)) {
            $this->evaluations->add($evaluation);
            $evaluation->setSequence($this);
        }

        return $this;
    }

    public function removeEvaluation(Evaluation $evaluation): self
    {
        if ($this->evaluations->removeElement($evaluation)) {
            // set the owning side to null (unless already changed)
            if ($evaluation->getSequence() === $this) {
                $evaluation->setSequence(null);
            }
        }

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
            $historiqueTeacher->setSequence($this);
        }

        return $this;
    }

    public function removeHistoriqueTeacher(HistoriqueTeacher $historiqueTeacher): self
    {
        if ($this->historiqueTeachers->removeElement($historiqueTeacher)) {
            // set the owning side to null (unless already changed)
            if ($historiqueTeacher->getSequence() === $this) {
                $historiqueTeacher->setSequence(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Skill>
     */
    public function getSkills(): Collection
    {
        return $this->skills;
    }

    public function addSkill(Skill $skill): self
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
            $skill->setSequence($this);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        if ($this->skills->removeElement($skill)) {
            // set the owning side to null (unless already changed)
            if ($skill->getSequence() === $this) {
                $skill->setSequence(null);
            }
        }

        return $this;
    }


}

<?php

namespace App\Entity;

use App\Repository\TermRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TermRepository::class)]
class Term
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $term = null;

    #[ORM\OneToMany(mappedBy: 'term', targetEntity: Sequence::class)]
    private Collection $sequences;

    #[ORM\OneToMany(mappedBy: 'term', targetEntity: VerrouReport::class)]
    private Collection $verrouReports;

    #[ORM\OneToMany(mappedBy: 'term', targetEntity: Report::class)]
    private Collection $reports;

    #[ORM\OneToMany(mappedBy: 'term', targetEntity: Skill::class)]
    private Collection $skills;

    #[ORM\OneToMany(mappedBy: 'term', targetEntity: Absence::class)]
    private Collection $absences;

    #[ORM\OneToMany(mappedBy: 'term', targetEntity: AbsenceTeacher::class)]
    private Collection $absenceTeachers;

    #[ORM\OneToMany(mappedBy: 'term', targetEntity: Conseil::class)]
    private Collection $conseils;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $slug = null;

    public function __construct()
    {
        $this->sequences = new ArrayCollection();
        $this->verrouReports = new ArrayCollection();
        $this->reports = new ArrayCollection();
        $this->skills = new ArrayCollection();
        $this->absences = new ArrayCollection();
        $this->absenceTeachers = new ArrayCollection();
        $this->conseils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTerm(): ?int
    {
        return $this->term;
    }

    public function setTerm(?int $term): self
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @return Collection<int, Sequence>
     */
    public function getSequences(): Collection
    {
        return $this->sequences;
    }

    public function addSequence(Sequence $sequence): self
    {
        if (!$this->sequences->contains($sequence)) {
            $this->sequences->add($sequence);
            $sequence->setTerm($this);
        }

        return $this;
    }

    public function removeSequence(Sequence $sequence): self
    {
        if ($this->sequences->removeElement($sequence)) {
            // set the owning side to null (unless already changed)
            if ($sequence->getTerm() === $this) {
                $sequence->setTerm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VerrouReport>
     */
    public function getVerrouReports(): Collection
    {
        return $this->verrouReports;
    }

    public function addVerrouReport(VerrouReport $verrouReport): self
    {
        if (!$this->verrouReports->contains($verrouReport)) {
            $this->verrouReports->add($verrouReport);
            $verrouReport->setTerm($this);
        }

        return $this;
    }

    public function removeVerrouReport(VerrouReport $verrouReport): self
    {
        if ($this->verrouReports->removeElement($verrouReport)) {
            // set the owning side to null (unless already changed)
            if ($verrouReport->getTerm() === $this) {
                $verrouReport->setTerm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Report>
     */
    public function getReports(): Collection
    {
        return $this->reports;
    }

    public function addReport(Report $report): self
    {
        if (!$this->reports->contains($report)) {
            $this->reports->add($report);
            $report->setTerm($this);
        }

        return $this;
    }

    public function removeReport(Report $report): self
    {
        if ($this->reports->removeElement($report)) {
            // set the owning side to null (unless already changed)
            if ($report->getTerm() === $this) {
                $report->setTerm(null);
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
            $skill->setTerm($this);
        }

        return $this;
    }

    public function removeSkill(Skill $skill): self
    {
        if ($this->skills->removeElement($skill)) {
            // set the owning side to null (unless already changed)
            if ($skill->getTerm() === $this) {
                $skill->setTerm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Absence>
     */
    public function getAbsences(): Collection
    {
        return $this->absences;
    }

    public function addAbsence(Absence $absence): self
    {
        if (!$this->absences->contains($absence)) {
            $this->absences->add($absence);
            $absence->setTerm($this);
        }

        return $this;
    }

    public function removeAbsence(Absence $absence): self
    {
        if ($this->absences->removeElement($absence)) {
            // set the owning side to null (unless already changed)
            if ($absence->getTerm() === $this) {
                $absence->setTerm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AbsenceTeacher>
     */
    public function getAbsenceTeachers(): Collection
    {
        return $this->absenceTeachers;
    }

    public function addAbsenceTeacher(AbsenceTeacher $absenceTeacher): self
    {
        if (!$this->absenceTeachers->contains($absenceTeacher)) {
            $this->absenceTeachers->add($absenceTeacher);
            $absenceTeacher->setTerm($this);
        }

        return $this;
    }

    public function removeAbsenceTeacher(AbsenceTeacher $absenceTeacher): self
    {
        if ($this->absenceTeachers->removeElement($absenceTeacher)) {
            // set the owning side to null (unless already changed)
            if ($absenceTeacher->getTerm() === $this) {
                $absenceTeacher->setTerm(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Conseil>
     */
    public function getConseils(): Collection
    {
        return $this->conseils;
    }

    public function addConseil(Conseil $conseil): self
    {
        if (!$this->conseils->contains($conseil)) {
            $this->conseils->add($conseil);
            $conseil->setTerm($this);
        }

        return $this;
    }

    public function removeConseil(Conseil $conseil): self
    {
        if ($this->conseils->removeElement($conseil)) {
            // set the owning side to null (unless already changed)
            if ($conseil->getTerm() === $this) {
                $conseil->setTerm(null);
            }
        }

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(?string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}

<?php

namespace App\Entity\ReportElements;

class Discipline
{
    protected $name = 'Discipline';
    protected $absence;
    protected $warningBehaviour;
    protected $blameBehaviour;
    protected $exclusion;
    protected $disciplinaryCommitee;
    

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getAbsence(): int
    {
        return $this->absence;
    }

    public function setAbsence(int $absence): self
    {
        $this->absence = $absence;

        return $this;
    }

    public function getWarningBehaviour(): string
    {
        return $this->warningBehaviour;
    }

    public function setWarningBehaviour(string $warningBehaviour): self
    {
        $this->warningBehaviour = $warningBehaviour;

        return $this;
    }

    public function getBlameBehaviour(): string
    {
        return $this->blameBehaviour;
    }

    public function setBlameBehaviour(string $blameBehaviour): self
    {
        $this->blameBehaviour = $blameBehaviour;

        return $this;
    }

    public function getExclusion(): int
    {
        return $this->exclusion;
    }

    public function setExclusion(int $exclusion): self
    {
        $this->exclusion = $exclusion;

        return $this;
    }

    public function getDisciplinaryCommitee(): string
    {
        return $this->disciplinaryCommitee;
    }

    public function setDisciplinaryCommitee(string $disciplinaryCommitee): self
    {
        $this->disciplinaryCommitee = $disciplinaryCommitee;

        return $this;
    }

    
}
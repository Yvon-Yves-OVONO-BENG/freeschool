<?php

namespace App\Entity\DeliberationElements;

use App\Entity\Student;
use App\Entity\Decision;

class DeliberationRow
{
    protected $student;
    protected $decision;
    protected $nextClassroomName;
    protected $moyenneTerm0;
    protected $moyenneTerm1;
    protected $moyenneTerm2;
    protected $moyenneTerm3;
    protected $motif;
    protected $deliberationDecision;


    public function getStudent(): ?Student
    {
        return $this->student;
    }

    public function setStudent(?Student $student): self
    {
        $this->student = $student;

        return $this;
    }
    
    public function getDecision(): ?Decision
    {
        return $this->decision;
    }

    public function setDecision(?Decision $decision): self
    {
        $this->decision = $decision;

        return $this;
    }

    public function getNextClassroomName(): ?string
    {
        return $this->nextClassroomName;
    }

    public function setNextClassroomName(?string $nextClassroomName): self
    {
        $this->nextClassroomName = $nextClassroomName;

        return $this;
    }


    public function getMoyenneTerm0(): ?float
    {
        return $this->moyenneTerm0;
    }

    public function setMoyenneTerm0(?float $moyenneTerm0): self
    {
        $this->moyenneTerm0 = $moyenneTerm0;

        return $this;
    }

    public function getMoyenneTerm1(): ?float
    {
        return $this->moyenneTerm1;
    }

    public function setMoyenneTerm1(?float $moyenneTerm1): self
    {
        $this->moyenneTerm1 = $moyenneTerm1;

        return $this;
    }

    public function getMoyenneTerm2(): ?float
    {
        return $this->moyenneTerm2;
    }

    public function setMoyenneTerm2(?float $moyenneTerm2): self
    {
        $this->moyenneTerm2 = $moyenneTerm2;

        return $this;
    }

    public function getMoyenneTerm3(): ?float
    {
        return $this->moyenneTerm3;
    }

    public function setMoyenneTerm3(?float $moyenneTerm3): self
    {
        $this->moyenneTerm3 = $moyenneTerm3;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): self
    {
        $this->motif = $motif;

        return $this;
    }


    public function getDeliberationDecision(): ?string
    {
        return $this->deliberationDecision;
    }

    public function setDeliberationDecision(?string $deliberationDecision): self
    {
        $this->deliberationDecision = $deliberationDecision;

        return $this;
    }

    public static function orderByAverage($a, $b): int
    {
        $moyenneA = $a->getMoyenneTerm0();
        $moyenneB = $b->getMoyenneTerm0();

        if($moyenneA == $moyenneB)
        {
          return 0;
        }

        return ($moyenneA > $moyenneB) ? -1 : 1;
    }
}
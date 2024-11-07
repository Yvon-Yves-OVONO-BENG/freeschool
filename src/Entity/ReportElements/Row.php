<?php

namespace App\Entity\ReportElements;

use App\Entity\ConstantsClass;

class Row
{
    protected $subject;
    protected $teacher;
    protected $skill;
    protected $skillEvaluation1;
    protected $skillEvaluation2;
    protected $evaluation1;
    protected $evaluation2;
    protected $evaluation3;
    protected $moyenne;
    protected $coefficient;
    protected $total;
    protected $rang;
    protected $appreciationFr;
    protected $appreciationEn;
    protected $cote;
    protected $minNote;
    protected $maxNote;

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getTeacher(): string
    {
        return $this->teacher;
    }

    public function setTeacher(string $teacher): self
    {
        $this->teacher = $teacher;

        return $this;
    }

    public function getSkill(): string
    {
        return $this->skill;
    }

    public function setSkill(string $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    public function getSkillEvaluation1(): string
    {
        return $this->skillEvaluation1;
    }

    public function setSkillEvaluation1(string $skillEvaluation1): self
    {
        $this->skillEvaluation1 = $skillEvaluation1;

        return $this;
    }

    public function getSkillEvaluation2(): string
    {
        return $this->skillEvaluation2;
    }

    public function setSkillEvaluation2(string $skillEvaluation2): self
    {
        $this->skillEvaluation2 = $skillEvaluation2;

        return $this;
    }

    public function getEvaluation1(): float
    {
        return $this->evaluation1;
    }

    public function setEvaluation1(float $evaluation1): self
    {
        $this->evaluation1 = $evaluation1;

        return $this;
       
    }

    public function getEvaluation2(): float
    {
        return $this->evaluation2;
    }

    public function setEvaluation2(float $evaluation2): self
    {
        $this->evaluation2 = $evaluation2;

        return $this;
    }

    public function getEvaluation3(): float
    {
        return $this->evaluation3;
    }

    public function setEvaluation3(float $evaluation3): self
    {
        $this->evaluation3 = $evaluation3;

        return $this;
    }

    public function getMoyenne(): float
    {
        return $this->moyenne;
    }

    public function setMoyenne(float $moyenne): self
    {
        $this->moyenne = $moyenne;

        return $this;
    }

    public function getCoefficient(): float
    {
        return $this->coefficient;
    }

    public function setCoefficient(float $coefficient): self
    {
        $this->coefficient = $coefficient;

        return $this;
    }

    public function getTotal(): float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getRang(): int
    {
        return $this->rang;
    }

    public function setRang(int $rang): self
    {
        $this->rang = $rang;

        return $this;
    }

    public function getAppreciationFr(): string
    {
        return $this->appreciationFr;
    }

    public function setAppreciationFr(string $appreciationFr): self
    {
        $this->appreciationFr = $appreciationFr;

        return $this;
    }

    public function getAppreciationEn(): string
    {
        return $this->appreciationEn;
    }

    public function setAppreciationEn(string $appreciationEn): self
    {
        $this->appreciationEn = $appreciationEn;

        return $this;
    }

    public function getCote(): string
    {
        return $this->cote;
    }

    public function setCote(string $cote): self
    {
        $this->cote = $cote;

        return $this;
    }

    public function getMinNote(): float
    {
        return $this->minNote;
    }

    public function setMinNote(float $minNote): self
    {
        $this->minNote = $minNote;

        return $this;
    }

    public function getMaxNote(): float
    {
        return $this->maxNote;
    }

    public function setMaxNote(float $maxNote): self
    {
        $this->maxNote = $maxNote;

        return $this;
    }

}
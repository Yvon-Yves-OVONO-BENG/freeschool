<?php

namespace App\Entity\RegisterAndListElements;

class AbsenceReportRow
{
    protected $studentName;
    protected $sex;
    protected $absence1;
    protected $absence2;
    protected $absence3;


    public function getStudentName(): ?string
    {
        return $this->studentName;
    }

    public function setStudentName(?string $studentName): self
    {
        $this->studentName = $studentName;

        return $this;
    }

    public function getSex(): ?string
    {
        return $this->sex;
    }

    public function setSex(?string $sex): self
    {
        $this->sex = $sex;

        return $this;
    }
    
    public function getAbsence1(): ?float
    {
        return $this->absence1;
    }

    public function setAbsence1(?float $absence1): self
    {
        $this->absence1 = $absence1;

        return $this;
    }


    public function getAbsence2(): ?float
    {
        return $this->absence2;
    }

    public function setAbsence2(?float $absence2): self
    {
        $this->absence2 = $absence2;

        return $this;
    }


    public function getAbsence3(): ?float
    {
        return $this->absence3;
    }

    public function setAbsence3(?float $absence3): self
    {
        $this->absence3 = $absence3;

        return $this;
    }
}
<?php

namespace App\Entity\RegisterAndListElements;

use App\Entity\Absence;
use App\Entity\Classroom;

class AbsenceReportHeader
{
    protected $classroom;
    protected $absences;

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;

        return $this;
    }

    public function getAbsences(): ?Absence
    {
        return $this->absences;
    }

    public function setAbsences(?Absence $absences): self
    {
        $this->absences = $absences;

        return $this;
    }
}
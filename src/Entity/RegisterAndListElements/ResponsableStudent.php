<?php

namespace App\Entity\RegisterAndListElements;

use App\Entity\Classroom;
use App\Entity\Student;

class ResponsableStudent
{
    private $classroom;
    private $king1;
    private $king2;
    private $delegate1;
    private $delegate2;

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;

        return $this;
    }

    public function getKing1(): ?Student
    {
        return $this->king1;
    }

    public function setKing1(?Student $king1): self
    {
        $this->king1 = $king1;

        return $this;
    }

    public function getKing2(): ?Student
    {
        return $this->king2;
    }

    public function setKing2(?Student $king2): self
    {
        $this->king2 = $king2;

        return $this;
    }

    public function getDelegate1(): ?Student
    {
        return $this->delegate1;
    }

    public function setDelegate1(?Student $delegate1): self
    {
        $this->delegate1 = $delegate1;

        return $this;
    }

    public function getDelegate2(): ?Student
    {
        return $this->delegate2;
    }

    public function setDelegate2(?Student $delegate2): self
    {
        $this->delegate2 = $delegate2;

        return $this;
    }


}
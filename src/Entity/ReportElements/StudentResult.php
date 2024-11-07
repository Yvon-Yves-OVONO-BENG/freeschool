<?php

namespace App\Entity\ReportElements;

class StudentResult
{
    protected  $name = "Résultats de l'élève";
    protected  $nameEnglish = 'Student results';
    protected  $totalStudentCoefficient = 0;
    protected  $totalClassroomCoefficient = 0;
    protected  $totalMark = 0;
    protected  $moyenne = 0;
    protected  $rang;
    
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNameEnglish(): string
    {
        return $this->nameEnglish;
    }

    public function setNameEnglish(string $nameEnglish): self
    {
        $this->nameEnglish = $nameEnglish;

        return $this;
    }

    public function getTotalStudentCoefficient(): float
    {
        return $this->totalStudentCoefficient;
    }

    public function setTotalStudentCoefficient(float $totalStudentCoefficient): self
    {
        $this->totalStudentCoefficient = $totalStudentCoefficient;

        return $this;
    }

    public function getTotalClassroomCoefficient(): float
    {
        return $this->totalClassroomCoefficient;
    }

    public function setTotalClassroomCoefficient(float $totalClassroomCoefficient): self
    {
        $this->totalClassroomCoefficient = $totalClassroomCoefficient;

        return $this;
    }

    public function getTotalMark(): float
    {
        return $this->totalMark;
    }

    public function setTotalMark(float $totalMark): self 
    {
        $this->totalMark = $totalMark;

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

    public function getRang(): int
    {
        return $this->rang;
    }

    public function setRang(int $rang): self 
    {
        $this->rang = $rang;

        return $this;
    }
    
}
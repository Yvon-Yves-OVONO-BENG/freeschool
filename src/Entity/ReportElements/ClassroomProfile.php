<?php

namespace App\Entity\ReportElements;

class ClassroomProfile
{
    protected $name = 'Profil de la classe';
    protected $nameEnglish = 'Class profile';
    protected $classroomAverage;
    protected $successRate;
    protected $firstAverage;
    protected $lastAverage;
    protected $numberAverage;
    
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
    
    public function getClassroomAverage(): float
    {
        return $this->classroomAverage;
    }

    public function setClassroomAverage(float $classroomAverage): self
    {
        $this->classroomAverage = $classroomAverage;

        return $this;
    }

    public function getSuccessRate(): float
    {
        return $this->successRate;
    }

    public function setSuccessRate(float $successRate): self
    {
        $this->successRate = $successRate;

        return $this;
    }

    public function getFirstAverage(): float
    {
        return $this->firstAverage;
    }

    public function setFirstAverage(float $firstAverage): self
    {
        $this->firstAverage = $firstAverage;

        return $this;
    }

    public function getLastAverage(): float
    {
        return $this->lastAverage;
    }

    public function setLastAverage(float $lastAverage): self
    {
        $this->lastAverage = $lastAverage;

        return $this;
    }

    public function getNumberAverage(): float
    {
        return $this->numberAverage;
    }

    public function setNumberAverage(float $numberAverage): self
    {
        $this->numberAverage = $numberAverage;

        return $this;
    }
    
}
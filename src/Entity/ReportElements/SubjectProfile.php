<?php

namespace App\Entity\ReportElements;

class SubjectProfile
{
    protected $firstAverage;
    protected $lastAverage;

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
    
}
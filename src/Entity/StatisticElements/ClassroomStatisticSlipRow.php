<?php

namespace App\Entity\StatisticElements;

class ClassroomStatisticSlipRow
{

    protected $subject;
    protected $registeredBoys = 0;
    protected $registeredGirls = 0;
    protected $composedBoys = 0;
    protected $composedGirls = 0;
    protected $passedBoys = 0;
    protected $passedGirls = 0;
    protected $generalAverage = 0;
    protected $generalAverageBoys = 0;
    protected $generalAverageGirls = 0;
    protected $firstMark = 0;
    protected $lastMark = 20;
    protected $appreciation = '';
    protected $title;

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function  setSubject(?string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getRegisteredBoys(): ?int
    {
        return $this->registeredBoys;
    }

    public function  setRegisteredBoys(?int $registeredBoys): self
    {
        $this->registeredBoys = $registeredBoys;

        return $this;
    }

    public function getRegisteredGirls(): ?int
    {
        return $this->registeredGirls;
    }

    public function  setRegisteredGirls(?int $registeredGirls): self
    {
        $this->registeredGirls = $registeredGirls;

        return $this;
    }


    public function getComposedBoys(): ?int
    {
        return $this->composedBoys;
    }

    public function  setComposedBoys(?int $composedBoys): self
    {
        $this->composedBoys = $composedBoys;

        return $this;
    }

    public function getComposedGirls(): ?int
    {
        return $this->composedGirls;
    }

    public function  setComposedGirls(?int $composedGirls): self
    {
        $this->composedGirls = $composedGirls;

        return $this;
    }


    public function getPassedBoys(): ?int
    {
        return $this->passedBoys;
    }

    public function  setPassedBoys(?int $passedBoys): self
    {
        $this->passedBoys = $passedBoys;

        return $this;
    }

    public function getPassedGirls(): ?int
    {
        return $this->passedGirls;
    }

    public function  setPassedGirls(?int $passedGirls): self
    {
        $this->passedGirls = $passedGirls;

        return $this;
    }


    public function getGeneralAverage(): ?float
    {
        return $this->generalAverage;
    }

    public function  setGeneralAverage(?float $generalAverage): self
    {
        $this->generalAverage = $generalAverage;

        return $this;
    }

     public function getGeneralAverageBoys(): ?float
    {
        return $this->generalAverageBoys;
    }

    public function  setGeneralAverageBoys(?float $generalAverageBoys): self
    {
        $this->generalAverageBoys = $generalAverageBoys;

        return $this;
    }

     public function getGeneralAverageGirls(): ?float
    {
        return $this->generalAverageGirls;
    }

    public function  setGeneralAverageGirls(?float $generalAverageGirls): self
    {
        $this->generalAverageGirls = $generalAverageGirls;

        return $this;
    }

    public function getLastMark(): ?float
    {
        return $this->lastMark;
    }

    public function  setLastMark(?float $lastMark): self
    {
        $this->lastMark = $lastMark;

        return $this;
    }

    public function getFirstMark(): ?float
    {
        return $this->firstMark;
    }

    public function  setFirstMark(?float $firstMark): self
    {
        $this->firstMark = $firstMark;

        return $this;
    }

    public function getAppreciation(): ?string
    {
        return $this->appreciation;
    }

    public function  setAppreciation(?string $appreciation): self
    {
        $this->appreciation = $appreciation;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function  setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

}
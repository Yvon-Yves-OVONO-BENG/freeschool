<?php

namespace App\Entity\ReportElements;

class StudentWork
{
    protected  $name = 'Travail';
    protected  $nameEnglish = 'Work';
    protected $rollOfHonour;
    protected $encouragement;
    protected $congratulation;
    protected $warningWork;
    protected $blameWork;
    

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

    public function getRollOfHonour(): string
    {
        return $this->rollOfHonour;
    }

    public function setRollOfHonour(string $rollOfHonour): self
    {
        $this->rollOfHonour = $rollOfHonour;

        return $this;
    }

    public function getEncouragement(): string
    {
        return $this->encouragement;
    }

    public function setEncouragement(string $encouragement): self
    {
        $this->encouragement = $encouragement;

        return $this;
    }

    public function getCongratulation(): string
    {
        return $this->congratulation;
    }

    public function setCongratulation(string $congratulation): self
    {
        $this->congratulation = $congratulation;

        return $this;
    }

    public function getWarningWork(): string
    {
        return $this->warningWork;
    }

    public function setWarningWork(string $warningWork): self
    {
        $this->warningWork = $warningWork;

        return $this;
    }

    public function getBlameWork(): string
    {
        return $this->blameWork;
    }

    public function setBlameWork(string $blameWork): self
    {
        $this->blameWork = $blameWork;

        return $this;
    }
    
}
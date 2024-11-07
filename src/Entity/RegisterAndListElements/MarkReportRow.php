<?php

namespace App\Entity\RegisterAndListElements;

class MarkReportRow
{
    protected $studentName;
    protected $sexStudent;
    protected $evaluation1;
    protected $evaluation2;
    protected $evaluation3;
    protected $evaluation4;
    protected $evaluation5;
    protected $evaluation6;


    public function getStudentName(): ?string
    {
        return $this->studentName;
    }

    public function setStudentName(?string $studentName): self
    {
        $this->studentName = $studentName;

        return $this;
    }

    public function getSexStudent(): ?string
    {
        return $this->sexStudent;
    }

    public function setSexStudent(?string $sexStudent): self
    {
        $this->sexStudent = $sexStudent;

        return $this;
    }


    public function getEvaluation1(): ?float
    {
        return $this->evaluation1;
    }

    public function setEvaluation1(?float $evaluation1): self
    {
        $this->evaluation1 = $evaluation1;

        return $this;
    }


    public function getEvaluation2(): ?float
    {
        return $this->evaluation2;
    }

    public function setEvaluation2(?float $evaluation2): self
    {
        $this->evaluation2 = $evaluation2;

        return $this;
    }


    public function getEvaluation3(): ?float
    {
        return $this->evaluation3;
    }

    public function setEvaluation3(?float $evaluation3): self
    {
        $this->evaluation3 = $evaluation3;

        return $this;
    }


    public function getEvaluation4(): ?float
    {
        return $this->evaluation4;
    }

    public function setEvaluation4(?float $evaluation4): self
    {
        $this->evaluation4 = $evaluation4;

        return $this;
    }


    public function getEvaluation5(): ?float
    {
        return $this->evaluation5;
    }

    public function setEvaluation5(?float $evaluation5): self
    {
        $this->evaluation5 = $evaluation5;

        return $this;
    }


    public function getEvaluation6(): ?float
    {
        return $this->evaluation6;
    }

    public function setEvaluation6(?float $evaluation6): self
    {
        $this->evaluation6 = $evaluation6;

        return $this;
    }

}
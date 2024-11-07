<?php

namespace App\Entity\ReportElements;

class ReportBody
{
    protected $rowsGroup1 = [];
    protected $rowsGroup2 = [];
    protected $rowsGroup3 = [];
    protected $summaryGroup1;
    protected $summaryGroup2;
    protected $summaryGroup3;


    public function getRowsGroup1(): array
    {
        return $this->rowsGroup1;
    }

    public function setRowsGroup1(array $rowsGroup1): self
    {
        $this->rowsGroup1 = $rowsGroup1;

        return $this;
    }

    public function getRowsGroup2(): array
    {
        return $this->rowsGroup2;
    }

    public function setRowsGroup2(array $rowsGroup2): self
    {
        $this->rowsGroup2 = $rowsGroup2;

        return $this;
    }

    public function getRowsGroup3(): array
    {
        return $this->rowsGroup3;
    }

    public function setRowsGroup3(array $rowsGroup3): self
    {
        $this->rowsGroup3 = $rowsGroup3;

        return $this;
    }

    public function getSummaryGroup1(): StudentResult
    {
        return $this->summaryGroup1;
    }

    public function setSummaryGroup1(StudentResult $summaryGroup1): self
    {
        $this->summaryGroup1 = $summaryGroup1;

        return $this;
    }

    public function getSummaryGroup2(): StudentResult
    {
        return $this->summaryGroup2;
    }

    public function setSummaryGroup2(StudentResult $summaryGroup2): self
    {
        $this->summaryGroup2 = $summaryGroup2;

        return $this;
    }

    public function getSummaryGroup3(): StudentResult
    {
        return $this->summaryGroup3;
    }

    public function setSummaryGroup3(StudentResult $summaryGroup3): self
    {
        $this->summaryGroup3 = $summaryGroup3;

        return $this;
    }

}
<?php

namespace App\Entity\ReportElements;

use App\Entity\ReportElements\ReportBody;
use App\Entity\ReportElements\ReportFooter;
use App\Entity\ReportElements\ReportHeader;

class StudentReport
{
   protected $reportHeader;
   protected $reportBody;
   protected $reportFooter;
   protected $numberOfLessons = 0;

   public function getReportHeader(): ?ReportHeader
   {
       return $this->reportHeader;
   }

   public function setReportHeader(?ReportHeader $reportHeader): self
   {
       $this->reportHeader = $reportHeader;

       return $this;
   }

   public function getReportBody(): ?ReportBody
   {
       return $this->reportBody;
   }

   public function setReportBody(?ReportBody $reportBody): self
   {
       $this->reportBody = $reportBody;

       return $this;
   }

   public function getReportFooter(): ?ReportFooter
   {
       return $this->reportFooter;
   }

   public function setReportFooter(?ReportFooter $reportFooter): self
   {
       $this->reportFooter = $reportFooter;

       return $this;
   }

    public function getNumberOfLessons(): ?int
   {
       return $this->numberOfLessons;
   }

   public function setNumberOfLessons(?int $numberOfLessons): self
   {
       $this->numberOfLessons = $numberOfLessons;

       return $this;
   }
    
}


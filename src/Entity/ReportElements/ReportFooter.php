<?php

namespace App\Entity\ReportElements;

use App\Entity\ReportElements\Remember;
use App\Entity\ReportElements\Discipline;
use App\Entity\ReportElements\ParentVisa;
use App\Entity\ReportElements\StudentWork;
use App\Entity\ReportElements\StudentResult;
use App\Entity\ReportElements\HeadmasterVisa;
use App\Entity\ReportElements\DecisionConseil;
use App\Entity\ReportElements\ClassroomProfile;
use App\Entity\ReportElements\CommiteeDecision;
use App\Entity\ReportElements\WorkAppreciation;
use App\Entity\ReportElements\PrincipalTeacherVisa;

class ReportFooter
{
    protected $studentResult;
    protected $remember;
    protected $classroomProfile;
    protected $discipline;
    protected $studentWork;
    protected $workAppreciation;
    protected $commiteeDecision;
    protected $headmasterVisa;
    protected $parentVisa;
    protected $principalTeacherVisa;
    protected $decisionConseil;

    public function getStudentResult(): StudentResult
    {
        return $this->studentResult;
    }

    public function setStudentResult(StudentResult $studentResult): self
    {
        $this->studentResult = $studentResult;

        return $this;
    }

    public function getRemember(): Remember
    {
        return $this->remember;
    }

    public function setRemember(Remember $remember): self
    {
        $this->remember = $remember;

        return $this;
    }
    
    public function getClassroomProfile(): ClassroomProfile
    {
        return $this->classroomProfile;
    }

    public function setClassroomProfile(ClassroomProfile $classroomProfile): self
    {
        $this->classroomProfile = $classroomProfile;

        return $this;
    }

    public function getDiscipline(): Discipline
    {
        return $this->discipline;
    }

    public function setDiscipline(Discipline $discipline): self
    {
        $this->discipline = $discipline;

        return $this;
    }

    public function getStudentWork(): StudentWork
    {
        return $this->studentWork;
    }

    public function setStudentWork(StudentWork $studentWork): self
    {
        $this->studentWork = $studentWork;

        return $this;
    }


    public function getWorkAppreciation(): WorkAppreciation
    {
        return $this->workAppreciation;
    }

    public function setWorkAppreciation(WorkAppreciation $workAppreciation): self
    {
        $this->workAppreciation = $workAppreciation;

        return $this;
    }
    
    public function getCommiteeDecision(): CommiteeDecision
    {
        return $this->commiteeDecision;
    }

    public function setCommiteeDecision(CommiteeDecision $commiteeDecision): self
    {
        $this->commiteeDecision = $commiteeDecision;

        return $this;
    }

    public function getHeadmasterVisa(): HeadmasterVisa
    {
        return $this->headmasterVisa;
    }

    public function setHeadmasterVisa(HeadmasterVisa $headmasterVisa): self
    {
        $this->headmasterVisa = $headmasterVisa;

        return $this;
    }

    public function getParentVisa(): ParentVisa
    {
        return $this->parentVisa;
    }

    public function setParentVisa(ParentVisa $parentVisa): self
    {
        $this->parentVisa = $parentVisa;

        return $this;
    }

    public function getPrincipalTeacherVisa(): PrincipalTeacherVisa
    {
        return $this->principalTeacherVisa;
    }

    public function setPrincipalTeacherVisa(PrincipalTeacherVisa $principalTeacherVisa): self
    {
        $this->principalTeacherVisa = $principalTeacherVisa;

        return $this;
    }


    public function getDecisionConseil(): DecisionConseil
    {
        return $this->decisionConseil;
    }

    public function setDecisionConseil(DecisionConseil $decisionConseil): self
    {
        $this->decisionConseil = $decisionConseil;

        return $this;
    }
}
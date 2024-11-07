<?php

namespace App\Entity\ReportElements;

class PrincipalTeacherVisa
{
    protected $name = 'Visa du professeur principal';
    protected $nameEnglish = 'Visa of the head teacher';

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
}
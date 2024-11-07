<?php

namespace App\Entity\ReportElements;

class WorkAppreciation
{
    protected $name = 'Appréciation du travail';
    protected $nameEnglish = 'Work appreciation';
    protected $appreciation;
    protected $content = "Un effort s'impose en";
    protected $contentEnglish = "An effort is required in";
    

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

    public function getAppreciation(): string
    {
        return $this->appreciation;
    }

    public function setAppreciation(string $appreciation): self
    {
        $this->appreciation = $appreciation;

        return $this;
    }


    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getContentEnglish(): string
    {
        return $this->contentEnglish;
    }

    public function setContentEnglish(string $contentEnglish): self
    {
        $this->contentEnglish = $contentEnglish;

        return $this;
    }
    
}
<?php

namespace App\Entity\RegisterAndListElements;

use App\Entity\Lesson;

class MarkReportHeader 
{   
    protected $lesson;
    protected $title = 'Compétences Visées';
    protected $skill1 = '';
    protected $skill2 = '';
    protected $skill3 = '';


    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): self
    {
        $this->lesson = $lesson;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getSkill1(): ?string
    {
        return $this->skill1;
    }

    public function setSkill1(?string $skill1): self
    {
        $this->skill1 = $skill1;

        return $this;
    }

    public function getSkill2(): ?string
    {
        return $this->skill2;
    }

    public function setSkill2(?string $skill2): self
    {
        $this->skill2 = $skill2;

        return $this;
    }

    public function getSkill3(): ?string
    {
        return $this->skill3;
    }

    public function setSkill3(?string $skill3): self
    {
        $this->skill3 = $skill3;

        return $this;
    }

}
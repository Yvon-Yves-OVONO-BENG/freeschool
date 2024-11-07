<?php 

namespace App\Entity\HeadmasterReport;

use App\Entity\Classroom;

class StudentAgeRow 
{
    private $classroom;
    private $lessThanBoysAge10 = 0;
    private $boysAge10 = 0;
    private $boysAge11 = 0;
    private $boysAge12 = 0;
    private $boysAge13 = 0;
    private $boysAge14 = 0;
    private $boysAge15 = 0;
    private $boysAge16 = 0;
    private $boysAge17 = 0;
    private $boysAge18 = 0;
    private $boysAge19 = 0;
    private $boysAge20 = 0;
    private $greatherThanBoysAge20 = 0;

    private $lessThanGirlsAge10 = 0;
    private $girlsAge10 = 0;
    private $girlsAge11 = 0;
    private $girlsAge12 = 0;
    private $girlsAge13 = 0;
    private $girlsAge14 = 0;
    private $girlsAge15 = 0;
    private $girlsAge16 = 0;
    private $girlsAge17 = 0;
    private $girlsAge18 = 0;
    private $girlsAge19 = 0;
    private $girlsAge20 = 0;
    private $greatherThanGirlsAge20 = 0;

    public function getClassroom(): ?Classroom
    {
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;

        return $this;
    }

    public function getLessThanBoysAge10(): ?int
    {
        return $this->lessThanBoysAge10;
    }

    public function setLessThanBoysAge10(?int $lessThanBoysAge10): self
    {
        $this->lessThanBoysAge10 = $lessThanBoysAge10;

        return $this;
    }

    public function getBoysAge10(): ?int
    {
        return $this->boysAge10;
    }

    public function setBoysAge10(?int $boysAge10): self
    {
        $this->boysAge10 = $boysAge10;

        return $this;
    }

    public function getBoysAge11(): ?int
    {
        return $this->boysAge11;
    }

    public function setBoysAge11(?int $boysAge11): self
    {
        $this->boysAge11 = $boysAge11;

        return $this;
    }

    public function getBoysAge12(): ?int
    {
        return $this->boysAge12;
    }

    public function setBoysAge12(?int $boysAge12): self
    {
        $this->boysAge12 = $boysAge12;

        return $this;
    }

    public function getBoysAge13(): ?int
    {
        return $this->boysAge13;
    }

    public function setBoysAge13(?int $boysAge13): self
    {
        $this->boysAge13 = $boysAge13;

        return $this;
    }

    public function getBoysAge14(): ?int
    {
        return $this->boysAge14;
    }

    public function setBoysAge14(?int $boysAge14): self
    {
        $this->boysAge14 = $boysAge14;

        return $this;
    }

    public function getBoysAge15(): ?int
    {
        return $this->boysAge15;
    }

    public function setBoysAge15(?int $boysAge15): self
    {
        $this->boysAge15 = $boysAge15;

        return $this;
    }

    public function getBoysAge16(): ?int
    {
        return $this->boysAge16;
    }

    public function setBoysAge16(?int $boysAge16): self
    {
        $this->boysAge16 = $boysAge16;

        return $this;
    }

    public function getBoysAge17(): ?int
    {
        return $this->boysAge17;
    }

    public function setBoysAge17(?int $boysAge17): self
    {
        $this->boysAge17 = $boysAge17;

        return $this;
    }

    public function getBoysAge18(): ?int
    {
        return $this->boysAge18;
    }

    public function setBoysAge18(?int $boysAge18): self
    {
        $this->boysAge18 = $boysAge18;

        return $this;
    }

    public function getBoysAge19(): ?int
    {
        return $this->boysAge19;
    }

    public function setBoysAge19(?int $boysAge19): self
    {
        $this->boysAge19 = $boysAge19;

        return $this;
    }

    public function getBoysAge20(): ?int
    {
        return $this->boysAge20;
    }

    public function setBoysAge20(?int $boysAge20): self
    {
        $this->boysAge20 = $boysAge20;

        return $this;
    }

    public function getGreatherThanBoysAge20(): ?int
    {
        return $this->greatherThanBoysAge20;
    }

    public function setGreatherThanBoysAge20(?int $greatherThanBoysAge20): self
    {
        $this->greatherThanBoysAge20 = $greatherThanBoysAge20;

        return $this;
    }

    public function getLessThanGirlsAge10(): ?int
    {
        return $this->lessThanGirlsAge10;
    }

    public function setLessThanGirlsAge10(?int $lessThanGirlsAge10): self
    {
        $this->lessThanGirlsAge10 = $lessThanGirlsAge10;

        return $this;
    }

    public function getGirlsAge10(): ?int
    {
        return $this->girlsAge10;
    }

    public function setGirlsAge10(?int $girlsAge10): self
    {
        $this->girlsAge10 = $girlsAge10;

        return $this;
    }

    public function getGirlsAge11(): ?int
    {
        return $this->girlsAge11;
    }

    public function setGirlsAge11(?int $girlsAge11): self
    {
        $this->girlsAge11 = $girlsAge11;

        return $this;
    }

    public function getGirlsAge12(): ?int
    {
        return $this->girlsAge12;
    }

    public function setGirlsAge12(?int $girlsAge12): self
    {
        $this->girlsAge12 = $girlsAge12;

        return $this;
    }

    public function getGirlsAge13(): ?int
    {
        return $this->girlsAge13;
    }

    public function setGirlsAge13(?int $girlsAge13): self
    {
        $this->girlsAge13 = $girlsAge13;

        return $this;
    }

    public function getGirlsAge14(): ?int
    {
        return $this->girlsAge14;
    }

    public function setGirlsAge14(?int $girlsAge14): self
    {
        $this->girlsAge14 = $girlsAge14;

        return $this;
    }

    public function getGirlsAge15(): ?int
    {
        return $this->girlsAge15;
    }

    public function setGirlsAge15(?int $girlsAge15): self
    {
        $this->girlsAge15 = $girlsAge15;

        return $this;
    }

    public function getGirlsAge16(): ?int
    {
        return $this->girlsAge16;
    }

    public function setGirlsAge16(?int $girlsAge16): self
    {
        $this->girlsAge16 = $girlsAge16;

        return $this;
    }

    public function getGirlsAge17(): ?int
    {
        return $this->girlsAge17;
    }

    public function setGirlsAge17(?int $girlsAge17): self
    {
        $this->girlsAge17 = $girlsAge17;

        return $this;
    }

    public function getGirlsAge18(): ?int
    {
        return $this->girlsAge18;
    }

    public function setGirlsAge18(?int $girlsAge18): self
    {
        $this->girlsAge18 = $girlsAge18;

        return $this;
    }

    public function getGirlsAge19(): ?int
    {
        return $this->girlsAge19;
    }

    public function setGirlsAge19(?int $girlsAge19): self
    {
        $this->girlsAge19 = $girlsAge19;

        return $this;
    }

    public function getGirlsAge20(): ?int
    {
        return $this->girlsAge20;
    }

    public function setGirlsAge20(?int $girlsAge20): self
    {
        $this->girlsAge20 = $girlsAge20;

        return $this;
    }

    public function getGreatherThanGirlsAge20(): ?int
    {
        return $this->greatherThanGirlsAge20;
    }

    public function setGreatherThanGirlsAge20(?int $greatherThanGirlsAge20): self
    {
        $this->greatherThanGirlsAge20 = $greatherThanGirlsAge20;

        return $this;
    }

    
}
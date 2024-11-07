<?php

namespace App\Entity\ReportElements;

class Remember
{
    protected  $name = 'Rappels';
    protected $moyenneTerm1;
    protected $rank1;
    protected $moyenneTerm2;
    protected $rank2;
    protected $moyenneTerm3;
    protected $rank3;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMoyenneTerm1(): float
    {
        return $this->moyenneTerm1;
    }

    public function setMoyenneTerm1(float $moyenneTerm1): self
    {
        $this->moyenneTerm1 = $moyenneTerm1;

        return $this;
    }

    public function getRank1(): int
    {
        return $this->rank1;
    }

    public function setRank1(int $rank1): self
    {
        $this->rank1 = $rank1;

        return $this;
    }

    public function getMoyenneTerm2(): float
    {
        return $this->moyenneTerm2;
    }

    public function setMoyenneTerm2(float $moyenneTerm2): self
    {
        $this->moyenneTerm2 = $moyenneTerm2;

        return $this;
    }

    public function getRank2(): int
    {
        return $this->rank2;
    }

    public function setRank2(int $rank2): self
    {
        $this->rank2 = $rank2;

        return $this;
    }

    public function getMoyenneTerm3(): float
    {
        return $this->moyenneTerm3;
    }

    public function setMoyenneTerm3(float $moyenneTerm3): self
    {
        $this->moyenneTerm3 = $moyenneTerm3;

        return $this;
    }

    public function getRank3(): int
    {
        return $this->rank3;
    }

    public function setRank3(int $rank3): self
    {
        $this->rank3 = $rank3;

        return $this;
    }
    
}
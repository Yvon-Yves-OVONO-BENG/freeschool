<?php

namespace App\Entity\ReportElements;

class HeadmasterVisa
{
    protected $name = 'Visa du Proviseur';
    protected $namePrincipal = 'Visa du Principal';
    protected $nameDirecteur = 'Visa du Directeur';

    /////////////////
    protected $nameEnglish = 'Visa of the Principal';
    protected $namePrincipalEnglish = 'Visa of the Principal';
    protected $nameDirecteurEnglish = 'Visa of the Director';

    ///////////
    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    ///////////
    public function getNamePrincipal (): string
    {
        return $this->namePrincipal ;
    }

    public function setNamePrincipal (string $namePrincipal ): self
    {
        $this->namePrincipal  = $namePrincipal ;

        return $this;
    }

    ///////////
    public function getNameDirecteur (): string
    {
        return $this->nameDirecteur ;
    }

    public function setNameDirecteur (string $nameDirecteur ): self
    {
        $this->nameDirecteur  = $nameDirecteur ;

        return $this;
    }

    //////////////
    public function getNameEnglish(): string
    {
        return $this->nameEnglish;
    }

    public function setNameEnglish(string $nameEnglish): self
    {
        $this->nameEnglish = $nameEnglish;

        return $this;
    }

    ///////////
    public function getNamePrincipalEnglish (): string
    {
        return $this->namePrincipalEnglish ;
    }

    public function setNamePrincipalEnglish (string $namePrincipalEnglish ): self
    {
        $this->namePrincipalEnglish  = $namePrincipalEnglish ;

        return $this;
    }

    ///////////
    public function getNameDirecteurEnglish (): string
    {
        return $this->nameDirecteurEnglish ;
    }

    public function setNameDirecteurEnglish (string $nameDirecteurEnglish ): self
    {
        $this->nameDirecteurEnglish  = $nameDirecteurEnglish ;

        return $this;
    }
}
<?php

namespace App\Entity;

use App\Repository\ReponseQuestionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReponseQuestionRepository::class)]
class ReponseQuestion
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reponseQuestions')]
    private ?QuestionSecrete $questionSecrete = null;

    #[ORM\ManyToOne(inversedBy: 'reponseQuestions')]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $reponse = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionSecrete(): ?QuestionSecrete
    {
        return $this->questionSecrete;
    }

    public function setQuestionSecrete(?QuestionSecrete $questionSecrete): static
    {
        $this->questionSecrete = $questionSecrete;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getReponse(): ?string
    {
        return $this->reponse;
    }

    public function setReponse(string $reponse): static
    {
        $this->reponse = $reponse;

        return $this;
    }
}

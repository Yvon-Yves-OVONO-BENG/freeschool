<?php

namespace App\Entity;

use App\Repository\QuestionSecreteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionSecreteRepository::class)]
class QuestionSecrete
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $questionSecrete = null;

    #[ORM\OneToMany(targetEntity: ReponseQuestion::class, mappedBy: 'questionSecrete')]
    private Collection $reponseQuestions;

    public function __construct()
    {
        $this->reponseQuestions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestionSecrete(): ?string
    {
        return $this->questionSecrete;
    }

    public function setQuestionSecrete(string $questionSecrete): static
    {
        $this->questionSecrete = $questionSecrete;

        return $this;
    }

    /**
     * @return Collection<int, ReponseQuestion>
     */
    public function getReponseQuestions(): Collection
    {
        return $this->reponseQuestions;
    }

    public function addReponseQuestion(ReponseQuestion $reponseQuestion): static
    {
        if (!$this->reponseQuestions->contains($reponseQuestion)) {
            $this->reponseQuestions->add($reponseQuestion);
            $reponseQuestion->setQuestionSecrete($this);
        }

        return $this;
    }

    public function removeReponseQuestion(ReponseQuestion $reponseQuestion): static
    {
        if ($this->reponseQuestions->removeElement($reponseQuestion)) {
            // set the owning side to null (unless already changed)
            if ($reponseQuestion->getQuestionSecrete() === $this) {
                $reponseQuestion->setQuestionSecrete(null);
            }
        }

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @Assert\Length(
     * max = 47,
     * maxMessage = "La compétence ne peut dépasser {{ limit }} caractères."
     * )
     */
    #[ORM\Column(length: 47)]
    private ?string $skill = null;

    #[ORM\ManyToOne(inversedBy: 'skills')]
    private ?Lesson $lesson = null;

    #[ORM\ManyToOne(inversedBy: 'skills')]
    private ?Term $term = null;

    #[ORM\ManyToOne(inversedBy: 'skills')]
    private ?Sequence $sequence = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSkill(): ?string
    {
        return $this->skill;
    }

    public function setSkill(string $skill): self
    {
        $this->skill = $skill;

        return $this;
    }

    public function getLesson(): ?Lesson
    {
        return $this->lesson;
    }

    public function setLesson(?Lesson $lesson): self
    {
        $this->lesson = $lesson;

        return $this;
    }

    public function getTerm(): ?Term
    {
        return $this->term;
    }

    public function setTerm(?Term $term): self
    {
        $this->term = $term;

        return $this;
    }

    public function getSequence(): ?Sequence
    {
        return $this->sequence;
    }

    public function setSequence(?Sequence $sequence): self
    {
        $this->sequence = $sequence;

        return $this;
    }
}

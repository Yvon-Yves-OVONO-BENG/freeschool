<?php

namespace App\Entity;

use App\Repository\AiQueryHistoryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AiQueryHistoryRepository::class)]
#[ORM\Table(name: 'ai_query_history')]
class AiQueryHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $question = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $answer = null;

    #[ORM\Column(length: 40)]
    private string $status = 'success';

    #[ORM\Column(length: 30)]
    private string $outputFormat = 'html';

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $queryPlan = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $resultPreview = null;

    #[ORM\Column(nullable: true)]
    private ?int $rowCount = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $filePath = null;

    #[ORM\ManyToOne]
    private ?User $createdBy = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getQuestion(): ?string { return $this->question; }
    public function setQuestion(string $question): self { $this->question = $question; return $this; }
    public function getAnswer(): ?string { return $this->answer; }
    public function setAnswer(?string $answer): self { $this->answer = $answer; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }
    public function getOutputFormat(): string { return $this->outputFormat; }
    public function setOutputFormat(string $outputFormat): self { $this->outputFormat = $outputFormat; return $this; }
    public function getQueryPlan(): ?array { return $this->queryPlan; }
    public function setQueryPlan(?array $queryPlan): self { $this->queryPlan = $queryPlan; return $this; }
    public function getResultPreview(): ?array { return $this->resultPreview; }
    public function setResultPreview(?array $resultPreview): self { $this->resultPreview = $resultPreview; return $this; }
    public function getRowCount(): ?int { return $this->rowCount; }
    public function setRowCount(?int $rowCount): self { $this->rowCount = $rowCount; return $this; }
    public function getFilePath(): ?string { return $this->filePath; }
    public function setFilePath(?string $filePath): self { $this->filePath = $filePath; return $this; }
    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): self { $this->createdBy = $createdBy; return $this; }
    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
